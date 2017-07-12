<?php

class TRP_Url_Converter {

    protected $absolute_home;
    protected $settings;

    // wpml-url-converter.class
    public function __construct( $settings ){
        $this->settings = $settings;
    }


    public function redirect_to_default_language() {
        global $TRP_LANGUAGE;
        if ( isset( $this->settings['add-subdirectory-to-default-language'] ) && $this->settings['add-subdirectory-to-default-language'] == 'no' && $TRP_LANGUAGE == $this->settings['default-language'] ) {
            return;
        }

        $lang_from_url = $this->get_lang_from_url_string( $this->cur_page_url() );
        if ( $lang_from_url == null ) {
            header( 'Location: ' . $this->get_url_for_language( $this->settings['default-language'] ) );
            exit;
        }
    }

    public function add_language_to_home_url( $url, $path, $orig_scheme, $blog_id ){
        global $TRP_LANGUAGE;
        if ( isset( $this->settings['add-subdirectory-to-default-language'] ) && $this->settings['add-subdirectory-to-default-language'] == 'no' && $TRP_LANGUAGE == $this->settings['default-language'] ) {
            return $url;
        }

        if( is_customize_preview() || is_admin() )
            return $url;


        $url_slug = $this->get_url_slug( $TRP_LANGUAGE );
        $abs_home = $this->get_abs_home();
        $new_url = trailingslashit( $abs_home ) . $url_slug;
        if ( ! empty( $path ) ){
            $new_url .= '/' . ltrim( $path, '/' );
        }
        //var_dump( $new_url );

        return apply_filters( 'trp_home_url', $new_url, $abs_home, $TRP_LANGUAGE, $path );
    }

    public function add_hreflang_to_head(){
        $languages = $this->settings['publish-languages'];
        if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) {
            $languages = $this->settings['translation-languages'];
        }

        foreach ( $languages as $language ) {
            echo '<link rel="alternate" hreflang="' . $language . '" href="' . $this->get_url_for_language( $language ) . '"/>';
        }
    }

    public function get_url_for_language ( $language = null, $url = null ) {
        global $post, $TRP_LANGUAGE;
        $trp_language_copy = $TRP_LANGUAGE;
        $new_url = '';
        if ( empty( $language ) ) {
            $language = $TRP_LANGUAGE;
        }
        $url_slug = $this->get_url_slug( $language );
        if ( empty( $url ) && is_object( $post ) && ( !is_home() ) ) {
            $TRP_LANGUAGE = $language;
            $new_url = get_permalink( $post->ID );
            $TRP_LANGUAGE = $trp_language_copy;
        }else{
            if ( empty( $url ) ) {
                $url = $this->cur_page_url();
            }
            $abs_home = trailingslashit( $this->get_abs_home() );
            $prefixes = apply_filters( 'trp_prefix_abs_home', array( $abs_home . $this->get_url_slug( $TRP_LANGUAGE ) . '/', $abs_home ) );
            foreach( $prefixes as $prefix ){
                if ( substr( $url, 0, strlen( $prefix ) ) == $prefix ) {
                    $path = substr($url, strlen($prefix));
                    $path = ltrim($path, '/');
                    $new_url = $abs_home . $url_slug . '/' . $path;
                    break;
                }
            }
        }

        if ( empty( $new_url ) ) {
            $new_url = $url;
        }

        return $new_url;
    }

    public function get_url_slug( $language_code ){
        $url_slug = $language_code;
        if( isset( $this->settings['url-slugs'][$language_code] ) ) {
            $url_slug = $this->settings['url-slugs'][$language_code];
        }

        if ( isset( $this->settings['add-subdirectory-to-default-language'] ) && $this->settings['add-subdirectory-to-default-language'] == 'no' && $language_code == $this->settings['default-language'] ) {
            $url_slug = '';
        }

        return $url_slug;
    }

    /**
     * Returns the unfiltered home_url by directly retrieving it from wp_options.
     */
    public function get_abs_home() {
        global $wpdb;

        $this->absolute_home = $this->absolute_home
            ? $this->absolute_home
            : ( ! is_multisite() && defined( 'WP_HOME' )
                ? WP_HOME
                : ( is_multisite() && ! is_main_site()
                    ? ( preg_match( '/^(https)/', get_option( 'home' ) ) === 1 ? 'https://'
                        : 'http://' ) . $wpdb->get_var( "	SELECT CONCAT(b.domain, b.path)
									FROM {$wpdb->blogs} b
									WHERE blog_id = {$wpdb->blogid}
									LIMIT 1" )

                    : $wpdb->get_var( "	SELECT option_value
									FROM {$wpdb->options}
									WHERE option_name = 'home'
									LIMIT 1" ) )
            );

        return $this->absolute_home;
    }



    /**
     * @param string $url url either with or without schema
     *                    Removes the subdirectory in which WordPress is installed from a url.
     *                    If WordPress is not installed in a subdirectory, then the input is returned unaltered.
     *
     * @return string the url input without the blog's subdirectory. Potentially existing schemata on the input are kept intact.
     */
    protected function strip_subdir_from_url( $url ) {
        /** @var WPML_URL_Converter $wpml_url_converter */


        $subdir       = parse_url( $this->get_abs_home(), PHP_URL_PATH );
        $subdir_slugs = array_values( array_filter( explode( '/', $subdir ) ) );

        $url_path_expl = explode( '/', preg_replace( '#^(http|https)://#', '', $url ) );
        array_shift( $url_path_expl );
        $url_slugs        = array_values( array_filter( $url_path_expl ) );
        $url_slugs_before = $url_slugs;
        $url_slugs        = array_diff_assoc( $url_slugs, $subdir_slugs );
        $url              = str_replace( '/' . join( '/', $url_slugs_before ), '/' . join( '/', $url_slugs ), $url );

        return untrailingslashit( $url );
    }



    public function get_lang_from_url_string( $url = null ) {
        if ( ! $url ){
            $url = $this->cur_page_url();
        }

        $url = $this->strip_subdir_from_url ( $url );

        if ( strpos ( $url, 'http://' ) === 0 || strpos ( $url, 'https://' ) === 0 ) {
            $url_path = parse_url ( $url, PHP_URL_PATH );
        } else {
            $pathparts = array_filter ( explode ( '/', $url ) );
            if ( count ( $pathparts ) > 1 ) {
                unset( $pathparts[ 0 ] );
                $url_path = implode ( '/', $pathparts );
            } else {
                $url_path = $url;
            }
        }

        $fragments = array_filter ( (array) explode ( "/", $url_path ) );
        $lang      = array_shift ( $fragments );

        $lang_get_parts = explode( '?', $lang );
        $lang           = $lang_get_parts[ 0 ];

        if ( isset( $this->settings['url-slugs'] ) ) {
            return $lang && in_array($lang, $this->settings['url-slugs']) ? array_search($lang, $this->settings['url-slugs']) : null; //$this->settings['default-language'];
        }else{
            return $lang && in_array($lang, $this->settings['translation-languages']) ? $lang : null;//$this->settings['default-language'];
        }
    }

    protected function cur_page_url() {
        $pageURL = 'http';

        if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on"))
            $pageURL .= "s";

        $pageURL .= "://";

        if( strpos( $_SERVER["HTTP_HOST"], $_SERVER["SERVER_NAME"] ) !== false ){
            $pageURL .=$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
        }
        else {
            if ($_SERVER["SERVER_PORT"] != "80")
                $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
            else
                $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }

        if ( function_exists('apply_filters') ) $pageURL = apply_filters('trp_curpageurl', $pageURL);

        return $pageURL;
    }


}