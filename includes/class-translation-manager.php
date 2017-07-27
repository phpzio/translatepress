<?php

class TRP_Translation_Manager{
    protected $settings;
    protected $translation_render;
    protected $trp_query;
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

        return TRP_PLUGIN_DIR . 'includes/partials/translation-manager.php' ;
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
        if ( isset( $post->ID ) && !empty( $post->ID ) && isset( $post->post_name ) && !empty( $post->post_name ) && $this->conditions_met( 'preview' ) && !is_home() && !is_archive() ) {
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

                    $current_language = $_POST['language'];

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
                                'translated' => $this->slug_manager->get_translated_slug( $slug_info['post_id'], $current_language ),
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
                                    'translated' => $this->slug_manager->get_translated_slug( $slug_info['post_id'], $language )
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

    public function add_shortcut_to_translation_editor( $wp_admin_bar ){
        if ( is_admin () || ! current_user_can( 'manage_options' )){
            return;
        }
        global $post;
        if ( is_object( $post ) && ! is_archive() && !is_home() ){
            $url = get_permalink( $post );
        }else{
            if ( ! $this->url_converter ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->url_converter = $trp->get_component('url_converter');
            }

            $url = $this->url_converter->cur_page_url();
        }
        $url = add_query_arg( 'trp-edit-translation', 'true', $url );

        $args = array(
            'id'    => 'trp_edit_translation',
            'title' => __( 'Edit Page Translations', TRP_PLUGIN_SLUG ),
            'href'  => $url,
            'meta'  => array( 'class' => 'trp-edit-translation' )
        );
        $wp_admin_bar->add_node( $args );
    }

    public function hide_admin_bar_when_in_editor( $show_admin_bar ) {

        if( $this->conditions_met( 'preview' ) ) {
            return false;
        }

        return $show_admin_bar;

    }
}