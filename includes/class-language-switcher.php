<?php

class TRP_Language_Switcher{

    protected $settings;
    protected $url_converter;

    public function __construct( $settings ){
        $this->settings = $settings;
        $this->url_converter = new TRP_Url_Converter( $settings );
        $language = $this->get_current_language();
        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = $language;
    }

    public function language_switcher(){
        get_home_url();
        ob_start();
        global $TRP_LANGUAGE;
        $current_language = $TRP_LANGUAGE;
        $published_languages = TRP_Utils::get_language_names( $this->settings['publish-languages'] );
        //todo switch between templates based on settings
        require_once TRP_PLUGIN_DIR . 'includes/partials/language-switcher-1.php';
        return ob_get_clean();
    }

    public function get_current_language(){
        /*return 'en';*/

        //todo add all possible ways of determining language: cookies, global define etc.
        if ( isset( $_GET['lang'] ) ){
            $language_code = esc_attr( $_GET['lang'] );
            if ( in_array( $language_code, $this->settings['translation-languages'] ) ) {
                return $language_code;
            }
        }else{
            return $this->url_converter->get_lang_from_url_string( );
        }

        return $this->settings['default-language'];
    }

    public function add_rewrite_rules(){
//        add_rewrite_rule('^ro/', '^ro/$matches[1]?lang=ro', 'top');
        //add_rewrite_tag('%lang%', '([^&]+)');
        //add_rewrite_rule('^ro/*', 'index.php?lang=ro', 'top');

        //add_rewrite_rule('^ro/(.*)', '?lang=ro', 'top');
        //only when changing languages
        //flush_rewrite_rules();


        //die('ffffffuuuuu');


    }

    protected function str_lreplace( $search, $replace, $subject ) {
        $pos = strrpos($subject, $search);
        if ( $pos !== false ) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }

    public function add_language_to_home_url( $url, $path, $orig_scheme, $blog_id ){
//        error_log('url   ' . $url);
//        error_log('path   ' .  $path);
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

        if( $new_stripped_path_url != 'http://local.profile-builder.dev' ) {
            //error_log( 'spa   ' . $stripped_path_url );
        }

        $return_url = $new_stripped_path_url . '/' . $TRP_LANGUAGE . $path;

        if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ){
            add_query_arg( 'trp-edit-translation', 'preview', $return_url );
        }

        return $return_url;
    }
}