<?php

class TRP_Translation_Manager{
    protected $settings;

    public function __construct( $settings ){
        $this->settings = $settings;
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
    }

    public function get_translations() {
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'trp_get_translations' && ! empty( $_POST['strings'] ) ) {
            $strings = json_decode( stripslashes ( $_POST['strings'] ) );
            if ( is_array( $strings ) ) {
                $ids = array();
                $originals = array();
                foreach ($strings as $string) {
                    if (isset($string->id) && is_numeric($string->id)) {
                        $id_array[] = (int)$string->id;
                    }else if ( isset( $string->original ) ) {
                        $originals[] = sanitize_text_field( $string->original );
                    }
                }
            }
        }

    }
}