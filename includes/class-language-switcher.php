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

    public function add_ls_to_menu( $items, $args ){
        if ( isset ( $this->settings['trp-ls-menu'] ) && $this->settings['trp-ls-menu'] == 'yes' && !empty ( $this->settings['trp-ls-menu-select'] ) ) {
            // todo this
            if ( isset( $args->menu->term_id ) && $args->menu->term_id == $this->settings['trp-ls-menu-select'] ) {
                ob_start();
                global $TRP_LANGUAGE;
                $current_language = $TRP_LANGUAGE;
                $published_languages = TRP_Utils::get_language_names($this->settings['publish-languages']);
                require TRP_PLUGIN_DIR . 'includes/partials/language-switcher-menu.php';
                $ls_html = ob_get_clean();
                $items .= $ls_html;
            }
        }
        return $items;
    }
}