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

    public function add_language_to_home_url( $url, $path, $orig_scheme, $blog_id ){
        //return $url;
//        error_log('url   ' . $url);
//        error_log('path   ' .  $path);

        if( is_customize_preview() || is_admin() )
            return $url;

        //todo this is not very reliable. use get_abs_home() + language + $path to construct the url rather than manipulating $url
        global $TRP_LANGUAGE;
        if( $path != '' ) {
            $stripped_path_url = $this->str_lreplace($path, '', $url);
        }else{
            $stripped_path_url = $url;
            $path = '/';
        }

        $new_stripped_path_url = rtrim( $stripped_path_url, '/' );
        if ( $stripped_path_url != $new_stripped_path_url ){
            $path = '/' . $path;
        }

        $return_url = $new_stripped_path_url . '/' . $TRP_LANGUAGE . $path;

      /*  if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ){
            error_log( 'adadadad' );
            $return_url = add_query_arg( 'trp-edit-translation', 'preview', $return_url );
        }*/

        return $return_url;
    }
}