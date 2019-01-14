
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="no-js">
<head>
    <?php
    global $TRP_LANGUAGE;
    $trp                = TRP_Translate_Press::get_trp_instance();
    $trp_languages      = $trp->get_component( 'languages' );
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

    do_action( 'trp_head' );
    ?>

    <title>TranslatePress</title>
    <!-- <script type="application/javascript">
        var trp_language = '<?php //echo $TRP_LANGUAGE; ?>';
        var trp_on_screen_language = '<?php //echo ( isset( $translation_languages[0] ) ) ? $translation_languages[0] : 'null' ; ?>';
        var trp_ajax_url = '<?php //echo apply_filters( 'trp_ajax_url', admin_url( 'admin-ajax.php' ) ); ?>';
    </script> -->
</head>
<body>
    <div id="trp-editor">
        <editor-tpl language="en" on_screen_language="fr" ajax_url="admin-ajax.php"></editor>
    </div>
    <?php do_action( 'trp_translation_manager_footer' ); ?>
</body>
</html>



<?php
