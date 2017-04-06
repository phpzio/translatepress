<?php
//mimic the actuall admin-ajax
define('DOING_AJAX', true);

if (!isset( $_POST['action']))
    die('-1');

//make sure you update this line
//to the relative location of the wp-load.php
//define('SHORTINIT', true);
require_once('../../../../wp-load.php');
require_once ABSPATH . WPINC . '/user.php';
require_once ABSPATH . WPINC . '/capabilities.php';
global $wpdb;
//error_log( 'this is wpdbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb  bbbbbbbbbbbbbbbbb');// . json_encode($wpdb));
//Typical headers
header('Content-Type: text/html');
send_nosniff_header();

//Disable caching
header('Cache-Control: no-cache');
header('Pragma: no-cache');

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
    if(current_user_can('manage_options'))
        do_action('SOMETEXT_'.$action);
    else
        do_action('SOMETEXT_nopriv_'.$action);
} else {
    die('-1');
}

