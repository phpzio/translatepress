<div id="trp-main-settings" class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields( 'trp_machine_translation_settings' ); ?>
        <h1> <?php esc_html_e( 'TranslatePress Machine Translation', 'translatepress-multilingual' );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

        <table id="trp-options" class="form-table trp-machine-translation-options">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable Machine Translation', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <select id="trp-machine-translation-enabled" name="trp_machine_translation_settings[machine-translation]" class="trp-select">
                        <option value="no" <?php selected( $this->settings['machine-translation'], 'no' ); ?>><?php esc_html_e( 'No', 'translatepress-multilingual') ?></option>
                        <option value="yes" <?php selected( $this->settings['machine-translation'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'translatepress-multilingual') ?></option>
                    </select>
                    <p class="description">
                        <?php _e( 'Enable or disable the automatic translation of the site. Only untranslated strings will receive a translation.', 'translatepress-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Translation Engine', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <?php $translation_engines = apply_filters( 'trp_machine_translation_engines', array() ); ?>

                    <select id="trp-machine-translation-engine" name="trp_machine_translation_settings[translation-engine]" class="trp-select">

                        <?php foreach( $translation_engines as $engine ) : ?>
                            <option value="<?php echo $engine['value']; ?>" <?php selected( $this->settings['translation-engine'], $engine['value'] ); ?>><?php echo $engine['label'] ?></option>
                        <?php endforeach; ?>

                    </select>
                    <p class="description">
                        <?php _e( 'Choose which engine you want to use in order to automatically translate your website.', 'translatepress-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <?php do_action ( 'trp_machine_translation_extra_settings_middle', $this->settings ); ?>


            <?php do_action ( 'trp_machine_translation_extra_settings_bottom', $this->settings ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
