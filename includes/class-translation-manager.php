<?php

/**
 * Class TRP_Translation_Manager
 *
 * Handles Front-end Translation Editor, including Ajax requests.
 */
class TRP_Translation_Manager{
    protected $settings;
    protected $translation_render;
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
                    '<h1>' . __( 'Cheatin&#8217; uh?' ) . '</h1>' .
                    '<p>' . __( 'Sorry, you are not allowed to access this page.' ) . '</p>',
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

    /**
     * Enqueue scripts and styles for translation Editor parent window.
     */
    public function enqueue_scripts_and_styles(){
        wp_enqueue_style( 'trp-select2-lib-css', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/css/select2.min.css', array(), TRP_PLUGIN_VERSION );
        wp_enqueue_script( 'trp-select2-lib-js', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/js/select2.min.js', array( 'jquery' ), TRP_PLUGIN_VERSION );

        wp_enqueue_script( 'trp-translation-manager-script',  TRP_PLUGIN_URL . 'assets/js/trp-editor-script.js', array(), TRP_PLUGIN_VERSION );
        wp_enqueue_style( 'trp-translation-manager-style',  TRP_PLUGIN_URL . 'assets/css/trp-editor-style.css', array('buttons'), TRP_PLUGIN_VERSION );

        wp_enqueue_script( 'trp-translation-overlay',  TRP_PLUGIN_URL . 'assets/js/trp-editor-overlay.js', array(), TRP_PLUGIN_VERSION );

        $scripts_to_print = apply_filters( 'trp-scripts-for-editor', array( 'jquery', 'jquery-ui-core', 'jquery-effects-core', 'jquery-ui-resizable', 'trp-translation-manager-script', 'trp-select2-lib-js', 'trp-translation-overlay' ) );
        $styles_to_print = apply_filters( 'trp-styles-for-editor', array( 'trp-translation-manager-style', 'trp-select2-lib-css', 'dashicons' /*'wp-admin', 'common', 'site-icon', 'buttons'*/ ) );
        wp_print_scripts( $scripts_to_print );
        wp_print_styles( $styles_to_print );
    }

    /**
     * Enqueue scripts and styles for translation Editor preview window.
     */
    public function enqueue_preview_scripts_and_styles(){
        if ( $this->conditions_met( 'preview' ) ) {
            wp_enqueue_script( 'trp-translation-manager-preview-script',  TRP_PLUGIN_URL . 'assets/js/trp-iframe-preview-script.js', array('jquery'), TRP_PLUGIN_VERSION );
            wp_enqueue_style('trp-preview-iframe-style',  TRP_PLUGIN_URL . 'assets/css/trp-preview-iframe-style.css', array(), TRP_PLUGIN_VERSION );
        }
    }

    /**
     * Echo page slug as meta tag in preview window.
     *
     * Hooked to wp_head
     */
    public function add_slug_as_meta_tag() {
        global $post;
        if ( isset( $post->ID ) && !empty( $post->ID ) && isset( $post->post_name ) && !empty( $post->post_name ) && $this->conditions_met( 'preview' ) && !is_home() && !is_front_page() && !is_archive() && !is_search() ) {
            echo '<meta name="trp-slug" content="' . $post->post_name. '" post-id="' . $post->ID . '"/>' . "\n";
        }

    }

    /**
     * Return array of original strings given their db ids.
     *
     * @param array $strings            Strings object to extract original
     * @param array $original_array     Original strings array to append to.
     * @param array $id_array           Id array to extract.
     * @return array                    Original strings array + Extracted strings from ids.
     */
    protected function extract_original_strings( $strings, $original_array, $id_array ){
        if ( count( $strings ) > 0 ) {
            foreach ($id_array as $id) {
                $original_array[] = $strings[$id]->original;
            }
        }
        return array_values( $original_array );
    }

    /**
     * Returns translations based on original strings and ids.
     *
     * Hooked to wp_ajax_trp_get_translations
     *       and wp_ajax_nopriv_trp_get_translations.
     */
    public function get_translations() {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            if ( isset( $_POST['action'] ) && $_POST['action'] === 'trp_get_translations' && !empty( $_POST['strings'] ) && !empty( $_POST['language'] ) && in_array( $_POST['language'], $this->settings['translation-languages'] ) ) {
                $strings = json_decode(stripslashes($_POST['strings']));
                if ( is_array( $strings ) ) {
                    $id_array = array();
                    $original_array = array();
                    $dictionaries = array();
                    $slug_info = false;
                    foreach ( $strings as $key => $string ) {
                        if ( isset( $string->slug ) && $string->slug === true ){
                            $slug_info = array(
                                'post_id'   => (int)$string->slug_post_id,
                                'id'        => (int)$string->id,
                                'original'  => sanitize_text_field( $string->original ) );
                            continue;
                        }
                        if ( isset( $string->id ) && is_numeric( $string->id ) ) {
                            $id_array[$key] = (int)$string->id;
                        } else if ( isset( $string->original ) ) {
                            $original_array[$key] = sanitize_text_field( $string->original );
                        }
                    }

                    $current_language = sanitize_text_field( $_POST['language'] );

                    $trp = TRP_Translate_Press::get_trp_instance();
                    if ( ! $this->trp_query ) {
                        $this->trp_query = $trp->get_component( 'query' );
                    }
                    if ( ! $this->slug_manager ) {
                        $this->slug_manager = $trp->get_component('slug_manager');
                    }
                    if ( ! $this->translation_render ) {
                        $this->translation_render = $trp->get_component('translation_render');
                    }

                    // necessary in order to obtain all the original strings
                    if ( $this->settings['default-language'] != $current_language ) {
                        if ( current_user_can ( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ) {
                            $this->translation_render->process_strings($original_array, $current_language);
                        }
                        $dictionaries[$current_language] = $this->trp_query->get_string_rows( $id_array, $original_array, $current_language );
                        if ( $slug_info !== false ) {
                            $dictionaries[$current_language][$slug_info['id']] = array(
                                'id'         => $slug_info['id'],
                                'original'   => $slug_info['original'],
                                'translated' => apply_filters( 'trp_translate_slug', $slug_info['original'], $slug_info['post_id'], $current_language ),
                            );
                        }

                    }else{
                        $dictionaries[$current_language] = array();
                    }

                    if ( isset( $_POST['all_languages'] ) && $_POST['all_languages'] === 'true' ) {
                        foreach ($this->settings['translation-languages'] as $language) {
                            if ($language == $this->settings['default-language']) {
                                $dictionaries[$language]['default-language'] = true;
                                continue;
                            }

                            if ($language == $current_language) {
                                continue;
                            }
                            if (empty($original_strings)) {
                                $original_strings = $this->extract_original_strings($dictionaries[$current_language], $original_array, $id_array);
                            }
                            if (current_user_can(apply_filters( 'trp_translating_capability', 'manage_options' ))) {
                                $this->translation_render->process_strings($original_strings, $language);
                            }
                            $dictionaries[$language] = $this->trp_query->get_string_rows(array(), $original_strings, $language);
                            if ( $slug_info !== false ) {
                                $dictionaries[$language][0] = array(
                                    'id'         => 0,
                                    'original'   => $slug_info['original'],
                                    'translated' => apply_filters( 'trp_translate_slug', $slug_info['original'], $slug_info['post_id'], $language ),
                                );
                            }
                        }
                    }

                    echo trp_safe_json_encode( $dictionaries );
                }

            }
        }

        die();
    }

    public function gettext_get_translations(){
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            if (isset($_POST['action']) && $_POST['action'] === 'trp_gettext_get_translations' && !empty($_POST['gettext_string_ids']) && !empty($_POST['language']) && in_array($_POST['language'], $this->settings['translation-languages'])) {

                if (!empty($_POST['gettext_string_ids']))
                    $gettext_string_ids = json_decode(stripslashes($_POST['gettext_string_ids']));
                else
                    $gettext_string_ids = array();

                $current_language = sanitize_text_field( $_POST['language'] );
                $dictionaries = array();

                if ( is_array( $gettext_string_ids ) ) {

                    $trp = TRP_Translate_Press::get_trp_instance();
                    if ( ! $this->trp_query ) {
                        $this->trp_query = $trp->get_component( 'query' );
                    }

                    /* build the current language dictionary */
                    $dictionaries[$current_language] = $this->trp_query->get_gettext_string_rows_by_ids( $gettext_string_ids, $current_language );

                    /* build the other languages dictionaries */

                    $original_strings = array();
                    $original_string_details = array();
                    if( !empty( $dictionaries[$current_language] ) ){
                        foreach( $dictionaries[$current_language] as $current_language_string ){
                            $original_strings[] = $current_language_string['original'];
                            $original_string_details[] = array( 'original' => $current_language_string['original'], 'domain' => $current_language_string['domain'] );
                        }
                    }

                    foreach ($this->settings['translation-languages'] as $language) {
                        if ($language == $current_language) {
                            continue;
                        }

                        $lang_original_string_details = $original_string_details;
                        if( !empty( $original_strings ) && !empty( $lang_original_string_details ) ){
                            $dictionaries[$language] = $this->trp_query->get_gettext_string_rows_by_original( $original_strings, $language );
                            if( empty( $dictionaries[$language] ) )
                                $dictionaries[$language]  = array();

                            $search_strings_array = array();

                            foreach( $dictionaries[$language] as $lang_string ){
                                $search_strings_array[] = array( 'original' => $lang_string['original'], 'domain' => $lang_string['domain']  );
                            }

                            if( !empty( $search_strings_array ) ){
                                foreach( $search_strings_array as $search_key => $search_string ){
                                    if( in_array( $search_string, $lang_original_string_details ) ) {
                                        $remove_original_key = array_search($search_string, $lang_original_string_details );
                                        unset($lang_original_string_details[$remove_original_key]);
                                    }
                                    else{
                                        unset($dictionaries[$language][$search_key]);
                                    }
                                }
                            }

                            /* add here in the db the strings that are not there and after that add them in the dictionary */
                            switch_to_locale( $language );
                            if( !empty( $lang_original_string_details ) ){
                                foreach( $lang_original_string_details as $lang_original_string_detail ){

                                    $translations = get_translations_for_domain( $lang_original_string_detail['domain'] );
                                    $translated  = $translations->translate( $lang_original_string_detail['original'] );                                                                      

                                    $db_id = $this->trp_query->insert_gettext_strings( array( array('original' => $lang_original_string_detail['original'], 'translated' => $translated, 'domain' => $lang_original_string_detail['domain']) ), $language );
                                    $dictionaries[$language][] = array('id' => $db_id, 'original' => $lang_original_string_detail['original'], 'translated' => ( $translated != $lang_original_string_detail['original'] ) ? $translated : '', 'domain' => $lang_original_string_detail['domain']);
                                }
                            }
                            restore_current_locale();

                            $dictionaries[$language] = array_values($dictionaries[$language]);

                        }
                    }

                }

                /* html entity decode the strings so we display them properly in the textareas  */
                foreach( $dictionaries as $lang => $dictionary ){
                    foreach( $dictionary as $key => $string ){
                        $string = array_map('html_entity_decode', $string );
                        $dictionaries[$lang][$key] = $string;
                    }
                }

                die( trp_safe_json_encode( $dictionaries ) );

            }
        }
    }

    /**
     * Save translations from ajax post.
     *
     * Hooked to wp_ajax_trp_save_translations.
     */
    public function save_translations(){

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ) {
            if ( isset( $_POST['action'] ) && $_POST['action'] === 'trp_save_translations' && !empty( $_POST['strings'] ) ) {
                $strings = json_decode(stripslashes($_POST['strings']));
                $update_strings = array();
                foreach ( $strings as $language => $language_strings ) {
                    if ( in_array( $language, $this->settings['translation-languages'] ) && $language != $this->settings['default-language'] ) {
                        foreach( $language_strings as $string ) {
                            if ( isset( $string->id ) && is_numeric( $string->id ) ) {
                                $update_strings[ $language ] = array();
                                array_push($update_strings[ $language ], array(
                                    'id' => (int)$string->id,
                                    'original' => sanitize_text_field($string->original),
                                    'translated' => preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $string->translated ),
                                    'status' => (int)$string->status
                                ));

                            }
                        }
                    }
                }

                if ( ! $this->trp_query ) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component( 'query' );
                }

                foreach( $update_strings as $language => $update_string_array ) {
                    $this->trp_query->insert_strings( array(), $update_string_array, $language );
                }
            }
        }

        die();
    }

    public function gettext_save_translations(){
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ) {
            if (isset($_POST['action']) && $_POST['action'] === 'trp_gettext_save_translations' && !empty($_POST['gettext_strings'])) {
                $strings = json_decode(stripslashes($_POST['gettext_strings']));
                $update_strings = array();
                foreach ( $strings as $language => $language_strings ) {
                    if ( in_array( $language, $this->settings['translation-languages'] ) ) {
                        foreach( $language_strings as $string ) {
                            if ( isset( $string->id ) && is_numeric( $string->id ) ) {
                                $update_strings[ $language ] = array();
                                array_push($update_strings[ $language ], array(
                                    'id' => (int)$string->id,
                                    'original' => sanitize_text_field($string->original),
                                    'translated' => preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $string->translated ),
                                    'domain' => sanitize_text_field( $string->domain ),
                                    'status' => (int)$string->status
                                ));
                            }
                        }
                    }
                }

                if ( ! $this->trp_query ) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component( 'query' );
                }

                foreach( $update_strings as $language => $update_string_array ) {
                    $this->trp_query->update_gettext_strings( $update_string_array, $language );
                }
            }
        }
        die();
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
        if( !is_admin() || $this::is_ajax_on_frontend() ) {
            global $TRP_LANGUAGE;

            global $trp_translated_gettext_texts;
            if (!$this->trp_query) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->trp_query = $trp->get_component('query');
            }

            $strings = $this->trp_query->get_all_gettext_strings($TRP_LANGUAGE);
            if (!empty($strings))
                $trp_translated_gettext_texts = $strings;
        }
    }

    /**
     * function that applies the gettext filter on frontend on different hooks depending on what we need
     */
    public function apply_gettext_filter_on_frontend(){
        /* on ajax hooks from frontend that have the init hook ( we found WooCommerce has it ) apply it earlier */
        if( $this::is_ajax_on_frontend() ){
            add_action( 'wp_loaded', array( $this, 'apply_gettext_filter' ) );
        }
        elseif( class_exists( 'WooCommerce' ) ){
            add_action( 'wp_loaded', array( $this, 'apply_gettext_filter' ), 19 );
        }//otherwise start from the wp_head hook
        else{
            add_action( 'wp_head', array( $this, 'apply_gettext_filter' ), 100 );
        }
    }

    /* apply the gettext filter here */
    public function apply_gettext_filter(){
        if( !is_admin() || $this::is_ajax_on_frontend() ) {
            add_filter('gettext', array($this, 'process_gettext_strings'), 100, 3);
            add_filter('gettext_with_context', array($this, 'process_gettext_strings_with_context'), 100, 4);
            add_filter('ngettext', array($this, 'process_ngettext_strings'), 100, 5);
            add_filter('ngettext_with_context', array($this, 'process_ngettext_strings_with_context'), 100, 6);
        }
    }

    /**
     * Function that determines if an ajax request came from the frontend
     * @return bool
     */
    static function is_ajax_on_frontend(){
        //check here for wp ajax or woocommerce ajax
        if( ( defined('DOING_AJAX') && DOING_AJAX ) || ( defined('WC_DOING_AJAX') && WC_DOING_AJAX ) ){
            $referer = '';
            if ( ! empty( $_REQUEST['_wp_http_referer'] ) )
                $referer = wp_unslash( esc_url_raw( $_REQUEST['_wp_http_referer'] ) );
            elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) )
                $referer = wp_unslash( esc_url_raw( $_SERVER['HTTP_REFERER'] ) );

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
        $TRP_LANGUAGE = $url_converter ->get_lang_from_url_string($referer);
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
        global $TRP_LANGUAGE;

        /* don't do anything if we don't have extra languages on the site */
        if( count( $this->settings['publish-languages'] ) < 1 )
            return $translation;

        if( ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'true' ) || $domain == 'translatepress-multilingual' )
            return $translation;

        /* for our own actions don't do nothing */
        if( isset( $_REQUEST['action'] ) && strpos($_REQUEST['action'], 'trp_') === 0 )
            return $translation;


        if ( !defined( 'DOING_AJAX' ) || $this::is_ajax_on_frontend() ) {

            global $trp_translated_gettext_texts, $trp_all_gettext_texts;
            $found_in_db = false;
            $db_id = '';

            /* initiate trp query object */
            if (!$this->trp_query) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->trp_query = $trp->get_component('query');
            }

            if( !isset( $trp_all_gettext_texts ) )
                $trp_all_gettext_texts = array();

            if( !empty( $trp_translated_gettext_texts ) ){
                foreach( $trp_translated_gettext_texts as $trp_translated_gettext_text ){
                    if( $text == $trp_translated_gettext_text['original'] && $domain == $trp_translated_gettext_text['domain'] ){
                        if( !empty( $trp_translated_gettext_text['translated'] ) && $translation != $trp_translated_gettext_text['translated'] ) {
                            $translation = $trp_translated_gettext_text['translated'];
                        }
                        $db_id = $trp_translated_gettext_text['id'];
                        $found_in_db = true;
                        /* update the db if a translation appeared in the po file later */
                        if( empty( $trp_translated_gettext_text['translated'] ) && $translation != $text ) {
                            $this->trp_query->update_gettext_strings( array( array( 'id' => $db_id, 'original' => $text, 'translated' => $translation, 'domain' => $domain), 'status' => TRP_Query::HUMAN_REVIEWED ), get_locale() );
                        }

                        break;
                    }
                }
            }

            if( !$found_in_db ){
                if( !in_array( array('original' => $text, 'translated' => $translation, 'domain' => $domain), $trp_all_gettext_texts ) ) {
                    $trp_all_gettext_texts[] = array('original' => $text, 'translated' => $translation, 'domain' => $domain);
                    $db_id = $this->trp_query->insert_gettext_strings( array( array('original' => $text, 'translated' => $translation, 'domain' => $domain) ), get_locale() );
                    /* insert it in the global of translated because now it is in the database */
                    $trp_translated_gettext_texts[] = array( 'id' => $db_id, 'original' => $text, 'translated' => ( $translation != $text ) ? $translation : '', 'domain' => $domain );
                }
            }

            if ( !$this->machine_translator ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->machine_translator = $trp->get_component('machine_translator');
            }
            if ( $this->machine_translator->is_available() ) {
                global $trp_gettext_strings_for_machine_translation;
                if ($text == $translation) {
                    foreach( $trp_translated_gettext_texts as $trp_translated_gettext_text ){
                        if( $trp_translated_gettext_text['id'] == $db_id ){
                            if( $trp_translated_gettext_text['translated'] == '' ){
                                $trp_gettext_strings_for_machine_translation[] = array( 'id' => $db_id, 'original' => $text, 'translated' => '', 'domain' => $domain, 'status' => TRP_Query::MACHINE_TRANSLATED );
                            }
                            break;
                        }
                    }
                }
            }

            $callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            if( !empty( $callstack_functions ) ){
                foreach( $callstack_functions as $callstack_function ){
                    if( $callstack_function['function'] == 'wp_enqueue_script' ||
                        $callstack_function['function'] == 'wp_enqueue_scripts' ||
                        $callstack_function['function'] == 'wp_editor' ||
                        $callstack_function['function'] == 'wp_enqueue_media' ||
                        $callstack_function['function'] == 'wp_register_script' ||
                        $callstack_function['function'] == 'wp_print_scripts'||
                        $callstack_function['function'] == 'wp_localize_script'||
                        $callstack_function['function'] == 'wp_print_media_templates' ||
                        $callstack_function['function'] == 'get_bloginfo' ||
                        $callstack_function['function'] == 'wp_get_document_title' ||
                        $callstack_function['function'] == 'wp_title' ||
                        $callstack_function['function'] == 'wptexturize'
                    ) {
                        return $translation;
                    }
                    
                    /* make sure we don't touch the woocommerce permalink rewrite slugs that are translated */
                    if( $callstack_function['function'] == 'wc_get_permalink_structure' ){
                        return $translation;
                    }

                }
            }

            if( ( !empty($TRP_LANGUAGE) && $this->settings["default-language"] != $TRP_LANGUAGE ) || ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview' ) )
                $translation = '<trp-gettext data-trpgettextoriginal=\'' . $db_id . '\'>' . $translation . '</trp-gettext>';
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

    /* we need the esc_ functions for html and attributes not to escape our tags so we put them back */
    function handle_esc_functions_for_gettext( $safe_text, $text ){
        if( preg_match( '/(&lt;)trp-gettext (.*?)(&gt;)/', $safe_text, $matches ) ) {
            if( !empty($matches[2]) ) {
                $safe_text = preg_replace('/(&lt;)trp-gettext (.*?)(&gt;)/', "<trp-gettext " . htmlspecialchars_decode( $matches[2], ENT_QUOTES ) . ">", $safe_text);
                $safe_text = preg_replace('/(&lt;)(.?)\/trp-gettext(&gt;)/', '</trp-gettext>', $safe_text);
            }
        }

        return $safe_text;
    }

    /* let the trp-gettext wrap and data-trpgettextoriginal pass through kses filters */
    function handle_kses_functions_for_gettext( $tags ){
        if( is_array($tags) ){
            $tags['trp-gettext'] = array( 'data-trpgettextoriginal' => true );
        }
        return $tags;
    }

    /**
     * make sure we remove the trp-gettext wrap from the format the date_i18n receives
     * ideally if in the gettext filter we would know 100% that a string is a valid date format then we would not wrap it but it seems that it is not easy to determine that ( explore further in the future $d = DateTime::createFromFormat('Y', date('y a') method); )
     */
    function handle_date_i18n_function_for_gettext( $j, $dateformatstring, $unixtimestamp, $gmt ){

        /* remove trp-gettext wrap */
        $dateformatstring = preg_replace( '/(<|&lt;)trp-gettext (.*?)(>|&gt;)/', '', $dateformatstring );
        $dateformatstring = preg_replace( '/(<|&lt;)(.?)\/trp-gettext(>|&gt;)/', '', $dateformatstring );


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
                        wp_die( __( 'Security check', 'translatepress-multilingual' ) );
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
    
}