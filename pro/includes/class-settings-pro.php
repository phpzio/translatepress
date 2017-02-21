<?php

class TRP_Settings_Pro extends TRP_Settings{

    protected function languages_selector( $languages ){
        require_once( TRP_PLUGIN_DIR . 'pro/partials/language-selector-pro.php' );
    }

    public function enqueue_sortable_language_script( $languages ){
        //wp_enqueue_script( 'trp-sortable-languages', TRP_PLUGIN_DIR . 'pro/assets/js/trp-sortable-languages.js' );
    }


}