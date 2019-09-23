<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Translatepress
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/index.php';
	//require dirname( dirname( __FILE__ ) ) . '/../woocommerce/woocommerce.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );


/**
 * Set some options before anything else
 */
function setup_settings(){
    update_option( 'permalink_structure', '/%postname%/' );
    update_option( 'trp_settings', maybe_unserialize( 'a:14:{s:16:"default-language";s:5:"en_US";s:17:"publish-languages";a:2:{i:0;s:5:"en_US";i:1;s:5:"it_IT";}s:21:"translation-languages";a:2:{i:0;s:5:"en_US";i:1;s:5:"it_IT";}s:9:"url-slugs";a:2:{s:5:"en_US";s:2:"en";s:5:"it_IT";s:2:"it";}s:22:"native_or_english_name";s:12:"english_name";s:36:"add-subdirectory-to-default-language";s:3:"yes";s:30:"force-language-to-custom-links";s:3:"yes";s:11:"g-translate";s:2:"no";s:15:"g-translate-key";s:0:"";s:17:"shortcode-options";s:16:"flags-full-names";s:12:"menu-options";s:16:"flags-full-names";s:14:"trp-ls-floater";s:3:"yes";s:15:"floater-options";s:16:"flags-full-names";s:22:"machine-translate-codes";a:2:{s:5:"en_US";s:2:"en";s:5:"it_IT";s:2:"it";}}' ) );
}
tests_add_filter( 'muplugins_loaded', 'setup_settings', 1 );


// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
