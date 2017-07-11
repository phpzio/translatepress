<?php


class TRP_Translate_Press_Pro extends TRP_Translate_Press{

    protected function load_dependencies(){
        parent::load_dependencies();
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-translation-render-pro.php' );
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-settings-pro.php' );
    }

    protected function initialize_components() {
        $this->trp_settings = new TRP_Settings_Pro();
        $this->translation_render = new TRP_Translation_Render_Pro($this->trp_settings->get_settings());
        $this->loader->add_action('admin_enqueue_scripts', $this->trp_settings, 'enqueue_sortable_language_script');
    }

}