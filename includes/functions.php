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

    if( !empty( $path ) ) {

        $mo_file = wp_cache_get( 'trp_x_' . $domain .'_'. $language );

        if( false === $mo_file ){
            $mo_file = new MO();
            $mo_file->import_from_file( $path );
            wp_cache_set( 'trp_x_' . $domain .'_'. $language, $mo_file );
        }

        if ( !$mo_file ) return $text;


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

    //Avangate Affiliate Network
    $avg_affiliate_id = get_option('translatepress_avg_affiliate_id');
    if  ( !empty( $avg_affiliate_id ) ) {
        return esc_url( add_query_arg( 'avgref', $avg_affiliate_id, $link ) );
    }
    else{
        // AffiliateWP
        $affiliate_id = get_option('translatepress_affiliate_id');
        if  ( !empty( $affiliate_id ) ) {
            return esc_url( add_query_arg( 'ref', $affiliate_id, $link ) );
        }
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

/**
 * function that checks if $_REQUEST['trp-edit-translation'] is set or if it has a certain value
 */
function trp_is_translation_editor( $value = '' ){
    if( isset( $_REQUEST['trp-edit-translation'] ) ){
        if( !empty( $value ) ) {
            if( $_REQUEST['trp-edit-translation'] === $value ) {
                return true;
            }
            else{
                return false;
            }
        }
        else{
            $possible_values = array ('preview', 'true');
            if( in_array( $_REQUEST['trp-edit-translation'], $possible_values ) ) {
                return true;
            }
        }
    }

    return false;
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
 * Remove '?fl_builder' query param from edit translation url (when clicking the admin bar button to enter the translation Editor)
 *
 * Otherwise after publishing out of BB and clicking TP admin bar button, it’s still showing the BB interface
 *
 * @param $url
 *
 * @return bool
 */
function trp_beaver_builder_compatibility( $url ){

    $url = remove_query_arg('fl_builder', $url );

    return esc_url ($url);

}
add_filter( 'trp_edit_translation_url', 'trp_beaver_builder_compatibility' );


/**
 * Mb Strings missing PHP library error notice
 */
function trp_mbstrings_notification(){
	echo '<div class="notice notice-error"><p>' . wp_kses( __( '<strong>TranslatePress</strong> requires <strong><a href="http://php.net/manual/en/book.mbstring.php">Multibyte String PHP library</a></strong>. Please contact your server administrator to install it on your server.','translatepress-multilingual' ), [ 'a' => [ 'href' => [] ], 'strong' => [] ] ) . '</p></div>';
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


/**
 * Use home_url in the https://www.peepso.com/ ajax front-end url so strings come back translated.
 *
 * @since 1.3.1
 *
 * @param array $data   Peepso data
 * @return array
 */
add_filter( 'peepso_data', 'trp_use_home_url_in_peepso_ajax' );
function trp_use_home_url_in_peepso_ajax( $data ){
    if ( is_array( $data ) && isset( $data['ajaxurl_legacy'] ) ){
        $data['ajaxurl_legacy'] = home_url( '/peepsoajax/' );
    }
    return $data;
}


function trp_remove_accents( $string ){

    if ( !preg_match('/[\x80-\xff]/', $string) )
        return $string;

    if (seems_utf8($string)) {
        $chars = array(
            // Decompositions for Latin-1 Supplement
            'ª' => 'a', 'º' => 'o',
            'À' => 'A', 'Á' => 'A',
            'Â' => 'A', 'Ã' => 'A',
            'Ä' => 'A', 'Å' => 'A',
            'Æ' => 'AE','Ç' => 'C',
            'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I',
            'Î' => 'I', 'Ï' => 'I',
            'Ð' => 'D', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O',
            'Ô' => 'O', 'Õ' => 'O',
            'Ö' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U',
            'Ü' => 'U', 'Ý' => 'Y',
            'Þ' => 'TH','ß' => 's',
            'à' => 'a', 'á' => 'a',
            'â' => 'a', 'ã' => 'a',
            'ä' => 'a', 'å' => 'a',
            'æ' => 'ae','ç' => 'c',
            'è' => 'e', 'é' => 'e',
            'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i',
            'ð' => 'd', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o',
            'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u',
            'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'þ' => 'th',
            'ÿ' => 'y', 'Ø' => 'O',
            // Decompositions for Latin Extended-A
            'Ā' => 'A', 'ā' => 'a',
            'Ă' => 'A', 'ă' => 'a',
            'Ą' => 'A', 'ą' => 'a',
            'Ć' => 'C', 'ć' => 'c',
            'Ĉ' => 'C', 'ĉ' => 'c',
            'Ċ' => 'C', 'ċ' => 'c',
            'Č' => 'C', 'č' => 'c',
            'Ď' => 'D', 'ď' => 'd',
            'Đ' => 'D', 'đ' => 'd',
            'Ē' => 'E', 'ē' => 'e',
            'Ĕ' => 'E', 'ĕ' => 'e',
            'Ė' => 'E', 'ė' => 'e',
            'Ę' => 'E', 'ę' => 'e',
            'Ě' => 'E', 'ě' => 'e',
            'Ĝ' => 'G', 'ĝ' => 'g',
            'Ğ' => 'G', 'ğ' => 'g',
            'Ġ' => 'G', 'ġ' => 'g',
            'Ģ' => 'G', 'ģ' => 'g',
            'Ĥ' => 'H', 'ĥ' => 'h',
            'Ħ' => 'H', 'ħ' => 'h',
            'Ĩ' => 'I', 'ĩ' => 'i',
            'Ī' => 'I', 'ī' => 'i',
            'Ĭ' => 'I', 'ĭ' => 'i',
            'Į' => 'I', 'į' => 'i',
            'İ' => 'I', 'ı' => 'i',
            'Ĳ' => 'IJ','ĳ' => 'ij',
            'Ĵ' => 'J', 'ĵ' => 'j',
            'Ķ' => 'K', 'ķ' => 'k',
            'ĸ' => 'k', 'Ĺ' => 'L',
            'ĺ' => 'l', 'Ļ' => 'L',
            'ļ' => 'l', 'Ľ' => 'L',
            'ľ' => 'l', 'Ŀ' => 'L',
            'ŀ' => 'l', 'Ł' => 'L',
            'ł' => 'l', 'Ń' => 'N',
            'ń' => 'n', 'Ņ' => 'N',
            'ņ' => 'n', 'Ň' => 'N',
            'ň' => 'n', 'ŉ' => 'n',
            'Ŋ' => 'N', 'ŋ' => 'n',
            'Ō' => 'O', 'ō' => 'o',
            'Ŏ' => 'O', 'ŏ' => 'o',
            'Ő' => 'O', 'ő' => 'o',
            'Œ' => 'OE','œ' => 'oe',
            'Ŕ' => 'R','ŕ' => 'r',
            'Ŗ' => 'R','ŗ' => 'r',
            'Ř' => 'R','ř' => 'r',
            'Ś' => 'S','ś' => 's',
            'Ŝ' => 'S','ŝ' => 's',
            'Ş' => 'S','ş' => 's',
            'Š' => 'S', 'š' => 's',
            'Ţ' => 'T', 'ţ' => 't',
            'Ť' => 'T', 'ť' => 't',
            'Ŧ' => 'T', 'ŧ' => 't',
            'Ũ' => 'U', 'ũ' => 'u',
            'Ū' => 'U', 'ū' => 'u',
            'Ŭ' => 'U', 'ŭ' => 'u',
            'Ů' => 'U', 'ů' => 'u',
            'Ű' => 'U', 'ű' => 'u',
            'Ų' => 'U', 'ų' => 'u',
            'Ŵ' => 'W', 'ŵ' => 'w',
            'Ŷ' => 'Y', 'ŷ' => 'y',
            'Ÿ' => 'Y', 'Ź' => 'Z',
            'ź' => 'z', 'Ż' => 'Z',
            'ż' => 'z', 'Ž' => 'Z',
            'ž' => 'z', 'ſ' => 's',
            // Decompositions for Latin Extended-B
            'Ș' => 'S', 'ș' => 's',
            'Ț' => 'T', 'ț' => 't',
            // Euro Sign
            '€' => 'E',
            // GBP (Pound) Sign
            '£' => '',
            // Vowels with diacritic (Vietnamese)
            // unmarked
            'Ơ' => 'O', 'ơ' => 'o',
            'Ư' => 'U', 'ư' => 'u',
            // grave accent
            'Ầ' => 'A', 'ầ' => 'a',
            'Ằ' => 'A', 'ằ' => 'a',
            'Ề' => 'E', 'ề' => 'e',
            'Ồ' => 'O', 'ồ' => 'o',
            'Ờ' => 'O', 'ờ' => 'o',
            'Ừ' => 'U', 'ừ' => 'u',
            'Ỳ' => 'Y', 'ỳ' => 'y',
            // hook
            'Ả' => 'A', 'ả' => 'a',
            'Ẩ' => 'A', 'ẩ' => 'a',
            'Ẳ' => 'A', 'ẳ' => 'a',
            'Ẻ' => 'E', 'ẻ' => 'e',
            'Ể' => 'E', 'ể' => 'e',
            'Ỉ' => 'I', 'ỉ' => 'i',
            'Ỏ' => 'O', 'ỏ' => 'o',
            'Ổ' => 'O', 'ổ' => 'o',
            'Ở' => 'O', 'ở' => 'o',
            'Ủ' => 'U', 'ủ' => 'u',
            'Ử' => 'U', 'ử' => 'u',
            'Ỷ' => 'Y', 'ỷ' => 'y',
            // tilde
            'Ẫ' => 'A', 'ẫ' => 'a',
            'Ẵ' => 'A', 'ẵ' => 'a',
            'Ẽ' => 'E', 'ẽ' => 'e',
            'Ễ' => 'E', 'ễ' => 'e',
            'Ỗ' => 'O', 'ỗ' => 'o',
            'Ỡ' => 'O', 'ỡ' => 'o',
            'Ữ' => 'U', 'ữ' => 'u',
            'Ỹ' => 'Y', 'ỹ' => 'y',
            // acute accent
            'Ấ' => 'A', 'ấ' => 'a',
            'Ắ' => 'A', 'ắ' => 'a',
            'Ế' => 'E', 'ế' => 'e',
            'Ố' => 'O', 'ố' => 'o',
            'Ớ' => 'O', 'ớ' => 'o',
            'Ứ' => 'U', 'ứ' => 'u',
            // dot below
            'Ạ' => 'A', 'ạ' => 'a',
            'Ậ' => 'A', 'ậ' => 'a',
            'Ặ' => 'A', 'ặ' => 'a',
            'Ẹ' => 'E', 'ẹ' => 'e',
            'Ệ' => 'E', 'ệ' => 'e',
            'Ị' => 'I', 'ị' => 'i',
            'Ọ' => 'O', 'ọ' => 'o',
            'Ộ' => 'O', 'ộ' => 'o',
            'Ợ' => 'O', 'ợ' => 'o',
            'Ụ' => 'U', 'ụ' => 'u',
            'Ự' => 'U', 'ự' => 'u',
            'Ỵ' => 'Y', 'ỵ' => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin)
            'ɑ' => 'a',
            // macron
            'Ǖ' => 'U', 'ǖ' => 'u',
            // acute accent
            'Ǘ' => 'U', 'ǘ' => 'u',
            // caron
            'Ǎ' => 'A', 'ǎ' => 'a',
            'Ǐ' => 'I', 'ǐ' => 'i',
            'Ǒ' => 'O', 'ǒ' => 'o',
            'Ǔ' => 'U', 'ǔ' => 'u',
            'Ǚ' => 'U', 'ǚ' => 'u',
            // grave accent
            'Ǜ' => 'U', 'ǜ' => 'u',
        );

        // Used for locale-specific rules
        $trp = TRP_Translate_Press::get_trp_instance();
        $trp_settings = $trp->get_component( 'settings' );
        $settings = $trp_settings->get_settings();

        $default_language= $settings["default-language"];
        $locale = $default_language;

        if ( 'de_DE' == $locale || 'de_DE_formal' == $locale || 'de_CH' == $locale || 'de_CH_informal' == $locale ) {
            $chars[ 'Ä' ] = 'Ae';
            $chars[ 'ä' ] = 'ae';
            $chars[ 'Ö' ] = 'Oe';
            $chars[ 'ö' ] = 'oe';
            $chars[ 'Ü' ] = 'Ue';
            $chars[ 'ü' ] = 'ue';
            $chars[ 'ß' ] = 'ss';
        } elseif ( 'da_DK' === $locale ) {
            $chars[ 'Æ' ] = 'Ae';
            $chars[ 'æ' ] = 'ae';
            $chars[ 'Ø' ] = 'Oe';
            $chars[ 'ø' ] = 'oe';
            $chars[ 'Å' ] = 'Aa';
            $chars[ 'å' ] = 'aa';
        } elseif ( 'ca' === $locale ) {
            $chars[ 'l·l' ] = 'll';
        } elseif ( 'sr_RS' === $locale || 'bs_BA' === $locale ) {
            $chars[ 'Đ' ] = 'DJ';
            $chars[ 'đ' ] = 'dj';
        }

        $string = strtr($string, $chars);
    } else {
        $chars = array();
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
            ."\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
            ."\xc3\xc4\xc5\xc7\xc8\xc9\xca"
            ."\xcb\xcc\xcd\xce\xcf\xd1\xd2"
            ."\xd3\xd4\xd5\xd6\xd8\xd9\xda"
            ."\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
            ."\xe4\xe5\xe7\xe8\xe9\xea\xeb"
            ."\xec\xed\xee\xef\xf1\xf2\xf3"
            ."\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
            ."\xfc\xfd\xff";

        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars = array();
        $double_chars['in'] = array("\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe");
        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }

    return $string;
};

/**
 * Filter ginger_iframe_banner and ginger_text_banner to use shortcodes so our conditional lang shortcode works.
 *
 * @since 1.3.1
 *
 * @param string $content
 * @return string
 */

add_filter('ginger_iframe_banner', 'trp_do_shortcode', 999 );
add_filter('ginger_text_banner', 'trp_do_shortcode', 999 );
function trp_do_shortcode($content){
    return do_shortcode(stripcslashes($content));
}

/**
 * Debuger function. Mainly designed for the get_url_for_language() function
 *
 * @since 1.3.6
 *
 * @param bool $enabled
 * @param array $logger
 */
function trp_bulk_debug($debug = false, $logger = array()){
    if(!$debug){
        return;
    }
    error_log('---------------------------------------------------------');
    $key_length = '';
    foreach ($logger as $key => $value){
        if ( strlen($key) > $key_length)
            $key_length = strlen($key);
    }

    foreach ($logger as $key => $value){
        error_log("$key :   " . str_repeat(' ', $key_length - strlen($key)) . $value);
    }
    error_log('---------------------------------------------------------');
}

/**
 * Compatibility with WooCommerce PDF Invoices & Packing Slips
 * https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
 *
 * @since 1.4.3
 *
 */
// fix attachment name in email
add_filter( 'wpo_wcpdf_filename', 'trp_woo_pdf_invoices_and_packing_slips_compatibility' );

// fix #trpgettext inside invoice pdf
add_filter( 'wpo_wcpdf_get_html', 'trp_woo_pdf_invoices_and_packing_slips_compatibility');
function trp_woo_pdf_invoices_and_packing_slips_compatibility($title){
	if ( class_exists( 'TRP_Translation_Manager' ) ) {
		return 	TRP_Translation_Manager::strip_gettext_tags($title);
	}
}

// fix font of pdf breaking because of str_get_html() call inside translate_page()
add_filter( 'trp_stop_translating_page', 'trp_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf', 10, 2 );
function trp_woo_pdf_invoices_and_packing_slips_compatibility_dont_translate_pdf( $bool, $output ){
	if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'generate_wpo_wcpdf' ) {
		return true;
	}
	return $bool;
}


/**
 * Compatibility with WooCommerce order notes
 *
 * When a new order is placed in secondary languages, in admin area WooCommerce->Orders->Edit Order, the right sidebar contains Order notes which can contain #trpst tags.
 *
 * @since 1.4.3
 */

// old orders
add_filter( 'woocommerce_get_order_note', 'trp_woo_notes_strip_trpst' );
// new orders
add_filter( 'woocommerce_new_order_note_data', 'trp_woo_notes_strip_trpst' );
function trp_woo_notes_strip_trpst( $note_array ){
	foreach ( $note_array as $item => $value ){
		$note_array[$item] = TRP_Translation_Manager::strip_gettext_tags( $value );
	}
	return $note_array;
}

/*
 * Compatibility with WooCommerce back-end display order shipping taxes
 */
add_filter('woocommerce_order_item_display_meta_key','trp_woo_data_strip_trpst');
add_filter('woocommerce_order_item_get_method_title','trp_woo_data_strip_trpst');
function trp_woo_data_strip_trpst( $data ){
	return TRP_Translation_Manager::strip_gettext_tags( $data );
}

/**
 * Compatibility with WooCommerce country list on checkout.
 *
 * Skip detection by translate-dom-changes of the list of countries
 *
 */
add_filter( 'trp_skip_selectors_from_dynamic_translation', 'trp_woo_skip_dynamic_translation' );
function trp_woo_skip_dynamic_translation( $skip_selectors ){
	$add_skip_selectors = array( '#select2-billing_country-results', '#select2-shipping_country-results' );
	return array_merge( $skip_selectors, $add_skip_selectors );
}

/**
 * Used for showing useful notice in Translation Editor
 *
 * @return bool
 */
function trp_is_paid_version() {
	$licence = get_option( 'trp_licence_key' );

	if ( ! empty( $licence ) ) {
		return true;
	}

	//list of class names
	$addons = apply_filters( 'trp_paid_addons', array(
		'TRP_Automatic_Language_Detection',
		'TRP_Browse_as_other_Role',
		'TRP_Extra_Languages',
		'TRP_Navigation_Based_on_Language',
		'TRP_Seo_Pack',
		'TRP_Translator_Accounts',
	) );

	foreach ( $addons as $className ) {
		if ( class_exists( $className ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Compatibility with WooCommerce product variation.
 *
 * Add span tag to woocommerce product variation name.
 *
 * Product variation name keep changes, but the prefix is the same. Wrap the prefix to allow translating that part separately.
 */
add_filter( 'woocommerce_product_variation_title', 'trp_woo_wrap_variation', 8, 4);
function trp_woo_wrap_variation($name, $product, $title_base, $title_suffix){
	$separator  = '<span> - </span>';
	return $title_suffix ? $title_base . $separator . $title_suffix : $title_base;
}


/**
 * Compatibility with Query Monitor
 *
 * Remove their HTML and reappend it after translate_page function finishes
 */
add_filter('trp_before_translate_content', 'trp_qm_strip_query_monitor_html', 10, 1 );
function trp_qm_strip_query_monitor_html( $output ) {

	$query_monitor = apply_filters( 'trp_query_monitor_begining_string', '<!-- Begin Query Monitor output -->' );
	$pos = strpos( $output, $query_monitor );

	if ( $pos !== false ){
		global $trp_query_monitor_string;
		$trp_query_monitor_string = substr( $output, $pos );
		$output = substr( $output, 0, $pos );

	}

	return $output;
}

add_filter( 'trp_translated_html', 'trp_qm_reappend_query_monitor_html', 10, 1 );
function trp_qm_reappend_query_monitor_html( $final_html ){
	global $trp_query_monitor_string;

	if ( isset( $trp_query_monitor_string ) && !empty( $trp_query_monitor_string ) ){
		$final_html .= $trp_query_monitor_string;
	}

	return $final_html;
}

// trpgettext tags don't get escaped because they add <small> tags through a regex.
add_filter( 'qm/output/title', 'trp_qm_strip_gettext', 100);
function trp_qm_strip_gettext( $data ){
	if ( is_array( $data ) ) {
		foreach( $data as $key => $value ){
			$data[$key] = trp_qm_strip_gettext($value);
		}
	}else {
		// remove small tags
		$data = preg_replace('(<(\/)?small>)', '', $data);
		// strip gettext (not needed, they are just numbers shown in admin bar anyway)
		$data = TRP_Translation_Manager::strip_gettext_tags( $data );
		// add small tags back the same way they do it in the filter 'qm/output/title'
		$data = preg_replace( '#\s?([^0-9,\.]+)#', '<small>$1</small>', $data );
	}
	return $data;
}

/**
 * Compatibility with SeedProd Coming Soon
 *
 * Manually include the scripts and styles if do_action('enqueue_scripts') is not called
 */
add_filter( 'trp_translated_html', 'trp_force_include_scripts', 10, 4 );
function trp_force_include_scripts( $final_html, $TRP_LANGUAGE, $language_code, $preview_mode ){
	if ( $preview_mode ){
		$trp = TRP_Translate_Press::get_trp_instance();
		$translation_render = $trp->get_component( 'translation_render' );
		$trp_data = $translation_render->get_trp_data();

		$scripts_and_styles = apply_filters('trp_editor_missing_scripts_and_styles', array(
			'jquery'                        => "<script type='text/javascript' src='" . includes_url( '/js/jquery/jquery.js' ) . "'></script>",
			'trp-iframe-preview-script.js'  => "<script type='text/javascript' src='" . TRP_PLUGIN_URL . "assets/js/trp-iframe-preview-script.js'></script>",
			'trp-translate-dom-changes.js'  => "<script>trp_data = '" . addslashes(json_encode($trp_data) ) . "'; trp_data = JSON.parse(trp_data);</script><script type='text/javascript' src='" . TRP_PLUGIN_URL . "assets/js/trp-translate-dom-changes.js'></script>",
			'trp-preview-iframe-style-css'  => "<link rel='stylesheet' id='trp-preview-iframe-style-css'  href='" . TRP_PLUGIN_URL . "assets/css/trp-preview-iframe-style.css' type='text/css' media='all' />",
			'dashicons'                     => "<link rel='stylesheet' id='dashicons-css'  href='" . includes_url( '/css/dashicons.min.css' ) . "' type='text/css' media='all' />"
		));

		$missing_script = '';
		foreach($scripts_and_styles as $key => $value ){
			if ( strpos( $final_html, $key ) === false ){
				$missing_script .= $value;
			}
		}

		if ( $missing_script !== '' ){
			$html = TranslatePress\str_get_html( $final_html, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT );
			if ( $html === false ) {
				return $final_html;
			}

			$body = $html->find( 'body', 0 );
			if ( $body ) {
				$body->innertext = $body->innertext . $missing_script;
			}

			$final_html = $html->save();
		}
	}
	return $final_html;
}

/*
 * Compatibility with plugins sending Gettext strings in requests such as Cartflows
 *
 * Strip gettext wrappings from the requests made from http->post()
 */
// Strip of gettext wrappings all the values of the body request array
add_filter( 'http_request_args', 'trp_strip_trpst_from_requests', 10, 2 );
function trp_strip_trpst_from_requests($args, $url){
	if( is_array( $args['body'] ) ) {
		array_walk_recursive( $args['body'], 'trp_array_walk_recursive_strip_gettext_tags' );
	}else{
		$args['body'] = TRP_Translation_Manager::strip_gettext_tags( $args['body'] );
	}
	return $args;
}
function trp_array_walk_recursive_strip_gettext_tags( &$value ){
	$value = TRP_Translation_Manager::strip_gettext_tags( $value );
}

// Strip of gettext wrappings the customer_name and customer_email keys. Found in WC Stripe and Cartflows
add_filter( 'wc_stripe_payment_metadata', 'trp_strip_request_metadata_keys' );
function trp_strip_request_metadata_keys( $metadata ){
	foreach( $metadata as $key => $value ) {
		$stripped_key = TRP_Translation_Manager::strip_gettext_tags( $key );
		if ( $stripped_key != $key ) {
			$metadata[ $stripped_key ] = $value;
			unset( $metadata[ $key ] );
		}
	}
	return $metadata;
}

/**
 * Compatibility with NextGEN Gallery
 *
 * They start an output buffer at init -1 (before ours at init 0). They print footer scripts after we run translate_page,
 * resulting in outputting scripts that won't be stripped of trpst trp-gettext wrappings.
 * This includes WooCommerce Checkout scripts, resulting in trpst wrappings around form fields like Street Address.
 *
 * Solution is to move their hook to priority 1
 *
 * This is the chosen hook (after_setup_theme) because
 * it needs to be after plugins_loaded (because TP is loading this file later)
 * and before init (because the we need to unregister the NextGen Gallery plugin's function hooked on init with priority -1 )
 */
add_action( 'after_setup_theme', 'trp_nextgen_compatibility' );
function trp_nextgen_compatibility(){
	if ( class_exists( 'C_Photocrati_Resource_Manager' ) ) {
		$object_C_Photocrati_Resource_Manager = C_Photocrati_Resource_Manager::$instance;

		// move from priority -1 to 1 because it needs to be after our hook on priority 0
		remove_action( 'init', array( &$object_C_Photocrati_Resource_Manager, 'start_buffer' ) );
		add_action( 'init', array( &$object_C_Photocrati_Resource_Manager, 'start_buffer', ), 1 );
	}
}

/**
 * Compatibility with WooCommerce added to cart message
 *
 * Makes sure title of product is translated.
 *
 * The title of product is added through sprintf %s of a Gettext.
 *
 */
add_filter( 'the_title', 'trp_woo_translate_product_title_added_to_cart', 10, 2 );
function trp_woo_translate_product_title_added_to_cart( $title, $id ){
	if( class_exists( 'WooCommerce' ) ){
		if ( version_compare( PHP_VERSION, '5.4.0', '>=' ) ) {
			$callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);//set a limit if it is supported to improve performance
		}
		else{
			$callstack_functions = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
		}

		$list_of_functions = apply_filters( 'trp_woo_translate_title_before_translate_page', array( 'wc_add_to_cart_message' ) );
		if( !empty( $callstack_functions ) ) {
			foreach ( $callstack_functions as $callstack_function ) {
				if ( in_array( $callstack_function['function'], $list_of_functions ) ) {
					$trp = TRP_Translate_Press::get_trp_instance();
					$translation_render = $trp->get_component( 'translation_render' );
					$title = $translation_render->translate_page($title);
					break;
				}
			}
		}
	}
	return $title;
}

/**
 * Compatibility with WooTour plugin
 *
 * They replace spaces (" ") with \u0020, after we apply #trpst and because we don't strip them it breaks html
 */
add_action('init', 'trp_wootour_add_gettext_filter');
function trp_wootour_add_gettext_filter(){
	if ( class_exists( 'WooTour_Booking' ) ){
		add_filter('gettext', 'trp_wootour_exclude_gettext_strings', 1000, 3 );
	}
}
function trp_wootour_exclude_gettext_strings($translation, $text, $domain){
	if ( $domain == 'woo-tour' ){
		if ( in_array( $text, array( 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' ) ) ){
			return TRP_Translation_Manager::strip_gettext_tags( $translation );
		}
	}
	return $translation;
}

/**
 * Compatibility with WooCommerce cart product name translation
 * For products with the character - in the product name.
 *
 * There is a difference between the rendered – and –. Two different characters.
 * Somehow in the cart is the minus one, in the shop listing is the longer separator.
 * Make the cart contain the same type of character which is obtained using get_the_title.
 */
add_filter( 'woocommerce_cart_item_name', 'trp_woo_cart_item_name', 8, 3 );
function trp_woo_cart_item_name( $product_name, $cart_item, $cart_item_key ){
	if ( isset( $cart_item['product_id'] ) ){
		$title = get_the_title( $cart_item['product_id'] );
		if ( !empty( $title )){
			if ( strpos( $product_name, '</a>' ) ) {
				preg_match_all('~<a(.*?)href="([^"]+)"(.*?)>~', $product_name, $matches);
				$product_name = sprintf( '<a href="%s">%s</a>', esc_url( $matches[2][0] ), $title );
			}
		}
	}
	return $product_name;
}
