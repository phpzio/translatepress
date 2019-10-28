<?php
$trp = TRP_Translate_Press::get_trp_instance();
if( !empty( $trp->active_pro_addons ) ){//if we have any Advanced or Pro addons active then show the license key activation form
?>
<div id="trp-license-settings" class="wrap">
    <form method="post" action="<?php echo $action; ?>">
            <?php settings_fields( 'trp_license_key' ); ?>
            <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>
            <?php do_action ( 'trp_settings_navigation_tabs' ); ?>
            <table class="form-table">
                    <tbody>
                    <tr valign="top">
                            <th scope="row" valign="top">
                                    <?php _e('License Key', 'translatepress-multilingual'); ?>
                                </th>
                            <td>
                                    <div>
                                        <input id="trp_license_key" name="trp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
                                        <?php wp_nonce_field( 'trp_license_nonce', 'trp_license_nonce' ); ?>
                                        <?php if( $status !== false && $status == 'valid' ) {
                                            $button_name =  'trp_edd_license_deactivate';
                                            $button_value = __('Deactivate License', 'translatepress-multilingual' );

                                            if( empty( $details['invalid'] ) )
                                                echo '<span title="'.__( 'Active on this site', 'translatepress-multilingual' ) .'" class="trp-active-license dashicons dashicons-yes"></span>';
                                            else
                                                echo '<span title="'.__( 'Your license is invalid', 'translatepress-multilingual' ) .'" class="trp-invalid-license dashicons dashicons-warning"></span>';

                                        }
                                        else {
                                            $button_name =  'trp_edd_license_activate';
                                            $button_value = __('Activate License', 'translatepress-multilingual');
                                        }
                                        ?>
                                        <input type="submit" class="button-secondary" name="<?php echo $button_name; ?>" value="<?php echo $button_value; ?>"/>
                                    </div>
                                    <p class="description">
                                            <?php _e( 'Enter your license key.', 'translatepress-multilingual' ); ?>
                                    </p>
                            </td>
                    </tbody>
                </table>
        </form>
</div>
<?php } else{ ?>
    <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>
    <?php do_action ( 'trp_settings_navigation_tabs' ); ?>
    <h4><?php printf( __( 'If you purchased a premium version, first install and activate any of the <a href="%s">Advanced or Pro Addons</a>. After this you will be prompted with an input to enter your license key.', 'translatepress-multilingual' ), admin_url('/admin.php?page=trp_addons_page') ); ?></h4>
<?php } ?>