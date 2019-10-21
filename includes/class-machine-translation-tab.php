<?php

class TRP_Machine_Translation_Tab {

    private $settings;

    public function __construct( $settings ) {

        $this->settings = $settings;

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
    public function sanitize_settings($mt_settings ){
        if( !empty( $mt_settings['machine-translation'] ) )
            $mt_settings['machine-translation'] = sanitize_text_field( $mt_settings['machine-translation']  );
        else
            $mt_settings['machine-translation'] = 'no';

        return apply_filters( 'trp_machine_translation_sanitize_settings', $mt_settings );
    }

    /*
    * Automatic Translation
    */
    public function machine_translation_page_content(){
        $trp                       = TRP_Translate_Press::get_trp_instance();

        $machine_translator_logger = $trp->get_component( 'machine_translator_logger' );
        $machine_translator_logger->maybe_reset_counter_date();

        $machine_translator        = $trp->get_component( 'machine_translator' );

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

    public function get_active_engine( ){
        // This $default is just a fail safe. Should never be used. The real default is set in TRP_Settings->set_options function
        $default = 'TRP_Google_Translate_V2_Machine_Translator';

        if( empty( $this->settings['trp_machine_translation_settings']['translation-engine'] ) )
            $value = $default;
        else {
            $value = 'TRP_' . ucwords( $this->settings['trp_machine_translation_settings']['translation-engine'] ) . '_Machine_Translator'; // class name needs to follow this pattern

            if( !class_exists( $value ) )
                $value = $default;
        }

        return new $value( $this->settings );
    }

    public function translation_engines_upsell( $engines ){
        $engines[] = array( 'value' => 'deepl_upsell', 'label' => __( 'DeepL', 'translatepress-multilingual' ) );

        return $engines;
    }
}
