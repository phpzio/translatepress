<?php
$translation_request = 'key='.$this->settings['g-translate-key'];
$translation_request .= '&source=en';
$translation_request .= '&target=es';
$translation_request .= '&q=about';


/* Due to url length restrictions we need so send a POST request faked as a GET request and send the strings in the body of the request and not in the URL */
$response = wp_remote_post( "https://www.googleapis.com/language/translate/v2", array(
        'headers' => array( 'X-HTTP-Method-Override' => 'GET' ),//this fakes a GET request
        'body' => $translation_request,
    )
);
?>
<div id="trp-addons-page" class="wrap">

    <h1> <?php _e( 'TranslatePress Settings', 'translatepress-multilingual' );?></h1>
    <?php do_action ( 'trp_settings_navigation_tabs' ); ?>

    <div class="grid feat-header">
        <div class="grid-cell">
            <h2><?php _e('Google API Key from settings page:', 'translatepress-multilingual');?> <span style="font-family:monospace"><?php echo $this->settings['g-translate-key']; ?></span></h2>

            <h3><?php _e('Response:', 'translatepress-multilingual');?></h3>
            <pre>
                <?php print_r( $response["response"] ); ?>
            </pre>
            <h3><?php _e('Response Body:', 'translatepress-multilingual');?></h3>
            <pre>
                <?php print_r( $response["body"] ); ?>
            </pre>

            <h3><?php _e('Entire Response From wp_remote_get():', 'translatepress-multilingual');?></h3>
            <pre>
                <?php print_r( $response ); ?>
            </pre>
        </div>
    </div>


</div>