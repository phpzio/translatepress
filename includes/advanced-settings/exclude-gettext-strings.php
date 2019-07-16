<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_exclude_gettext_strings' );
function trp_register_disable_dynamic_translation( $settings_array ){
	$settings_array[] = array(
		'name'          => 'exclude_gettext_strings',
		'type'          => 'list',
		'label'         => esc_html__( 'Exclude Gettext Strings', 'translatepress-multilingual' ),
		'description'   => wp_kses( __( 'Exclude these strings from being translated as Gettext strings. <br/>Can still be translated as regular strings.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

/**
 * Exclude gettext from being translated
 */
//add_filter('gettext', 'trpc_exclude_strings', 1000, 3 );
function trpc_exclude_strings ($translation, $text, $domain ){
	// domain and string

	if ( $domain == 'wprentals-core' || $domain == 'wprentals' ) {
		$exclude_strings = array(
			'Reservation fee',
			'Listing',
			'Upgrade to Featured',
			'Publish Listing with Featured',
			'Package'
		);
		if ( in_array( $text, $exclude_strings ) ) {
			return $text;
		}
	}
	return $translation;
}