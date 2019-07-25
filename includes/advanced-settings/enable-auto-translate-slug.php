<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_enable_auto_translate_slug', 80 );
function trp_register_enable_auto_translate_slug( $settings_array ){
	$settings_array[] = array(
		'name'          => 'enable_auto_translate_slug',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Automatically translate slugs', 'translatepress-multilingual' ),
		'description'   => wp_kses( __( 'Generate automatic translations of slugs for posts, pages and Custom Post Types.<br/>Requires <a href="https://translatepress.com/docs/addons/seo-pack/" title="TranslatePress Add-on SEO Pack documentation" target="_blank"> SEO Pack Add-on</a> to be installed and activated.', 'translatepress-multilingual' ), array( 'br' => array(), 'a' => array( 'href' => array(), 'title' => array(), 'target' => array() ) ) ),
	);
	return $settings_array;
}

add_filter('trp_machine_translate_slug', 'trp_enable_auto_translate_slug');
function trp_enable_auto_translate_slug($allow) {

	$option = get_option( 'trp_advanced_settings', true );
	if ( isset( $option['enable_auto_translate_slug'] ) && $option['enable_auto_translate_slug'] === 'yes' ) {
		return true;
	}
	return $allow;
}
