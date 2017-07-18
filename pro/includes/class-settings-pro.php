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

    public function add_navigation_tabs(){
        $tabs = apply_filters( 'trp_settings_tabs', array(
            array(
                'name'      => 'General',
                'url'       => admin_url( 'options-general.php?page=translate-press' ),
                'nav_tab'   => 'trp-settings-general'
            ),
            array(
                'name'  => 'License',
                'url'   => admin_url( 'admin.php?page=trp_license_key' ),
                'nav_tab'   => 'trp-settings-license'
            )
        ));

        $active_tab = $tabs[0]['nav_tab'];
        if ( isset( $_GET['nav_tab'] ) ){
            foreach ( $tabs as $tab ){
                if ( $_GET['nav_tab'] == $tab['nav_tab'] ) {
                    $active_tab = $tab['nav_tab'];
                }
            }
        }

        require ( TRP_PLUGIN_DIR . 'pro/includes/partials/settings-navigation-tabs.php');
    }

    public function check_translation_settings( $settings ){
        return $settings;
    }

}