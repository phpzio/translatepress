<?php

class TRP_Translation_Manager{
    protected $settings;
    protected $translation_render;
    protected $trp_query;
    protected $machine_translator;
    protected $slug_manager;
    protected $url_converter;


    public function __construct( $settings ){
        $this->settings = $settings;
    }

    // mode == true, mode == preview
    protected function conditions_met( $mode = 'true' ){
        if ( isset( $_GET['trp-edit-translation'] ) && esc_attr( $_GET['trp-edit-translation'] ) == $mode ) {
            if ( current_user_can( 'manage_options' ) && ! is_admin() ) {
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

    public function translation_editor( $page_template ){
        if ( ! $this->conditions_met() ){
            return $page_template;
        }

        return TRP_PLUGIN_DIR . 'partials/translation-manager.php' ;
    }

    public function enqueue_scripts_and_styles(){
        wp_enqueue_style( 'trp-select2-lib-css', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/css/select2.min.css', array(), TRP_PLUGIN_VERSION );
        wp_enqueue_script( 'trp-select2-lib-js', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/js/select2.min.js', array( 'jquery' ), TRP_PLUGIN_VERSION );

        wp_enqueue_script( 'trp-translation-manager-script',  TRP_PLUGIN_URL . 'assets/js/trp-editor-script.js', array(), TRP_PLUGIN_VERSION );
        wp_enqueue_style( 'trp-translation-manager-style',  TRP_PLUGIN_URL . 'assets/css/trp-editor-style.css', array(), TRP_PLUGIN_VERSION );

        $scripts_to_print = apply_filters( 'trp-scripts-for-editor', array( 'jquery', 'jquery-ui-core', 'jquery-effects-core', 'jquery-ui-resizable', 'trp-translation-manager-script', 'trp-select2-lib-js' ) );
        $styles_to_print = apply_filters( 'trp-styles-for-editor', array( 'trp-translation-manager-style', 'trp-select2-lib-css' /*'wp-admin', 'dashicons', 'common', 'site-icon', 'buttons'*/ ) );
        wp_print_scripts( $scripts_to_print );
        wp_print_styles( $styles_to_print );
    }

    public function enqueue_preview_scripts_and_styles(){
        if ( $this->conditions_met( 'preview' ) ) {
            wp_enqueue_script( 'trp-translation-manager-preview-script',  TRP_PLUGIN_URL . 'assets/js/trp-iframe-preview-script.js', array('jquery'), TRP_PLUGIN_VERSION );
            wp_enqueue_style('trp-preview-iframe-style',  TRP_PLUGIN_URL . 'assets/css/trp-preview-iframe-style.css', array(), TRP_PLUGIN_VERSION );
        }
    }

    public function add_slug_as_meta_tag() {
        global $post;
        if ( isset( $post->ID ) && !empty( $post->ID ) && isset( $post->post_name ) && !empty( $post->post_name ) && $this->conditions_met( 'preview' ) && !is_home() && !is_front_page() && !is_archive() && !is_search() ) {
            echo '<meta name="trp-slug" content="' . $post->post_name. '" post-id="' . $post->ID . '"/>' . "\n";
        }

    }

    protected function extract_original_strings( $strings, $original_array, $id_array ){
        if ( count( $strings ) > 0 ) {
            foreach ($id_array as $id) {
                $original_array[] = $strings[$id]->original;
            }
        }
        return array_values( $original_array );
    }

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
                        $this->trp_query = $trp->get_component( 'query' );;
                    }
                    if ( ! $this->slug_manager ) {
                        $this->slug_manager = $trp->get_component('slug_manager');
                    }
                    if ( ! $this->translation_render ) {
                        $this->translation_render = $trp->get_component('translation_render');
                    }

                    // necessary in order to obtain all the original strings
                    if ( $this->settings['default-language'] != $current_language ) {
                        if ( current_user_can ( 'manage_options' ) ) {
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
                            if (current_user_can('manage_options')) {
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

                    echo json_encode( $dictionaries );
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
                        $this->trp_query = $trp->get_component( 'query' );;
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

                                    $translated = translate( $lang_original_string_detail['original'], $lang_original_string_detail['domain'] );

                                    $db_id = $this->trp_query->insert_gettext_strings( array( array('original' => $lang_original_string_detail['original'], 'translated' => $translated, 'domain' => $lang_original_string_detail['domain']) ), $language );
                                    $dictionaries[$language][] = array('id' => $db_id, 'original' => $lang_original_string_detail['original'], 'translated' => $translated, 'domain' => $lang_original_string_detail['domain']);
                                }
                            }
                            restore_current_locale();

                            $dictionaries[$language] = array_values($dictionaries[$language]);

                        }
                    }

                }                
                die( json_encode( $dictionaries ) );

            }
        }
    }

    public function save_translations(){

        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( 'manage_options' ) ) {
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
                                    'translated' => sanitize_text_field($string->translated),
                                    'status' => (int)$string->status
                                ));

                            }
                        }
                    }
                }

                if ( ! $this->trp_query ) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component( 'query' );;
                }

                foreach( $update_strings as $language => $update_string_array ) {
                    $this->trp_query->insert_strings( array(), $update_string_array, $language );
                }
            }
        }

        die();
    }

    public function gettext_save_translations(){
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( 'manage_options' ) ) {
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
                                    'status' => (int)$string->status
                                ));
                            }
                        }
                    }
                }

                if ( ! $this->trp_query ) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component( 'query' );;
                }

                foreach( $update_strings as $language => $update_string_array ) {
                    $this->trp_query->update_gettext_strings( $update_string_array, $language );
                }
            }
        }
        die();
    }

    public function add_shortcut_to_translation_editor( $wp_admin_bar ) {

        if( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if( is_admin () ) {
            $url = add_query_arg( 'trp-edit-translation', 'true', home_url() );

            $title = __( 'Translate Site', TRP_PLUGIN_SLUG );
            $url_target = '_blank';
        } else {
            global $post;

            if( is_object( $post ) && ! is_archive() && ! is_home() && !is_front_page() && ! is_search() ) {
                $url = get_permalink( $post );
            } else {
                if( ! $this->url_converter ) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->url_converter = $trp->get_component( 'url_converter' );
                }

                $url = $this->url_converter->cur_page_url();
            }

            $url = add_query_arg( 'trp-edit-translation', 'true', $url );

            $title = __( 'Translate Page', TRP_PLUGIN_SLUG );
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
                'title'     => __( 'Settings', TRP_PLUGIN_SLUG ),
                'href'      => admin_url( 'options-general.php?page=translate-press' ),
                'parent'    => 'trp_edit_translation',
                'meta'      => array(
                    'class' => 'trp-settings-page'
                )
            )
        );

    }

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
        if( !is_admin() ) {
            global $TRP_LANGUAGE;

            global $trp_translated_gettext_texts;
            if (!$this->trp_query) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->trp_query = $trp->get_component('query');;
            }

            $strings = $this->trp_query->get_all_gettext_strings($TRP_LANGUAGE);
            if (!empty($strings))
                $trp_translated_gettext_texts = $strings;
        }
    }

    /* only apply the gettext filter from the wp_head hook down */
    public function apply_gettext_filter(){
        if( !is_admin() )
            add_filter( 'gettext', array( $this, 'process_gettext_strings' ), 100, 3 );
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

        if( ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'true' ) || $domain == TRP_PLUGIN_SLUG )
            return $translation;


        if ( !defined( 'DOING_AJAX' ) ) {
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
                        $callstack_function['function'] == 'wp_print_media_templates'
                    ) {
                        return $translation;
                    }
                }
            }

            global $trp_translated_gettext_texts, $trp_all_gettext_texts;
            $found_in_db = false;
            $db_id = '';

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
                        /* @todo update the db if a translation appeared in the po file later */
                        break;
                    }
                }
            }

            if( !$found_in_db ){
                if (!$this->trp_query) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component('query');;
                }

                if( !in_array( array('original' => $text, 'translated' => $translation, 'domain' => $domain), $trp_all_gettext_texts ) ) {
                    $trp_all_gettext_texts[] = array('original' => $text, 'translated' => $translation, 'domain' => $domain);
                    $db_id = $this->trp_query->insert_gettext_strings( array( array('original' => $text, 'translated' => $translation, 'domain' => $domain) ), $TRP_LANGUAGE );
                }
            }

            if ( !$this->machine_translator ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->machine_translator = $trp->get_component('machine_translator');
            }
            if ( $this->machine_translator->is_available() ) {
                if( !in_array( array('original' => $text, 'translated' => $translation, 'domain' => $domain), $trp_all_gettext_texts ) ) {
                    global $trp_gettext_strings_for_machine_translation;
                    if ($text == $translation) {
                        $trp_gettext_strings_for_machine_translation[] = array( 'id' => $db_id, 'original' => $text, 'translated' => '', 'domain' => $domain, 'status' => TRP_Query::MACHINE_TRANSLATED );
                    }
                }
            }

            if( ( !empty($TRP_LANGUAGE) && $this->settings["default-language"] != $TRP_LANGUAGE ) || ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) )
                $translation = '<trp-gettext data-trpgettextoriginal=\'' . $db_id . '\'>' . $translation . '</trp-gettext>';
        }

        return $translation;
    }

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
                    $new_strings[] = $trp_gettext_string_for_machine_translation['original'];
                }

                $machine_strings = $this->machine_translator->translate_array( $new_strings, $TRP_LANGUAGE );

                if( !empty( $machine_strings ) ){
                    foreach( $machine_strings as $key => $machine_string ){
                        $trp_gettext_strings_for_machine_translation[$key]['translated'] = $machine_string;
                    }
                }

                if (!$this->trp_query) {
                    $trp = TRP_Translate_Press::get_trp_instance();
                    $this->trp_query = $trp->get_component('query');;
                }

                $this->trp_query->update_gettext_strings( $trp_gettext_strings_for_machine_translation, $TRP_LANGUAGE );

            }
        }
    }
    
}