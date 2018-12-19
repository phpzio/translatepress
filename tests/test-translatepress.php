<?php
/**
 * Class TranslatePressTest
 *
 * @package Translatepress
 */

/**
 * Sample test case.
 */
class TranslatePressTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
        //$this->assertEquals( 'http://www.url.com' , 'hjjhjhjh');

        $trp_settings = new TRP_Settings();
        $trp_render = new TRP_Translation_Render( $trp_settings->get_settings() );

        $this->assertEquals( $trp_render->translate_page( 'this is a text' ), 'this is a text' ) ;

	}
}
