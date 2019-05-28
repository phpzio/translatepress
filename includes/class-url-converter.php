<?php

/**
 * Class TRP_Url_Converter
 *
 * Manages urls of translated pages.
 */
class TRP_Url_Converter {

    protected $absolute_home;
    protected $settings;

    /**
     * TRP_Url_Converter constructor.
     *
     * @param array $settings       Settings option.
     */
    public function __construct( $settings ){
        $this->settings = $settings;
    }

    /**
     * Add language code as a subdirectory after home url.
     *
     * Hooked to home_url.
     *
     * @param string $url               Given Url.
     * @param string $path              Given path.
     * @param string $orig_scheme       Scheme.
     * @param int $blog_id              Blog id.
     * @return string
     */
    public function add_language_to_home_url( $url, $path, $orig_scheme, $blog_id ){
        global $TRP_LANGUAGE;
        if ( isset( $this->settings['add-subdirectory-to-default-language'] ) && $this->settings['add-subdirectory-to-default-language'] == 'no' && $TRP_LANGUAGE == $this->settings['default-language'] ) {
            return $url;
        }

        if( is_customize_preview() || $this->is_admin_request()  || $this->is_sitemap_link( $path ) )
            return $url;

        $url_slug = $this->get_url_slug( $TRP_LANGUAGE );
        $abs_home = $this->get_abs_home();
        $new_url = trailingslashit( trailingslashit( $abs_home ) . $url_slug );
        if ( ! empty( $path ) ){
            $new_url .= ltrim( $path, '/' );
        }

        return apply_filters( 'trp_home_url', $new_url, $abs_home, $TRP_LANGUAGE, $path, $url );
    }

    /**
     * Check if this is a request at the backend.
     *
     * @return bool true if is admin request, otherwise false.
     */
    public function is_admin_request() {
        $current_url = $this->cur_page_url();
        $admin_url = strtolower( admin_url() );

        // we can't use wp_get_referer() It looks like it creates an infinite loop because it calls home_url() and we're filtering that
        $referrer = '';
        if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
            $referrer = wp_unslash( $_REQUEST['_wp_http_referer'] );
        } else if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $referrer = wp_unslash( $_SERVER['HTTP_REFERER'] );
        }

        //consider an admin request a call to the rest api that came from the admin area
        if( false !== strpos( $current_url, '/wp-json/' ) && 0 === strpos( $referrer, $admin_url ) ){
            return true;
        }

        /**
         * Check if this is a admin request. If true, it
         * could also be a AJAX request from the frontend.
         */
        if ( 0 === strpos( $current_url, $admin_url ) ) {
            /**
             * Check if the user comes from a admin page.
             */
            if ( 0 === strpos( $referrer, $admin_url ) ) {
                return true;
            } else {
                if ( function_exists( 'wp_doing_ajax' ) ) {
                    return ! wp_doing_ajax();
                } else {
                    return ! ( defined( 'DOING_AJAX' ) && DOING_AJAX );
                }
            }
        } else {
            return false;
        }
    }

    /**
     * A function that is used inside the home_url filter to detect if the current link is a sitemap link
     * @param $path the path that is passed inside home_url
     * @return bool
     */
    public function is_sitemap_link( $path ) {

        //we first check in the $path
        if( !empty( $path ) ){
            if( strpos($path, 'sitemap') !== false && strpos($path, '.xml') !== false )
                return true;
            else
                return false;
        }
        else { //if the path is empty check the request URI
            if (strpos( $_SERVER['REQUEST_URI'], 'sitemap') !== false && strpos( $_SERVER['REQUEST_URI'], '.xml') !== false)
                return true;
        }

        return false;
    }

    /**
     * Add Hreflang entries for each language to Header.
     */
    public function add_hreflang_to_head(){

        $languages = $this->settings['publish-languages'];
        if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) {
            $languages = $this->settings['translation-languages'];
        }

        foreach ( $languages as $language ) {
            // hreflang should have - instead of _ . For example: en-EN, not en_EN like the locale
            $hreflang = str_replace('_', '-', $language);
            $hreflang = apply_filters('trp_hreflang', $hreflang, $language);
            echo '<link rel="alternate" hreflang="' . esc_attr( $hreflang ). '" href="' . esc_url( $this->get_url_for_language( $language ) ) . '"/>';
        }
    }

    /**
     * Function that changes the lang attribute in the html tag to the current language.
     *
     * @param string $output
     * @return string
     */
    public function change_lang_attr_in_html_tag( $output ){
        global $TRP_LANGUAGE;
        $lang = get_bloginfo('language');
        if ( $lang && !empty($TRP_LANGUAGE) ) {
            $output = str_replace( 'lang="'. $lang .'"', 'lang="'. str_replace('_', '-', $TRP_LANGUAGE ) .'"', $output );
        }

        return $output;
    }

    /**
     * Returns language-specific url for given language.
     *
     * Defaults to current Url and current language.
     *
     * @param string $language      Language code that we want to translate into.
     * @param string $url           Url to encode.
     * @return string
     */

    public function get_url_for_language ( $language = null, $url = null, $trp_link_is_processed = '#TRPLINKPROCESSED') {
        $debug = false;
        // initializations
        global $TRP_LANGUAGE;
        $hash = hash( 'md4', (string)$language . (string)$url . (string)$trp_link_is_processed . (string)$TRP_LANGUAGE );
        $new_url = wp_cache_get('get_url_for_language_' . $hash, 'trp');
        if ( $new_url !== false ){
            return $new_url;
        }

        $trp_language_copy = $TRP_LANGUAGE;
        if ( empty( $language ) ) {
            $language = $TRP_LANGUAGE;
        }

        if ( empty($url) ){
            $url = $this->cur_page_url();
        }

        $url_obj = wp_cache_get('url_obj_' . hash('md4', $url), 'trp');
        if( $url_obj === false ){
            $url_obj = new \TranslatePress\Uri($url);
            wp_cache_set('url_obj_' . hash('md4', $url), $url_obj, 'trp' );
        }

        $abs_home_url_obj = wp_cache_get('url_obj_' . hash('md4',  $this->get_abs_home() ), 'trp');
        if( $abs_home_url_obj === false ){
            $abs_home_url_obj = new \TranslatePress\Uri( $this->get_abs_home() );
            wp_cache_set('url_obj_' . hash('md4', $this->get_abs_home()), $abs_home_url_obj, 'trp' );
        }

        if( $TRP_LANGUAGE == $this->settings['default-language'] ){
            $trp_link_is_processed = '';
        }

        // actual logic of the function
        if ( $this->url_is_file($url) ){
            trp_bulk_debug($debug, array('url' => $url, 'abort' => 'is file'));
            wp_cache_set('get_url_for_language_' . $hash, $url . $trp_link_is_processed, 'trp');
            return $url . $trp_link_is_processed; //abort for files
        }

        if ( !$url_obj->isSchemeless() && $url_obj->getScheme() != 'http' && $url_obj->getScheme() != 'https' ){
            trp_bulk_debug($debug, array('url' => $url, 'abort' => "is different scheme ".$url_obj->getScheme()));
            wp_cache_set('get_url_for_language_' . $hash, $url . $trp_link_is_processed, 'trp');
            return $url . $trp_link_is_processed; // abort for non-http/https links
        }

        if ( $url_obj->isSchemeless() && !$url_obj->getPath() ){
            trp_bulk_debug($debug, array('url' => $url, 'abort' => "is anchor or has get params"));
            wp_cache_set('get_url_for_language_' . $hash, $url, 'trp');
            return $url; // abort for anchors or params only.
        }

        if ( $url_obj->getHost() && $abs_home_url_obj->getHost() && $url_obj->getHost() != $abs_home_url_obj->getHost() ){
            trp_bulk_debug($debug, array('url' => $url, 'abort' => "is external url "));
            wp_cache_set('get_url_for_language_' . $hash, $url, 'trp');
            return $url; // abort for external url's
        }

        if( $this->get_lang_from_url_string($url) === null && $this->settings['default-language'] === $language && $this->settings['add-subdirectory-to-default-language'] !== 'yes' ){
            trp_bulk_debug($debug, array('url' => $url, 'abort' => "URL already has the correct language added to it and default language has subdir"));
            wp_cache_set('get_url_for_language_' . $hash, $url, 'trp');
            return $url;
        }

        if( $this->get_lang_from_url_string($url) === $language ){
            trp_bulk_debug($debug, array('url' => $url, 'abort' => "URL already has the correct language added to it"));
            wp_cache_set('get_url_for_language_' . $hash, $url, 'trp');
            return $url;
        }

        // maybe find the post_id for the current URL
        $possible_post_id = wp_cache_get( 'possible_post_id_'. hash('md4', $url ), 'trp' );
        if ( $possible_post_id ){
            $post_id = $possible_post_id;
            trp_bulk_debug($debug, array('url' => $url, 'found post id' => $post_id, 'for language' => $TRP_LANGUAGE));
        } else {
            $post_id = url_to_postid( $url );
            wp_cache_set( 'possible_post_id_' . hash('md4', $url ), $post_id, 'trp' );
            if ( $post_id ) { trp_bulk_debug($debug, array('url' => $url, 'found post id' => $post_id, 'for default language' => $TRP_LANGUAGE)); }

            if ( $post_id == 0 ) {
                // try again but with the default language home_url
                $TRP_LANGUAGE = $this->settings['default-language'];
                $post_id = url_to_postid( $url );
                wp_cache_set( 'possible_post_id_' . hash('md4', $url ), $post_id, 'trp' );
                if($post_id){ trp_bulk_debug($debug, array('url' => $url, 'found post id' => $post_id, 'for default language' => $TRP_LANGUAGE)); }
                $TRP_LANGUAGE = $trp_language_copy;
            }
        }

        if( $post_id ){

            /*
             * We need to find if the current URL (either passed as parameter or found via cur_page_url)
             * has extra arguments compared to it's permalink.
             * We need the permalink based on the language IN THE URL, not the one passed to this function,
             * as that represents the language to be translated into.
             */

            /*
             * WE ARE NOT USING \TranslatePress\Uri
             * due to URL's having extra path elements after the permalink slug. Using the class would strip those end points.
             *
             */

            $TRP_LANGUAGE = $this->get_lang_from_url_string( $url );

            $processed_permalink = get_permalink($post_id);

            if($url_obj->isSchemeless()){
                $arguments = str_replace(trailingslashit($processed_permalink), '', trailingslashit(trailingslashit( home_url() ) . ltrim($url, '/') ) );
            } else {
                $arguments = str_replace($processed_permalink, '', $url );
            }

            // if nothing was replaced, something was wrong, just use the normal permalink without any arguments.
            if( $arguments == $url ) $arguments = '';

            $TRP_LANGUAGE = $language;
            $new_url = get_permalink( $post_id ) . $arguments;
            trp_bulk_debug($debug, array('url' => $url, 'new url' => $new_url, 'found post id' => $post_id, 'url type' => 'based on permalink', 'for language' => $TRP_LANGUAGE));
            $TRP_LANGUAGE = $trp_language_copy;

        } else {
            // we're just adding the new language to the url
            $new_url_obj = $url_obj;
            if ($abs_home_url_obj->getPath() == "/"){
                $abs_home_url_obj->setPath('');
            }
            if( $this->get_lang_from_url_string($url) === null ){
                // these are the custom url. They don't have language
                $abs_home_considered_path = trim(str_replace($abs_home_url_obj->getPath(), '', $url_obj->getPath()), '/');
                $new_url_obj->setPath( trailingslashit( trailingslashit($abs_home_url_obj->getPath()) . trailingslashit($this->get_url_slug( $language )) . $abs_home_considered_path ) );
                $new_url = $new_url_obj->getUri();

                trp_bulk_debug($debug, array('url' => $url, 'new url' => $new_url, 'lang' => $language, 'url type' => 'custom url without language parameter'));
            } else {
                // these have language param in them and we need to replace them with the new language
                $abs_home_considered_path = trim(str_replace($abs_home_url_obj->getPath(), '', $url_obj->getPath()), '/');
                $no_lang_orig_path = explode('/', $abs_home_considered_path);
                unset($no_lang_orig_path[0]);
                $no_lang_orig_path = implode('/', $no_lang_orig_path );

                if ( !$this->get_url_slug( $language ) ){
                    $url_lang_slug = '';
                } else {
                    $url_lang_slug = trailingslashit($this->get_url_slug( $language ));
                }

                $new_url_obj->setPath( trailingslashit( trailingslashit($abs_home_url_obj->getPath()) . $url_lang_slug . ltrim($no_lang_orig_path, '/') ) );
                $new_url = $new_url_obj->getUri();

                trp_bulk_debug($debug, array('url' => $url, 'new url' => $new_url, 'lang' => $language, 'url type' => 'custom url with language', 'abs home path' => $abs_home_url_obj->getPath()));
            }
        }

        /* fix links for woocommerce on language switcher for product categories and product tags */
        if( class_exists( 'WooCommerce' ) ){
            $english_woocommerce_slugs = array('product-category', 'product-tag', 'product');
            foreach ($english_woocommerce_slugs as $english_woocommerce_slug){
                // current woo slugs are based on the localized default language OR the current language
                $current_slug = get_transient( 'tp_'.$english_woocommerce_slug.'_'. $this->settings['default-language'] );
                if( $current_slug === false ){
                    $current_slug = trp_x( $english_woocommerce_slug, 'slug', 'woocommerce', $this->settings['default-language'] );
                    set_transient( 'tp_'.$english_woocommerce_slug.'_'. $this->settings['default-language'], $current_slug, 12 * HOUR_IN_SECONDS );
                }
                if( strpos($new_url, '/'.$current_slug.'/') === false){
                    $current_slug = get_transient( 'tp_'.$english_woocommerce_slug.'_'. $TRP_LANGUAGE );
                    if( $current_slug === false ){
                        $current_slug = trp_x( $english_woocommerce_slug, 'slug', 'woocommerce', $TRP_LANGUAGE );
                        set_transient( 'tp_'.$english_woocommerce_slug.'_'. $TRP_LANGUAGE, $current_slug, 12 * HOUR_IN_SECONDS );
                    }
                }

                $translated_slug = get_transient( 'tp_'.$english_woocommerce_slug.'_'. $language );
                if( $current_slug === false ){
                    $translated_slug = trp_x( $english_woocommerce_slug, 'slug', 'woocommerce', $language );
                    set_transient( 'tp_'.$english_woocommerce_slug.'_'. $language, $translated_slug, 12 * HOUR_IN_SECONDS );
                }
                $new_url = str_replace( '/'.$current_slug.'/', '/'.$translated_slug.'/', $new_url );
            }
        }

        if ( empty( $new_url ) ) {
            $new_url = $url;
        }

	    $new_url = apply_filters( 'trp_get_url_for_language', $new_url, $url, $language, $this->get_abs_home(), $this->get_lang_from_url_string($url), $this->get_url_slug( $language ) );
        wp_cache_set('get_url_for_language_' . $hash, $new_url . $trp_link_is_processed, 'trp');
        return $new_url . $trp_link_is_processed ;

    }

    /**
     * Check is a url is an actual file on the server, in which case don't add a language param.
     *
     * @param string $url
     * @return bool
     */
    public function url_is_file( $url = null ){
        $trp = TRP_Translate_Press::get_trp_instance();
        $translation_render = $trp->get_component("translation_render");

        if ( empty( $url ) || $translation_render->is_external_link($url) ){
            return false;
        }

        $path = trailingslashit(ABSPATH) . str_replace(untrailingslashit($this->get_abs_home()), '', $url);

        return is_file($path);
    }

    /**
     * Get language code slug to use in url.
     *
     * @param string $language_code         Full language code.
     * @param bool $accept_empty_return     Whether to take into account the add-subdirectory-to-default-language setting.
     * @return string                       Url slug.
     */
    public function get_url_slug( $language_code, $accept_empty_return = true ){
        $url_slug = $language_code;
        if( isset( $this->settings['url-slugs'][$language_code] ) ) {
            $url_slug = $this->settings['url-slugs'][$language_code];
        }

        if ( $accept_empty_return && isset( $this->settings['add-subdirectory-to-default-language'] ) && $this->settings['add-subdirectory-to-default-language'] == 'no' && $language_code == $this->settings['default-language'] ) {
            $url_slug = '';
        }

        return $url_slug;
    }

    /**
     * Return absolute home url as stored in database, unfiltered.
     *
     * @return string
     */
    public function get_abs_home() {
	    $this->absolute_home = wp_cache_get('get_abs_home', 'trp');
	    if ( $this->absolute_home !== false ){
		    return $this->absolute_home;
	    }

	    global $wpdb;

        // returns the unfiltered home_url by directly retrieving it from wp_options.
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

        if( empty($this->absolute_home) ){
            $this->absolute_home = get_option("siteurl");
        }

	    // always return absolute_home based on the http or https version of the current page request. This means no more redirects.
	    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') {
		    $this->absolute_home = str_replace( 'http://', 'https://', $this->absolute_home );
	    } else {
		    $this->absolute_home = str_replace( 'https://', 'http://', $this->absolute_home );
	    }

	    wp_cache_set( 'get_abs_home', $this->absolute_home, 'trp' );

        return $this->absolute_home;
    }

    /**
     * Return the language code from the url.
     *
     * Uses current url if none given.
     *
     * @param string $url       Url.
     * @return string           Language code.
     */
    public function get_lang_from_url_string( $url = null ) {
        if ( ! $url ){
            $url = $this->cur_page_url();
        }

        $language = wp_cache_get('url_language_' . hash('md4', $url) , 'trp' );
        if ( $language !== false ){
            return $language;
        }

        $url_obj = wp_cache_get('url_obj_' . hash('md4', $url), 'trp');
        if( $url_obj === false ){
            $url_obj = new \TranslatePress\Uri($url);
            wp_cache_set('url_obj_' . hash('md4', $url), $url_obj, 'trp' );
        }

        $abs_home_url_obj = wp_cache_get('url_obj_' . hash('md4',  $this->get_abs_home() ), 'trp');
        if( $abs_home_url_obj === false ){
            $abs_home_url_obj = new \TranslatePress\Uri( $this->get_abs_home() );
            wp_cache_set('url_obj_' . hash('md4', $this->get_abs_home()), $abs_home_url_obj, 'trp' );
        }

        if( $url_obj->getPath() ){
            if ($abs_home_url_obj->getPath() == "/"){
                $abs_home_url_obj->setPath('');
            }
            $possible_path = str_replace($abs_home_url_obj->getPath(), '', $url_obj->getPath());
            $lang = ltrim( $possible_path,'/' );
            $lang = explode('/', $lang);
            if( $lang == false ){
                wp_cache_set('url_language_' . hash('md4', $url), null, 'trp');
                return null;
            }
            // If we have a language in the URL, the first element of the array should be it.
            $lang = $lang[0];

            $lang = apply_filters( 'trp_get_lang_from_url_string', $lang, $url );

            // the lang slug != actual lang. So we need to do array_search so we don't end up with en instead of en_US
            if( isset($this->settings['url-slugs']) && in_array($lang, $this->settings['url-slugs']) ){
                $language = array_search($lang, $this->settings['url-slugs']);
                wp_cache_set('url_language_' . hash('md4', $url), $language, 'trp');
                return $language;
            }
        }
        wp_cache_set('url_language_' . hash('md4', $url), null, 'trp');
        return null;
    }

    /**
     * Return current page url.
     * Always using $this->get_abs_home(), instead of home_url() since that one is filtered by TP
     * @return string
     */
    public function cur_page_url() {

        $req_uri = wp_cache_get('cur_page_url', 'trp');
        if ( $req_uri ){
            return $req_uri;
        }

        $req_uri = $_SERVER['REQUEST_URI'];

        $home_path = trim( parse_url( $this->get_abs_home(), PHP_URL_PATH ), '/' );
        $home_path_regex = sprintf( '|^%s|i', preg_quote( $home_path, '|' ) );

        // Trim path info from the end and the leading home path from the front.
        $req_uri = ltrim($req_uri, '/');
        $req_uri = preg_replace( $home_path_regex, '', $req_uri );
        $req_uri = trim($this->get_abs_home(), '/') . '/' . ltrim( $req_uri, '/' );


        if ( function_exists('apply_filters') ) $pageURL = apply_filters('trp_curpageurl', $req_uri);
        wp_cache_set('cur_page_url', $req_uri, 'trp');
        return $req_uri;
    }

    /**
     * we need to modify the permalinks structure for woocommerce when we switch languages
     * when woo registers post_types and taxonomies in the rewrite parameter of the function they change the slugs of the items (they are localized with _x )
     * we can't flush the permalinks on every page load so we filter the rewrite_rules option
     */
    public function woocommerce_filter_permalinks_on_other_languages( $rewrite_rules ){
        if( class_exists( 'WooCommerce' ) ){
            global $TRP_LANGUAGE;
            if( $TRP_LANGUAGE != $this->settings['default-language'] ){
                global $default_language_wc_permalink_structure; //we use a global because apparently you can't do switch to locale and restore multiple times. I should keep an eye on this
                /* get rewrite rules from original language */
                if( empty($default_language_wc_permalink_structure) ) {
                    $default_language_wc_permalink_structure = get_transient( 'tp_default_language_wc_permalink_structure_'.$this->settings['default-language'] );
                    if( $default_language_wc_permalink_structure === false ) {
                        $default_language_wc_permalink_structure = array();
                        $default_language_wc_permalink_structure['product_rewrite_slug'] = trp_x('product', 'slug', 'woocommerce', $this->settings['default-language']);
                        $default_language_wc_permalink_structure['category_rewrite_slug'] = trp_x('product-category', 'slug', 'woocommerce', $this->settings['default-language']);
                        $default_language_wc_permalink_structure['tag_rewrite_slug'] = trp_x('product-tag', 'slug', 'woocommerce', $this->settings['default-language']);

                        set_transient('tp_default_language_wc_permalink_structure_' . $this->settings['default-language'], $default_language_wc_permalink_structure, 12 * HOUR_IN_SECONDS);
                    }
                }

                $current_language_permalink_structure = get_transient( 'tp_current_language_permalink_structure_'.$TRP_LANGUAGE );
                if( $current_language_permalink_structure === false ) {
                    //always generate the slugs for defaults on the current language
                    $current_language_permalink_structure = array();
                    $current_language_permalink_structure['product_rewrite_slug'] = trp_x('product', 'slug', 'woocommerce', $TRP_LANGUAGE);
                    $current_language_permalink_structure['category_rewrite_slug'] = trp_x('product-category', 'slug', 'woocommerce', $TRP_LANGUAGE);
                    $current_language_permalink_structure['tag_rewrite_slug'] = trp_x('product-tag', 'slug', 'woocommerce', $TRP_LANGUAGE);

                    set_transient( 'tp_current_language_permalink_structure_'.$TRP_LANGUAGE, $current_language_permalink_structure, 12 * HOUR_IN_SECONDS );
                }


                $new_rewrite_rules = array();

                $search = array( '/^'.$default_language_wc_permalink_structure['product_rewrite_slug'].'\//', '/^'.$default_language_wc_permalink_structure['category_rewrite_slug'].'\//', '/^'.$default_language_wc_permalink_structure['tag_rewrite_slug'].'\//' );
                $replace = array( $current_language_permalink_structure['product_rewrite_slug'].'/', $current_language_permalink_structure['category_rewrite_slug'].'/', $current_language_permalink_structure['tag_rewrite_slug'].'/' );

                if( !empty( $rewrite_rules ) && is_array($rewrite_rules) ) {
                    foreach ($rewrite_rules as $rewrite_key => $rewrite_values) {
                        $new_rewrite_rules[preg_replace($search, $replace, $rewrite_key)] = preg_replace($search, $replace, $rewrite_values);
                    }
                }

            }
        }

        if( !empty($new_rewrite_rules) ) {
            return $new_rewrite_rules;
        }
        else
            return $rewrite_rules;
    }

    /* on frontend on other languages dinamically generate the woo permalink structure for the default slugs */
    function woocommerce_filter_permalink_option( $value ){
        global $TRP_LANGUAGE;
        if( $TRP_LANGUAGE != $this->settings['default-language'] ) {
            if( trim($value['product_base'], '/') === trp_x( 'product', 'slug', 'woocommerce', $this->settings['default-language'] ) ){
                $value['product_base'] = '';
            }

            if( trim($value['category_base'], '/') === trp_x( 'product-category', 'slug', 'woocommerce', $this->settings['default-language'] ) ){
                $value['category_base'] = '';
            }

            if( trim($value['tag_base'], '/') === trp_x( 'product-tag', 'slug', 'woocommerce', $this->settings['default-language'] ) ){
                $value['tag_base'] = '';
            }

        }

        return $value;
    }

    /* don't update the woocommerce_permalink option on the frontend if we are not on the default language */
    function woocommerce_handle_permalink_option_on_frontend($value, $old_value){
        global $TRP_LANGUAGE;
        if( isset($TRP_LANGUAGE) && $TRP_LANGUAGE != $this->settings['default-language'] ){
            $value = $old_value;
        }
        return $value;
    }

}