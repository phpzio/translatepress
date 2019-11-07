<?php


class TRP_String_Translation{
    protected $settings;

    public function __construct( $settings ){
        $this->settings = $settings;
    }

    /**
     * Start String Translation Editor.
     *
     * Hooked to template_include.
     *
     * @param string $page_template         Current page template.
     * @return string                       Template for translation Editor.
     */
    public function string_translation_editor( $page_template ){
        if ( ! $this->is_string_translation_editor() ){
            return $page_template;
        }

        return TRP_PLUGIN_DIR . 'includes/string-translation/string-translation-editor.php' ;
    }

    /**
     * Return true if we are on String translation page.
     *
     * Also wp_die and show 'Cheating' message if we are on translation page but user does not have capabilities to view it
     *
     * @return bool
     */
    public function is_string_translation_editor(){
        if ( isset( $_REQUEST['trp-string-translation'] ) && esc_attr( $_REQUEST['trp-string-translation'] ) === 'true' ) {
            if ( current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) && ! is_admin() ) {
                return true;
            }else{
                wp_die(
                    '<h1>' . esc_html__( 'Cheatin&#8217; uh?' ) . '</h1>' .
                    '<p>' . esc_html__( 'Sorry, you are not allowed to access this page.' ) . '</p>',
                    403
                );
            }
        }
        return false;
    }

    /**
     * Enqueue script and styles for String Translation Editor page
     *
     * Hooked to trp_string_translation_editor_footer
     */
    public function enqueue_scripts_and_styles(){
        wp_enqueue_style( 'trp-editor-style', TRP_PLUGIN_URL . 'assets/css/trp-editor.css', array('dashicons', 'buttons'), TRP_PLUGIN_VERSION );
        wp_enqueue_script( 'trp-string-translation-editor',  TRP_PLUGIN_URL . 'assets/js/trp-string-translation-editor.js', array(), TRP_PLUGIN_VERSION );


//        wp_localize_script( 'trp-string-translation-editor', 'trp_localized_strings', $this->localized_text() );


        // Show upload media dialog in default language
        switch_to_locale( $this->settings['default-language'] );
        // Necessary for add media button
        wp_enqueue_media();

        // Necessary for add media button
        wp_print_media_templates();
        restore_current_locale();

        // Necessary for translate-dom-changes to have a nonce as the same user as the Editor.
        // The Preview iframe (which loads translate-dom-changes script) can load as logged out which sets an different nonce

        $scripts_to_print = apply_filters( 'trp-scripts-for-editor', array( 'jquery', 'jquery-ui-core', 'jquery-effects-core', 'jquery-ui-resizable', 'trp-string-translation-editor' ) );
        $styles_to_print = apply_filters( 'trp-styles-for-editor', array( 'dashicons', 'trp-editor-style','media-views', 'imgareaselect' /*'wp-admin', 'common', 'site-icon', 'buttons'*/ ) );
        wp_print_scripts( $scripts_to_print );
        wp_print_styles( $styles_to_print );

        // Necessary for add media button
        print_footer_scripts();

    }
}