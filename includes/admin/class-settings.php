<?php

class TRP_Settings{

    protected $settings;
    protected $version;

    public function __construct( $version ) {
        $this->version = $version;
        $this->set_options();
    }

    public function getSettings(){
        return $this->settings;
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

        if ( !isset ( $settings['default-language'] ) ) {
            $settings['default-language'] = 'en';
        }
        if ( !isset ( $settings['translation-languages'] ) ){
            $settings['translation_languages'] = array();
        }

        foreach ( $settings['translation-languages'] as $language_code ){
            $this->check_table( $settings['default-language'], $language_code );
        }


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

    private function check_table( $default, $translated ){
        global $wpdb;
        $table_name = $wpdb->prefix . 'trp_dictionary_' . $default . '_' . $translated;
        if ( $wpdb->get_var( "SHOW TABLES LIKE ' $table_name '" ) != $table_name ) {
            // table not in database. Create new table
            $charset_collate = $wpdb->get_charset_collate();

            // todo different charset collation for each language?
            $sql = "CREATE TABLE `" . $table_name . "`(
                                    id bigint(20) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                                    original  varchar(32) NOT NULL,
                                    translated  varchar(32),
                                    human int(20),
                                    UNIQUE KEY id (id) )
                                     $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

    }
}