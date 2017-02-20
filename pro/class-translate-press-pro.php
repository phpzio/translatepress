<?php


class TRP_Translate_Press_Pro extends TRP_Translate_Press{

    protected function load_dependencies(){
        parent::load_dependencies();
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-translation-render-pro.php' );
    }

    protected function initialize_components(){
        $this->settings = new TRP_Settings( );
        $this->trp_query = new TRP_Query( $this->settings->get_settings() );
        $this->settings->set_trp_query( $this->trp_query );
        $this->machine_translator = new TRP_Machine_Translator( $this->settings->get_settings(), $this->trp_query );
        $this->translation_render = new TRP_Translation_Render_Pro( $this->settings->get_settings(), $this->machine_translator, $this->trp_query );
    }

}