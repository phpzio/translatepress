<?php

/**
 * Outputs language switcher.
 *
 * Uses customization options from Shortcode language switcher.
 */
function trp_the_language_switcher(){
    $trp = TRP_Translate_Press::get_trp_instance();
    $language_switcher = $trp->get_component( 'language_switcher' );
    echo $language_switcher->language_switcher();
}

/**
 * Wrapper function for json_encode to eliminate possible UTF8 special character errors
 * @param $value
 * @return mixed|string|void
 */
function trp_safe_json_encode($value){
    if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
        $encoded = json_encode($value, JSON_PRETTY_PRINT);
    } else {
        $encoded = json_encode($value);
    }
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            return $encoded;
        case JSON_ERROR_DEPTH:
            return 'Maximum stack depth exceeded'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_STATE_MISMATCH:
            return 'Underflow or the modes mismatch'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_CTRL_CHAR:
            return 'Unexpected control character found';
        case JSON_ERROR_SYNTAX:
            return 'Syntax error, malformed JSON'; // or trigger_error() or throw new Exception()
        case JSON_ERROR_UTF8:
            $clean = trp_utf8ize($value);
            return trp_safe_json_encode($clean);
        default:
            return 'Unknown error'; // or trigger_error() or throw new Exception()

    }
}

/**
 * Helper function for trp_safe_json_encode that helps eliminate utf8 json encode errors
 * @param $mixed
 * @return array|string
 */
function trp_utf8ize($mixed) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = trp_utf8ize($value);
        }
    } else if (is_string ($mixed)) {
        return utf8_encode($mixed);
    }
    return $mixed;
}

/**
 * function that gets the translation for a string with context directly from a .mo file
 * @TODO this was developped firstly for woocommerce so it maybe needs further development.
*/
function trp_x( $text, $context, $domain, $language ){

    /* try to find the correct path for the textdomain */
    $path = trp_find_translation_location_for_domain( $domain, $language );

    $mo_file = new MO();

    if( !empty( $path ) ) {
        if (!$mo_file->import_from_file( $path )) return $text;

        if (!empty($mo_file->entries[$context . '' . $text]))
            $text = $mo_file->entries[$context . '' . $text]->translations[0];
    }

    return $text;
}

/**
 * Function that tries to find the path for a translation file defined by textdomain and language
 * @param $domain the textdomain of the string that you want the translation for
 * @param $language the language in which you want the translation
 * @return string the path of the mo file if it is found else an empty string
 */
function trp_find_translation_location_for_domain( $domain, $language ){

    $path = '';

    if( file_exists( WP_LANG_DIR . '/plugins/'. $domain .'-' . $language . '.mo') ) {
        $path = WP_LANG_DIR . '/plugins/'. $domain .'-' . $language . '.mo';
    }
    elseif ( file_exists( WP_LANG_DIR . '/themes/'. $domain .'-' . $language . '.mo') ){
        $path = WP_LANG_DIR . '/themes/'. $domain .'-' . $language . '.mo';
    } elseif( $domain === '' && file_exists( WP_LANG_DIR . '/' . $language . '.mo')){
        $path = WP_LANG_DIR . '/' . $language . '.mo';
    } else {
        $possible_translation_folders = array( '', 'languages/', 'language/', 'translations/', 'translation/', 'lang/' );
        foreach( $possible_translation_folders as $possible_translation_folder ){
            if (file_exists(get_template_directory() . '/' . $possible_translation_folder . $domain . '-' . $language . '.mo')) {
                $path = get_template_directory() . '/' . $possible_translation_folder . $domain . '-' . $language . '.mo';
            } elseif ( file_exists(WP_PLUGIN_DIR . '/' . $domain . '/' . $possible_translation_folder . $domain . '-' . $language . '.mo') ) {
                $path = WP_PLUGIN_DIR . '/' . $domain . '/' . $possible_translation_folder . $domain . '-' . $language . '.mo';
            }
        }
    }

    return $path;
}

/**
 * Function that appends the affiliate_id to a given url
 * @param $link string the given url to append
 * @return string url with the added affiliate_id
 */
function trp_add_affiliate_id_to_link( $link ){

    $affiliate_id = get_option('translatepress_affiliate_id');

    if  ( !empty( $affiliate_id ) ) {

        return esc_url( add_query_arg( 'ref', $affiliate_id, $link ) );

    }

    return esc_url( $link );
}

/**
 * Function that makes string safe for display.
 *
 * Can be used on original or translated string.
 * Removes any unwanted html code from the string.
 * Do not confuse with trim.
 */
function trp_sanitize_string( $filtered ){
	$filtered = preg_replace( '/<script\b[^>]*>(.*?)<\/script>/is', '', $filtered );

	// don't remove \r \n \t. They are part of the translation, they give structure and context to the text.
	//$filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
	$filtered = trim( $filtered );

	$found = false;
	while ( preg_match('/%[a-f0-9]{2}/i', $filtered, $match) ) {
		$filtered = str_replace($match[0], '', $filtered);
		$found = true;
	}

	if ( $found ) {
		// Strip out the whitespace that may now exist after removing the octets.
		$filtered = trim( preg_replace('/ +/', ' ', $filtered) );
	}

	return $filtered;
}



/** Compatibility functions */

/**
 * Do not redirect when elementor preview is present
 *
 * @param $allow_redirect
 *
 * @return bool
 */
function trp_elementor_compatibility( $allow_redirect ){
	// compatibility with Elementor preview. Do not redirect to subdir language when elementor preview is present.
	if ( isset( $_GET['elementor-preview'] ) ) {
		return false;
	}
	return $allow_redirect;
}
add_filter( 'trp_allow_language_redirect', 'trp_elementor_compatibility' );


/**
 * Mb Strings missing PHP library error notice
 */
function trp_mbstrings_notification(){
	echo '<div class="notice notice-error"><p>' . __( '<strong>TranslatePress</strong> requires <strong><a href="http://php.net/manual/en/book.mbstring.php">Multibyte String PHP library</a></strong>. Please contact your server administrator to install it on your server.','translatepress-multilingual' ) . '</p></div>';
}

function trp_missing_mbstrings_library( $allow_to_run ){
	if ( ! extension_loaded('mbstring') ) {
		add_action( 'admin_menu', 'trp_mbstrings_notification' );
		return false;
	}
	return $allow_to_run;
}
add_filter( 'trp_allow_tp_to_run', 'trp_missing_mbstrings_library' );

/**
 * Don't have html inside menu title tags. Some themes just put in the title the content of the link without striping HTML
 */
add_filter( 'nav_menu_link_attributes', 'trp_remove_html_from_menu_title', 10, 3);
function trp_remove_html_from_menu_title( $atts, $item, $args ){
    $atts['title'] = wp_strip_all_tags($atts['title']);
    return $atts;
}

/**
 * Rework wp_trim_words so we can trim Chinese, Japanese and Thai words since they are based on characters as words.
 *
 * @since 1.3.0
 *
 * @param string $text      Text to trim.
 * @param int    $num_words Number of words. Default 55.
 * @param string $more      Optional. What to append if $text needs to be trimmed. Default '&hellip;'.
 * @return string Trimmed text.
 */
function trp_wp_trim_words( $text, $num_words = 55, $more = null, $original_text ) {
    if ( null === $more ) {
        $more = __( '&hellip;' );
    }
    // what we receive is the short text in the filter
    $text = $original_text;
    $text = wp_strip_all_tags( $text );

    $trp = TRP_Translate_Press::get_trp_instance();
    $trp_settings = $trp->get_component( 'settings' );
    $settings = $trp_settings->get_settings();

    $default_language= $settings["default-language"];

    $char_is_word = false;
    foreach (array('ch', 'ja', 'tw') as $lang){
        if (strpos($default_language, $lang) !== false){
            $char_is_word = true;
        }
    }

    if ( $char_is_word && preg_match( '/^utf\-?8$/i', get_option( 'blog_charset' ) ) ) {
        $text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $text ), ' ' );
        preg_match_all( '/./u', $text, $words_array );
        $words_array = array_slice( $words_array[0], 0, $num_words + 1 );
        $sep = '';
    } else {
        $words_array = preg_split( "/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
        $sep = ' ';
    }

    if ( count( $words_array ) > $num_words ) {
        array_pop( $words_array );
        $text = implode( $sep, $words_array );
        $text = $text . $more;
    } else {
        $text = implode( $sep, $words_array );
    }

    return $text;
}
add_filter('wp_trim_words', 'trp_wp_trim_words', 100, 4);
