<tr>
    <th scope="row"> <?php _e( 'Translation Languages', TRP_PLUGIN_SLUG ) ?> </th>
    <td>
        <table id="trp-languages-table">
            <thead>
                <tr>
                    <th colspan="2"><?php _e( 'Language', TRP_PLUGIN_SLUG ); ?></th>
                    <th><?php _e( 'URL Slug', TRP_PLUGIN_SLUG ); ?></th>
                    <th><?php _e( 'Active', TRP_PLUGIN_SLUG ); ?></th>
                </tr>
            </thead>
            <tbody id="trp-sortable-languages">

            <?php foreach ( $this->settings['translation-languages'] as $selected_language_code ){
                $default_language = ( $selected_language_code == $this->settings['default-language'] );?>
                <tr class="trp-language">
                    <td><span class="trp-sortable-handle"></span></td>
                    <td>
                        <select name="trp_settings[translation-languages][]" class="trp-select2 trp-translation-language" <?php echo ( $default_language ) ? 'disabled' : '' ?>>
                            <?php foreach( $languages as $language_code => $language_name ){ ?>
                            <option value="<?php echo $language_code; ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
                                <?php echo $language_name; ?>
                            </option>
                            <?php }?>
                        </select>
                    </td>
                    <td>
                        <input class="trp-language-slug" name="trp_settings[url-slugs][<?php echo $selected_language_code ?>]" type="text" style="text-transform: lowercase;" value="<?php echo $this->url_converter->get_url_slug( $selected_language_code ); ?>">
                    </td>
                    <td align="center">
                        <input type="checkbox" class="trp-translation-published" name="trp_settings[publish-languages][]" value="<?php echo $selected_language_code; ?>" <?php echo ( in_array( $selected_language_code, $this->settings['publish-languages'] ) ) ? 'checked ' : ''; echo ( $default_language ) ? 'disabled ' : ''; ?> />
                        <?php if ( $default_language ) { ?>
                                <input type="hidden" class="trp-hidden-default-language" name="trp_settings[translation-languages][]" value="<?php echo $selected_language_code;?>" />
                                <input type="hidden" class="trp-hidden-default-language" name="trp_settings[publish-languages][]" value="<?php echo $selected_language_code;?>" />
                        <?php } ?>
                    </td>
                    <td>
                        <a class="trp-remove-language" style=" <?php echo ( $default_language ) ? 'display:none' : '' ?>" data-confirm-message="<?php _e( 'Are you sure you want to remove this language?', TRP_PLUGIN_SLUG ); ?>"><?php _e( 'Remove', TRP_PLUGIN_SLUG ); ?></a>
                    </td>
                </tr>
            <?php }?>
            </tbody>
        </table>
        <div id="trp-new-language">
            <select id="trp-select-language" class="trp-select2 trp-translation-language" >
                <option value=""><?php _e( 'Choose...', TRP_PLUGIN_SLUG );?></option>
                <?php foreach( $languages as $language_code => $language_name ){ ?>
                    <option value="<?php echo $language_code; ?>">
                        <?php echo $language_name; ?>
                    </option>
                <?php }?>
            </select>
            <button type="button" id="trp-add-language" class="button-secondary"><?php _e( 'Add', TRP_PLUGIN_SLUG );?></button>
        </div>
        <p class="description">
            <?php _e( 'Select the languages you wish to make your website available in.', TRP_PLUGIN_SLUG ); ?>
        </p>
    </td>
</tr>
