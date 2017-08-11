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
        wp_enqueue_style( 'trp-translation-manager-style',  TRP_PLUGIN_URL . 'assets/css/trp-editor-style.css', array(), TRP_PLUGIN_VERSION );

        $scripts_to_print = apply_filters( 'trp-scripts-for-editor', array( 'jquery', 'jquery-ui-core', 'jquery-effects-core', 'jquery-ui-resizable', 'trp-translation-manager-script', 'trp-select2-lib-js' ) );
        $styles_to_print = apply_filters( 'trp-styles-for-editor', array( 'trp-translation-manager-style', 'trp-select2-lib-css' /*'wp-admin', 'dashicons', 'common', 'site-icon', 'buttons'*/ ) );
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

    /**
     * Save translations from ajax post.
     *
     * Hooked to wp_ajax_trp_save_translations.
     */
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

    /**
     * Display button to enter translation Editor in admin bar
     *
     * Hooked to admin_bar_menu.
     *
     * @param $wp_admin_bar
     */
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
}