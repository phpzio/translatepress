<?php

class TRP_Language_Switcher{

    protected $settings;
    protected $url_converter;

    public function __construct( $settings, $url_converter ){
        $this->settings = $settings;
        $this->url_converter = $url_converter;
        $language = $this->get_current_language();
        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = $language;
    }

    public function language_switcher(){
        ob_start();
        global $TRP_LANGUAGE;
        $current_language = $TRP_LANGUAGE;
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );
        //todo switch between templates based on settings
        require TRP_PLUGIN_DIR . 'includes/partials/language-switcher-1.php';
        return ob_get_clean();
    }

    public function get_current_language(){
        //todo add all possible ways of determining language: cookies, global define etc.
        if ( isset( $_REQUEST['lang'] ) ){
            $language_code = esc_attr( $_REQUEST['lang'] );
            if ( in_array( $language_code, $this->settings['translation-languages'] ) ) {
                return $language_code;
            }
        }else{
            return $this->url_converter->get_lang_from_url_string( );
        }

        return $this->settings['default-language'];
    }


    protected function str_lreplace( $search, $replace, $subject ) {
        $pos = strrpos($subject, $search);
        if ( $pos !== false ) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }


    protected function ends_with($haystack, $needle){
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    public function enqueue_language_switcher_scripts( ){
        wp_enqueue_script('trp-dynamic-translator', TRP_PLUGIN_URL . 'assets/js/trp-language-switcher.js', array('jquery'));

        wp_enqueue_script( 'trp-floater-language-switcher-script', TRP_PLUGIN_URL . 'assets/js/trp-floater-language-switcher.js', array( 'jquery' ) );
        wp_enqueue_style( 'trp-floater-language-switcher-style', TRP_PLUGIN_URL . 'assets/css/trp-floater-language-switcher.css' );
    }

    public function add_language_to_home_url( $url, $path, $orig_scheme, $blog_id ){
        if( is_customize_preview() || is_admin() )
            return $url;

        global $TRP_LANGUAGE;
        $abs_home = $this->url_converter->get_abs_home();
        $new_url = $abs_home . '/' . $TRP_LANGUAGE;
        if ( ! empty( $path ) ){
            $new_url .= '/' . ltrim( $path, '/' );
        }

        return apply_filters( 'trp_home_url', $new_url, $abs_home, $TRP_LANGUAGE, $path );
    }

    public function add_floater_language_switcher() {

        // Current language
        global $TRP_LANGUAGE;

        // All the published languages
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );

        // Check if we display language code or name
        $trp_floater_ls_names = ( isset( $this->settings['trp-ls-floater-names'] ) && $this->settings['trp-ls-floater-names'] == 'yes' ? 'name' : 'code' );

        // Add a specific class for when we display language code and another for language name
        $trp_floater_ls_class = ( $trp_floater_ls_names == 'name' ? 'trp-floater-ls-name' : 'trp-floater-ls-code' );

        $current_language = array();
        $other_languages = array();
        foreach( $published_languages as $code => $name ) {
            if( $code == $TRP_LANGUAGE ) {
                // $current_language = ( $trp_floater_ls_names == 'name' ? ucfirst( $name ) : strtoupper( $code ) );
                $current_language['code'] = strtoupper( $code );
                $current_language['name'] = ucfirst( $name );
            } else {
                $other_languages[$code] = $name;
            }
        }

        ?>
        <div id="trp-floater-ls" class="<?php echo $trp_floater_ls_class ?>">
            <!-- class="trp-with-flags" ----- should be added only when we display flags -->
            <div id="trp-floater-ls-current-language" class="trp-with-flags">
                <a href="javascript:void(0)" class="trp-floater-ls-disabled-language" onclick="void(0)"><?php echo $this->add_flag( $current_language['code'], $current_language['name'] ); echo ( $trp_floater_ls_names == 'name' ? $current_language['name'] : $current_language['code'] ); ?></a>
            </div>
            <!-- class="trp-with-flags" ----- should be added only when we display flags -->
            <div id="trp-floater-ls-language-list" class="trp-with-flags">
                <?php
                foreach( $other_languages as $code => $name ) {
                    $language_label = ( $trp_floater_ls_names == 'name' ? $name : strtoupper( $code ) )
                    ?>
                    <a href="javascript:void(0)" title="<?php echo $name; ?>" onclick="trp_floater_change_language( '<?php echo $code; ?>' )"><?php echo $this->add_flag( $code, $name ); echo $language_label; ?></a>
                <?php
                }
                ?>
                <a href="javascript:void(0)" class="trp-floater-ls-disabled-language"><?php echo $this->add_flag( $current_language['code'], $current_language['name'] ); echo ( $trp_floater_ls_names == 'name' ? $current_language['name'] : $current_language['code'] ); ?></a>
            </div>
        </div>

    <?php
    }

    public function add_flag( $language_code, $language_name ) {

        // Path to folder with flags images
        $flags_path = TRP_PLUGIN_URL .'assets/images/flags/';
        $flags_path = apply_filters( 'trp_flags_path', $flags_path, $language_code );

        // File name for specific flag
        $flag_file_name = $language_code .'.png';
        $flag_file_name = apply_filters( 'trp_flag_file_name', $flag_file_name, $language_code );

        // HTML code to display flag image
        $flag_html = '<img class="trp-flag-image" src="'. $flags_path . $flag_file_name .'" width="18" height="12" alt="' . $language_code . '" title="' . $language_name . '">';

        return $flag_html;

    }

}