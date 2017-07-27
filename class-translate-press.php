<?php

class TRP_Translate_Press{
    protected $loader;
    protected $settings;
    protected $translation_render;
    protected $machine_translator;
    protected $query;
    protected $language_switcher;
    protected $translation_manager;
    protected $slug_manager;
    protected $url_converter;
    protected $languages;
    public static $translate_press = null;


    public static function get_trp_instance(){
        if ( self::$translate_press == null ){
            if ( file_exists ( plugin_dir_path(__FILE__) . 'pro/class-translate-press-pro.php' ) ) {
                require_once plugin_dir_path(__FILE__) . 'pro/class-translate-press-pro.php';
                if ( class_exists( 'TRP_Translate_Press_Pro' ) ) {
                    self::$translate_press = new TRP_Translate_Press_Pro();
                }
            }
            if ( self::$translate_press == null ) {
                self::$translate_press = new TRP_Translate_Press();
            }
        }

        return self::$translate_press;
    }

    public function __construct() {
        define( 'TRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'TRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'TRP_PLUGIN_SLUG', 'translatepress' );
        define( 'TRP_PLUGIN_VERSION', '1.0.0-beta' );

        $this->load_dependencies();
        $this->initialize_components();
        $this->initialize_common_components();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
    }

    public function get_component( $component ){
        return $this->$component;
    }

    protected function load_dependencies() {
        require_once TRP_PLUGIN_DIR . 'includes/class-settings.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-translation-manager.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-hooks-loader.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-languages.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-translation-render.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-language-switcher.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-machine-translator.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-query.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-url-converter.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-slug-manager.php';
        require_once TRP_PLUGIN_DIR . 'includes/functions.php';
        require_once TRP_PLUGIN_DIR . 'assets/lib/simplehtmldom/simple_html_dom.php';

        $this->loader = new TRP_Hooks_Loader();
        $this->languages = new TRP_Languages();
    }

    protected function initialize_components() {
        $this->settings = new TRP_Settings();

        $this->translation_render = new TRP_Translation_Render( $this->settings->get_settings() );
    }

    protected function initialize_common_components(){
        $this->url_converter = new TRP_Url_Converter( $this->settings->get_settings() );
        $this->language_switcher = new TRP_Language_Switcher( $this->settings->get_settings(), $this->url_converter );
        $this->query = new TRP_Query( $this->settings->get_settings() );
        $this->slug_manager = new TRP_Slug_Manager( $this->settings->get_settings() );
        $this->machine_translator = new TRP_Machine_Translator( $this->settings->get_settings() );
        $this->translation_manager = new TRP_Translation_Manager( $this->settings->get_settings() );

    }

    protected function define_admin_hooks() {
        $this->loader->add_action( 'admin_menu', $this->settings, 'register_menu_page' );
        $this->loader->add_action( 'admin_init', $this->settings, 'register_setting' );
        $this->loader->add_action( 'admin_notices', $this->settings, 'admin_notices' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this->settings, 'enqueue_scripts_and_styles', 10, 1 );


        $this->loader->add_action( 'wp_ajax_nopriv_trp_get_translations', $this->translation_manager, 'get_translations' );
        $this->loader->add_action( 'wp_head', $this->translation_manager, 'add_slug_as_meta_tag', 1 );


        $this->loader->add_action( 'wp_ajax_trp_get_translations', $this->translation_manager, 'get_translations' );
        $this->loader->add_action( 'wp_ajax_trp_save_translations', $this->translation_manager, 'save_translations' );
        $this->loader->add_action( 'wp_ajax_trp_publish_language', $this->translation_manager, 'publish_language' );

    }

    protected function define_frontend_hooks(){
        $this->loader->add_action( 'wp', $this->translation_render, 'start_object_cache' );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_render, 'enqueue_dynamic_translation', 1 );


        $this->loader->add_action( 'wp_enqueue_scripts', $this->language_switcher, 'enqueue_language_switcher_scripts' );
        $this->loader->add_action( 'wp_footer', $this->language_switcher, 'add_floater_language_switcher' );
        $this->loader->add_filter( 'init', $this->language_switcher, 'add_ls_to_menu' );
        $this->loader->add_action( 'wp_get_nav_menu_items', $this->language_switcher, 'ls_menu_permalinks', 10, 3 );
        add_shortcode( 'language-switcher', array( $this->language_switcher, 'language_switcher' ) );

        $this->loader->add_action( 'trp_head', $this->translation_manager, 'enqueue_scripts_and_styles' );
        $this->loader->add_filter( 'template_include', $this->translation_manager, 'translation_editor' );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_manager, 'enqueue_preview_scripts_and_styles' );
        $this->loader->add_action( 'admin_bar_menu', $this->translation_manager, 'add_shortcut_to_translation_editor', 90, 1 );
        $this->loader->add_filter( 'show_admin_bar', $this->translation_manager, 'hide_admin_bar_when_in_editor', 90 );

        /* manage slug translation hooks */
        $this->loader->add_filter( 'request', $this->slug_manager, 'change_slug_var_in_request' );
        $this->loader->add_filter( 'sanitize_title', $this->slug_manager, 'change_query_for_page_by_page_slug', 10, 3 );
        $this->loader->add_filter( 'post_link', $this->slug_manager, 'translate_slug_for_posts', 10, 3 );
        $this->loader->add_filter( 'get_page_uri', $this->slug_manager, 'translate_slugs_for_pages', 10, 2 );
        $this->loader->add_action( 'wp_ajax_trp_save_slug_translation', $this->slug_manager, 'save_translated_slug' );

        $this->loader->add_action( 'template_redirect', $this->url_converter, 'redirect_to_default_language' );
        $this->loader->add_filter( 'home_url', $this->url_converter, 'add_language_to_home_url', 1, 4 );
        $this->loader->add_action( 'wp_head', $this->url_converter, 'add_hreflang_to_head' );

        $this->loader->add_filter( 'widget_text', null, 'do_shortcode', 11 );
        $this->loader->add_filter( 'widget_text', null, 'shortcode_unautop', 11 );

        $this->loader->add_filter( 'trp_language_name', $this->languages, 'beautify_language_name', 10, 3 );


    }

    public function run() {
        $this->loader->run();
    }

}