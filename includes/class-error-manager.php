<?php

/**
 * Class TRP_Error_Manager
 */
class TRP_Error_Manager{
    protected $settings;
    /* @var TRP_Settings */
    protected $trp_settings;

    public function __construct( $settings){
        $this->settings = $settings;
    }

    /**
     * Record specified error in trp_db_errors option
     *
     * @param $error_details array Suggested fields:
    'last_error'  => $this->db->last_error,
    'details'   => 'Insert general description',
    'disable_automatic_translations' => bool
     */
    public function record_error( $error_details ){
        $option = get_option('trp_db_errors', array(
            'notifications' => array(),
            'errors' => array()
        ));
        if ( count( $option['errors'] ) >= 5 ){
            // only record the last few errors to avoid huge db options
            array_shift($option['errors'] );
        }
        $error_details['date_time'] = date('Y-m-d H:i:s');
        $error_details['timestamp'] = time();

        // specific actions for this error: add notification message and disable machine translation
        if ( isset( $error_details['disable_automatic_translations'] ) && $error_details['disable_automatic_translations'] === true ){

            if ( ! $this->trp_settings ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->trp_settings = $trp->get_component( 'settings' );
            }
            $error_message = wp_kses( sprintf(  __('<strong>TranslatePress</strong> encountered SQL errors. <a href="%s" title="View TranslatePress SQL Errors">Check out the errors</a>.', 'translatepress-multilingual'), admin_url( 'admin.php?page=trp_error_manager' ) ), array('a' => array('href' => array(), 'title' => array()), 'strong' => array()));
            $mt_settings_option = get_option('trp_machine_translation_settings', $this->trp_settings->get_default_trp_machine_translation_settings() );
            if ( $mt_settings_option['machine-translation'] != 'no' ) {
                $mt_settings_option['machine-translation'] = 'no';
                update_option('trp_machine_translation_settings', $mt_settings_option );

                // filter is needed to block automatic translation in this execution. The settings don't update throughout the plugin for this request. Only the next request will have machine translation turned off.
                add_filter( 'trp_disable_automatic_translations_due_to_error', __return_true() );

                $error_message = wp_kses( __('Automatic translation has been disabled.','translatepress-multilingual'), array('strong' => array() ) ) . ' ' . $error_message ;
            }
            if ( !isset( $option['notifications']['disable_automatic_translations'] ) ) {
                $option['notifications']['disable_automatic_translations' ] = array(
                    // we need a unique ID so that after the notice is dismissed and this type of error appears again, it's not already marked as dismissed for that user
                    'notification_id' => 'disable_automatic_translations' . time(),
                    'message' => $error_message
                );
            }
        }


        $option['errors'][] = $error_details;
        update_option( 'trp_db_errors', $option );
    }


    /**
     * Remove notification from trp_db_errors too (not only user_meta) when dismissed by user
     *
     * Necessary in order to allow logging of this error in the future. Basically allow creation of new notifications about this error.
     *
     * Hooked to trp_dismiss_notification
     *
     * @param $notification_id
     * @param $current_user
     */
    public function clear_notification_from_db($notification_id, $current_user ){
        $option = get_option( 'trp_db_errors', false );
        if ( isset( $option['notifications'] ) ) {
            foreach ($option['notifications'] as $key => $logged_notification ){
                if ( $logged_notification['notification_id'] === $notification_id || $key === $notification_id ) {
                    unset( $option['notifications'][$key] );
                    update_option('trp_db_errors', $option );
                    break;
                }
            }
        }
    }

    /**
     * When enabling machine translation, clear the Automatic translation has been disabled message
     *
     * @param $mt_settings
     * @return string $mt_settings
     */
    public function clear_disable_machine_translation_notification_from_db( $mt_settings ){
        if ( $mt_settings['machine-translation'] === 'yes' ){
            $this->clear_notification_from_db('disable_automatic_translations', null);
        }
        return $mt_settings;
    }

    /**
     *
     * Hooked to admin_init
     */
    public function show_notification_about_errors(){
        $option = get_option( 'trp_db_errors', false );
        if ( $option !== false ) {
            foreach( $option['notifications'] as $logged_notification ) {
                $notifications = TRP_Plugin_Notifications::get_instance();

                $notification_id = $logged_notification['notification_id'];

                $message = '<p style="padding-right:30px;">' . $logged_notification['message'] . '</p>';
                //make sure to use the trp_dismiss_admin_notification arg
                $message .= '<a href="' . add_query_arg(array('trp_dismiss_admin_notification' => $notification_id)) . '" type="button" class="notice-dismiss" style="text-decoration: none;z-index:100;"><span class="screen-reader-text">' . __('Dismiss this notice.', 'translatepress-multilingual') . '</span></a>';

                $notifications->add_notification($notification_id, $message, 'trp-notice trp-narrow notice error is-dismissible', true, array('translate-press'), true);
            }
        }
    }

    public function register_submenu_errors_page(){
        add_submenu_page( 'TRPHidden', 'TranslatePress Error Manager', 'TRPHidden', 'manage_options', 'trp_error_manager', array( $this, 'error_manager_page_content' ) );
    }

    public function error_manager_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/error-manager-page.php';
    }

    public function output_db_errors( $html_content ){
        $option = get_option( 'trp_db_errors', false );
        if ( $option !== false && isset($option['errors']) ) {
            $html_content .= '<table>';
            foreach ($option['errors'] as $count => $error) {
                $count = ( is_int( $count) ) ? $count + 1 : $count;
                $html_content .= '<tr><td>' . esc_html($count) . '</td></tr>';
                foreach( $error as $key => $error_detail ){
                    $html_content .= '<tr><td><strong>' . esc_html($key ) . '</strong></td>' . '<td>' .esc_html( $error_detail ) . '</td></tr>';
                }

            }
            $html_content .= '</table>';
        }
        return $html_content;
    }
}
