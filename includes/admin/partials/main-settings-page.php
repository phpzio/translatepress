
<div id="trp-main-settings">
    <form method="post" action="options.php">
        <?php settings_fields( 'trp_settings' ); ?>
        <h1> <?php _e( 'Translate Press', TRP_PLUGIN_SLUG );?></h1>
        <table id="trp-options" class="form-table">
            <tr>
                <th scope="row"><?php _e( 'Default Language', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <select id="trp-default-language" name="trp_settings[default-language]">
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

            <tr>
                <th scope="row"> <?php _e( 'Translation Languages', TRP_PLUGIN_SLUG ) ?> </th>
                <td>
                    <select multiple id="trp-translation-languages" name="trp_settings[translation-languages][]">
                        <?php foreach( $languages as $language_code => $language_name ){ ?>
                            <option value="<?php echo $language_code; ?>" <?php echo ( isset($this->settings['translation-languages'] ) && in_array( $language_code, $this->settings['translation-languages'] ) ? 'selected' : '' ); ?>>
                                <?php echo $language_name; ?>
                            </option>
                        <?php }?>
                    </select>
                    <p class="description">
                        <?php _e( 'Select the languages you wish to make your website available in.', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>
            </tr>

            <?php do_action ( 'trp_extra_settings', $this->settings ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
