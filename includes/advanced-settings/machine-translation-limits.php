<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_machine_translation_limit', 10 );
function trp_register_machine_translation_limit( $settings_array ){
    $settings_array[] = array(
        'name'          => 'machine_translation_limit',
        'type'          => 'number',
        'default'       => '1000000',
        'label'         => esc_html__( 'Limit machine translation / characters per day', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'Add a limit to the number of automatically translated characters so you can better budget your project. Check out the <a href="#">logger for more information</a>. ', 'translatepress-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ),
    );
    return $settings_array;
}

add_filter( 'trp_register_advanced_settings', 'trp_register_machine_translation_counter', 10 );
function trp_register_machine_translation_counter( $settings_array ){
    $settings_array[] = array(
        'name'          => 'machine_translation_counter',
        'type'          => 'machine_translation_counter',
        'label'         => esc_html__( 'Today\'s character count:', 'translatepress-multilingual' ),
    );
    return $settings_array;
}

add_filter( 'trp_register_advanced_settings', 'trp_register_machine_translation_counter_date', 10 );
function trp_register_machine_translation_counter_date( $settings_array ){
    $settings_array[] = array(
        'name'          => 'machine_translation_counter_date',
        'type'          => 'machine_translation_counter_date',
        'label'         => esc_html__( 'Today: ', 'translatepress-multilingual' ),
    );
    return $settings_array;
}

add_filter( 'trp_register_advanced_settings', 'trp_register_machine_translation_log', 10 );
function trp_register_machine_translation_log( $settings_array ){
    $settings_array[] = array(
        'name'          => 'machine_translation_log',
        'type'          => 'checkbox',
        'label'         => esc_html__( 'Log machine translation queries.', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'Only enable for testing purposes. Can impact performance<br>All records are stored in the wp_trp_machine_translation_log database table.', 'translatepress-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ),
    );
    return $settings_array;
}

add_filter( 'trp_register_advanced_settings', 'trp_register_machine_translation_separator', 10 );
function trp_register_machine_translation_separator( $settings_array ){
    $settings_array[] = array(
        'name'          => 'machine_translation_separator',
        'type'          => 'separator',
    );
    return $settings_array;
}

add_action('plugins_loaded','trp_machine_translation_limits_default');
function trp_machine_translation_limits_default(){
    $adv_options = get_option('trp_advanced_settings', array());
    if (!isset($adv_options['machine_translation_limits']))
    {
        $adv_options['machine_translation_limits'] = '1000000';
        update_option('trp_advanced_settings', $adv_options);
    }
}