
<div id="trp-main-settings" class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields( 'trp_automated_translation_settings' ); ?>
        <h1> <?php _e( 'Automated Translation Settings', 'translatepress-multilingual' );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

        <table id="trp-options" class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Automated Translation Provider', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <select id="trp-auto-translator" name="trp_automated_translation_settings[translator]" class="trp-select">
                        <option value="disabled" <?php selected( $this->settings_automated_translations['translator'], 'disabled' ); ?>><?php _e( 'None (disabled)', 'translatepress-multilingual') ?></option>
                        <option value="google" <?php selected( $this->settings_automated_translations['translator'], 'google' ); ?>><?php _e( 'Google Translate', 'translatepress-multilingual') ?></option>
                        <option value="deepl" <?php selected( $this->settings_automated_translations['translator'], 'deepl' ); ?>><?php _e( 'Deepl (Recomended)', 'translatepress-multilingual') ?></option>
                    </select>
                    <p class="description">
                        <?php _e( 'Enable or disable the automatic translation of the site with Google Translate. Only untranslated strings will receive a translation.<br>You can later edit these automatic translations.<br>Note: Not all languages support automatic translation. Please consult the <a href="https://cloud.google.com/translate/docs/languages" target="_blank" title="Automatic translation supported languages.">supported languages list</a>. ', 'translatepress-multilingual' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Google Translate API Key', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <input type="text" id="google-key" class="trp-text-input" name="trp_automated_translation_settings[google-key]" value="<?php if( !empty( $this->settings_automated_translations['google-key'] ) ) echo esc_attr( $this->settings_automated_translations['google-key']);?>"/>
                    <?php if( !empty( $this->settings_automated_translations['google-key'] ) ) echo '<a href="'.admin_url( 'admin.php?page=trp_test_google_key_page' ).'">'.__( "Test api key", 'translatepress-multilingual' ).'</a>'; ?>
                    <p class="description">
                        <?php _e( 'Visit this <a href="https://cloud.google.com/docs/authentication/api-keys" target="_blank">link</a> to see how you can set up an API key. ', 'translatepress-multilingual' ); ?>
                        <?php echo sprintf( __( '<br>If you want to restrict usage of the API from Google Dashboard use this HTTP referrer: %s', 'translatepress-multilingual' ), $gtranslate_referer ); ?>
                    </p>
                </td>

            </tr>

            <tr>
                <th scope="row"><?php _e( 'Deepl Translate API Key', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <input type="text" id="google-key" class="trp-text-input" name="trp_automated_translation_settings[deepl-key]" value="<?php if( !empty( $this->settings_automated_translations['deepl-key'] ) ) echo esc_attr( $this->settings_automated_translations['deepl-key']);?>"/>
                    <?php if( !empty( $this->settings_automated_translations['deepl-key'] ) ) echo '<a href="'.admin_url( 'admin.php?page=trp_test_google_key_page' ).'">'.__( "Test api key", 'translatepress-multilingual' ).'</a>'; ?>
                    <p class="description">
                        <?php _e( 'Visit this <a href="https://cloud.google.com/docs/authentication/api-keys" target="_blank">link</a> to see how you can set up an API key. ', 'translatepress-multilingual' ); ?>
                        <?php echo sprintf( __( '<br>If you want to restrict usage of the API from Google Dashboard use this HTTP referrer: %s', 'translatepress-multilingual' ), $gtranslate_referer ); ?>
                    </p>
                </td>

            </tr>


            <?php do_action ( 'trp_extra_automated_translation_settings', $this->settings_automated_translations ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
