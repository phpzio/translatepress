<tr>
    <th scope="row"> <?php _e( 'Translation Languages', TRP_PLUGIN_SLUG ) ?> </th>
    <td>
        <span id="trp-languages-header"><b><?php _e( 'Language', TRP_PLUGIN_SLUG ); ?></b></span>
        <span id="trp-published-header"><b> <?php _e( 'Published', TRP_PLUGIN_SLUG ); ?></b></span>
        <div id="trp-sortable-languages">
            <?php foreach ( $this->settings['translation-languages'] as $selected_language_code ){
                $default_language = ( $selected_language_code == $this->settings['default-language'] );?>
                <div class="trp-language">
                    <span class="trp-sortable-handle"></span>
                    <select name="trp_settings[translation-languages][]" class="trp-select2 trp-translation-language" <?php echo ( $default_language ) ? 'disabled' : '' ?>>
                        <?php foreach( $languages as $language_code => $language_name ){ ?>
                        <option value="<?php echo $language_code; ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
                            <?php echo $language_name; ?>
                        </option>
                        <?php }?>
                    </select>
                    <input type="checkbox" class="trp-translation-published" name="trp_settings[publish-languages][]" <?php echo ( $default_language ) ? 'disabled checked' : '' ?>>
                    <?php if ( $default_language ) { ?>
                            <input type="hidden" id="trp-hidden-default-language" name="trp_settings[translation-languages][]" value="<?php echo $selected_language_code;?>" />
                        <?php } ?>
                    <a class="trp-remove-language" style=" <?php echo ( $default_language ) ? 'display:none' : '' ?>" data-confirm-message="<?php _e( 'Are you sure you want to remove this language?', TRP_PLUGIN_SLUG ); ?>"><?php _e( 'Remove', TRP_PLUGIN_SLUG ); ?></a>

                </div>
            <?php }?>
        </div>
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
