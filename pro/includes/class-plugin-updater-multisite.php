<?php

class TRP_Plugin_Updater_Multisite extends TRP_Plugin_Updater {
    protected function get_option( $option ){
        return get_site_option( $option );
    }

    protected function delete_option( $option ){
        delete_site_option( $option );
    }

    protected function update_option( $option, $value ){
        update_site_option( $option, $value );
    }

    public function license_menu() {
        add_submenu_page( 'settings.php',
            'TranslatePress License',
            'TranslatePress License',
            'manage_options',
            'trp_license_key',
            array( $this, 'license_page' )
        );
    }

    public function license_page(){
        $license = get_site_option('trp_license_key');
        $status = get_site_option('trp_license_status');
        ob_start();
        require TRP_PLUGIN_DIR . 'pro/includes/partials/license-settings-page.php';
        echo ob_get_clean();
    }

    public function license_page_url( $tabs ){
        foreach( $tabs as $key => $tab ){
            if ( $tab['page'] == 'trp_license_key' ){
                $tabs[$key]['url'] = network_admin_url( 'settings.php?page=trp_license_key' );
            }
        }
        return $tabs;
    }



}