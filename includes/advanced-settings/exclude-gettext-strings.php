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
		'description'   => wp_kses( __( 'Exclude these strings from being translated as Gettext strings. <br/>Can still be translated as regular strings.', 'translatepress-multilingual' ), array( 'br' => array() ) ),
	);
	return $settings_array;
}

/**
 * Exclude gettext from being translated
 */
add_action( 'init', 'trp_load_exclude_strings' );
function trp_load_exclude_strings(){
	$is_ajax_on_frontend = TRP_Translation_Manager::is_ajax_on_frontend();

	/* on ajax hooks from frontend that have the init hook ( we found WooCommerce has it ) apply it earlier */
	if( $is_ajax_on_frontend ){
		add_action( 'wp_loaded', 'trp_load_exclude_strings_gettext' );
	}
	else{//otherwise start from the wp_head hook
		add_action( 'wp_head', 'trp_load_exclude_strings_gettext', 100 );
	}

	//if we have woocommerce installed and it is not an ajax request add a gettext hook starting from wp_loaded and remove it on wp_head
	if( class_exists( 'WooCommerce' ) && !$is_ajax_on_frontend ){
		// WooCommerce launches some ajax calls before wp_head, so we need to apply_gettext_filter earlier to catch them
		add_action( 'wp_loaded', 'trp_load_exclude_strings_gettext', 19 );
	}
}

function trp_load_exclude_strings_gettext() {
	add_filter('gettext', 'trp_exclude_strings', 1000, 3 );
}

function trp_exclude_strings ($translation, $text, $domain ){
	$option = get_option( 'trp_advanced_settings', true );
	if ( isset( $option['exclude_gettext_strings'] ) ) {

		foreach( $option['exclude_gettext_strings']['domain'] as $key => $value ){
			if ( $domain === $value && $text === $option['exclude_gettext_strings']['string'][$key] ){
				return $text;
			}
		}
	}
	return $translation;
}
