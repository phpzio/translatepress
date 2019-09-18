<?php

add_filter( 'trp_machine_translation_engines', 'trp_gt_add_engine' );
function trp_gt_add_engine( $engines ){
    $engines[] = array( 'value' => 'google_translate_v2', 'label' => __( 'Google Translate v2', 'translatepress-multilingual' ) );

    return $engines;
}

add_action( 'trp_machine_translation_extra_settings_middle', 'trp_gt_add_settings' );
function trp_gt_add_settings( $settings ){
    $trp                = TRP_Translate_Press::get_trp_instance();
    $machine_translator = $trp->get_component( 'machine_translator' );
    ?>

    <tr>
        <th scope="row"><?php esc_html_e( 'Google Translate API Key', 'translatepress-multilingual' ); ?> </th>
        <td>
            <input type="text" id="trp-g-translate-key" class="trp-text-input" name="trp_machine_translation_settings[g-translate-key]" value="<?php if( !empty( $settings['g-translate-key'] ) ) echo esc_attr( $settings['g-translate-key']);?>"/>
            <?php if( !empty( $settings['g-translate-key'] ) ) echo '<a href="'.esc_url( admin_url( 'admin.php?page=trp_test_google_key_page' ) ).'">'.esc_html__( "Test API key", 'translatepress-multilingual' ).'</a>'; ?>
            <p class="description">
                <?php echo wp_kses( __( 'Visit <a href="https://cloud.google.com/docs/authentication/api-keys" target="_blank">this link</a> to see how you can set up an API key, <strong>control API costs</strong> and set HTTP referrer restrictions.', 'translatepress-multilingual' ), [ 'a' => [ 'href' => [], 'title' => [], 'target' => [] ], 'strong' => [] ] ); ?>
                <br><?php echo sprintf( esc_html__( 'Your HTTP referrer is: %s', 'translatepress-multilingual' ), $machine_translator->get_referer() ); ?>
            </p>
        </td>

    </tr>

    <?php
}

add_filter( 'trp_machine_translation_sanitize_settings', 'trp_gt_sanitize_settings' );
function trp_gt_sanitize_settings( $settings ){
    if( !empty( $settings['g-translate-key'] ) )
        $settings['g-translate-key'] = sanitize_text_field( $settings['g-translate-key']  );

    $trp           = TRP_Translate_Press::get_trp_instance();
    $trp_languages = $trp->get_component( 'languages' );
    $trp_settings  = $trp->get_component( 'settings' );

    $settings['google-translate-codes'] = $trp_languages->get_iso_codes( $trp_settings->get_setting( 'translation-languages' ) );

    return $settings;
}
