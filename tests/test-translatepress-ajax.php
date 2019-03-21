<?php
/**
* Test cases for Translatepress ajax functions.
*/
class TranslatePressAjaxTest extends WP_Ajax_UnitTestCase{
    //this is for the moment not used but is left here as an example. Remember that the ajax functions need to terminate with wp_die() not die() as die() will kill the test
    public function test_get_translations_ajax() {

        /*$_POST['security'] = wp_create_nonce( 'get_translations' );
        $_POST['language'] = 'it_IT';
        $_POST['strings'] = json_encode();

        try {
            $this->_handleAjax( 'trp_get_translations' );
        } catch ( WPAjaxDieStopException $e ) {
            // We expected this, do nothing.
        }


        // Check that the exception was thrown.
        $this->assertTrue( isset( $e ) );

        // The output should be a 1 for success.
        $this->assertEquals( '1', $e->getMessage() );*/

    }

}