<?php

class TRP_Plugin_Updater{

    public function __construct(){
        // this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
        define( 'TRP_STORE_URL', 'http://yoursite.com' );
        // the name of your product. This should match the download name in EDD exactly
        define( 'TRP_ITEM_NAME', 'TranslatePress' );
        if( !class_exists( 'TRP_EDD_SL_Plugin_Updater' ) ) {
            // load our custom updater
            include TRP_PLUGIN_DIR . 'pro/includes/class-edd-sl-plugin-updater.php';
        }
        // retrieve our license key from the DB
        $license_key = trim( $this->get_option( 'trp_license_key' ) );
        // setup the updater
        $edd_updater = new TRP_EDD_SL_Plugin_Updater( TRP_STORE_URL, __FILE__, array(
                'version' 	=> '1.0', 		// current version number
                'license' 	=> $license_key, 	// license key (used get_option above to retrieve from DB)
                'item_name' => TRP_ITEM_NAME, 	// name of this plugin
                'author' 	=> 'Cozmoslabs',  // author of this plugin
                'url'       => home_url()
            )
        );
    }

    protected function get_option( $license_key_option ){
        return get_option( $license_key_option );
    }

    protected function delete_option( $license_key_option ){
        delete_option( $license_key_option );
    }

    protected function update_option( $license_key_option, $value ){
        update_option( $license_key_option, $value );
    }

    protected function license_page_url( ){
        return admin_url( 'admin.php?page=' . 'trp_license_key' );
    }

    public function license_menu() {
        add_submenu_page(
            'TRPHidden',
            'TranslatePress License',
            'TRPHidden',
            'manage_options',
            'trp_license_key',
            array( $this, 'license_page' )
        );
    }

    public function license_page(){
        $license = $this->get_option('trp_license_key');
        $status = $this->get_option('trp_license_status');
        $action = 'options.php';
        ob_start();
        require TRP_PLUGIN_DIR . 'pro/includes/partials/license-settings-page.php';
        echo ob_get_clean();
    }

    public function register_option() {
        // creates our settings in the options table
        register_setting('trp_license_key', 'trp_license_key', array( $this, 'edd_sanitize_license' ) );
    }

    public function edd_sanitize_license( $new ) {
        $old = $this->get_option( 'trp_license_key' );
        if( $old && $old != $new ) {
            $this->delete_option( 'trp_license_status' ); // new license has been entered, so must reactivate
        }
        return $new;
    }

    public function admin_notices() {
        if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

            switch( $_GET['sl_activation'] ) {

                case 'false':
                    $message = urldecode( $_GET['message'] );
                    ?>
                    <div class="error">
                        <p><?php echo $message; ?></p>
                    </div>
                    <?php
                    break;

                case 'true':
                default:
                    // Developers can put a custom success message here for when activation is successful if they way.
                    break;

            }
        }
    }

    public function activate_license() {
        // listen for our activate button to be clicked
        if( isset( $_POST['edd_license_activate'] ) ) {
            // run a quick security check
            if( ! check_admin_referer( 'trp_license_nonce', 'trp_license_nonce' ) )
                return; // get out if we didn't click the Activate button
            // retrieve the license from the database
            $license = trim( $this->get_option( 'trp_license_key' ) );

            // data to send in our API request
            $api_params = array(
                'edd_action' => 'activate_license',
                'license'    => $license,
                'item_name'  => urlencode( TRP_ITEM_NAME ), // the name of our product in EDD
                'url'        => home_url()
            );

            // Call the custom API.
            $response = wp_remote_post( TRP_STORE_URL, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

            // make sure the response came back okay
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

                $response_error_message = $response->get_error_message();
                $message = ( is_wp_error( $response ) && ! empty( $response_error_message ) ) ? $response->get_error_message() : __( 'An error occurred, please try again.' );

            } else {

                $license_data = json_decode( wp_remote_retrieve_body( $response ) );

                if ( false === $license_data->success ) {

                    switch( $license_data->error ) {

                        case 'expired' :

                            $message = sprintf(
                                __( 'Your license key expired on %s.' ),
                                date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
                            );
                            break;

                        case 'revoked' :

                            $message = __( 'Your license key has been disabled.' );
                            break;

                        case 'missing' :

                            $message = __( 'Invalid license.' );
                            break;

                        case 'invalid' :
                        case 'site_inactive' :

                            $message = __( 'Your license is not active for this URL.' );
                            break;

                        case 'item_name_mismatch' :

                            $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), TRP_ITEM_NAME );
                            break;

                        case 'no_activations_left':

                            $message = __( 'Your license key has reached its activation limit.' );
                            break;

                        default :

                            $message = __( 'An error occurred, please try again.' );
                            break;
                    }

                }

            }

            // Check if anything passed on a message constituting a failure
            if ( ! empty( $message ) ) {
                $base_url = admin_url( 'admin.php?page=' . 'trp_license_key' );
                $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

                wp_redirect( $redirect );
                exit();
            }

            // $license_data->license will be either "valid" or "invalid"

            $this->update_option( 'trp_license_status', $license_data->license );
            wp_redirect( $this->license_page_url() );
            exit();
        }
    }

}