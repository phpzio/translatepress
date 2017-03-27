<?php

class TRP_Translation_Manager{
    protected $settings;
    protected $translation_render;
    protected $trp_query;

    public function __construct( $settings, $translation_render, $trp_query ){
        $this->settings = $settings;
        $this->translation_render = $translation_render;
        $this->trp_query = $trp_query;
    }

    // mode == true, mode == preview
    protected function conditions_met( $mode = 'true' ){
        if ( current_user_can( 'manage_options' ) &&/* ! is_admin() &&*/ isset( $_GET['trp-edit-translation'] ) && esc_attr( $_GET['trp-edit-translation'] ) == $mode ) {
            return true;
        }
        return false;
    }

    public function translation_editor( $page_template ){

        if ( ! $this->conditions_met() ){
            return $page_template;
        }

        global $trp_settings;
        $trp_settings = $this->settings;
        //todo no outside urls
        return TRP_PLUGIN_DIR . 'includes/partials/translation-manager.php' ;
    }

    public function enqueue_scripts_and_styles(){


        wp_enqueue_script( 'trp-translation-manager-script',  TRP_PLUGIN_URL . 'assets/js/trp-editor-script.js' );
        wp_enqueue_style( 'trp-translation-manager-style',  TRP_PLUGIN_URL . 'assets/css/trp-editor-style.css' );
        wp_localize_script( 'trp-scripts-for-editor', 'some_variable', array( 'customajax' => TRP_PLUGIN_URL . '/includes/trp-ajax.php' , __FILE__ ) );
        //error_log(TRP_PLUGIN_URL . '/includes/trp-ajax.php' );

        $scripts_to_print = apply_filters( 'trp-scripts-for-editor', array( 'jquery', 'trp-translation-manager-script' ) );
        //todo maybe more styles here
        $styles_to_print = apply_filters( 'trp-styles-for-editor', array( 'trp-translation-manager-style' /*'wp-admin', 'dashicons', 'common', 'site-icon', 'buttons'*/ ) );

        wp_print_scripts( $scripts_to_print );
        wp_print_styles( $styles_to_print );
    }

    public function enqueue_preview_scripts_and_styles(){
        if ( $this->conditions_met( 'preview' ) ) {
            /* twentyfifteen theme scrolls header uncontrolled on page load because of this  */
            show_admin_bar( false );
        }
        wp_enqueue_script( 'trp-translation-manager-preview-script',  TRP_PLUGIN_URL . 'assets/js/trp-iframe-preview-script.js', array('jquery') );


    }

    protected function extract_original_strings( $strings, $original_array, $id_array ){
        if ( !empty( $strings ) ) {
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
                    foreach ($strings as $key => $string) {
                        if ( isset( $string->id ) && is_numeric( $string->id ) ) {
                            $id_array[$key] = (int)$string->id;
                        } else if ( isset( $string->original ) ) {
                            $original_array[$key] = sanitize_text_field( $string->original );
                        }
                    }
                    // todo make sure the language exists in the settings
                    $current_language = esc_attr( $_POST['language'] );

                    // necessary in order to obtain all the original strings
                    if ( $this->settings['default-language'] != $current_language ) {
                        $this->translation_render->process_strings($original_array, $current_language);
                        $dictionaries[$current_language] = $this->trp_query->get_string_rows($id_array, $original_array, $current_language);
                    }

                    foreach( $this->settings['translation-languages'] as $language ) {
                        if ( $language == $this->settings['default-language'] ){
                            $dictionaries[$language]['default-language'] = true;
                            continue;
                        }
                        if ( $language == $current_language ){
                            continue;
                        }
                        if ( empty( $original_strings ) ){
                            $original_strings = $this->extract_original_strings( $dictionaries[$current_language], $original_array, $id_array );
                        }
                        $this->translation_render->process_strings( $original_strings, $language );
                        $dictionaries[$language] = $this->trp_query->get_string_rows( $id_array, $original_array, $current_language );
                    }

                    //error_log(json_encode($dictionaries));


                    echo json_encode( $dictionaries );
                }

            }
        }

        die();
    }

}