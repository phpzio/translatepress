<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_translation_memory', 1 );
function trp_register_translation_memory( $settings_array ){
    $settings_array[] = array(
        'name'          => 'enable_translation_memory',
        'type'          => 'radio',
        'options'       => array('on-for-admin', 'on-for-all', 'off'),
        'labels'        => array(   esc_html__( 'On for administrators and translators', 'translatepress-multilingual' ),
                                    esc_html__( 'On for all users (not recommended)', 'translatepress-multilingual' ),
                                    esc_html__( 'Off', 'translatepress-multilingual' )
                            ),
        'label'         => esc_html__( 'Automatic Translation Memory', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'For strings without a translation it tries to serve the translation of similar strings.<br/> This is good for when you make small changes to the original content so you do not loose the translation entirely. <br/>Works on the front-end only. A similar feature is already available in the editor.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
    );

    $settings_array[] = array(
        'name'          => 'translation_memory_min_chars',
        'type'          => 'input',
        'default'       => '20',
        'label'         => esc_html__( 'Minimum string length', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'The minimum length a string can have in order to be taken into consideration to search for a similar translation. <br/> It only applies to the automatic translation memory, not the suggestions from the Translation Editor.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
    );

    $settings_array[] = array(
        'name'          => 'translation_memory_min_similarity',
        'type'          => 'input',
        'default'       => '90',
        'label'         => esc_html__( 'Minimum string similarity', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'The minimum string similarity in percentage (max 100%) a string can have in order to automatically display a suggested translation instead. <br/> It only applies to the automatic translation memory, not the suggestions from the Translation Editor.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
    );

    $settings_array[] = array(
        'type'          => 'separator',
    );

    return $settings_array;
}

add_action( 'plugins_loaded', 'trp_translation_memory_enable_by_default' );
function trp_translation_memory_enable_by_default()
{
    $option = get_option('trp_advanced_settings', array());
    if (is_array($option) && !isset($option['enable_translation_memory'])) {
        $option['enable_translation_memory'] = "on-for-admin";
        update_option('trp_advanced_settings', $option);
    }

    if (is_array($option) && !isset($option['translation_memory_min_chars'])) {
        $option['translation_memory_min_chars'] = "25";
        update_option('trp_advanced_settings', $option);
    }

    if (is_array($option) && !isset($option['translation_memory_min_similarity'])) {
        $option['translation_memory_min_similarity'] = "94";
        update_option('trp_advanced_settings', $option);
    }
}

