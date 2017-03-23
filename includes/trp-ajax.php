<?php
/**
 * WordPress AJAX Process Execution.
 *
 * @package WordPress
 * @subpackage Administration
 *
 * @link https://codex.wordpress.org/AJAX_in_Plugins
 */

/**
 * Executing AJAX process.
 *
 * @since 2.1.0
 */
define( 'DOING_AJAX', true );
define( 'SHORTINIT', true );

/** Load WordPress Bootstrap */
require_once( dirname( dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) ) . '/wp-load.php' );

// Load the L10n library.
require_once( ABSPATH . WPINC . '/l10n.php' );

// Load most of WordPress.
require( ABSPATH . WPINC . '/class-wp-walker.php' );
require( ABSPATH . WPINC . '/formatting.php' );
require( ABSPATH . WPINC . '/capabilities.php' );
require( ABSPATH . WPINC . '/class-wp-roles.php' );
require( ABSPATH . WPINC . '/class-wp-role.php' );
require( ABSPATH . WPINC . '/class-wp-user.php' );
require( ABSPATH . WPINC . '/query.php' );
require( ABSPATH . WPINC . '/date.php' );
require( ABSPATH . WPINC . '/theme.php' );
require( ABSPATH . WPINC . '/class-wp-theme.php' );
require( ABSPATH . WPINC . '/template.php' );
require( ABSPATH . WPINC . '/user.php' );
require( ABSPATH . WPINC . '/class-wp-user-query.php' );
require( ABSPATH . WPINC . '/meta.php' );
require( ABSPATH . WPINC . '/class-wp-meta-query.php' );
require( ABSPATH . WPINC . '/general-template.php' );
require( ABSPATH . WPINC . '/link-template.php' );
require( ABSPATH . WPINC . '/author-template.php' );
require( ABSPATH . WPINC . '/post.php' );
require( ABSPATH . WPINC . '/class-walker-page.php' );
require( ABSPATH . WPINC . '/class-walker-page-dropdown.php' );
require( ABSPATH . WPINC . '/class-wp-post.php' );
require( ABSPATH . WPINC . '/post-template.php' );
require( ABSPATH . WPINC . '/revision.php' );
require( ABSPATH . WPINC . '/post-thumbnail-template.php' );
require( ABSPATH . WPINC . '/deprecated.php' );
require( ABSPATH . WPINC . '/script-loader.php' );
require( ABSPATH . WPINC . '/taxonomy.php' );
require( ABSPATH . WPINC . '/class-wp-term.php' );
require( ABSPATH . WPINC . '/class-wp-tax-query.php' );
require( ABSPATH . WPINC . '/shortcodes.php' );
require( ABSPATH . WPINC . '/media.php' );
require( ABSPATH . WPINC . '/widgets.php' );
require( ABSPATH . WPINC . '/class-wp-widget.php' );
require( ABSPATH . WPINC . '/class-wp-widget-factory.php' );
require( ABSPATH . WPINC . '/nav-menu.php' );


// Load multisite-specific files.
if ( is_multisite() ) {
    require( ABSPATH . WPINC . '/ms-functions.php' );
    require( ABSPATH . WPINC . '/ms-default-filters.php' );
    require( ABSPATH . WPINC . '/ms-deprecated.php' );
}

wp_plugin_directory_constants();
wp_templating_constants(  );

//include( dirname( __FILE__ ) . '/functions.php' );
//do_action( 'after_setup_theme' );



// Require an action parameter
if ( empty( $_REQUEST['action'] ) )
    die( '0' );

@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
@header( 'X-Robots-Tag: noindex' );

send_nosniff_header();
nocache_headers();

//$action = esc_attr(trim($_POST['action']));
$action = trim($_POST['action'] );

//A bit of security
$allowed_actions = array(
    'trp_get_translations',
    'custom_action2'
);




//For logged in users
/*add_action('SOMETEXT_trp_get_translations', 'handler_fun1');
add_action('SOMETEXT_custom_action2', 'handler_fun1');

//For guests
add_action('SOMETEXT_nopriv_custom_action2', 'handler_fun2');
add_action('SOMETEXT_nopriv_custom_action1', 'handler_fun1');
*/
if(in_array($action, $allowed_actions)) {
    //if(current_user_can('manage_options'))
        do_action('SOMETEXT_'.$action);
   // else
        do_action('SOMETEXT_nopriv_'.$action);
} else {
    die('-1');
}

