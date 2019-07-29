<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_translation_memory', 1 );
function trp_register_translation_memory( $settings_array ){
    $settings_array[] = array(
        'name'          => 'enable_translation_memory',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Automatic Translation Memory', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'For strings without a translation it tries to serve the translation of similar strings.<br/> This is good for when you make small changes to the original content so you do not loose the translation entirely.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
    );
    return $settings_array;
}

add_action( 'plugins_loaded', 'trp_translation_memory_enable_by_default' );
function trp_translation_memory_enable_by_default(){
    $option = get_option( 'trp_advanced_settings', array());
    if ( is_array($option) && !isset( $option['enable_translation_memory'] ) ){
        $option['enable_translation_memory'] = "yes";
        update_option('trp_advanced_settings', $option);
    }
}

