
<div id="trp-main-settings">
    <form method="post" action="options.php">
        <?php settings_fields( 'trp_settings' ); ?>
        <h1> <?php _e( 'TranslatePress Settings', TRP_PLUGIN_SLUG );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

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
                <th scope="row"><?php _e( 'Use subdirectory for default language', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <select id="trp-g-translate" name="trp_settings[add-subdirectory-to-default-language]" class="trp-select">
                        <option value="no" <?php selected( $this->settings['add-subdirectory-to-default-language'], 'no' ); ?>><?php _e( 'No', TRP_PLUGIN_SLUG) ?></option>
                        <option value="yes" <?php selected( $this->settings['add-subdirectory-to-default-language'], 'yes' ); ?>><?php _e( 'Yes', TRP_PLUGIN_SLUG) ?></option>
                    </select>
                    <p class="description">
                        <?php _e( 'Select Yes if you want to add the subdirectory in the url for the default language.', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Force language in custom links', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <select id="trp-g-translate" name="trp_settings[force-language-to-custom-links]" class="trp-select">
                        <option value="no" <?php selected( $this->settings['force-language-to-custom-links'], 'no' ); ?>><?php _e( 'No', TRP_PLUGIN_SLUG) ?></option>
                        <option value="yes" <?php selected( $this->settings['force-language-to-custom-links'], 'yes' ); ?>><?php _e( 'Yes', TRP_PLUGIN_SLUG) ?></option>
                    </select>
                    <p class="description">
                        <?php _e( 'Select Yes if you want to force custom links without language encoding to  add the subdirectory in the url for the default language.', TRP_PLUGIN_SLUG ); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e( 'Google Translate', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <select id="trp-g-translate" name="trp_settings[g-translate]" class="trp-select">
                        <option value="no" <?php selected( $this->settings['g-translate'], 'no' ); ?>><?php _e( 'No', TRP_PLUGIN_SLUG) ?></option>
                        <option value="yes" <?php selected( $this->settings['g-translate'], 'yes' ); ?>><?php _e( 'Yes', TRP_PLUGIN_SLUG) ?></option>
                    </select>
                    <p class="description">
                        <?php _e( 'Enable or disable the automatic translation of the site with Google Translate. <br>Note: Not all languages support automatic translation. Please consult the <a href="https://cloud.google.com/translate/docs/languages" target="_blank" title="Automatic translation supported languages.">supported languages list</a>. ', TRP_PLUGIN_SLUG ); ?>
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

            <tr>
                <th scope="row"><?php _e( 'Language Switcher', TRP_PLUGIN_SLUG ); ?> </th>
                <td>
                    <div class="trp-ls-type">
                        <input type="checkbox" disabled checked id="trp-ls-shortcode" ><b><?php _e( 'Shortcode ', TRP_PLUGIN_SLUG ); ?>[language-switcher] </b>
                        <div>
                            <?php $this->output_language_switcher_select( 'shortcode-options', $this->settings['shortcode-options'] ); ?>
                        </div>
                        <p class="description">
                            <?php _e( 'Use shortcode on any page or widget.', TRP_PLUGIN_SLUG ); ?>
                        </p>
                    </div>
                    <div class="trp-ls-type">
                        <label><input type="checkbox" id="trp-ls-menu" disabled checked ><b><?php _e( 'Menu item', TRP_PLUGIN_SLUG ); ?></b></label>
                        <div>
                            <?php $this->output_language_switcher_select( 'menu-options', $this->settings['menu-options'] ); ?>
                        </div>
                        <p class="description">
                            <?php _e( 'Go to Appearance -> Menus to add Language Switcher Languages in any menu.', TRP_PLUGIN_SLUG ); ?>
                        </p>
                    </div>
                    <div class="trp-ls-type">
                        <label><input type="checkbox" id="trp-ls-floater" name="trp_settings[trp-ls-floater]"  value="yes"  <?php if ( isset($this->settings['trp-ls-floater']) && ( $this->settings['trp-ls-floater'] == 'yes' ) ){ echo 'checked'; }  ?>><b><?php _e( 'Floating language selection', TRP_PLUGIN_SLUG ); ?></b></label>
                        <div>
                            <?php $this->output_language_switcher_select( 'floater-options', $this->settings['floater-options'] ); ?>
                        </div>
                        <p class="description">
                            <?php _e( 'Have a floating dropdown following the user on every page.', TRP_PLUGIN_SLUG ); ?>
                        </p>
                    </div>
                </td>
            </tr>

            <?php do_action ( 'trp_extra_settings', $this->settings ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php _e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
