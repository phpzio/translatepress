<?php

class TRP_Settings_Pro extends TRP_Settings{

    protected function languages_selector( $languages ){
        if ( ! $this->url_converter ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component( 'url_converter' );
        }
        require_once( TRP_PLUGIN_DIR . 'pro/includes/partials/language-selector-pro.php' );
    }

    public function enqueue_sortable_language_script( ){
        wp_enqueue_script( 'trp-sortable-languages', TRP_PLUGIN_URL . 'pro/assets/js/trp-sortable-languages.js', array( 'jquery-ui-sortable') );
    }


}