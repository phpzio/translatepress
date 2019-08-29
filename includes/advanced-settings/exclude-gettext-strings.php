<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_exclude_gettext_strings', 100 );
function trp_register_exclude_gettext_strings( $settings_array ){
	$settings_array[] = array(
		'name'          => 'exclude_gettext_strings',
		'type'          => 'list',
		'columns'       => array(
								'string' => __('Gettext String', 'translatepress-multilingual' ),
								'domain' => __('Domain', 'translatepress-multilingual')
							),
		'label'         => esc_html__( 'Exclude Gettext Strings', 'translatepress-multilingual' ),
		'description'   => wp_kses( __( 'Exclude these strings from being translated as Gettext strings. Leave the domain empty to take into account any gettext string.<br/>Can still be translated as regular strings.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

/**
 * Exclude gettext from being translated
 */
add_filter('gettext', 'trpc_exclude_strings', 1000, 3 );
function trpc_exclude_strings ($translation, $text, $domain ){
	$option = get_option( 'trp_advanced_settings', true );
	if ( isset( $option['exclude_gettext_strings'] ) ) {

		foreach( $option['exclude_gettext_strings']['domain'] as $key => $value ){
			if ( $domain === $value && $text === $option['exclude_gettext_strings']['string'][$key] ){
				return $text;
			}

            if ( $domain === '' && $text === $option['exclude_gettext_strings']['string'][$key] ){
                return $text;
            }
		}
	}
	return $translation;
}