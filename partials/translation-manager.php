<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    global $TRP_LANGUAGE;
    $trp                 = TRP_Translate_Press::get_trp_instance();
    $trp_languages       = $trp->get_component( 'languages' );
    $translation_manager = $trp->get_component( 'translation_manager' );
    $translation_render  = $trp->get_component( 'translation_render' );
    $settings_component  = $trp->get_component( 'settings' );
    $url_converter       = $trp->get_component('url_converter');
    $trp_settings        = $settings_component->get_settings();

    $language_names = $trp_languages->get_language_names( $trp_settings['translation-languages'] );

    // move the current language to the beginning of the array
    $translation_languages = $trp_settings['translation-languages'];
    if ( $TRP_LANGUAGE != $trp_settings['default-language'] ) {
        $current_language_key = array_search( $TRP_LANGUAGE, $trp_settings['translation-languages'] );
        unset( $translation_languages[$current_language_key] );
        $translation_languages = array_merge( array( $TRP_LANGUAGE ), array_values( $translation_languages ) );
    }
    $default_language_key = array_search( $trp_settings['default-language'], $translation_languages );
    unset( $translation_languages[$default_language_key] );
    $ordered_secondary_languages = array_values( $translation_languages );

    $current_language_published = ( in_array( $TRP_LANGUAGE, $trp_settings[ 'publish-languages' ] ) );
    $current_url = $url_converter->cur_page_url();

    $selectors = $translation_render->get_accessors_array( '-' ); // suffix selectors such as array( '-alt', '-src', '-title', '-content', '-value', '-placeholder', '-href', '-outertext', '-innertext' )
    $selectors[] = ''; // empty string suffix added for using just the base attribute data-trp-translate-id  (instead of data-trp-translate-id-alt)
    $data_attributes = $translation_render->get_base_attribute_selectors();

    do_action( 'trp_head' );

    //setup view_as roles
    $view_as_roles = array(
        __('Current User', 'translatepress-multilingual') => 'current_user',
        __('Logged Out',   'translatepress-multilingual') => 'logged_out'
    );
    $all_roles = wp_roles()->roles;

    if( !empty( $all_roles ) ){
        foreach( $all_roles as $role )
            $view_as_roles[$role['name']] = '';
    }

    $view_as_roles = apply_filters( 'trp_view_as_values', $view_as_roles );
    $string_groups = apply_filters( 'trp_string_group_order', array_values( $translation_manager->string_groups() ) );

    $flags_path = array();
    foreach( $trp_settings['translation-languages'] as $language_code ) {
	    $default_path = TRP_PLUGIN_URL . 'assets/images/flags/';
	    $flags_path[$language_code] = apply_filters( 'trp_flags_path', $default_path, $language_code );
    }
    ?>

    <title>TranslatePress</title>
</head>
<body>

    <div id="trp-editor-container">
        <trp-editor
            ref='trp_editor'
            trp_settings='<?php echo esc_attr( json_encode( $trp_settings ) ); ?>'
            language_names='<?php echo esc_attr( json_encode( $language_names ) ); ?>'
            ordered_secondary_languages='<?php echo esc_attr( json_encode( $ordered_secondary_languages ) ); ?>'
            current_language="<?php echo esc_attr( $TRP_LANGUAGE ); ?>"
            on_screen_language="<?php echo esc_attr( ( isset( $ordered_secondary_languages[0] ) ) ? $ordered_secondary_languages[0] : '' ); ?>"
            view_as_roles='<?php echo esc_attr( json_encode( $view_as_roles ) ); ?>'
            url_to_load="<?php echo esc_url( add_query_arg( 'trp-edit-translation', 'preview', $current_url ) );?>"
            string_selectors='<?php echo esc_attr( json_encode( $selectors ) ); ?>'
            data_attributes='<?php echo esc_attr( json_encode( $data_attributes ) ); ?>'
            editor_nonces='<?php echo esc_attr( json_encode( $translation_manager->editor_nonces() ) ); ?>'
            ajax_url='<?php echo esc_url( apply_filters( 'trp_wp_ajax_url', admin_url( 'admin-ajax.php' ) ) ); ?>'
            string_group_order='<?php echo esc_attr( json_encode( $string_groups ) ); ?>'
            merge_rules='<?php echo esc_attr( json_encode( $translation_manager->get_merge_rules() ) ); ?>'
            localized_text='<?php echo esc_attr( json_encode( $translation_manager->localized_text() ) ); ?>'
            paid_version="<?php echo esc_attr( trp_is_paid_version() ? 'true' : 'false' ); ?>"
            flags_path="<?php echo esc_attr( json_encode( $flags_path ) ); ?>"
        >
        </trp-editor>
    </div>

    <?php do_action( 'trp_translation_manager_footer' ); ?>
</body>
</html>

<?php
