<?php

class TRP_Settings{

    protected $settings;
    protected $trp_query;
    protected $url_converter;
    protected $trp_languages;

    public function __construct( ) {
        $this->set_options();
    }

    public function get_language_switcher_options(){
        $ls_options = apply_filters( 'trp_language_switcher_output', array(
            'full-names'        => array( 'full_names'  => true, 'short_names'  => false, 'flags' => false, 'label' => __( 'Full Language Names', TRP_PLUGIN_SLUG ) ),
            'short-names'       => array( 'full_names'  => false, 'short_names'  => true, 'flags' => false, 'label' => __( 'Short Language Names', TRP_PLUGIN_SLUG ) ),
            'flags-full-names'  => array( 'full_names'  => true, 'short_names'  => false, 'flags' => true, 'label' => __( 'Flags with Full Language Names', TRP_PLUGIN_SLUG ) ),
            'flags-short-names' => array( 'full_names'  => false, 'short_names'  => true, 'flags' => true, 'label' => __( 'Flags with Short Language Names', TRP_PLUGIN_SLUG ) ),
            'only-flags'        => array( 'full_names'  => false, 'short_names'  => false, 'flags' => true, 'label' => __( 'Only Flags', TRP_PLUGIN_SLUG ) ),
        ) );
        return $ls_options;
    }

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

    public function get_settings(){
        return $this->settings;
    }

    public function register_menu_page(){
        add_options_page( 'TranslatePress', 'TranslatePress', apply_filters( 'trp_settings_capability', 'manage_options' ), 'translate-press', array( $this, 'settings_page_content' ) );
    }

    public function settings_page_content(){
//        error_log( json_encode($this->settings));
        if ( ! $this->trp_languages ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_languages = $trp->get_component( 'languages' );
        }
        $languages = $this->trp_languages->get_languages( 'english_name' );
        require_once TRP_PLUGIN_DIR . 'includes/partials/main-settings-page.php';
    }

    public function register_setting(){
        register_setting( 'trp_settings', 'trp_settings', array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $settings ){
        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );;
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

        foreach ( $settings['translation-languages'] as $language_code ){
            if ( $settings['default-language'] != $language_code ) {
                $this->trp_query->check_table( $language_code );
            }
        }

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
            $settings['shortcode-options'] = 'full-names';
        }
        if ( ! isset( $available_options[ $settings['menu-options'] ] ) ){
            $settings['menu-options'] = 'full-names';
        }
        if ( ! isset( $available_options[ $settings['floater-options'] ] ) ){
            $settings['floater-options'] = 'full-names';
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

        $settings['google-translate-codes'] = $this->trp_languages->get_iso_codes( $settings['publish-languages'] );

        return apply_filters( 'trp_extra_sanitize_settings', $settings );
    }

    public function admin_notices(){
        settings_errors( 'trp_settings' );
    }

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
            'shortcode-options'                     => 'full-names',
            'menu-options'                          => 'full-names',
            'floater-options'                       => 'full-names',
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
            $settings_option = $this->check_translation_settings( $settings_option );
        }

        $this->settings = $settings_option;
    }

    public function enqueue_scripts_and_styles( $hook ) {
        if ( $hook == 'settings_page_translate-press' ) {
            wp_enqueue_style(
                'trp-settings-style',
                TRP_PLUGIN_URL . 'assets/css/trp-back-end-style.css'
            );
            wp_enqueue_script(
                'trp-settings-script',
                TRP_PLUGIN_URL . 'assets/js/trp-back-end-script.js'
            );

            wp_enqueue_script( 'trp-select2-lib-js', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/js/select2.min.js', array( 'jquery' ) );
            wp_enqueue_style( 'trp-select2-lib-css', TRP_PLUGIN_URL . 'assets/lib/select2-lib/dist/css/select2.min.css');
        }
    }

    protected function languages_selector( $languages ){
        $selected_language_code = '';
        ?>
        <tr>
            <th scope="row"> <?php _e( 'Translation Language', TRP_PLUGIN_SLUG ) ?> </th>
            <td>
                <select id="trp-translation-language" name="trp_settings[translation-languages][]" class="trp-select2">
                    <option value=""><?php _e( 'Choose...', TRP_PLUGIN_SLUG );?></option>
                    <?php foreach( $languages as $language_code => $language_name ){ ?>
                    <option value="<?php echo $language_code; ?>" <?php if ( in_array( $language_code, $this->settings['translation-languages'] ) && $language_code != $this->settings['default-language'] ) { echo 'selected'; $selected_language_code = $language_code; } ?>>
                        <?php echo $language_name; ?>
                    </option>
                <?php }?>
                </select>
                <label>
                    <span id="trp-published-language"><b><?php _e( 'Active?', TRP_PLUGIN_SLUG ); ?></b></span>
                    <input id="trp-active-checkbox" type="checkbox" class="trp-translation-published " name="trp_settings[publish-languages][]" value="<?php echo $selected_language_code; ?>" <?php echo (  ( count ( $this->settings['translation-languages'] ) == 1 ) ||  ( in_array( $selected_language_code, $this->settings['publish-languages'] ) ) ) ? 'checked' : ''; ?>>
                </label>
                <p class="description">
                    <?php _e( 'Select the language you wish to make your website available in.<br>To select multiple languages, you will need the PRO version.', TRP_PLUGIN_SLUG ); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    public function check_translation_settings( $settings ){
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

    protected function create_menu_entries( $languages ){
        if ( ! $this->trp_languages ){
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_languages = $trp->get_component( 'languages' );
        }
        $published_languages = $this->trp_languages->get_language_names( $languages, 'english_name' );
        $published_languages['current_language'] = __( '- Current Language -', TRP_PLUGIN_SLUG );
        $languages[] = 'current_language';
        $posts = get_posts( array( 'post_type' =>'language-switcher',  'posts_per_page'   => -1  ) );

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
                'post_type' => 'language-switcher'
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
}

