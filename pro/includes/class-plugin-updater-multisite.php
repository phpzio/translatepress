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

    protected function license_page_url( ){

        return network_admin_url( 'settings.php?page=trp_license_key' );
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
        $license = $this->get_option('trp_license_key');
        $status = $this->get_option('trp_license_status');
        $action = '';
        ob_start();
        require TRP_PLUGIN_DIR . 'pro/includes/partials/license-settings-page.php';
        echo ob_get_clean();
    }

    public function add_license_page_url_to_tab( $tabs ){
        foreach( $tabs as $key => $tab ){
            if ( $tab['page'] == 'trp_license_key' ){
                $tabs[$key]['url'] = $this->license_page_url();
            }
        }
        return $tabs;
    }

    function activate_network_license1(){
        if( is_super_admin( get_current_user_id() ) ){
            $namesetting = sanitize_text_field( $_POST['wpnd_settings']['new_blog_name'] );
            update_site_option('wpnd_settings_blogname', $namesetting);
            $urlsetting = esc_url_raw( $_POST['wpnd_settings']['new_blog_url'] );
            update_site_option('wpnd_settings_blogurl', $urlsetting);
            wp_redirect(add_query_arg(array('page' => 'wpnd_settings', 'updated' => 'true'), network_admin_url('settings.php')));
        }
        exit();
    }

    public function activate_license() {
        if(  !isset ( $_POST['trp_license_key'] ) || ! is_super_admin( get_current_user_id() )  )
            return;
        $this->update_option('trp_license_key', esc_attr( $_POST['trp_license_key'] ));
        parent::activate_license();

    }

}