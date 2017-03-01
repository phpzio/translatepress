<?php

class TRP_Translate_Press{
    protected $loader;
    protected $settings;
    protected $translation_render;
    protected $machine_translator;
    protected $trp_query;
    protected $language_switcher;
    protected $translation_manager;

    public function __construct() {
        define( 'TRP_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'TRP_PLUGIN_URL', plugin_dir_url( dirname (__FILE__) ) );
        define( 'TRP_PLUGIN_SLUG', 'translate-press' );

        $this->load_dependencies();
        $this->initialize_components();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
    }

    protected function load_dependencies() {
        // todo if file exists on everything
        require_once TRP_PLUGIN_DIR . 'includes/admin/class-settings.php';
        require_once TRP_PLUGIN_DIR . 'includes/admin/class-translation-manager.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-hooks-loader.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-utils.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-translation-render.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-language-switcher.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-machine-translator.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-query.php';
        require_once TRP_PLUGIN_DIR . 'assets/lib/simplehtmldom/simple_html_dom.php';

        $this->loader = new TRP_Hooks_Loader();
    }

    // todo make a common factor to avoid duplication for pro class
    protected function initialize_components(){
        $this->settings = new TRP_Settings( );
        $this->trp_query = new TRP_Query( $this->settings->get_settings() );
        $this->settings->set_trp_query( $this->trp_query );
        $this->machine_translator = new TRP_Machine_Translator( $this->settings->get_settings(), $this->trp_query );
        $this->translation_render = new TRP_Translation_Render( $this->settings->get_settings(), $this->machine_translator, $this->trp_query );
        $this->language_switcher = new TRP_Language_Switcher( $this->settings->get_settings() );
        $this->translation_manager = new TRP_Translation_Manager( $this->settings->get_settings() );
    }

    protected function define_admin_hooks() {
        $this->loader->add_action( 'admin_menu', $this->settings, 'register_menu_page' );
        $this->loader->add_action( 'admin_init', $this->settings, 'register_setting' );
        $this->loader->add_action( 'admin_notices', $this->settings, 'admin_notices' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this->settings, 'enqueue_scripts_and_styles' );

    }

    protected function define_frontend_hooks(){
        $this->loader->add_action( 'wp', $this->translation_render, 'start_object_cache' );

        $this->loader->add_action( 'trp_head', $this->translation_manager, 'enqueue_scripts_and_styles' );
        $this->loader->add_filter( 'template_include', $this->translation_manager, 'translation_editor' );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_manager, 'enqueue_preview_scripts_and_styles' );

        add_shortcode( 'language-switcher', array( $this->language_switcher, 'language_switcher' ) );
    }

    public function run() {
        $this->loader->run();
    }

}