<?php

class TRP_Settings{

    protected $settings;
    protected $version;

    public function __construct( $version ) {
        $this->set_options();
        $this->version = $version;
    }

    public function register_menu_page(){

        // TODO add icon url, and menu position in menu page.
        add_menu_page( 'Translate Press', 'Translate Press', apply_filters( 'trp_settings_capability', 'manage_options' ), 'translate-press-settings', array( $this, 'settings_page_content' ) );
    }

    public function settings_page_content(){
        $languages = TRP_Utils::get_languages();
        require_once TRP_PLUGIN_DIR . 'includes/admin/partials/main-settings-page.php';
    }

    public function register_setting(){
        register_setting( 'trp_settings', 'trp_settings', array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $settings ){
        return apply_filters( 'trp_extra_sanitize_settings', $settings );
    }

    public function admin_notices(){
        settings_errors( 'trp_settings' );
    }

    private function set_options(){
        $settings = get_option( 'trp_settings', 'not_set' );
        if ( 'not_set' == $settings ){
            // initialize default settings
            $settings = array(
                'default-language'      => 'en',
                'translation-languages' => array()
            );
            update_option ( 'trp_settings', $settings );
        }

        $this->settings = $settings;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            'trp-settings-style',
            TRP_PLUGIN_DIR . 'assets/css/trp-back-end-style.css',
            array(),
            $this->version,
            FALSE
        );
    }
}