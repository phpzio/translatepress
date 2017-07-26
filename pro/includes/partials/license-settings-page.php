<div id="trp-license-settings">
    <form method="post" action="<?php echo $action; ?>">
        <?php settings_fields( 'trp_license_key' ); ?>
        <h1> <?php _e( 'TranslatePress Settings', TRP_PLUGIN_SLUG );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>
        <table class="form-table">
            <tbody>
            <tr valign="top">
                <th scope="row" valign="top">
                    <?php _e('License Key'); ?>
                </th>
                <td>
                    <input id="trp_license_key" name="trp_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
                    <p class="description">
                        <?php _e( 'Enter your license key.', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>
            </tr>
            <?php if( false !== $license ) { ?>
                <tr valign="top">
                    <th scope="row" valign="top">
                        <?php _e('Activate License'); ?>
                    </th>
                    <td>
                        <?php if( $status !== false && $status == 'valid' ) { ?>
                            <span style="color:green;"><?php _e('active'); ?></span>
                            <?php wp_nonce_field( 'trp_license_nonce', 'trp_license_nonce' ); ?>
                            <input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
                        <?php } else {
                            wp_nonce_field( 'trp_license_nonce', 'trp_license_nonce' ); ?>
                            <input type="submit" class="button-secondary" name="edd_license_activate" value="<?php _e('Activate License'); ?>"/>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php submit_button(); ?>
    </form>
</div>