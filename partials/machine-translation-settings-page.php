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

            <tr>
                <th scope="row"></th>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=trp_test_google_key_page' ) ); ?>" class="button-secondary"><?php _e( 'Test API credentials', 'translatepress-multilingual' ); ?></a>
                    <p class="description">
                        <?php _e( 'Click here to check if the selected translation engine is configured correctly.', 'translatepress-multilingual' ) ?>
                    </p>
                </td>
            </tr>

            <tr style="border-bottom: 1px solid #ccc;">
                <th scope="row"></th>
                <td></td>
            </tr>

            <tr>
               <th scope="row"><?php esc_html_e( 'Limit machine translation / characters per day', 'translatepress-multilingual' ); ?></th>
               <td>
                   <label>
                       <input type="number" name="trp_machine_translation_settings[machine_translation_limit]" value="<?php echo isset( $this->settings['machine_translation_limit'] ) ? $this->settings['machine_translation_limit'] : 1000000; ?>">
                   </label>
                   <p class="description">
                       <?php esc_html_e( 'Add a limit to the number of automatically translated characters so you can better budget your project.', 'translatepress-multilingual' ); ?>
                   </p>
               </td>
           </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Today\'s character count:', 'translatepress-multilingual' ); ?></th>
                <td>
                    <?php echo isset( $this->settings['machine_translation_counter'] ) ? $this->settings['machine_translation_counter'] : 0; ?>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Today: ', 'translatepress-multilingual' ); ?></th>
                <td>
                    <?php echo isset( $this->settings['machine_translation_counter_date'] ) ? $this->settings['machine_translation_counter_date'] : date('Y-m-d'); ?>
                </td>
            </tr>

            <tr>
               <th scope=row><?php esc_html_e( 'Log machine translation queries.', 'translatepress-multilingual' ); ?></th>
               <td>
                   <label>
                       <input type=checkbox name="trp_machine_translation_settings[machine_translation_log]" value="yes" <?php isset( $this->settings['machine_translation_log'] ) ? checked( $this->settings['machine_translation_log'], 'yes' ) : checked( '', 'yes' ); ?>>
                       <?php _e( 'Yes' , 'translatepress-multilingual' ); ?>
                   </label>
                   <p class="description">
                       <?php echo wp_kses( __( 'Only enable for testing purposes. Can impact performance.<br>All records are stored in the wp_trp_machine_translation_log database table. Use a plugin like <a href="https://wordpress.org/plugins/wp-data-access/">WP Data Access</a> to browse the logs or directly from your database manager (PHPMyAdmin, etc.)', 'translatepress-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ); ?>
                   </p>
               </td>
           </tr>

            <?php do_action ( 'trp_machine_translation_extra_settings_bottom', $this->settings ); ?>
        </table>

        <p class="submit"><input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    </form>
</div>
