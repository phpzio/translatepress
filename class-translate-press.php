<?php

/**
 * Class TRP_Translate_Press
 *
 * Singleton. Loads required files, initializes components and hooks methods.
 *
 */
class TRP_Translate_Press{
    protected $loader;
    protected $settings;
    protected $translation_render;
    protected $machine_translator;
    protected $query;
    protected $language_switcher;
    protected $translation_manager;
    protected $url_converter;
    protected $languages;
    protected $slug_manager;
    public static $translate_press = null;

    /**
     * Get singleton object.
     *
     * @return TRP_Translate_Press      Singleton object.
     */
    public static function get_trp_instance(){
        if ( self::$translate_press == null ){
            self::$translate_press = new TRP_Translate_Press();
        }

        return self::$translate_press;
    }

    /**
     * TRP_Translate_Press constructor.
     */
    public function __construct() {
        define( 'TRP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'TRP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'TRP_PLUGIN_BASE', plugin_basename( __DIR__ . '/index.php' ) );
        define( 'TRP_PLUGIN_SLUG', 'translatepress-multilingual' );
        define( 'TRP_PLUGIN_VERSION', '1.3.8' );

	    wp_cache_add_non_persistent_groups(array('trp'));

        $this->load_dependencies();
        $this->initialize_components();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
    }

    /**
     * Returns particular component by name.
     *
     * @param string $component     'loader' | 'settings' | 'translation_render' |
     *                              'machine_translator' | 'query' | 'language_switcher' |
     *                              'translation_manager' | 'url_converter' | 'languages'
     * @return mixed
     */
    public function get_component( $component ){
        return $this->$component;
    }

    /**
     * Includes necessary files.
     */
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
        require_once TRP_PLUGIN_DIR . 'includes/class-uri.php';
        require_once TRP_PLUGIN_DIR . 'includes/class-plugin-notices.php';
        require_once TRP_PLUGIN_DIR . 'includes/functions.php';
        require_once TRP_PLUGIN_DIR . 'assets/lib/simplehtmldom/simple_html_dom.php';
        require_once TRP_PLUGIN_DIR . 'includes/shortcodes.php';
    }

    /**
     * Instantiates components.
     */
    protected function initialize_components() {
        $this->loader = new TRP_Hooks_Loader();
        $this->languages = new TRP_Languages();
        $this->settings = new TRP_Settings();
        $this->translation_render = new TRP_Translation_Render( $this->settings->get_settings() );
        $this->url_converter = new TRP_Url_Converter( $this->settings->get_settings() );
        $this->language_switcher = new TRP_Language_Switcher( $this->settings->get_settings(), $this );
        $this->query = new TRP_Query( $this->settings->get_settings() );
        $this->machine_translator = new TRP_Machine_Translator( $this->settings->get_settings() );
        $this->translation_manager = new TRP_Translation_Manager( $this->settings->get_settings() );
        $this->notifications = new TRP_Trigger_Plugin_Notifications();
    }

    /**
     * Hooks methods used in admin area.
     */
    protected function define_admin_hooks() {
        $this->loader->add_action( 'admin_menu', $this->settings, 'register_menu_page' );
        $this->loader->add_action( 'admin_init', $this->settings, 'register_setting' );
        $this->loader->add_action( 'admin_notices', $this->settings, 'admin_notices' );
        $this->loader->add_action( 'admin_enqueue_scripts', $this->settings, 'enqueue_scripts_and_styles', 10, 1 );
        $this->loader->add_filter( 'plugin_action_links_' . TRP_PLUGIN_BASE , $this->settings, 'plugin_action_links', 10, 1 );
        $this->loader->add_action( 'trp_settings_navigation_tabs', $this->settings, 'add_navigation_tabs' );
        $this->loader->add_action( 'trp_language_selector', $this->settings, 'languages_selector', 10, 1 );


        $this->loader->add_action( 'wp_ajax_nopriv_trp_get_translations', $this->translation_manager, 'get_translations' );
        $this->loader->add_action( 'wp_head', $this->translation_manager, 'add_slug_as_meta_tag', 1 );


        $this->loader->add_action( 'wp_ajax_trp_get_translations', $this->translation_manager, 'get_translations' );
        $this->loader->add_action( 'wp_ajax_trp_save_translations', $this->translation_manager, 'save_translations' );
        $this->loader->add_action( 'wp_ajax_trp_create_translation_block', $this->translation_manager, 'create_translation_block' );
        $this->loader->add_action( 'init', $this->translation_manager, 'split_translation_block' );


        $this->loader->add_action( 'wp_ajax_trp_process_js_strings_in_translation_editor', $this->translation_render, 'process_js_strings_in_translation_editor' );
        
        $this->loader->add_action( 'wp_ajax_trp_gettext_get_translations', $this->translation_manager, 'gettext_get_translations' );
        $this->loader->add_action( 'wp_ajax_trp_gettext_save_translations', $this->translation_manager, 'gettext_save_translations' );
        
        $this->loader->add_action( 'wp_ajax_trp_publish_language', $this->translation_manager, 'publish_language' );

    }

    /**
     * Hooks methods used in front-end
     */
    protected function define_frontend_hooks(){
        $this->loader->add_action( 'init', $this->translation_render, 'start_output_buffer', 0 );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_render, 'enqueue_dynamic_translation', 1 );
        $this->loader->add_filter( 'wp_redirect', $this->translation_render, 'force_preview_on_url_redirect', 99, 2 );
        $this->loader->add_filter( 'wp_redirect', $this->translation_render, 'force_language_on_form_url_redirect', 99, 2 );
        $this->loader->add_filter( 'trp_before_translate_content', $this->translation_render, 'force_preview_on_url_in_ajax', 10 );
        $this->loader->add_filter( 'trp_before_translate_content', $this->translation_render, 'force_form_language_on_url_in_ajax', 20 );
        /* handle CDATA str replacement from the content as it is messing up the renderer */
        $this->loader->add_filter( "trp_before_translate_content", $this->translation_render, 'handle_cdata', 1000 );
        
        /* apply translation filters for REST API response */
        $post_types = get_post_types();
        foreach ( $post_types as $post_type ) {            
            $this->loader->add_filter( 'rest_prepare_'.$post_type, $this->translation_render, 'handle_rest_api_translations' );
        }




        $this->loader->add_action( 'wp_enqueue_scripts', $this->language_switcher, 'enqueue_language_switcher_scripts' );
        $this->loader->add_action( 'wp_footer', $this->language_switcher, 'add_floater_language_switcher' );
        $this->loader->add_filter( 'init', $this->language_switcher, 'register_ls_menu_switcher' );
        $this->loader->add_action( 'wp_get_nav_menu_items', $this->language_switcher, 'ls_menu_permalinks', 10, 3 );
        add_shortcode( 'language-switcher', array( $this->language_switcher, 'language_switcher' ) );


        $this->loader->add_action( 'trp_head', $this->translation_manager, 'enqueue_scripts_and_styles' );
        $this->loader->add_filter( 'template_include', $this->translation_manager, 'translation_editor', 9999 );
        $this->loader->add_action( 'wp_enqueue_scripts', $this->translation_manager, 'enqueue_preview_scripts_and_styles' );
        $this->loader->add_action( 'admin_bar_menu', $this->translation_manager, 'add_shortcut_to_translation_editor', 90, 1 );
        $this->loader->add_action( 'admin_head', $this->translation_manager, 'add_styling_to_admin_bar_button', 10 );
        $this->loader->add_filter( 'show_admin_bar', $this->translation_manager, 'hide_admin_bar_when_in_editor', 90 );



        $this->loader->add_filter( 'home_url', $this->url_converter, 'add_language_to_home_url', 1, 4 );
        $this->loader->add_action( 'wp_head', $this->url_converter, 'add_hreflang_to_head' );
        $this->loader->add_filter( 'language_attributes', $this->url_converter, 'change_lang_attr_in_html_tag', 10, 1 );


        $this->loader->add_filter( 'widget_text', null, 'do_shortcode', 11 );
        $this->loader->add_filter( 'widget_text', null, 'shortcode_unautop', 11 );

        /* handle dynamic texts with gettext */
        $this->loader->add_filter( 'locale', $this->languages, 'change_locale' );
        
        $this->loader->add_action( 'init', $this->translation_manager, 'create_gettext_translated_global' );        
        $this->loader->add_action( 'admin_init', $this->translation_manager, 'create_gettext_translated_global' );
        $this->loader->add_action( 'init', $this->translation_manager, 'apply_gettext_filter_on_frontend' );
        $this->loader->add_action( 'admin_init', $this->translation_manager, 'apply_gettext_filter' );
        $this->loader->add_action( 'shutdown', $this->translation_manager, 'machine_translate_gettext', 100 );


        /* we need to treat the date_i18n function differently so we remove the gettext wraps */
        $this->loader->add_filter( 'date_i18n', $this->translation_manager, 'handle_date_i18n_function_for_gettext', 1, 4 );
	    /* strip esc_url() from gettext wraps */
	    $this->loader->add_filter( 'clean_url', $this->translation_manager, 'trp_strip_gettext_tags_from_esc_url', 1, 3 );
	    /* strip sanitize_title() from gettext wraps and apply custom trp_remove_accents */
	    $this->loader->add_filter( 'sanitize_title', $this->translation_manager, 'trp_sanitize_title', 1, 3 );

        /* define an update hook here */
        $this->loader->add_action( 'plugins_loaded', $this->query, 'check_for_necessary_updates', 10 );

        $this->loader->add_filter( 'trp_language_name', $this->languages, 'beautify_language_name', 10, 4 );
        $this->loader->add_filter( 'trp_languages', $this->languages, 'reorder_languages', 10, 2 );

        /* set up wp_mail hooks */
        $this->loader->add_filter( 'wp_mail', $this->translation_render, 'wp_mail_filter', 200 );

        /* hide php ors and notice when we are storing strings in db */
        $this->loader->add_action( 'init', $this->translation_render, 'trp_debug_mode_off', 0 );

        /* fix wptexturize to always replace with the default translated strings */
        $this->loader->add_action( 'gettext_with_context', $this->translation_render, 'fix_wptexturize_characters', 999, 4 );

        /* ?or init ? hook here where you can change the $current_user global */
        $this->loader->add_action( 'init', $this->translation_manager, 'trp_view_as_user' );
        
        /** 
         * we need to modify the permalinks structure for woocommerce when we switch languages
         * when woo registers post_types and taxonomies in the rewrite parameter of the function they change the slugs of the items (they are localized with _x )
         * we can't flush the permalinks on every page load so we filter the rewrite_rules option 
         */
        $this->loader->add_filter( "option_rewrite_rules", $this->url_converter, 'woocommerce_filter_permalinks_on_other_languages' );
        $this->loader->add_filter( "option_woocommerce_permalinks", $this->url_converter, 'woocommerce_filter_permalink_option' );
        $this->loader->add_filter( "pre_update_option_woocommerce_permalinks", $this->url_converter, 'woocommerce_handle_permalink_option_on_frontend', 10, 2 );

        /* add to the body class the current language */
        $this->loader->add_filter( "body_class", $this->translation_manager, 'add_language_to_body_class' );

        /* load textdomain */
        $this->loader->add_action( "init", $this, 'init_translation', 8 );
    }

    /**
     * Register hooks to WP.
     */
    public function run() {
    	/*
    	 * Hook that prevents running the hooks. Caution: some TP code like constructors of classes still run!
    	 */
    	$run_tp = apply_filters( 'trp_allow_tp_to_run', true );
    	if ( $run_tp ) {
		    $this->loader->run();
	    }
    }

    /**
     * Load plugin textdomain
     */
    public function init_translation(){
        load_plugin_textdomain( 'translatepress-multilingual', false, basename(dirname(__FILE__)) . '/languages/' );
    }

}