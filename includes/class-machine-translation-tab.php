<?php

class TRP_Machine_Translation_Tab {

    private $settings;

    public function __construct() {

        $settings = get_option( 'trp_machine_translation_settings', false );

        if( !empty( $settings ) )
            $this->settings = $settings;
        else
            $this->settings = array( 'machine-translation' => 'no' );

    }

    /*
    * Add new tab to TP settings
    *
    * Hooked to trp_settings_tabs
    */
    public function add_tab_to_navigation( $tabs ){
        $tab = array(
            'name'  => __( 'Machine Translation', 'translatepress-multilingual' ),
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
        add_submenu_page( 'TRPHidden', 'TranslatePress Machine Translation', 'TRPHidden', 'manage_options', 'trp_machine_translation', array( $this, 'trp_machine_translation_page_content' ) );
        add_submenu_page( 'TRPHidden', 'TranslatePress Test Google API Key', 'TRPHidden', 'manage_options', 'trp_test_google_key_page', array( $this, 'trp_test_google_key_page_content' ) );
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
        settings_errors( 'trp_machine_translation_settings' );
    }

    /*
    * Sanitize settings
    */
    public function sanitize_settings( $settings ){
        if( !empty( $settings['machine-translation'] ) )
            $settings['machine-translation'] = sanitize_text_field( $settings['machine-translation']  );
        else
            $settings['machine-translation'] = 'no';

        return apply_filters( 'trp_machine_translation_sanitize_settings', $settings );
    }

    /*
    * Advanced page content
    */
    public function trp_machine_translation_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/machine-translation-settings-page.php';
    }

    /**
    * Test Google Key page content.
    */
    public function trp_test_google_key_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/test-google-key-settings-page.php';
    }

    public function load_engines(){
        include_once TRP_PLUGIN_DIR . 'includes/google-translate/functions.php';
        include_once TRP_PLUGIN_DIR . 'includes/google-translate/class-google-translate-v2-machine-translator.php';
    }
}
