<?php
add_filter( 'trp_register_advanced_settings', 'trp_register_enable_hreflang_xdefault', 90 );
function trp_register_enable_hreflang_xdefault( $settings_array ){
    $settings_array[] = array(
        'name'          => 'enable_hreflang_xdefault',
        'type'          => 'select',
        'default'       => 'disabled',
        'label'         => esc_html__( 'Enable the hreflang x-default tag for language:', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'Enables the hreflang="x-default" for an entire language. See documentation for more details.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
        'options'       => trp_get_lang_for_xdefault(),
    );
    return $settings_array;
}

function trp_get_lang_for_xdefault(){
    $trp_obj = TRP_Translate_Press::get_trp_instance();
    $settings_obj = $trp_obj->get_component('settings');
    $lang_obj = $trp_obj->get_component( 'languages' );

    $published_lang = $settings_obj->get_setting('publish-languages');
    $published_lang_labels = $lang_obj->get_language_names( $published_lang );

    return array_merge(['disabled' => 'Disabled'], $published_lang_labels);
}
