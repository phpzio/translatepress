<?php


class TRP_Translate_Press_Pro extends TRP_Translate_Press{

    protected function load_dependencies(){
        parent::load_dependencies();
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-translation-render-pro.php' );
    }

    protected function initialize_components(){
        $this->settings = new TRP_Settings( $this->get_version() );
        $this->machine_translator = new TRP_Machine_Translator( $this->get_version(), $this->settings->getSettings() );
        $this->translation_render = new TRP_Translation_Render_Pro( $this->get_version(), $this->settings->getSettings(), $this->machine_translator );
    }
}