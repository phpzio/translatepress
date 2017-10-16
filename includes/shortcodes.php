<?php
// add conditional language shortcode
add_shortcode( 'trp_language', 'trp_language_content');

/* ---------------------------------------------------------------------------
 * Shortcode [trp_language language="en_EN"] [/trp_language]
 * --------------------------------------------------------------------------- */

function trp_language_content( $attr, $content = null ){
    extract(shortcode_atts(array(
        'language' => '',
    ), $attr));

    $current_language = get_locale();

    if( $current_language == $language ){
        $output = do_shortcode($content);
    }else{
        $output = "";
    }

    return $output;
}