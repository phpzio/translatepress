
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

            <?php do_action ( 'trp_extra_settings', $this->settings ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
