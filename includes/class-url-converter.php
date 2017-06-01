<?php

class TRP_Url_Converter {

    protected $absolute_home;
    protected $settings;

    // wpml-url-converter.class
    public function __construct( $settings ){
        $this->settings = $settings;
    }

    public function add_hreflang_to_head(){
        foreach ( $this->settings['publish-languages'] as $language ) {
            echo '<link rel="alternate" hreflang="' . $language .'" href="' . $this->get_url_for_language( '', $language ) . '" />';
        }
    }

    public function get_url_for_language ( $url, $language ){
        global $TRP_LANGUAGE;
        if ( empty ( $url ) ) {
            $url = $this->cur_page_url();
        }
        $abs_home = $this->get_abs_home();

        $new_url = '';
        $prefixes = apply_filters( 'trp_prefix_abs_home', array( $abs_home . '/' . $TRP_LANGUAGE . '/', $abs_home ) );
        foreach( $prefixes as $prefix ){
            if ( substr( $url, 0, strlen( $prefix ) ) == $prefix ) {
                $path = substr($url, strlen($prefix));
                $path = ltrim($path, '/');
                $new_url = $abs_home . '/' . $language . '/' . $path;
                break;
            }
        }

        if ( empty( $new_url ) ) {
            $new_url = $url;
        }

        //todo replace slugs with slug_manager->translate_slug_for_posts.
        return $new_url;

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


// wpml_strip_subdir_from_url
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

        return $lang && in_array ( $lang, $this->settings['publish-languages'] ) ? $lang : $this->settings['default-language'] ;
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