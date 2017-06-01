<?php


class TRP_Translate_Press_Pro extends TRP_Translate_Press{

    protected function load_dependencies(){
        parent::load_dependencies();
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-translation-render-pro.php' );
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-settings-pro.php' );
    }

    protected function initialize_components(){
        $this->settings = new TRP_Settings_Pro();
        $this->url_converter = new TRP_Url_Converter( $this->settings->get_settings() );
        $this->language_switcher = new TRP_Language_Switcher( $this->settings->get_settings(), $this->url_converter );
        $this->trp_query = new TRP_Query( $this->settings->get_settings() );
        $this->settings->set_trp_query( $this->trp_query );
        $this->machine_translator = new TRP_Machine_Translator( $this->settings->get_settings(), $this->trp_query );
        $this->translation_render = new TRP_Translation_Render_Pro( $this->settings->get_settings(), $this->machine_translator, $this->trp_query );
        $this->slug_manager = new TRP_Slug_Manager( $this->settings->get_settings(), $this->url_converter, $this->trp_query );
        $this->translation_manager = new TRP_Translation_Manager( $this->settings->get_settings(), $this->translation_render, $this->trp_query, $this->slug_manager );

        $this->loader->add_action( 'admin_enqueue_scripts', $this->settings, 'enqueue_sortable_language_script' );
    }

}