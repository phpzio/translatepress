<?php

/**
 * Class TRP_Translation_Manager
 *
 * Handles Front-end Translation Editor, including Ajax requests.
 */
class TRP_Translation_Manager{
    protected $settings;
	/** @var TRP_Translation_Render */
    protected $translation_render;
	/** @var TRP_Query */
    protected $trp_query;
    protected $machine_translator;
    protected $slug_manager;
    protected $url_converter;

    /**
     * TRP_Translation_Manager constructor.
     *
     * @param array $settings       Settings option.
     */
    public function __construct( $settings ){
        $this->settings = $settings;
    }

    // mode == true, mode == preview
    /**
     * Returns boolean whether current page is part of the Translation Editor.
     *
     * @param string $mode          'true' | 'preview'
     * @return bool                 Whether current page is part of the Translation Editor.
     */
    protected function conditions_met( $mode = 'true' ){
        if ( isset( $_REQUEST['trp-edit-translation'] ) && esc_attr( $_REQUEST['trp-edit-translation'] ) == $mode ) {
            if ( current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) && ! is_admin() ) {
                return true;
            }elseif ( esc_attr( $_REQUEST['trp-edit-translation'] ) == "preview" ){
                return true;
            }else{
                wp_die(
                    '<h1>' . esc_html__( 'Cheatin&#8217; uh?' ) . '</h1>' .
                    '<p>' . esc_html__( 'Sorry, you are not allowed to access this page.' ) . '</p>',
                    403
                );
            }
        }
        return false;
    }

    /**
     * Start Translation Editor.
     *
     * Hooked to template_include.
     *
     * @param string $page_template         Current page template.
     * @return string                       Template for translation Editor.
     */
    public function translation_editor( $page_template ){
        if ( ! $this->conditions_met() ){
            return $page_template;
        }

        return TRP_PLUGIN_DIR . 'partials/translation-manager.php' ;
    }

	public function get_merge_rules(){
		$localized_text = $this->string_groups();

    	$merge_rules = array (
    		'top_parents'           => array( 'p', 'div', 'li', 'ol', 'ul', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'h7', 'body', 'footer', 'article', 'main', 'iframe', 'section', 'figure', 'figcaption', 'blockquote', 'cite' ),
		    'self_object_type'      => array( 'translate-press' ),
		    'incompatible_siblings' => array( '[data-trpgettextoriginal]', '[data-trp-node-group="' . $localized_text['dynamicstrings'] . '"]'  )
	    );

    	return apply_filters( 'trp_merge_rules', $merge_rules );
	}

	public function localized_text() {
		$update_seo_add_on = ( class_exists('TRP_Seo_Pack') && !defined('TRP_SP_PLUGIN_VERSION' ) );

        return $this->string_groups() + array(
        	// attribute names
        	'src'                                                       => esc_html__( 'Image source', 'translatepress-multilingual' ),
			'alt'                                                       => esc_html__( 'Alt attribute', 'translatepress-multilingual' ),
			'title'                                                     => esc_html__( 'Title attribute', 'translatepress-multilingual' ),
			'href'                                                      => esc_html__( 'Anchor link', 'translatepress-multilingual' ),
			'placeholder'                                               => esc_html__( 'Placeholder attribute', 'translatepress-multilingual' ),
			'submit'                                                    => esc_html__( 'Submit attribute', 'translatepress-multilingual' ),
			'text'                                                      => esc_html__( 'Text', 'translatepress-multilingual' ),

			'saved'                                                     => esc_html__( 'Saved', 'translatepress-multilingual' ),
			'save_translation'                                          => esc_html__( 'Save translation', 'translatepress-multilingual' ),
			'saving_translation'                                        => esc_html__( 'Saving translation...', 'translatepress-multilingual' ),
			'unsaved_changes'                                           => esc_html__( 'You have unsaved changes!', 'translatepress-multilingual' ),
			'discard'                                                   => esc_html__( 'Discard changes', 'translatepress-multilingual' ),
			'discard_all'                                               => esc_html__( 'Discard All', 'translatepress-multilingual' ),
			'strings_loading'                                           => esc_attr__( 'Loading Strings...', 'translatepress-multilingual' ),
			'select_string'                                             => esc_attr__( 'Select string to translate...', 'translatepress-multilingual' ),
			'close'                                                     => esc_attr__( 'Close Editor', 'translatepress-multilingual' ),
			'from'                                                      => esc_html__( 'From', 'translatepress-multilingual' ),
			'to'                                                        => esc_html__( 'To', 'translatepress-multilingual' ),
			'next'                                                      => esc_html__( 'Next', 'translatepress-multilingual' ),
			'previous'                                                  => esc_html__( 'Previous', 'translatepress-multilingual' ),
			'add_media'                                                 => esc_html__( 'Add Media', 'translatepress-multilingual' ),
			'other_lang'                                                => esc_html__( 'Other languages', 'translatepress-multilingual' ),
			'view_as'                                                   => esc_html__( 'View As', 'translatepress-multilingual' ),
			'view_as_pro'                                               => esc_html__( 'Available in our Pro Versions', 'translatepress-multilingual' ),

			//wp media upload
			'select_or_upload'                                          => esc_html__( 'Select or Upload Media', 'translatepress-multilingual' ),
			'use_this_media'                                            => esc_html__( 'Use this media', 'translatepress-multilingual' ),

			// title attributes
			'edit'                                                      => esc_attr__( 'Translate', 'translatepress-multilingual' ),
			'merge'                                                     => esc_attr__( 'Translate entire block element', 'translatepress-multilingual' ),
			'split'                                                     => esc_attr__( 'Split block to translate strings individually', 'translatepress-multilingual' ),
			'save_title_attr'                                           => esc_attr__( 'Save changes to translation. Shortcut: CTRL(⌘) + S', 'translatepress-multilingual' ),
			'next_title_attr'                                           => esc_attr__( 'Navigate to next string in dropdown list. Shortcut: CTRL(⌘) + ALT + Right Arrow', 'translatepress-multilingual' ),
			'previous_title_attr'                                       => esc_attr__( 'Navigate to previous string in dropdown list. Shortcut: CTRL(⌘) + ALT + Left Arrow', 'translatepress-multilingual' ),
			'discard_all_title_attr'                                    => esc_attr__( 'Discard all changes. Shortcut: CTRL(⌘) + ALT + Z', 'translatepress-multilingual' ),
			'discard_individual_changes_title_attribute'                => esc_attr__( 'Discard changes to this text box. To discard changes to all text boxes use shortcut: CTRL(⌘) + ALT + Z', 'translatepress-multilingual' ),

			'split_confirmation'                                        => esc_js( __( 'Are you sure you want to split this phrase into smaller pieces?', 'translatepress-multilingual' ) ),
			'translation_not_loaded_yet'                                => wp_kses( __( 'This string is not ready for translation yet. <br>Try again in a moment...', 'translatepress-multilingual'), array( 'br' => array() ) ),

	        'bor_update_notice'                                         => esc_js( __( 'For this option to work, please update the Browse as other role add-on to the latest version.', 'translatepress-multilingual' ) ),
	        'seo_update_notice'                                         => ($update_seo_add_on) ? esc_js( __( 'To translate slugs, please update the SEO Pack add-on to the latest version.', 'translatepress-multilingual' ) ) : 'seo_pack_update_not_needed' ,

			//Notice when the user has not defined a secondary language
			'extra_lang_row1'                                           => wp_kses( sprintf( __( 'You can add a new language from <a href="%s">Settings->TranslatePress</a>', 'translatepress-multilingual' ), esc_url( admin_url( 'options-general.php?page=translate-press' ) ) ), array( 'a' => ['href' => []] ) ),
			'extra_lang_row2'                                           => wp_kses( __( 'However, you can still use TranslatePress to <strong style="background: #f5fb9d;">modify gettext strings</strong> available in your page.', 'translatepress-multilingual' ), array( 'strong' => ['style' => [] ] )),
			'extra_lang_row3'                                           => esc_html__( 'Strings that are user created can not be modified, only those from themes and plugins.', 'translatepress-multilingual' ),
			//Pro version upselling
			'extra_upsell_title'                                        => esc_html__( 'Extra Translation Features', 'translatepress-multilingual' ),
			'extra_upsell_row1'                                         => esc_html__( 'Support for 221 Extra Languages', 'translatepress-multilingual' ),
			'extra_upsell_row2'                                         => esc_html__( 'Yoast SEO support', 'translatepress-multilingual' ),
			'extra_upsell_row3'                                         => esc_html__( 'Translate SEO Title, Description, Slug', 'translatepress-multilingual' ),
			'extra_upsell_row4'                                         => esc_html__( 'Publish only when translation is done', 'translatepress-multilingual' ),
			'extra_upsell_row5'                                         => esc_html__( 'Translate by Browsing as User Role', 'translatepress-multilingual' ),
			'extra_upsell_row6'                                         => esc_html__( 'Different Menus Items per Language', 'translatepress-multilingual' ),
			'extra_upsell_row7'                                         => esc_html__( 'Automatic User Language Detection', 'translatepress-multilingual' ),
			'extra_upsell_row8'                                         => esc_html__( 'Supported By Real People', 'translatepress-multilingual' ),
			'extra_upsell_button'                                       => wp_kses( sprintf( '<a class="button-primary" target="_blank" href="%s">%s</a>', esc_url( trp_add_affiliate_id_to_link( 'https://translatepress.com/pricing/?utm_source=wpbackend&utm_medium=clientsite&utm_content=tpeditor&utm_campaign=tpfree' ) ), __( 'Find Out More', 'translatepress-multilingual' ) ), array( 'a' => ['class'=> [] , 'target'=> [],'href'=> []] )  )
        );
	}

	public function string_groups(){
		$string_groups = array(
			'slugs'           => esc_html__( 'Slugs', 'translatepress-multilingual' ),
            'metainformation' => esc_html__( 'Meta Information', 'translatepress-multilingual' ),
            'stringlist'      => esc_html__( 'String List', 'translatepress-multilingual' ),
            'gettextstrings'  => esc_html__( 'Gettext Strings', 'translatepress-multilingual' ),
            'images'          => esc_html__( 'Images', 'translatepress-multilingual' ),
            'dynamicstrings'  => esc_html__( 'Dynamic Added Strings', 'translatepress-multilingual' ),
		);
		return apply_filters( 'trp_string_groups', $string_groups );
	}

	public function editor_nonces(){
		$nonces = array(
            'gettranslationsnonceregular'   => wp_create_nonce('get_translations'),
            'savetranslationsnonceregular'  => wp_create_nonce('save_translations'),
            'gettranslationsnoncegettext'   => wp_create_nonce('gettext_get_translations'),
            'savetranslationsnoncegettext'  => wp_create_nonce('gettext_save_translations'),
            'gettranslationsnoncepostslug'  => wp_create_nonce('postslug_get_translations'),
            'savetranslationsnoncepostslug' => wp_create_nonce('postslug_save_translations'),
            'splittbnonce'                  => wp_create_nonce('split_translation_block'),
            'mergetbnonce'                  => wp_create_nonce('merge_translation_block'),
            'logged_out'                    => wp_create_nonce('trp_view_aslogged_out' . get_current_user_id()),
		);

		return apply_filters( 'trp_editor_nonces', $nonces );
	}

    /**
     * Enqueue scripts and styles for translation Editor parent window.
     *
     * hooked to trp_translation_manager_footer
     */
    public function enqueue_scripts_and_styles(){
        wp_enqueue_style( 'trp-editor-style', TRP_PLUGIN_URL . 'assets/css/trp-editor.css', array('dashicons', 'buttons'), TRP_PLUGIN_VERSION );
        wp_enqueue_script( 'trp-editor',  TRP_PLUGIN_URL . 'assets/js/trp-editor.js', array(), TRP_PLUGIN_VERSION );

        // Show upload media dialog in default language
	    switch_to_locale( $this->settings['default-language'] );
	    // Necessary for add media button
	    wp_enqueue_media();

	    // Necessary for add media button
	    wp_print_media_templates();
	    restore_current_locale();

	    $scripts_to_print = apply_filters( 'trp-scripts-for-editor', array( 'jquery', 'jquery-ui-core', 'jquery-effects-core', 'jquery-ui-resizable', 'trp-editor' ) );
        $styles_to_print = apply_filters( 'trp-styles-for-editor', array( 'dashicons', 'trp-editor-style','media-views', 'imgareaselect' /*'wp-admin', 'common', 'site-icon', 'buttons'*/ ) );
        wp_print_scripts( $scripts_to_print );
        wp_print_styles( $styles_to_print );

	    // Necessary for add media button
	    print_footer_scripts();

    }

    /**
     * Enqueue scripts and styles for translation Editor preview window.
     */
    public function enqueue_preview_scripts_and_styles(){
        if ( $this->conditions_met( 'preview' ) ) {
            wp_enqueue_script( 'trp-translation-manager-preview-script',  TRP_PLUGIN_URL . 'assets/js/trp-iframe-preview-script.js', array('jquery'), TRP_PLUGIN_VERSION );
            wp_enqueue_style('trp-preview-iframe-style',  TRP_PLUGIN_URL . 'assets/css/trp-preview-iframe-style.css', array('dashicons'), TRP_PLUGIN_VERSION );
        }
    }

    /**
     * Display button to enter translation Editor in admin bar
     *
     * Hooked to admin_bar_menu.
     *
     * @param $wp_admin_bar
     */
    public function add_shortcut_to_translation_editor( $wp_admin_bar ) {
        if( ! current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ) {
            return;
        }

        if( is_admin () ) {
            $url = add_query_arg( 'trp-edit-translation', 'true', trailingslashit( home_url() ) );

            $title = __( 'Translate Site', 'translatepress-multilingual' );
            $url_target = '_blank';
        } else {

	        if( ! $this->url_converter ) {
		        $trp = TRP_Translate_Press::get_trp_instance();
		        $this->url_converter = $trp->get_component( 'url_converter' );
	        }

	        $url = $this->url_converter->cur_page_url();
	        $url = add_query_arg( 'trp-edit-translation', 'true', $url );

            $title = __( 'Translate Page', 'translatepress-multilingual' );
            $url_target = '';
        }

        $wp_admin_bar->add_node(
            array(
                'id'        => 'trp_edit_translation',
                'title'     => '<span class="ab-icon"></span><span class="ab-label">'. $title .'</span>',
                'href'      => $url,
                'meta'      => array(
                    'class'     => 'trp-edit-translation',
                    'target'    => $url_target
                )
            )
        );

        $wp_admin_bar->add_node(
            array(
                'id'        => 'trp_settings_page',
                'title'     => __( 'Settings', 'translatepress-multilingual' ),
                'href'      => admin_url( 'options-general.php?page=translate-press' ),
                'parent'    => 'trp_edit_translation',
                'meta'      => array(
                    'class' => 'trp-settings-page'
                )
            )
        );

    }

	/**
	* Add the glyph icon for Translate Site button in admin bar
	*
	* hooked to admin_head action
	*/
    public function add_styling_to_admin_bar_button() {
		echo "<style type='text/css'> #wpadminbar #wp-admin-bar-trp_edit_translation .ab-icon:before {    content: '\\f326';    top: 3px;}
		#wpadminbar #wp-admin-bar-trp_edit_translation > .ab-item {
			text-indent: 0;
		}

		#wpadminbar li#wp-admin-bar-trp_edit_translation {
			display: block;
		}</style>";
	}


	/**
     * Function to hide admin bar when in editor preview mode.
     *
     * Hooked to show_admin_bar.
     *
     * @param bool $show_admin_bar      TRUE | FALSE
     * @return bool
     */
    public function hide_admin_bar_when_in_editor( $show_admin_bar ) {

        if( $this->conditions_met( 'preview' ) ) {
            return false;
        }

        return $show_admin_bar;

    }

    /**
     * Create a global with the gettext strings that exist in the database
     */
    public function create_gettext_translated_global(){


        global $trp_translated_gettext_texts;
        if( !is_admin() || $this::is_ajax_on_frontend() ) {
            global $TRP_LANGUAGE;

            if (!$this->trp_query) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->trp_query = $trp->get_component('query');
            }

            $strings = $this->trp_query->get_all_gettext_strings($TRP_LANGUAGE);
            if ( !empty( $strings ) ) {
	            $trp_translated_gettext_texts = $strings;

	            foreach ( $trp_translated_gettext_texts as $key => $value ) {
		            $trp_strings[ $value['domain'] . '::' . $value['original'] ] = $value;
	            }
	            $trp_translated_gettext_texts = $trp_strings;
            }
        }
    }

    /**
     * function that applies the gettext filter on frontend on different hooks depending on what we need
     */
    public function initialize_gettext_processing(){
        $is_ajax_on_frontend = $this::is_ajax_on_frontend();

        /* on ajax hooks from frontend that have the init hook ( we found WooCommerce has it ) apply it earlier */
        if( $is_ajax_on_frontend ){
            add_action( 'wp_loaded', array( $this, 'apply_gettext_filter' ) );
        }
        else{//otherwise start from the wp_head hook
            add_action( 'wp_head', array( $this, 'apply_gettext_filter' ), 100 );
        }

        //if we have woocommerce installed and it is not an ajax request add a gettext hook starting from wp_loaded and remove it on wp_head
        if( class_exists( 'WooCommerce' ) && !$is_ajax_on_frontend ){
            // WooCommerce launches some ajax calls before wp_head, so we need to apply_gettext_filter earlier to catch them
            add_action( 'wp_loaded', array( $this, 'apply_woocommerce_gettext_filter' ), 19 );
        }
    }

    /* apply the gettext filter here */
    public function apply_gettext_filter(){

        //if we have wocommerce installed remove te hook that was added on wp_loaded
        if( class_exists( 'WooCommerce' ) ){
            // WooCommerce launches some ajax calls before wp_head, so we need to apply_gettext_filter earlier to catch them
            remove_action( 'wp_loaded', array( $this, 'apply_woocommerce_gettext_filter' ), 19 );
        }

        $this->call_gettext_filters();

    }

    public function apply_woocommerce_gettext_filter(){
        $this->call_gettext_filters('woocommerce_');
    }

    public function call_gettext_filters( $prefix = '' ){
        global $pagenow;
        // Do not process gettext strings on wp-login pages. Do not process strings in admin area except for when when is_ajax_on_frontend.
        if( ( $pagenow != 'wp-login.php' ) && ( !is_admin() || $this::is_ajax_on_frontend() ) ) {
            add_filter('gettext', array($this, $prefix.'process_gettext_strings'), 100, 3);
            add_filter('gettext_with_context', array($this, $prefix.'process_gettext_strings_with_context'), 100, 4);
            add_filter('ngettext', array($this, $prefix.'process_ngettext_strings'), 100, 5);
            add_filter('ngettext_with_context', array($this, $prefix.'process_ngettext_strings_with_context'), 100, 6);
        }
    }

    /**
     * Function that determines if an ajax request came from the frontend
     * @return bool
     */
    static function is_ajax_on_frontend(){

        /* for our own actions return false */
        if( isset( $_REQUEST['action'] ) && strpos($_REQUEST['action'], 'trp_') === 0 )
            return false;

        $trp = TRP_Translate_Press::get_trp_instance();
        $url_converter = $trp->get_component("url_converter");

        //check here for wp ajax or woocommerce ajax
        if( ( defined('DOING_AJAX') && DOING_AJAX ) || ( defined('WC_DOING_AJAX') && WC_DOING_AJAX ) ){
            $referer = '';
            if ( ! empty( $_REQUEST['_wp_http_referer'] ) ){
                // this one is actually REQUEST_URI from the previous page. It's set by the wp_nonce_field() and wp_referer_field()
                // confusingly enough, wp_get_referer() basically returns $_SERVER['REQUEST_URL'] from the prev page (not a full URL) or
                // $_SERVER['HTTP_REFERER'] that's setup by the client/browser as a full URL (https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referer)
                $referer_uri = wp_unslash( esc_url_raw( $_REQUEST['_wp_http_referer'] ) );
                $req_uri = $referer_uri;

                $home_path = trim( parse_url( $url_converter->get_abs_home(), PHP_URL_PATH ), '/' );
                $home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

                // Trim path info from the end and the leading home path from the front.
                $req_uri = ltrim($req_uri, '/');
                $req_uri = preg_replace( $home_path_regex, '', $req_uri );
                $req_uri = trim($url_converter->get_abs_home(), '/') . '/' . ltrim( $req_uri, '/' );

                $referer = $req_uri;

            } elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
                // this one is an actual URL that the browser sets.
                $referer = wp_unslash( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );

            }

            //if the request did not come from the admin set propper variables for the request (being processed in ajax they got lost) and return true
            if( ( strpos( $referer, admin_url() ) === false ) ){
                TRP_Translation_Manager::set_vars_in_frontend_ajax_request( $referer );
                return true;
            }
        }

        return false;
    }

    /**
     * Function that sets the needed vars in the ajax request. Beeing ajax the globals got reset and also the REQUEST globals
     * @param $referer
     */
    static function set_vars_in_frontend_ajax_request( $referer ){

        /* for our own actions don't do nothing */
        if( isset( $_REQUEST['action'] ) && strpos($_REQUEST['action'], 'trp_') === 0 )
            return;

        /* if the request came from preview mode make sure to keep it */
        if( strpos( $referer, 'trp-edit-translation=preview' ) !== false && !isset( $_REQUEST['trp-edit-translation'] ) ) {
            $_REQUEST['trp-edit-translation'] = 'preview';
        }

        if( strpos( $referer, 'trp-edit-translation=preview' ) !== false && strpos( $referer, 'trp-view-as=' ) !== false && strpos( $referer, 'trp-view-as-nonce=' ) !== false ) {
            $parts = parse_url($referer);
            parse_str($parts['query'], $query);
            $_REQUEST['trp-view-as'] = $query['trp-view-as'];
            $_REQUEST['trp-view-as-nonce'] = $query['trp-view-as-nonce'];
        }

        global $TRP_LANGUAGE;
        $trp = TRP_Translate_Press::get_trp_instance();
        $url_converter = $trp->get_component( 'url_converter' );
        $TRP_LANGUAGE = $url_converter->get_lang_from_url_string($referer);
        if( empty( $TRP_LANGUAGE ) ) {
            $settings_obj = new TRP_Settings();
            $settings = $settings_obj->get_settings();
            $TRP_LANGUAGE = $settings["default-language"];
        }
    }


    /**
     * Function that replaces the translations with the ones in the database if they are differnt, wrapps the texts in the html and
     * builds a global for machine translation with the strings that are not translated
     * @param $translation
     * @param $text
     * @param $domain
     * @return string
     */
    public function process_gettext_strings( $translation, $text, $domain ){
	    // if we have nested gettexts strip previous ones, and consider only the outermost
    	$text = TRP_Translation_Manager::strip_gettext_tags( $text );
    	$translation = TRP_Translation_Manager::strip_gettext_tags( $translation );

        //try here to exclude some strings that do not require translation
    	$excluded_gettext_strings = array( '', ' ', '&hellip;', '&nbsp;', '&raquo;' );
    	if( in_array( trp_full_trim( $text ), $excluded_gettext_strings ) )
    	    return $translation;

        //set a global so we remember the last string we processed and if it is the same with the current one return a result immediately for performance reasons ( this could happen in loops )
        global $tp_last_gettext_processed;
        if( isset( $tp_last_gettext_processed[$text.'::'.$domain] ) )
            return $tp_last_gettext_processed[$text.'::'.$domain];

        global $TRP_LANGUAGE;

        if( ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'true' ) || $domain == 'translatepress-multilingual' )
            return $translation;

        /* for our own actions don't do nothing */
        if( isset( $_REQUEST['action'] ) && strpos($_REQUEST['action'], 'trp_') === 0 )
            return $translation;

        //use a global for is_ajax_on_frontend() so we don't execute it multiple times
        global $tp_gettext_is_ajax_on_frontend;
        if( !isset($tp_gettext_is_ajax_on_frontend) )
            $tp_gettext_is_ajax_on_frontend = $this::is_ajax_on_frontend();

        if ( !defined( 'DOING_AJAX' ) || $tp_gettext_is_ajax_on_frontend ) {
	        $db_id       = '';
	        $skip_gettext_querying = apply_filters( 'trp_skip_gettext_querying', false, $translation, $text, $domain );
	        if ( !$skip_gettext_querying ) {
		        global $trp_translated_gettext_texts, $trp_all_gettext_texts;

		        $found_in_db = false;

		        /* initiate trp query object */
		        if ( ! $this->trp_query ) {
			        $trp             = TRP_Translate_Press::get_trp_instance();
			        $this->trp_query = $trp->get_component( 'query' );
		        }

		        if ( ! isset( $trp_all_gettext_texts ) ) {
			        $trp_all_gettext_texts = array();
		        }

		        if ( ! empty( $trp_translated_gettext_texts ) ) {
			        if ( isset( $trp_translated_gettext_texts[ $domain . '::' . $text ] ) ) {
				        $trp_translated_gettext_text = $trp_translated_gettext_texts[ $domain . '::' . $text ];

				        if ( ! empty( $trp_translated_gettext_text['translated'] ) && $translation != $trp_translated_gettext_text['translated'] ) {
					        $translation = str_replace( trim( $text ), $trp_translated_gettext_text['translated'], $text );
				        }
				        $db_id       = $trp_translated_gettext_text['id'];
				        $found_in_db = true;
				        // update the db if a translation appeared in the po file later
				        if ( empty( $trp_translated_gettext_text['translated'] ) && $translation != $text ) {
					        $this->trp_query->update_gettext_strings( array(
						        array(
							        'id'         => $db_id,
							        'original'   => $text,
							        'translated' => $translation,
							        'domain'     => $domain,
                                    'status' => $this->trp_query->get_constant_human_reviewed()
						        )
					        ), get_locale() );
				        }
			        }
		        }

		        if ( ! $found_in_db ) {
			        if ( ! in_array( array(
				        'original'   => $text,
				        'translated' => $translation,
				        'domain'     => $domain
			        ), $trp_all_gettext_texts )
			        ) {
				        $trp_all_gettext_texts[] = array(
					        'original'   => $text,
					        'translated' => $translation,
					        'domain'     => $domain
				        );
				        $db_id                   = $this->trp_query->insert_gettext_strings( array(
					        array(
						        'original'   => $text,
						        'translated' => $translation,
						        'domain'     => $domain
					        )
				        ), get_locale() );
				        /* insert it in the global of translated because now it is in the database */
				        $trp_translated_gettext_texts[ $domain . '::' . $text ] = array(
					        'id'         => $db_id,
					        'original'   => $text,
					        'translated' => ( $translation != $text ) ? $translation : '',
					        'domain'     => $domain
				        );
			        }
		        }

		        if ( ! $this->machine_translator ) {
			        $trp                      = TRP_Translate_Press::get_trp_instance();
			        $this->machine_translator = $trp->get_component( 'machine_translator' );
		        }
		        if ( $this->machine_translator->is_available() ) {
			        global $trp_gettext_strings_for_machine_translation;
			        if ( $text == $translation ) {
				        foreach ( $trp_translated_gettext_texts as $trp_translated_gettext_text ) {
					        if ( $trp_translated_gettext_text['id'] == $db_id ) {
						        if ( $trp_translated_gettext_text['translated'] == '' ) {
							        $trp_gettext_strings_for_machine_translation[] = array(
								        'id'         => $db_id,
								        'original'   => $text,
								        'translated' => '',
								        'domain'     => $domain,
								        'status'     => $this->trp_query->get_constant_machine_translated()
							        );
						        }
						        break;
					        }
				        }
			        }
		        }
	        }

            $blacklist_functions = apply_filters( 'trp_gettext_blacklist_functions', array(
            	'wp_enqueue_script',
	            'wp_enqueue_scripts',
	            'wp_editor',
	            'wp_enqueue_media',
	            'wp_register_script',
	            'wp_print_scripts',
	            'wp_localize_script',
	            'wp_print_media_templates',
	            'get_bloginfo',
	            'wp_get_document_title',
	            'wp_title',
	            'wp_trim_words',
	            'sanitize_title',
	            'sanitize_title_with_dashes',
	            'esc_url',
	            'wc_get_permalink_structure' // make sure we don't touch the woocommerce permalink rewrite slugs that are translated
            ), $text, $translation, $domain );

            if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
                $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
            }
            else{
                $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            }
            if( !empty( $callstack_functions ) ){
                foreach( $callstack_functions as $callstack_function ){
	                if ( in_array( $callstack_function['function'], $blacklist_functions ) ){
                        $tp_last_gettext_processed = array( $text.'::'.$domain => $translation );
		                return $translation;
	                }

	                /* make sure we don't touch the woocommerce process_payment function in WC_Gateway_Stripe. It does a wp_remote_post() call to stripe with localized parameters */
                    if( $callstack_function['function'] == 'process_payment' && $callstack_function['class'] == 'WC_Gateway_Stripe' ){
                        $tp_last_gettext_processed = array( $text.'::'.$domain => $translation );
                        return $translation;
                    }

                }
            }
            unset($callstack_functions);//maybe free up some memory

	        if ( did_action( 'init' ) ) {
		        if ( ( ! empty( $TRP_LANGUAGE ) && $this->settings["default-language"] != $TRP_LANGUAGE ) || ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview' ) ) {
			        //add special start and end tags so that it does not influence html in any way. we will replace them with < and > at the start of the translate function
			        $translation = apply_filters( 'trp_process_gettext_tags', '#!trpst#trp-gettext data-trpgettextoriginal=' . $db_id . '#!trpen#' . $translation . '#!trpst#/trp-gettext#!trpen#', $translation, $skip_gettext_querying, $text, $domain );
		        }
	        }
        }
        $tp_last_gettext_processed = array( $text.'::'.$domain => $translation );
        return $translation;
    }

    /**
     * caller for woocommerce domain texts
     * @param $translation
     * @param $text
     * @param $domain
     * @return string
     */
    function woocommerce_process_gettext_strings( $translation, $text, $domain ){
        if( $domain === 'woocommerce' ){
            $translation = $this->process_gettext_strings( $translation, $text, $domain );
        }
        return $translation;
    }

    /**
     * Function that filters gettext strings with context _x
     * @param $translation
     * @param $text
     * @param $context
     * @param $domain
     * @return string
     */
    function process_gettext_strings_with_context( $translation, $text, $context, $domain ){
        $translation = $this->process_gettext_strings( $translation, $text, $domain );
        return $translation;
    }

    /**
     * caller for woocommerce domain texts with context
     */
    function woocommerce_process_gettext_strings_with_context( $translation, $text, $context, $domain ){
        if( $domain === 'woocommerce' ) {
            $translation = $this->process_gettext_strings_with_context( $translation, $text, $context, $domain );
        }
        return $translation;
    }

    /**
     * function that filters the _n translations
     * @param $translation
     * @param $single
     * @param $plural
     * @param $number
     * @param $domain
     * @return string
     */
    function process_ngettext_strings($translation, $single, $plural, $number, $domain){
        if( $number == 1 )
            $translation = $this->process_gettext_strings( $translation, $single, $domain );
        else
            $translation = $this->process_gettext_strings( $translation, $plural, $domain );

        return $translation;
    }

    /**
     * caller for woocommerce domain numeric texts
     */
    function woocommerce_process_ngettext_strings($translation, $single, $plural, $number, $domain){
        if( $domain === 'woocommerce' ) {
            $translation = $this->process_ngettext_strings($translation, $single, $plural, $number, $domain);
        }

        return $translation;
    }

    /**
     * function that filters the _nx translations
     * @param $translation
     * @param $single
     * @param $plural
     * @param $number
     * @param $context
     * @param $domain
     * @return string
     */
    function process_ngettext_strings_with_context( $translation, $single, $plural, $number, $context, $domain ){
        $translation = $this->process_ngettext_strings( $translation, $single, $plural, $number, $domain );
        return $translation;
    }

    /**
     * caller for woocommerce domain numeric texts with context
     */
    function woocommerce_process_ngettext_strings_with_context( $translation, $single, $plural, $number, $context, $domain ){
        if( $domain === 'woocommerce' ) {
            $translation = $this->process_ngettext_strings_with_context( $translation, $single, $plural, $number, $context, $domain );
        }
        return $translation;
    }

    /**
     * function that machine translates gettext strings
     */
    function machine_translate_gettext(){
        /* @todo  set the original language to detect and also decide if we automatically translate for the default language */
        global $TRP_LANGUAGE, $trp_gettext_strings_for_machine_translation;
        if( !empty( $trp_gettext_strings_for_machine_translation ) ){
            if ( ! $this->machine_translator ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->machine_translator = $trp->get_component('machine_translator');
            }

            // machine translate new strings
            if ( $this->machine_translator->is_available() ) {
                $new_strings = array();
                foreach( $trp_gettext_strings_for_machine_translation as $trp_gettext_string_for_machine_translation ){
                    /* google has a problem translating this characters...for some reasons it puts spaces after them so we need to 'encode' them and decode them back. hopefully it won't break anything important */
                    $new_strings[] = str_replace( array( '%', '$', '#' ), array( '1TP1', '1TP2', '1TP3' ), $trp_gettext_string_for_machine_translation['original'] );
                }

                $machine_strings = $this->machine_translator->translate_array( $new_strings, $TRP_LANGUAGE );

                if( !empty( $machine_strings ) ){
                    foreach( $machine_strings as $key => $machine_string ){
                        $trp_gettext_strings_for_machine_translation[$key]['translated'] = str_ireplace( array( '1TP1', '1TP2', '1TP3' ), array( '%', '$', '#' ), $machine_string );
                    }
                }

                if (!$this->trp_query) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component('query');
                }

                $this->trp_query->update_gettext_strings( $trp_gettext_strings_for_machine_translation, $TRP_LANGUAGE );
            }
        }
    }



    /**
     * make sure we remove the trp-gettext wrap from the format the date_i18n receives
     * ideally if in the gettext filter we would know 100% that a string is a valid date format then we would not wrap it but it seems that it is not easy to determine that ( explore further in the future $d = DateTime::createFromFormat('Y', date('y a') method); )
     */
    function handle_date_i18n_function_for_gettext( $j, $dateformatstring, $unixtimestamp, $gmt ){

        /* remove trp-gettext wrap */
        $dateformatstring = preg_replace( '/#!trpst#trp-gettext (.*?)#!trpen#/', '', $dateformatstring );
        $dateformatstring = preg_replace( '/#!trpst#(.?)\/trp-gettext#!trpen#/', '', $dateformatstring );


        global $wp_locale;
        $i = $unixtimestamp;

        if ( false === $i ) {
            $i = current_time( 'timestamp', $gmt );
        }

        if ( ( !empty( $wp_locale->month ) ) && ( !empty( $wp_locale->weekday ) ) ) {
            $datemonth = $wp_locale->get_month( date( 'm', $i ) );
            $datemonth_abbrev = $wp_locale->get_month_abbrev( $datemonth );
            $dateweekday = $wp_locale->get_weekday( date( 'w', $i ) );
            $dateweekday_abbrev = $wp_locale->get_weekday_abbrev( $dateweekday );
            $datemeridiem = $wp_locale->get_meridiem( date( 'a', $i ) );
            $datemeridiem_capital = $wp_locale->get_meridiem( date( 'A', $i ) );
            $dateformatstring = ' '.$dateformatstring;
            $dateformatstring = preg_replace( "/([^\\\])D/", "\\1" . backslashit( $dateweekday_abbrev ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])F/", "\\1" . backslashit( $datemonth ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])l/", "\\1" . backslashit( $dateweekday ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])M/", "\\1" . backslashit( $datemonth_abbrev ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])a/", "\\1" . backslashit( $datemeridiem ), $dateformatstring );
            $dateformatstring = preg_replace( "/([^\\\])A/", "\\1" . backslashit( $datemeridiem_capital ), $dateformatstring );

            $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
        }
        $timezone_formats = array( 'P', 'I', 'O', 'T', 'Z', 'e' );
        $timezone_formats_re = implode( '|', $timezone_formats );
        if ( preg_match( "/$timezone_formats_re/", $dateformatstring ) ) {
            $timezone_string = get_option( 'timezone_string' );
            if ( $timezone_string ) {
                $timezone_object = timezone_open( $timezone_string );
                $date_object = date_create( null, $timezone_object );
                foreach ( $timezone_formats as $timezone_format ) {
                    if ( false !== strpos( $dateformatstring, $timezone_format ) ) {
                        $formatted = date_format( $date_object, $timezone_format );
                        $dateformatstring = ' '.$dateformatstring;
                        $dateformatstring = preg_replace( "/([^\\\])$timezone_format/", "\\1" . backslashit( $formatted ), $dateformatstring );
                        $dateformatstring = substr( $dateformatstring, 1, strlen( $dateformatstring ) -1 );
                    }
                }
            }
        }
        $j = @date( $dateformatstring, $i );

        return $j;

    }

	/**
	 * Strip gettext tags from urls that were parsed by esc_url
	 *
	 * Esc_url() replaces spaces with %20. This is why it is not automatically stripped like the rest of the urls.
	 *
	 * @since 1.3.8
	 *
	 * @param $good_protocol_url
	 * @param $original_url
	 * @param $_context
	 *
	 * @return mixed
	 */
	function trp_strip_gettext_tags_from_esc_url( $good_protocol_url, $original_url, $_context ){
		if( strpos( $good_protocol_url, '%20data-trpgettextoriginal=' ) !== false ) {
			// first replace %20 with space  so that gettext tags can be stripped.
			$good_protocol_url = str_replace( '%20data-trpgettextoriginal=', ' data-trpgettextoriginal=', $good_protocol_url );
			$good_protocol_url = TRP_Translation_Manager::strip_gettext_tags( $good_protocol_url );
		}

		return $good_protocol_url;
	}

	/**
	 * Filter sanitize_title() to use our own remove_accents() function so it's based on the default language, not current locale.
	 *
	 * Also removes trp gettext tags before running the filter because it strip # and ! and / making it impossible to strip the #trpst later
	 *
	 * @since 1.3.1
	 *
	 * @param string $title
	 * @param string $raw_title
	 * @param string $context
	 * @return string
	 */
	public function trp_sanitize_title(  $title, $raw_title, $context ){
		// remove trp_tags before sanitization, because otherwise some characters (#,!,/, spaces ) are stripped later, and it becomes impossible to strip trp-gettext later
		$raw_title = TRP_Translation_Manager::strip_gettext_tags( $raw_title );

		if ( 'save' == $context )
			$title = trp_remove_accents( $raw_title );

		remove_filter( 'sanitize_title', array( $this, 'trp_sanitize_title') , 1 );
		$title = apply_filters( 'sanitize_title', $title, $raw_title, $context );
		add_filter( 'sanitize_title', array( $this, 'trp_sanitize_title'), 1, 3 );

		return $title;
	}


	/**
	 * function that strips the gettext tags from a string
	 * @param $string
	 * @return mixed
	 */
	static function strip_gettext_tags( $string ){
		if( strpos( $string, ' data-trpgettextoriginal=' ) !== false ) {
			$string = preg_replace('/ data-trpgettextoriginal=\d+#!trpen#/', '', $string);
			$string = str_replace('#!trpst#trp-gettext', '', $string);
			$string = str_replace('#!trpst#/trp-gettext', '', $string);
			$string = str_replace('#!trpst#\/trp-gettext', '', $string);
			$string = str_replace('#!trpen#', '', $string);
		}

		return $string;
	}

    /**
     * Add the current language as a class to the body
     * @param $classes
     * @return array
     */
    public function add_language_to_body_class( $classes ){
        global $TRP_LANGUAGE;
        if( !empty( $TRP_LANGUAGE ) ){
            $classes[] = 'translatepress-'.$TRP_LANGUAGE;
        }
        return $classes;
    }

    /**
     * Function that switches the view of the user to other roles
     */
    public function trp_view_as_user(){
        if( !is_admin() || $this::is_ajax_on_frontend() ) {
            if (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] === 'preview' && isset($_REQUEST['trp-view-as']) && isset($_REQUEST['trp-view-as-nonce'])) {

                if( apply_filters( 'trp_allow_translator_role_to_view_page_as_other_roles', true ) ){
                    $current_user_can_change_roles = current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) || current_user_can( 'manage_options' );
                }
                else{
                    $current_user_can_change_roles = current_user_can( 'manage_options' );
                }

                if ( $current_user_can_change_roles ) {
                    if ( ! wp_verify_nonce( $_REQUEST['trp-view-as-nonce'], 'trp_view_as'. sanitize_text_field( $_REQUEST['trp-view-as'] ) . get_current_user_id() ) ) {
                        wp_die( esc_html__( 'Security check', 'translatepress-multilingual' ) );
                    } else {
                        global $current_user;
                        $view_as = sanitize_text_field( $_REQUEST['trp-view-as'] );
                        if( $view_as === 'current_user' ){
                            return;
                        }
                        elseif ( $view_as === 'logged_out' ){
                            $current_user = new WP_User(0, 'trp_logged_out');
                        }
                        else{
                            $current_user = apply_filters( 'trp_temporary_change_current_user_role', $current_user, $view_as );
                        }
                    }
                }
            }
        }
    }

	/**
	 * Return true if the string contains characters which are not allowed in the query
	 *
	 * Only valid for utf8.
	 * Function is an extract of strip_invalid_text() function from wp-includes/wp-db.php
	 *
	 * @param $string
	 *
	 * @return bool
	 */
	public function has_bad_characters( $string ) {
		$regex = '/
					(
						(?: [\x00-\x7F]                  # single-byte sequences   0xxxxxxx
						|   [\xC2-\xDF][\x80-\xBF]       # double-byte sequences   110xxxxx 10xxxxxx
						|   \xE0[\xA0-\xBF][\x80-\xBF]   # triple-byte sequences   1110xxxx 10xxxxxx * 2
						|   [\xE1-\xEC][\x80-\xBF]{2}
						|   \xED[\x80-\x9F][\x80-\xBF]
						|   [\xEE-\xEF][\x80-\xBF]{2}';

		$regex .= '
						|    \xF0[\x90-\xBF][\x80-\xBF]{2} # four-byte sequences   11110xxx 10xxxxxx * 3
						|    [\xF1-\xF3][\x80-\xBF]{3}
						|    \xF4[\x80-\x8F][\x80-\xBF]{2}
					';


		$regex .= '){1,40}                          # ...one or more times
					)
					| .                                  # anything else
					/x';
		$stripped_string = preg_replace( $regex, '$1', $string );

		if ( $stripped_string === $string ){
			return false;
		}else {
			return true;
		}
	}

	/**
	 * Records a series of strings which may have encoding issues
	 *
	 * Does not alter dictionary.
	 *
	 * @param $dictionary
	 * @param $prepared_query
	 * @param $strings_array
	 *
	 * @return mixed
	 */
	public function display_possible_db_errors( $dictionary, $prepared_query, $strings_array ){
		global $trp_editor_notices;
		if ( trp_is_translation_editor( 'preview' ) && is_array( $dictionary ) && count( $dictionary ) === 0 ){
			if ( $this->has_bad_characters( $prepared_query ) ) {
				$html = "<div class='trp-notice trp-notice-warning'><p class='trp-bad-encoded-strings'>" . __(  '<strong>Warning:</strong> Some strings have possibly incorrectly encoded characters. This may result in breaking the queries, rendering the page untranslated in live mode. Consider revising the following strings or their method of outputting.', 'translatepress-multilingual' ) . "</p>";
				$html .= "<ul class='trp-bad-encoded-strings-list'>";
				foreach( $strings_array as $string ){
					if ( $this->has_bad_characters( $string ) ){
						$html .= "<li>" . $string . "</li>";
					}
				}
				$html .= "</ul></div>";

				$trp_editor_notices .= $html;
			}
		}

		// no modifications to the dictionary
		return $dictionary;
	}

}
