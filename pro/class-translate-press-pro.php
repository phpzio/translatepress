<?php


class TRP_Translate_Press_Pro extends TRP_Translate_Press{
    protected $trp_plugin_updater;

    protected function load_dependencies(){
        require_once(  TRP_PLUGIN_DIR . 'pro/includes/class-plugin-updater.php' );
        parent::load_dependencies();
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-translation-render-pro.php' );
        require_once( TRP_PLUGIN_DIR . 'pro/includes/class-settings-pro.php' );
    }

    protected function initialize_components() {
        $this->trp_plugin_updater = new TRP_Plugin_Updater();
        $this->trp_settings = new TRP_Settings_Pro();
        $this->translation_render = new TRP_Translation_Render_Pro($this->trp_settings->get_settings());

        $this->loader->add_action( 'admin_menu', $this->trp_plugin_updater, 'license_menu' );
        $this->loader->add_action( 'admin_init', $this->trp_plugin_updater, 'register_option' );
        $this->loader->add_action( 'admin_notices', $this->trp_plugin_updater, 'admin_notices' );
        $this->loader->add_action( 'admin_init', $this->trp_plugin_updater, 'activate_license' );

        $this->loader->add_action( 'admin_enqueue_scripts', $this->trp_settings, 'enqueue_sortable_language_script' );
        $this->loader->add_action( 'trp_settings_navigation_tabs', $this->trp_settings, 'add_navigation_tabs' );
    }

}