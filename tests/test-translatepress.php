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

    private $trp;
    private $trp_url_converter;
    private $trp_settings;
    private $trp_query;
    private $trp_render;


    const NOT_TRANSLATED = 0;
    const MACHINE_TRANSLATED = 1;
    const HUMAN_REVIEWED = 2;
    const BLOCK_TYPE_REGULAR_STRING = 0;
    const BLOCK_TYPE_ACTIVE = 1;
    const BLOCK_TYPE_DEPRECATED = 2;

    public function get_new_strings(){
        $new_strings = array(
            'Normal strings',
            '¿Here we have some characters that can be problematic when they are in the end of beginning¡',
            '¡For good measure ¡¿ put some in the middle¿',
            'This is regular string placeholder'
        );
        return $new_strings;
    }

    public function get_update_strings(){
        $update_strings = array(
            array( 'id' => 1, 'original' => 'Normal strings', 'translated' => 'one', 'status' => 2, 'block_type' => 0 ),
            array( 'id' => 2, 'original' => '¿Here we have some characters that can be problematic when they are in the end of beginning¡', 'translated' => 'two', 'status' => 2, 'block_type' => 0 ),
            array( 'id' => 3, 'original' => '¡For good measure ¡¿ put some in the middle¿', 'translated' => 'three', 'status' => 2, 'block_type' => 0 ),
            array( 'id' => 4, 'original' => 'This is regular string placeholder', 'translated' => 'four', 'status' => 2, 'block_type' => 0 ),
        );
        return $update_strings;
    }

    /*public function get_gettext_strings(){
        $gettext_strings = array(
            array( 'original' => 'Gettext strings', 'translated' => 'Translated Gettext String', 'domain' => 'phpunit', 'status' => 2 ),
        );
        return $gettext_strings;
    }*/

    public function setUp() {
        parent::setUp();

        $this->trp = TRP_Translate_Press::get_trp_instance();
        $this->trp_url_converter = $this->trp->get_component('url_converter');
        $this->trp_settings = $this->trp->get_component('settings');
        $this->trp_query = $this->trp->get_component( 'query' );
        $this->trp_render = $this->trp->get_component( 'translation_render' );


    }

	/**
	 * Test urls
	 */
	public function test_translatepress_urls() {

        $post_id = self::factory()->post->create( array(
            'post_title' => 'This is a translation post test',
        ) );


        $settings = $this->trp_settings->get_settings();

        //get the secondary language
        $secondary_language = $settings['translation-languages'][1];


        global $TRP_LANGUAGE;
        $TRP_LANGUAGE_copy = $TRP_LANGUAGE;
        $default_home_url = home_url();
        $this->assertEquals( $default_home_url, 'http://example.org/en/' ) ;
        $this->go_to( $default_home_url );
        $this->assertTrue( !is_404() );
        $TRP_LANGUAGE = $secondary_language;
        $secondary_home_url = home_url();
        $this->assertEquals( $secondary_home_url, 'http://example.org/it/' ) ;
        $this->go_to( $secondary_home_url );
        $this->assertTrue( !is_404() );
        $TRP_LANGUAGE = $TRP_LANGUAGE_copy;

        $default_language_post_url = $this->trp_url_converter->get_url_for_language( $settings['default-language'], get_permalink($post_id) );
        $this->assertEquals( $default_language_post_url, 'http://example.org/en/this-is-a-translation-post-test/' ) ;
        $this->go_to( $default_language_post_url );
        $this->assertTrue( !is_404() );

        $secondary_language_post_url = $this->trp_url_converter->get_url_for_language( $secondary_language, get_permalink($post_id) );
        $this->assertEquals( $secondary_language_post_url, 'http://example.org/it/this-is-a-translation-post-test/' ) ;
        $TRP_LANGUAGE = $secondary_language;
        $this->go_to( $secondary_language_post_url );
        $this->assertTrue( !is_404() );
        $TRP_LANGUAGE = $TRP_LANGUAGE_copy;

        $default_home_url_from_secondary_language = $this->trp_url_converter->get_url_for_language( $settings['default-language'], $secondary_home_url );
        $this->assertEquals( $default_home_url_from_secondary_language, 'http://example.org/en/' ) ;
        $this->go_to( $default_home_url_from_secondary_language );
        $this->assertTrue( !is_404() );


        $secondary_language_custom_url = $this->trp_url_converter->get_url_for_language( $secondary_language, 'http://example.org/en/this-is-a-custom-link/' );
        $this->assertEquals( $secondary_language_custom_url, 'http://example.org/it/this-is-a-custom-link/' ) ;

        $secondary_language_custom_url_with_param = $this->trp_url_converter->get_url_for_language( $secondary_language, 'http://example.org/en/this-is-a-custom-link/?param=true' );
        $this->assertEquals( $secondary_language_custom_url_with_param, 'http://example.org/it/this-is-a-custom-link/?param=true' );


        $secondary_language_custom_url_with_parameters = $this->trp_url_converter->get_url_for_language( $secondary_language, 'http://example.org/en/this-is-a-custom-link/?param=true&param2=false' );
        $this->assertEquals( $secondary_language_custom_url_with_parameters, 'http://example.org/it/this-is-a-custom-link/?param=true&param2=false' );

	}


    public function test_translatepress_translations() {

        $new_strings = $this->get_new_strings();
        $update_strings = $this->get_update_strings();

        $this->trp_query->check_table('en_US', 'it_IT');
        $this->trp_query->insert_strings( $new_strings, array(), 'it_IT' );
        $this->trp_query->insert_strings( array(), $update_strings, 'it_IT' );

        $post_content = file_get_contents( dirname(__FILE__)  . "/input_html_en.html");


        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = 'it_IT';
        $translation = $this->trp_render->translate_page( $post_content );


        $expected_translation = file_get_contents( dirname(__FILE__)  . "/output_html_it.html");

        $this->assertEquals( $expected_translation, $translation );

    }


    /*public function test_translatepress_gettext(){

        $this->go_to( '/' );

        $gettext_strings = $this->get_gettext_strings();

        $this->trp_query->check_gettext_table('it_IT');
        $this->trp_query->insert_gettext_strings( $gettext_strings, 'it_IT' );


        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = 'it_IT';
        switch_to_locale( 'it_IT' );
        $gettext_content = '<h3>'.__('Gettext strings', 'phpunit').'</h3>';
        $gettext_translation = $this->trp_render->translate_page( $gettext_content );
	    print_r($gettext_content);
    }*/

}
