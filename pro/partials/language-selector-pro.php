<tr>
    <th scope="row"> <?php _e( 'Translation Languages', TRP_PLUGIN_SLUG ) ?> </th>
    <td>
        <div id="trp-sortable-languages">

            <?php foreach ( $this->settings['translation-languages'] as $selected_language_code ){ ?>
                <div>
                    <select id="trp-translation-languages" name="trp_settings[translation-languages][]" class="trp-select2" >
                        <?php foreach( $languages as $language_code => $language_name ){ ?>
                        <option value="<?php echo $language_code; ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
                            <?php echo $language_name; ?>
                        </option>
                        <?php }?>
                    </select>
                </div>
            <?php }?>
        </div>
        <p class="description">
            <?php _e( 'Select the languages you wish to make your website available in.', TRP_PLUGIN_SLUG ); ?>
        </p>
    </td>
</tr>
