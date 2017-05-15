<?php

class TRP_Settings{

    protected $settings;
    protected $trp_query;

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

        // TODO add icon url, and menu position in menu page.
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

        if ( !isset ( $settings['default-language'] ) ) {
            $settings['default-language'] = 'en';
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
        //error_log( json_encode( $settings ));

        return apply_filters( 'trp_extra_sanitize_settings', $settings );
    }

    public function admin_notices(){
        settings_errors( 'trp_settings' );
    }

    protected function set_options(){
        $settings = get_option( 'trp_settings', 'not_set' );

        if ( 'not_set' == $settings ){
            // initialize default settings
            //todo set default language based on get_locale(). https://wpcentral.io/internationalization/ for a full list
            $default = 'en';
            $settings = array(
                'default-language'      => $default,
                'translation-languages' => array( $default ),
                'publish-languages'     => array( $default )
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
}

