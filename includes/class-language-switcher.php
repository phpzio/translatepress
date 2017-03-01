<?php

class TRP_Language_Switcher{

    protected $settings;

    public function __construct( $settings ){
        $this->settings = $settings;
        $language = $this->get_current_language();
        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = $language;
    }

    public function language_switcher(){
        ob_start();
        global $TRP_LANGUAGE;
        $current_language = $TRP_LANGUAGE;
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );
        //todo switch between templates based on settings
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
        return $this->settings['default-language'];
    }
}