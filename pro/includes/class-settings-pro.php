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
        wp_enqueue_script( 'trp-sortable-languages', TRP_PLUGIN_URL . 'pro/assets/js/trp-sortable-languages.js', array( 'jquery-ui-sortable'), TRP_PLUGIN_VERSION );
        if ( ! $this->trp_languages ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_languages = $trp->get_component( 'languages' );
        }
        $all_language_codes = $this->trp_languages->get_all_language_codes();
        $iso_codes = $this->trp_languages->get_iso_codes( $all_language_codes, false );
        wp_localize_script( 'trp-sortable-languages', 'trp_url_slugs_info', array( 'iso_codes' => $iso_codes, 'error_message_duplicate_slugs' => __( 'Error! Duplicate Url slug values.', TRP_PLUGIN_SLUG ) ) );
    }

    public function add_navigation_tabs(){
        $tabs = apply_filters( 'trp_settings_tabs', array(
            array(
                'name'  => __( 'General', TRP_PLUGIN_SLUG ),
                'url'   => admin_url( 'options-general.php?page=translate-press' ),
                'page'  => 'translate-press'
            ),
            array(
                'name'  => __( 'Translate Site', TRP_PLUGIN_SLUG ),
                'url'   => add_query_arg( 'trp-edit-translation', 'true', home_url() ),
                'page'  => 'trp_translation_editor'
            ),
            array(
                'name'  => __( 'License', TRP_PLUGIN_SLUG ),
                'url'   => admin_url( 'admin.php?page=trp_license_key' ),
                'page'  => 'trp_license_key'
            )
        ));

        $active_tab = 'translate-press';
        if ( isset( $_GET['page'] ) ){
            $active_tab = esc_attr( $_GET['page'] );
        }

        require ( TRP_PLUGIN_DIR . 'pro/includes/partials/settings-navigation-tabs.php');
    }

    public function check_translation_settings( $settings ){
        return $settings;
    }

}