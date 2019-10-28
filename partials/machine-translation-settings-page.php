<div id="trp-main-settings" class="wrap">
    <form method="post" action="options.php">
        <?php settings_fields( 'trp_machine_translation_settings' ); ?>
        <h1> <?php esc_html_e( 'TranslatePress Automatic Translation', 'translatepress-multilingual' );?></h1>
        <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

        <table id="trp-options" class="form-table trp-machine-translation-options">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable Automatic Translation', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <select id="trp-machine-translation-enabled" name="trp_machine_translation_settings[machine-translation]" class="trp-select">
                        <option value="no" <?php selected( $this->settings['trp_machine_translation_settings']['machine-translation'], 'no' ); ?>><?php esc_html_e( 'No', 'translatepress-multilingual') ?></option>
                        <option value="yes" <?php selected( $this->settings['trp_machine_translation_settings']['machine-translation'], 'yes' ); ?>><?php esc_html_e( 'Yes', 'translatepress-multilingual') ?></option>
                    </select>

                    <p class="description">
                        <?php _e( 'Enable or disable the automatic translation of the site. To minimize translation costs, each untranslated string is automatically translated only once, then stored in the database.', 'translatepress-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Translation Engine', 'translatepress-multilingual' ); ?> </th>
                <td>
                    <?php $translation_engines = apply_filters( 'trp_machine_translation_engines', array() ); ?>

                    <?php foreach( $translation_engines as $engine ) : ?>
                        <label for="trp-translation-engine-<?= esc_attr( $engine['value'] ) ?>" style="margin-right:10px;">
                             <input type="radio" class="trp-translation-engine trp-radio" id="trp-translation-engine-<?= esc_attr( $engine['value'] ) ?>" name="trp_machine_translation_settings[translation-engine]" value="<?= esc_attr( $engine['value'] ) ?>" <?php checked( $this->settings['trp_machine_translation_settings']['translation-engine'], $engine['value'] ); ?>>
                             <?= $engine['label'] ?>
                        </label>
                    <?php endforeach; ?>

                    <p class="description">
                        <?php _e( 'Choose which engine you want to use in order to automatically translate your website.', 'translatepress-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <?php if( !class_exists( 'TRP_DeepL' ) ) : ?>
                <tr style="display:none;">
                    <th scope="row"></th>
                    <td>
                        <p class="trp-upsell-multiple-languages" id="trp-upsell-deepl">
                            <?php
                                $url = trp_add_affiliate_id_to_link('https://translatepress.com/?utm_source=wpbackend&utm_medium=clientsite&utm_content=deepl_upsell&utm_campaign=tpfree');
                                $lnk = sprintf( wp_kses( __( '<strong>DeepL</strong> automatic translation is available as a premium add-on in <a href="%s" class="button button-primary" target="_blank" title="TranslatePress Pro">TranslatePress PRO</a>', 'translatepress-multilingual' ), array( 'strong' => array(), 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target'=> array(), 'class' => array() ) ) ), esc_url( $url ) );
                                $lnk .= '<br/>' . __( 'By upgrading you\'ll get access to all paid add-ons, premium support and help fund the future development of TranslatePress.', 'translatepress-multilingual' );
                                echo $lnk;
                            ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>

            <?php do_action ( 'trp_machine_translation_extra_settings_middle', $this->settings['trp_machine_translation_settings'] ); ?>

            <?php if( !empty( $machine_translator->get_api_key() ) ) : ?>
                <tr id="trp-test-api-key">
                    <th scope="row"></th>
                    <td>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=trp_test_machine_api' ) ); ?>" class="button-secondary"><?php _e( 'Test API credentials', 'translatepress-multilingual' ); ?></a>
                        <p class="description">
                            <?php _e( 'Click here to check if the selected translation engine is configured correctly.', 'translatepress-multilingual' ) ?>
                        </p>
                    </td>
                </tr>
            <?php endif; ?>

            <tr style="border-bottom: 1px solid #ccc;"></tr>

            <tr>
                <th scope=row><?php esc_html_e( 'Block Crawlers', 'translatepress-multilingual' ); ?></th>
                <td>
                    <label>
                        <input type=checkbox name="trp_machine_translation_settings[block-crawlers]" value="yes" <?php isset( $this->settings['trp_machine_translation_settings']['block-crawlers'] ) ? checked( $this->settings['trp_machine_translation_settings']['block-crawlers'], 'yes' ) : checked( '', 'yes' ); ?>>
                        <?php _e( 'Yes' , 'translatepress-multilingual' ); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e( 'Block crawlers from triggering automatic translations on your website.', 'translatepress-multilingual' ); ?>
                    </p>
                </td>
            </tr>

            <tr>
               <th scope="row"><?php esc_html_e( 'Limit machine translation / characters per day', 'translatepress-multilingual' ); ?></th>
               <td>
                   <label>
                       <input type="number" name="trp_machine_translation_settings[machine_translation_limit]" value="<?php echo isset( $this->settings['trp_machine_translation_settings']['machine_translation_limit'] ) ? $this->settings['trp_machine_translation_settings']['machine_translation_limit'] : 1000000; ?>">
                   </label>
                   <p class="description">
                       <?php esc_html_e( 'Add a limit to the number of automatically translated characters so you can better budget your project.', 'translatepress-multilingual' ); ?>
                   </p>
               </td>
           </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Today\'s character count:', 'translatepress-multilingual' ); ?></th>
                <td>
                    <strong><?php echo isset( $this->settings['trp_machine_translation_settings']['machine_translation_counter'] ) ? $this->settings['trp_machine_translation_settings']['machine_translation_counter'] : 0; ?></strong>
                    (<?php echo isset( $this->settings['trp_machine_translation_settings']['machine_translation_counter_date'] ) ? $this->settings['trp_machine_translation_settings']['machine_translation_counter_date'] : date('Y-m-d'); ?>)
                </td>
            </tr>

            <tr>
               <th scope=row><?php esc_html_e( 'Log machine translation queries.', 'translatepress-multilingual' ); ?></th>
               <td>
                   <label>
                       <input type=checkbox name="trp_machine_translation_settings[machine_translation_log]" value="yes" <?php isset( $this->settings['trp_machine_translation_settings']['machine_translation_log'] ) ? checked( $this->settings['trp_machine_translation_settings']['machine_translation_log'], 'yes' ) : checked( '', 'yes' ); ?>>
                       <?php _e( 'Yes' , 'translatepress-multilingual' ); ?>
                   </label>
                   <p class="description">
                       <?php echo wp_kses( __( 'Only enable for testing purposes. Can impact performance.<br>All records are stored in the wp_trp_machine_translation_log database table. Use a plugin like <a href="https://wordpress.org/plugins/wp-data-access/">WP Data Access</a> to browse the logs or directly from your database manager (PHPMyAdmin, etc.)', 'translatepress-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ); ?>
                   </p>
               </td>
           </tr>

            <?php do_action ( 'trp_machine_translation_extra_settings_bottom', $this->settings['trp_machine_translation_settings'] ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
