<?php

add_filter( 'trp_machine_translation_engines', 'trp_gt_add_engine', 10 );
function trp_gt_add_engine( $engines ){
    $engines[] = array( 'value' => 'google_translate_v2', 'label' => __( 'Google Translate v2', 'translatepress-multilingual' ) );

    return $engines;
}

add_action( 'trp_machine_translation_extra_settings_middle', 'trp_gt_add_settings' );
function trp_gt_add_settings( $mt_settings ){
    $trp                = TRP_Translate_Press::get_trp_instance();
    $machine_translator = $trp->get_component( 'machine_translator' );
    ?>

    <tr>
        <th scope="row"><?php esc_html_e( 'Google Translate API Key', 'translatepress-multilingual' ); ?> </th>
        <td>
            <input type="text" id="trp-g-translate-key" class="trp-text-input" name="trp_machine_translation_settings[google-translate-key]" value="<?php if( !empty( $mt_settings['google-translate-key'] ) ) echo esc_attr( $mt_settings['google-translate-key']);?>"/>
            <p class="description">
                <?php echo wp_kses( __( 'Visit <a href="https://cloud.google.com/docs/authentication/api-keys" target="_blank">this link</a> to see how you can set up an API key, <strong>control API costs</strong> and set HTTP referrer restrictions.', 'translatepress-multilingual' ), [ 'a' => [ 'href' => [], 'title' => [], 'target' => [] ], 'strong' => [] ] ); ?>
                <br><?php echo sprintf( esc_html__( 'Your HTTP referrer is: %s', 'translatepress-multilingual' ), $machine_translator->get_referer() ); ?>
            </p>
        </td>

    </tr>

    <?php
}

add_filter( 'trp_machine_translation_sanitize_settings', 'trp_gt_sanitize_settings' );
function trp_gt_sanitize_settings( $mt_settings ){
    if( !empty( $mt_settings['google-translate-key'] ) )
        $mt_settings['google-translate-key'] = sanitize_text_field( $mt_settings['google-translate-key']  );

    return $mt_settings;
}
