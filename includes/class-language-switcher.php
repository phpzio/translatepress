<?php

class TRP_Language_Switcher{

    protected $settings;

    public function __construct( $settings ){
        $this->settings = $settings;
        $language = $this->get_current_language();
        define( 'TRP_LANGUAGE', $language );
    }

    public function language_switcher(){
        ob_start();
        //todo dummy. get published languages
        $languages = array(
            'en'     => 'English',
            'et'     => 'Estonian',
            'fr'     => 'French',
            'de'     => 'German',
            'el'     => 'Greek' );
        require_once TRP_PLUGIN_DIR . 'includes/partials/language-switcher-1.php';
        return ob_get_clean();
    }

    public function get_current_language(){

        //todo add all possible ways of determining language: cookies, global define etc.
        if ( ! empty ( $_GET['lang'] ) ){
            $language_code = esc_attr( $_GET['lang'] );
            if ( in_array( $language_code, $this->settings['translation-languages'] ) ) {
                return $language_code;
            }
        }
        return false;
    }
}