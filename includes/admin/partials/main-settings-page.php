
<div id="trp-main-settings">
    <form method="post" action="options.php">
        <?php settings_fields( 'trp_settings' ); ?>
        <h1> <?php _e( 'Translate Press', TRP_PLUGIN_SLUG );?></h1>
        <table id="trp-options" class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Manage Translations', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <a href="<?php echo add_query_arg( 'trp-edit-translation', 'true', home_url() ); ?>"><button type="button" class="button-secondary trp-open-translation-editor"> <?php _e( 'Open Translation Editor', TRP_PLUGIN_SLUG ); ?></button></a>
                </td>

            </tr>
            <tr>
                <th scope="row"><?php _e( 'Default Language', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <select id="trp-default-language" name="trp_settings[default-language]" class="trp-select2">
                        <?php
                        foreach( $languages as $language_code => $language_name ){ ?>
                            <option value="<?php echo $language_code; ?>" <?php echo ( $this->settings['default-language'] == $language_code ? 'selected' : '' ); ?> >
                                <?php echo $language_name; ?>
                            </option>
                        <?php }?>
                    </select>
                    <p class="description">
                        <?php _e( 'Select the original language your website was written in. ', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>
            </tr>

            <?php $this->languages_selector( $languages ); ?>

            <tr>
                <th scope="row"><?php _e( 'Google Translate', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <select id="trp-g-translate" name="trp_settings[g-translate]" class="trp-select">
                            <option value="no" <?php selected( $this->settings['g-translate'], 'no' ); ?>><?php _e( 'No', TRP_PLUGIN_SLUG) ?></option>
                            <option value="yes" <?php selected( $this->settings['g-translate'], 'yes' ); ?>><?php _e( 'Yes', TRP_PLUGIN_SLUG) ?></option>
                    </select>
                    <p class="description">
                        <?php _e( 'Enable or disable the automatic translation of the site with Google Translate. ', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Google Translate API Key', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <input type="text" id="trp-g-translate-key" class="trp-text-input" name="trp_settings[g-translate-key]" value="<?php if( !empty( $this->settings['g-translate-key'] ) ) echo esc_attr( $this->settings['g-translate-key']);?>"/>
                    <p class="description">
                        <?php _e( 'Visit this <a href="https://support.google.com/cloud/answer/6158862" target="_blank">link</a> to see how you can set up an API key. ', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>

            </tr>
            

            <?php do_action ( 'trp_extra_settings', $this->settings ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
