<?php

class TRP_Settings{

    protected $settings;
    protected $trp_query;

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

    public function __construct( ) {
        $this->set_options();
    }

    public function get_settings(){
        return $this->settings;
    }

    public function set_trp_query( $trp_query ){
        $this->trp_query = $trp_query;
    }

    public function register_menu_page(){
        add_options_page( 'Translate Press', 'Translate Press', apply_filters( 'trp_settings_capability', 'manage_options' ), 'translate-press', array( $this, 'settings_page_content' ) );
    }

    public function settings_page_content(){
        $languages = TRP_Utils::get_languages();
        require_once TRP_PLUGIN_DIR . 'includes/admin/partials/main-settings-page.php';
    }

    public function register_setting(){
        register_setting( 'trp_settings', 'trp_settings', array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $settings ){
        error_log( json_encode( $settings ));
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

        $settings['google-translate-codes'] = TRP_Utils::get_google_translate_codes( $settings['publish-languages'] );



        return apply_filters( 'trp_extra_sanitize_settings', $settings );
    }

    public function admin_notices(){
        settings_errors( 'trp_settings' );
    }

    protected function set_options(){
        $settings = get_option( 'trp_settings', 'not_set' );

        if ( 'not_set' == $settings ){
            // initialize default settings
            $default = get_locale();
            if ( empty( $default ) ){
                $default = 'en_US';
            }
            $settings = array(
                'default-language'      => $default,
                'translation-languages' => array( $default ),
                'publish-languages'     => array( $default ),
                'g-translate'           => 'no',
                'trp-ls-floater'        => 'yes',
                'shortcode-options'     => 'full-names',
                'menu-options'          => 'full-names',
                'floater-options'       => 'full-names',
                'url-slugs'             => array( 'en' => 'en' ),
            );
            update_option ( 'trp_settings', $settings );
        }

        $this->settings = $settings;
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
        ?>
        <tr>
            <th scope="row"> <?php _e( 'Translation Language', TRP_PLUGIN_SLUG ) ?> </th>
            <td>
                <select id="trp-translation-language" name="trp_settings[translation-languages][]" class="trp-select2">
                    <option value=""><?php _e( 'Choose...', TRP_PLUGIN_SLUG );?></option>
                    <?php foreach( $languages as $language_code => $language_name ){ ?>
                    <option value="<?php echo $language_code; ?>" <?php echo ( in_array( $language_code, $this->settings['translation-languages'] ) && $language_code != $this->settings['default-language'] ) ? 'selected' : '' ; ?>>
                        <?php echo $language_name; ?>
                    </option>
                <?php }?>
                </select>
                <label>
                    <input type="checkbox" class="trp-translation-published" name="trp_settings[publish-languages][]" value="<?php $language_code = array_values(array_slice($this->settings['translation-languages'], -1)); /*last element*/ echo $language_code[0]; ?>" <?php echo ( in_array( $language_code, $this->settings['publish-languages'] ) && ( $language_code != $this->settings['default-language'] ) ) ? 'checked' : ''; ?>>
                    <span id="trp-published-language"><b><?php _e( 'Publish', TRP_PLUGIN_SLUG ); ?></b></span>
                </label>
                <p class="description">
                    <?php _e( 'Select the language you wish to make your website available in.<br>To select multiple languages, you will need the PRO version.', TRP_PLUGIN_SLUG ); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    protected function create_menu_entries( $languages ){
        $published_languages = TRP_Utils::get_language_names( $languages );

        foreach ( $published_languages as $language_code => $language_name ) {
            $existing_ls = get_page_by_title( $language_name, OBJECT, 'language-switcher'  );
            if ( $existing_ls == null ) {
                $ls = array(
                    'post_title' => $language_name,
                    'post_content' => $language_code,
                    'post_status' => 'publish',
                    'post_type' => 'language-switcher'
                );
                wp_insert_post($ls);
            }
        }

        $posts = get_posts( array( 'post_type' =>'language-switcher',  'posts_per_page'   => -1  ) );
        foreach ( $posts as $post ){
            if ( ! in_array( $post->post_content, $languages ) ){
                wp_delete_post( $post->ID );
            }
        }
    }
}

