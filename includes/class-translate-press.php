<?php

class TRP_Translate_Press{
    protected $loader;
    protected $version;

    public function __construct() {
        define( 'TRP_PLUGIN_DIR', plugin_dir_path( dirname( __FILE__ ) ) );
        define( 'TRP_PLUGIN_URL', plugin_dir_url(__FILE__) );
        define( 'TRP_PLUGIN_SLUG', 'translate-press' );

        $this->version = '0.0.1';

        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    private function load_dependencies() {
        require_once TRP_PLUGIN_DIR . '/includes/admin/class-settings.php';
        require_once TRP_PLUGIN_DIR . '/includes/class-hooks-loader.php';
        require_once TRP_PLUGIN_DIR . '/includes/class-utils.php';

        $this->loader = new TRP_Hooks_Loader();
    }

    private function define_admin_hooks() {
        $settings = new TRP_Settings( $this->get_version() );

        $this->loader->add_action( 'admin_menu', $settings, 'register_menu_page' );
        $this->loader->add_action( 'admin_init', $settings, 'register_setting' );
        $this->loader->add_action( 'admin_notices', $settings, 'admin_notices' );
        $this->loader->add_action( 'admin_enqueue_scripts', $settings, 'enqueue_styles' );

    }

    public function run() {
        $this->loader->run();
    }

    public function get_version() {
        return $this->version;
    }
}