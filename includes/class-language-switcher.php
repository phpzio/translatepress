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

        // TODO: Floater
        add_action( 'wp_footer', array( $this, 'add_floater_language_switcher' ) );
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
        // TODO: Floater
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

    // TODO: Floater
    public function add_floater_language_switcher() {

        global $TRP_LANGUAGE;

        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );

        $trp_floater_ls_names = ( isset( $this->settings['trp-ls-floater-names'] ) && $this->settings['trp-ls-floater-names'] == 'yes' ? 'name' : 'code' );
        $trp_floater_ls_class = ( $trp_floater_ls_names == 'name' ? 'trp-floater-ls-name' : 'trp-floater-ls-code' );

        $current_language = '';
        $other_languages = array();
        foreach( $published_languages as $code => $name ) {
            if( $code == $TRP_LANGUAGE ) {
                $current_language = ( $trp_floater_ls_names == 'name' ? ucfirst( $name ) : strtoupper( $code ) );
            } else {
                $other_languages[$code] = $name;
            }
        }

        ?>
        <div id="trp-floater-ls" class="<?php echo $trp_floater_ls_class ?>">
            <div id="trp-floater-ls-current-language">
                <a href="javascript:void(0)" class="trp-floater-ls-disabled-language" onclick="void(0)"><?php echo $current_language ?></a>
            </div>
            <div id="trp-floater-ls-language-list">
                <?php
                foreach( $other_languages as $code => $name ) {
                    $language_label = ( $trp_floater_ls_names == 'name' ? $name : strtoupper( $code ) )
                    ?>
                    <a href="javascript:void(0)" title="<?php echo $name ?>" onclick="trp_floater_change_language( '<?php echo $code ?>' )"><?php echo $language_label ?></a>
                <?php
                }
                ?>
                <a href="javascript:void(0)" class="trp-floater-ls-disabled-language"><?php echo $current_language ?></a>
            </div>
        </div>

    <?php
    }

}