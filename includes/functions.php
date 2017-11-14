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
 * @TODO this was developped firstly for woocommerce so it needs further development. It only works for plugins at the moment
*/
function trp_x( $text, $context, $domain, $language ){
    $mo_file = new MO();

    if( file_exists( WP_LANG_DIR . '/plugins/'. $domain .'-' . $language . '.mo') ) {
        if (!$mo_file->import_from_file(WP_LANG_DIR . '/plugins/' . $domain . '-' . $language . '.mo')) return $text;


        if (!empty($mo_file->entries[$context . '' . $text]))
            $text = $mo_file->entries[$context . '' . $text]->translations[0];
    }

    return $text;
}