<?php

class TRP_Machine_Translation_Tab {

    private $settings;

    public function __construct() {

        $settings = get_option( 'trp_machine_translation_settings', false );

        if( !empty( $settings ) )
            $this->settings = $settings;
        else
            $this->settings = array( 'machine-translation' => 'no' );

        if( !class_exists( 'TRP_DeepL' ) )
            add_filter( 'trp_machine_translation_engines', [ $this, 'translation_engines_upsell' ], 20 );

    }

    /*
    * Add new tab to TP settings
    *
    * Hooked to trp_settings_tabs
    */
    public function add_tab_to_navigation( $tabs ){
        $tab = array(
            'name'  => __( 'Automatic Translation', 'translatepress-multilingual' ),
            'url'   => admin_url( 'admin.php?page=trp_machine_translation' ),
            'page'  => 'trp_machine_translation'
        );

        array_splice( $tabs, 2, 0, array( $tab ) );

        return $tabs;
    }

    /*
    * Add submenu for advanced page tab
    *
    * Hooked to admin_menu
    */
    public function add_submenu_page() {
        add_submenu_page( 'TRPHidden', 'TranslatePress Automatic Translation', 'TRPHidden', 'manage_options', 'trp_machine_translation', array( $this, 'machine_translation_page_content' ) );
        add_submenu_page( 'TRPHidden', 'TranslatePress Test Automatic Translation API', 'TRPHidden', 'manage_options', 'trp_test_machine_api', array( $this, 'test_api_page_content' ) );
    }

    /**
    * Register setting
    *
    * Hooked to admin_init
    */
    public function register_setting(){
        register_setting( 'trp_machine_translation_settings', 'trp_machine_translation_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
    * Output admin notices after saving settings.
    */
    public function admin_notices(){
        if( isset( $_GET['page'] ) && $_GET['page'] == 'trp_machine_translation' )
            settings_errors();
    }

    /*
    * Sanitize settings
    */
    public function sanitize_settings( $settings ){
        if( !empty( $settings['machine-translation'] ) )
            $settings['machine-translation'] = sanitize_text_field( $settings['machine-translation']  );
        else
            $settings['machine-translation'] = 'no';

        $trp           = TRP_Translate_Press::get_trp_instance();
        $trp_languages = $trp->get_component( 'languages' );
        $trp_settings  = $trp->get_component( 'settings' );

        $settings['machine-translate-codes'] = $trp_languages->get_iso_codes( $trp_settings->get_setting( 'translation-languages' ) );

        return apply_filters( 'trp_machine_translation_sanitize_settings', $settings );
    }

    /*
    * Advanced page content
    */
    public function machine_translation_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/machine-translation-settings-page.php';
    }

    /**
    * Test selected API functionality
    */
    public function test_api_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/test-api-settings-page.php';
    }

    public function load_engines(){
        include_once TRP_PLUGIN_DIR . 'includes/google-translate/functions.php';
        include_once TRP_PLUGIN_DIR . 'includes/google-translate/class-google-translate-v2-machine-translator.php';
    }

    public function get_active_engine( $settings ){
        $default = 'TRP_Google_Translate_V2_Machine_Translator';

        if( empty( $settings['translation-engine'] ) )
            $value = $default;
        else {
            $value = 'TRP_' . ucwords( $settings['translation-engine'] ) . '_Machine_Translator'; // class name needs to follow this pattern

            if( !class_exists( $value ) )
                $value = $default;
        }

        return new $value( $settings );
    }

    public function translation_engines_upsell( $engines ){
        $engines[] = array( 'value' => 'deepl_upsell', 'label' => __( 'DeepL', 'translatepress-multilingual' ) );

        return $engines;
    }
}
