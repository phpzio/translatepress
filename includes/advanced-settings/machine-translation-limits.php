<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_machine_translation_limits', 10 );
function trp_register_machine_translation_limits( $settings_array ){
    $settings_array[] = array(
        'name'          => 'machine_translation_limits',
        'type'          => 'number',
        'default'       => '1000000',
        'label'         => esc_html__( 'Limit machine translation: characters per day.', 'translatepress-multilingual' ),
        'description'   => wp_kses( __( 'Add a limit to the number of automatically translated characters so you can better budget your project. Check out the <a href="#">logger for more information</a>. ', 'translatepress-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ),
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