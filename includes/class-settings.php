<?php

/**
 * Class TRP_Settings
 *
 * In charge of settings page and settings option.
 */
class TRP_Settings{

    protected $settings;
    protected $trp_query;
    protected $url_converter;
    protected $trp_languages;

    /**
     * Return array of customization options for language switchers.
     *
     * Customization options include whether to add flags, full names or short names.
     * Used for all types of language switchers.
     *
     * @return array            Array with customization options.
     */
    public function get_language_switcher_options(){
        $ls_options = apply_filters( 'trp_language_switcher_output', array(
            'full-names'        => array( 'full_names'  => true, 'short_names'  => false, 'flags' => false, 'label' => __( 'Full Language Names', 'translatepress-multilingual' ) ),
            'short-names'       => array( 'full_names'  => false, 'short_names'  => true, 'flags' => false, 'label' => __( 'Short Language Names', 'translatepress-multilingual' ) ),
            'flags-full-names'  => array( 'full_names'  => true, 'short_names'  => false, 'flags' => true, 'label' => __( 'Flags with Full Language Names', 'translatepress-multilingual' ) ),
            'flags-short-names' => array( 'full_names'  => false, 'short_names'  => true, 'flags' => true, 'label' => __( 'Flags with Short Language Names', 'translatepress-multilingual' ) ),
            'only-flags'        => array( 'full_names'  => false, 'short_names'  => false, 'flags' => true, 'label' => __( 'Only Flags', 'translatepress-multilingual' ) ),
        ) );
        return $ls_options;
    }

    /**
     * Echo html for selecting language from all available language in settings.
     *
     * @param string $ls_type       shortcode_options | menu_options | floater_options
     * @param string $ls_setting    The selected language switcher customization setting (get_language_switcher_options())
     */
    public function output_language_switcher_select( $ls_type, $ls_setting ){
        $ls_options = $this->get_language_switcher_options();
        $output = '<select id=' . $ls_type . ' name=trp_settings[' . $ls_type .'] class="trp-select trp-ls-select-option">';
        foreach( $ls_options as $key => $ls_option ){
            $selected = ( $ls_setting == $key ) ? 'selected' : '';
            $output .= '<option value="' . $key . '" ' . $selected . ' >' . $ls_option['label'] . '</option>';
        }
        $output .= '</select>';

        echo $output;
    }

    /**
     * Returns settings_option.
     *
     * @return array        Settings option.
     */
    public function get_settings(){
        if ( $this->settings == null ){
            $this->set_options();
        }
        return $this->settings;
    }

    /**
     * Register Settings subpage for TranslatePress
     */
    public function register_menu_page(){
        add_options_page( 'TranslatePress', 'TranslatePress', apply_filters( 'trp_settings_capability', 'manage_options' ), 'translate-press', array( $this, 'settings_page_content' ) );
        add_submenu_page( 'TRPHidden', 'TranslatePress Addons', 'TRPHidden', 'manage_options', 'trp_addons_page', array($this, 'addons_page_content') );
        add_submenu_page( 'TRPHidden', 'TranslatePress Test Google API Key', 'TRPHidden', 'manage_options', 'trp_test_google_key_page', array($this, 'test_google_key_page_content') );
    }

    /**
     * Settings page content.
     */
    public function settings_page_content(){
        if ( ! $this->trp_languages ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_languages = $trp->get_component( 'languages' );
        }
        $languages = $this->trp_languages->get_languages( 'english_name' );
        require_once TRP_PLUGIN_DIR . 'partials/main-settings-page.php';
    }

    /**
     * Addons page content.
     */
    public function addons_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/addons-settings-page.php';
    }

    /**
     * Addons page content.
     */
    public function test_google_key_page_content(){
        require_once TRP_PLUGIN_DIR . 'partials/test-google-key-settings-page.php';
    }

    /**
     * Register settings option.
     */
    public function register_setting(){
        register_setting( 'trp_settings', 'trp_settings', array( $this, 'sanitize_settings' ) );
    }

    /**
     * Sanitizes settings option after save.
     *
     * Updates menu items for languages to be used in Menus.
     *
     * @param array $settings       Raw settings option.
     * @return array                Sanitized option page.
     */
    public function sanitize_settings( $settings ){
        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );
        }
        if ( ! $this->trp_languages ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_languages = $trp->get_component( 'languages' );
        }
        if ( !isset ( $settings['default-language'] ) ) {
            $settings['default-language'] = 'en_US';
        }
        if ( !isset ( $settings['translation-languages'] ) ){
            $settings['translation-languages'] = array();
        }
        if ( !isset ( $settings['publish-languages'] ) ){
            $settings['publish-languages'] = array();
        }

        $settings['translation-languages'] = array_filter( array_unique( $settings['translation-languages'] ) );
        $settings['publish-languages'] = array_filter( array_unique( $settings['publish-languages'] ) );

        if ( ! in_array( $settings['default-language'], $settings['translation-languages'] ) ){
            array_unshift( $settings['translation-languages'], $settings['default-language'] );
        }
        if ( ! in_array( $settings['default-language'], $settings['publish-languages'] ) ){
            array_unshift( $settings['publish-languages'], $settings['default-language'] );
        }


        if( !empty( $settings['g-translate'] ) )
            $settings['g-translate'] = sanitize_text_field( $settings['g-translate']  );
        else
            $settings['g-translate'] = 'no';



        if( !empty( $settings['g-translate-key'] ) )
            $settings['g-translate-key'] = sanitize_text_field( $settings['g-translate-key']  );

        if( !empty( $settings['native_or_english_name'] ) )
            $settings['native_or_english_name'] = sanitize_text_field( $settings['native_or_english_name']  );
        else
            $settings['native_or_english_name'] = 'english_name';

        if( !empty( $settings['add-subdirectory-to-default-language'] ) )
            $settings['add-subdirectory-to-default-language'] = sanitize_text_field( $settings['add-subdirectory-to-default-language']  );
        else
            $settings['add-subdirectory-to-default-language'] = 'no';

        if( !empty( $settings['force-language-to-custom-links'] ) )
            $settings['force-language-to-custom-links'] = sanitize_text_field( $settings['force-language-to-custom-links']  );
        else
            $settings['force-language-to-custom-links'] = 'no';


        if ( !empty( $settings['trp-ls-floater'] ) ){
            $settings['trp-ls-floater'] = sanitize_text_field( $settings['trp-ls-floater'] );
        }else{
            $settings['trp-ls-floater'] = 'no';
        }

        $available_options = $this->get_language_switcher_options();
        if ( ! isset( $available_options[ $settings['shortcode-options'] ] ) ){
            $settings['shortcode-options'] = 'flags-full-names';
        }
        if ( ! isset( $available_options[ $settings['menu-options'] ] ) ){
            $settings['menu-options'] = 'flags-full-names';
        }
        if ( ! isset( $available_options[ $settings['floater-options'] ] ) ){
            $settings['floater-options'] = 'flags-full-names';
        }

        if ( ! isset( $settings['url-slugs'] ) ){
            $settings['url-slugs'] = $this->trp_languages->get_iso_codes( $settings['translation-languages'] );
        }

        foreach( $settings['translation-languages'] as $language_code ){
            if ( empty ( $settings['url-slugs'][$language_code] ) ){
                $settings['url-slugs'][$language_code] = $language_code;
            }else{
                $settings['url-slugs'][$language_code] = sanitize_title( strtolower( $settings['url-slugs'][$language_code] )) ;
            }
        }

        // check for duplicates in url slugs
        $duplicate_exists = false;
        foreach( $settings['url-slugs'] as $urlslug ) {
            if ( count ( array_keys( $settings['url-slugs'], $urlslug ) ) > 1 ){
                $duplicate_exists = true;
                break;
            }
        }
        if ( $duplicate_exists ){
            foreach( $settings['translation-languages'] as $language_code ) {
                $settings['url-slugs'][$language_code] = $language_code;
            }
        }

        $this->create_menu_entries( $settings['publish-languages'] );

        require_once( ABSPATH . 'wp-includes/load.php' );
        foreach ( $settings['translation-languages'] as $language_code ){
            if ( $settings['default-language'] != $language_code ) {
                $this->trp_query->check_table( $settings['default-language'], $language_code );
            }
            wp_download_language_pack( $language_code );
            $this->trp_query->check_gettext_table( $language_code );
        }

        $settings['google-translate-codes'] = $this->trp_languages->get_iso_codes( $settings['publish-languages'] );

        return apply_filters( 'trp_extra_sanitize_settings', $settings );
    }

    /**
     * Output admin notices after saving settings.
     */
    public function admin_notices(){
        settings_errors( 'trp_settings' );
    }

    /**
     * Set options array variable to be used across plugin.
     *
     * Sets a default option if it does not exist.
     */
    protected function set_options(){
        $settings_option = get_option( 'trp_settings', 'not_set' );

        // initialize default settings
        $default = get_locale();
        if ( empty( $default ) ){
            $default = 'en_US';
        }
        $default_settings = array(
            'default-language'                      => $default,
            'translation-languages'                 => array( $default ),
            'publish-languages'                     => array( $default ),
            'native_or_english_name'                => 'english_name',
            'add-subdirectory-to-default-language'  => 'no',
            'force-language-to-custom-links'        => 'no',
            'g-translate'                           => 'no',
            'trp-ls-floater'                        => 'yes',
            'shortcode-options'                     => 'flags-full-names',
            'menu-options'                          => 'flags-full-names',
            'floater-options'                       => 'flags-full-names',
            'url-slugs'                             => array( 'en_US' => 'en' ),
        );
        if ( 'not_set' == $settings_option ){
            update_option ( 'trp_settings', $default_settings );
            $settings_option = $default_settings;
        }else{
            foreach ( $default_settings as $key_default_setting => $value_default_setting ){
                if ( !isset ( $settings_option[$key_default_setting] ) ) {
                    $settings_option[$key_default_setting] = $value_default_setting;
                }
            }
            $settings_option = $this->check_settings_option( $settings_option, $default_settings );

        }

        $this->settings = $settings_option;
    }

    /**
     * Enqueue scripts and styles for settings page.
     *
     * @param string $hook          Admin page.
     */
    public function enqueue_scripts_and_styles( $hook ) {
        if ( $hook == 'settings_page_translate-press' || 'settings_page_trp_license_key' ) {
            wp_enqueue_style(
                'trp-settings-style',
                TRP_PLUGIN_URL . 'assets/css/trp-back-end-style.css',
                array(),
                TRP_PLUGIN_VERSION
            );
        }

        if ( $hook == 'settings_page_translate-press' ) {
            wp_enqueue_script( 'trp-settings-script', TRP_PLUGIN_URL . 'assets/js/trp-back-end-script.js', array( 'jquery' ), TRP_PLUGIN_VERSION );
            if ( ! $this->trp_languages ){
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->trp_languages = $trp->get_component( 'languages' );
            }
            $all_language_codes = $this->trp_languages->get_all_language_codes();
            $iso_codes = $this->trp_languages->get_iso_codes( $all_language_codes, false );
            wp_localize_script( 'trp-settings-script', 'trp_iso_codes', $iso_codes );

            wp_enqueue_script( 'trp-select2-lib-js', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/js/select2.min.js', array( 'jquery' ), TRP_PLUGIN_VERSION );
            wp_enqueue_style( 'trp-select2-lib-css', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/css/select2.min.css', array(), TRP_PLUGIN_VERSION );
        }
    }

    /**
     * Output HTML for Translation Language option.
     *
     * Hooked to trp_language_selector.
     *
     * @param array $languages          All available languages.
     */
    public function languages_selector( $languages ){
        if ( ! $this->url_converter ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component('url_converter');
        }
        $selected_language_code = '';
        ?>
        <tr>
            <th scope="row"> <?php _e( 'Translation Languages', translatepress-multilingual ) ?> </th>
            <td>
                <table id="trp-languages-table">
                    <thead>
                    <tr>
                        <th colspan="2"><?php _e( 'Language', translatepress-multilingual ); ?></th>
                        <th><?php _e( 'Slug', translatepress-multilingual ); ?></th>
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
                                        <option title="<?php echo $language_code; ?>" value="<?php echo $language_code; ?>" <?php echo ( $language_code == $selected_language_code ) ? 'selected' : ''; ?>>
                                            <?php echo $language_name; ?>
                                        </option>
                                    <?php }?>
                                </select>
                            </td>
                            <td>
                                <input class="trp-language-slug" name="trp_settings[url-slugs][<?php echo $selected_language_code ?>]" type="text" style="text-transform: lowercase;" value="<?php echo $this->url_converter->get_url_slug( $selected_language_code, false ); ?>">
                                <input type="hidden" class="trp-hidden-default-language" name="trp_settings[publish-languages][]" value="<?php echo $selected_language_code;?>" />
                            </td>
                        </tr>
                    <?php }?>
                    </tbody>
                </table>
                <div id="trp-new-language">
                    <select id="trp-select-language" class="trp-select2 trp-translation-language" >
                        <option value=""><?php _e( 'Choose...', translatepress-multilingual );?></option>
                        <?php foreach( $languages as $language_code => $language_name ){ ?>
                            <option title="<?php echo $language_code; ?>" value="<?php echo $language_code; ?>">
                                <?php echo $language_name; ?>
                            </option>
                        <?php }?>
                    </select>
                    <button type="button" id="trp-add-language" class="button-secondary"><?php _e( 'Add', translatepress-multilingual );?></button>
                    <script>
                        jQuery(document).ready(function() {
                            jQuery('#trp-add-language').on("click", function(){
                                jQuery(".trp-upsell-multiple-languages").show('fast');
                            })
                        })
                    </script>
                </div>
                <p class="description">
                    <?php
                        _e( 'Select the language you wish to make your website available in.', 'translatepress-multilingual');
                    ?>
                </p>
                <p class="trp-upsell-multiple-languages" style="display: none;">
                    <?php
                    $url = trp_add_affiliate_id_to_link('https://translatepress.com/?utm_source=wpbackend&utm_medium=clientsite&utm_content=multiple_languages&utm_campaign=tpfree');
                    $link = sprintf( wp_kses( __( 'To add <strong>more then two languages</strong> and support for SEO Title, Description, Slug and more checkout <a href="%s" class="button button-primary" target="_blank" title="TranslatePress Pro">TranslatePress PRO</a>', 'translatepress-multilingual' ), array( 'strong' => array(), 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target'=> array(), 'class' => array() ) ) ), esc_url( $url ) );
                    $link .= '<br/>' . __('Not only you are getting extra features and premium support, you also help fund the future development of TranslatePress.', 'translatepress-multilingual');
                    echo $link;
                    ?>
                </p>
            </td>
        </tr>

        <?php
    }

    /**
     * Validate settings option.
     *
     * @param array $settings               Settings option.
     * @param array $default_settings       Default settings option.
     * @return array                        Validated settings option.
     */
    public function check_settings_option( $settings, $default_settings ){
        if ( class_exists( 'TRP_Extra_Languages' ) ){
            // checks are made in the Add-on later
            return $settings;
        }
        foreach ( $settings['translation-languages'] as $language_code ) {
            if ( $settings['default-language'] != $language_code ) {
                $translation_language = $language_code;
                break;
            }
        }
        $settings['translation-languages'] = array( $settings['default-language'] );
        if ( !empty( $translation_language ) ){
            $settings['translation-languages'][] = $translation_language;
        }

        foreach ( $settings['publish-languages'] as $language_code ) {
            if ( $settings['default-language'] != $language_code ) {
                $translation_language = $language_code;
                break;
            }
        }
        $settings['publish-languages'] = array( $settings['default-language'] );
        if ( !empty( $translation_language ) ){
            $settings['publish-languages'][] = $translation_language;
        }

        return $settings;
    }

    /**
     * Update language switcher menu items.
     *
     * @param array $languages          Array of language codes to create menu items for.
     */
    protected function create_menu_entries( $languages ){
        if ( ! $this->trp_languages ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_languages = $trp->get_component( 'languages' );
        }
        $published_languages = $this->trp_languages->get_language_names( $languages, 'english_name' );
        $published_languages['current_language'] = __( 'Current Language', 'translatepress-multilingual' );
        $languages[] = 'current_language';
        $posts = get_posts( array( 'post_type' =>'language_switcher',  'posts_per_page'   => -1  ) );

        foreach ( $published_languages as $language_code => $language_name ) {
            $existing_ls = null;
            foreach( $posts as $post ){
                if ( $post->post_content == $language_code ){
                    $existing_ls = $post;
                    break;
                }
            }

            $ls = array(
                'post_title' => $language_name,
                'post_content' => $language_code,
                'post_status' => 'publish',
                'post_type' => 'language_switcher'
            );
            if ( $existing_ls ){
                $ls['ID'] = $existing_ls->ID;
                wp_update_post( $ls );
            }else{
                wp_insert_post( $ls );
            }
        }

        foreach ( $posts as $post ){
            if ( ! in_array( $post->post_content, $languages ) ){
                wp_delete_post( $post->ID );
            }
        }
    }

    /**
     * Add navigation tabs in settings.
     *
     */
    public function add_navigation_tabs(){
        $tabs = apply_filters( 'trp_settings_tabs', array(
            array(
                'name'  => __( 'General', 'translatepress-multilingual' ),
                'url'   => admin_url( 'options-general.php?page=translate-press' ),
                'page'  => 'translate-press'
            ),
            array(
                'name'  => __( 'Translate Site', 'translatepress-multilingual' ),
                'url'   => add_query_arg( 'trp-edit-translation', 'true', home_url() ),
                'page'  => 'trp_translation_editor'
            )
        ));

        if( class_exists('TRP_LICENSE_PAGE') ) {
            $tabs[] = array(
                'name'  => __( 'License', 'translatepress-multilingual' ),
                'url'   => admin_url( 'admin.php?page=trp_license_key' ),
                'page'  => 'trp_license_key'
            );
        }
        else{
            $tabs[] = array(
                'name'  => __( 'Addons', 'translatepress-multilingual' ),
                'url'   => admin_url( 'admin.php?page=trp_addons_page' ),
                'page'  => 'trp_addons_page'
            );
        }

        $active_tab = 'translate-press';
        if ( isset( $_GET['page'] ) ){
            $active_tab = esc_attr( $_GET['page'] );
        }

        require ( TRP_PLUGIN_DIR . 'partials/settings-navigation-tabs.php');
    }

}

