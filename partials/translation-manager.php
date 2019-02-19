<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    global $TRP_LANGUAGE;
    $trp                = TRP_Translate_Press::get_trp_instance();
    $trp_languages      = $trp->get_component( 'languages' );
    $translation_manager= $trp->get_component( 'translation_manager' );
    $settings_component = $trp->get_component( 'settings' );
    $url_converter      = $trp->get_component('url_converter');
    $trp_settings       = $settings_component->get_settings();

    $available_languages = $trp_languages->get_language_names( $trp_settings['translation-languages'] );

    // move the current language to the beginning of the array
    $translation_languages = $trp_settings['translation-languages'];
    if ( $TRP_LANGUAGE != $trp_settings['default-language'] ) {
        $current_language_key = array_search( $TRP_LANGUAGE, $trp_settings['translation-languages'] );
        unset( $translation_languages[$current_language_key] );
        $translation_languages = array_merge( array( $TRP_LANGUAGE ), array_values( $translation_languages ) );
    }
    $default_language_key = array_search( $trp_settings['default-language'], $translation_languages );
    unset( $translation_languages[$default_language_key] );
    $translation_languages = array_values( $translation_languages );

    $current_language_published = ( in_array( $TRP_LANGUAGE, $trp_settings[ 'publish-languages' ] ) );
    $current_url = $url_converter->cur_page_url();

    //@TODO: needs to be removed; this is necessary because on `localhost:3000` the TRP URL function returns a protcol relative URL ( e.g. //yoursite.com )
    //and we need to compare the URL with the one from the iFrame (which has the protocol added)
    $current_url = 'http:' . $current_url;

    //@todo
    $selectors = array('', '-alt', '-src' );

    do_action( 'trp_head' );

    //setup view_as roles
    $view_as_roles = array(
        __('Current User', 'translatepress-multilingual') => 'current_user',
        __('Logged Out', 'translatepress-multilingual') => 'logged_out'
    );
    $all_roles = wp_roles()->roles;

    if( !empty( $all_roles ) ){
        foreach( $all_roles as $role )
            $view_as_roles[$role['name']] = '';
    }

    $view_as_roles = apply_filters( 'trp_view_as_values', $view_as_roles );
    $string_types = apply_filters( 'trp_string_type_order', array_values( $translation_manager->string_types() ) );
    ?>

    <title>TranslatePress</title>
</head>
<body>

    <div id="trp-editor-container">
        <trp-editor
            ref='trp_editor'
            trp_settings='<?php echo json_encode( $trp_settings ); ?>'
            available_languages='<?php echo json_encode( $available_languages ); ?>'
            current_language="<?php echo $TRP_LANGUAGE; ?>"
            on_screen_language="<?php echo ( isset( $translation_languages[0] ) ) ? $translation_languages[0] : 'null' ; ?>"
            view_as_roles='<?php echo json_encode( $view_as_roles ); ?>'
            url_to_load="<?php echo add_query_arg( 'trp-edit-translation', 'preview', $current_url );?>"
            string_selectors='<?php echo json_encode( $selectors ); ?>'
            editor_nonces='<?php echo json_encode( $translation_manager->editor_nonces() ); ?>'
            ajax_url= '<?php echo apply_filters( 'trp_wp_ajax_url', admin_url( 'admin-ajax.php' ) ); ?>'
            string_type_order= '<?php echo json_encode( $string_types ); ?>'
        >
        </trp-editor>
    </div>

    <?php do_action( 'trp_translation_manager_footer' ); ?>
</body>
</html>



<?php
