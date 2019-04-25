<?php
/**
 * Class TranslatePressTest
 *
 * @package Translatepress
 */

/**
 * Test cases for Translatepress functions.
 */
class TranslatePressTest extends WP_UnitTestCase {

    private $trp;
    private $trp_url_converter;
    private $trp_settings;
    private $trp_query;
    private $trp_render;
    private $trp_manager;


    const NOT_TRANSLATED = 0;
    const MACHINE_TRANSLATED = 1;
    const HUMAN_REVIEWED = 2;
    const BLOCK_TYPE_REGULAR_STRING = 0;
    const BLOCK_TYPE_ACTIVE = 1;
    const BLOCK_TYPE_DEPRECATED = 2;

    protected static function getRenderMethod($name) {
        $class = new ReflectionClass('TRP_Translation_Render');
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

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

    public function get_gettext_strings(){
        $gettext_strings = array(
            array( 'original' => 'Gettext strings', 'translated' => 'Translated Gettext String', 'domain' => 'phpunit', 'status' => 2 ),
        );
        return $gettext_strings;
    }

    public function setUp() {
        parent::setUp();

        $this->trp = TRP_Translate_Press::get_trp_instance();
        $this->trp_url_converter = $this->trp->get_component('url_converter');
        $this->trp_settings = $this->trp->get_component('settings');
        $this->trp_query = $this->trp->get_component( 'query' );
        $this->trp_render = $this->trp->get_component( 'translation_render' );
        $this->trp_manager = $this->trp->get_component( 'translation_manager' );


    }

    function tearDown() {
        parent::tearDown();
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


        $this->assertTrue( $this->trp_render->is_external_link( 'http://google.com' ) );
        $this->assertFalse( $this->trp_render->is_external_link( 'http://example.org/it/this-is-a-custom-link/' ) );

        $this->assertEquals( 'http://example.org/it/this-is-a-custom-link/', $this->trp_render->maybe_is_local_url( 'https://www.example.org/it/this-is-a-custom-link/' ) );

        $is_different_language = self::getRenderMethod('is_different_language');
        $is_different_language_response = $is_different_language->invokeArgs( $this->trp_render, array( 'http://example.org/it/this-is-a-custom-link/' ));
        $this->assertTrue($is_different_language_response);

        $is_admin_link = self::getRenderMethod('is_admin_link');
        $is_admin_link_response = $is_admin_link->invokeArgs( $this->trp_render, array( 'http://example.org/it/this-is-a-custom-link/' ));
        $this->assertFalse($is_admin_link_response);
        $is_admin_link_response = $is_admin_link->invokeArgs( $this->trp_render, array( 'http://example.org/wp-admin/' ));
        $this->assertTrue($is_admin_link_response);

        $_REQUEST[ 'trp-form-language' ] = 'it';
        $this->assertEquals( "http://example.org/it/form/", $this->trp_render->force_language_on_form_url_redirect( 'http://example.org/form/', '' ) );

        $this->go_to( $secondary_language_post_url );
        $this->assertEquals( "http://example.org/it/this-is-a-translation-post-test/", $this->trp_url_converter->cur_page_url() );

        $this->go_to( $secondary_language_post_url );
        $this->assertEquals( "it_IT", $this->trp_url_converter->get_lang_from_url_string() );
        $this->assertEquals( "en_US", $this->trp_url_converter->get_lang_from_url_string('http://example.org/en/form/') );

        $this->assertEquals( "http://example.org", $this->trp_url_converter->get_abs_home() );

        $this->assertEquals( "it", $this->trp_url_converter->get_url_slug('it_IT') );



	}


    public function test_translatepress_translations() {

        $new_strings = $this->get_new_strings();
        $update_strings = $this->get_update_strings();

        $this->trp_query->check_table('en_US', 'it_IT');
        $this->trp_query->insert_strings( $new_strings, 'it_IT' );
        $this->trp_query->update_strings( $update_strings, 'it_IT' );

        $post_content = file_get_contents( dirname(__FILE__)  . "/input_html_en.html");


        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = 'it_IT';
        $translation = $this->trp_render->translate_page( $post_content );


        $expected_translation = file_get_contents( dirname(__FILE__)  . "/output_html_it.html");

        $this->assertEquals( $expected_translation, $translation );

    }


    public function test_translatepress_json_translations() {

        $new_strings = $this->get_new_strings();
        $update_strings = $this->get_update_strings();

        //$this->trp_query->check_table('en_US', 'it_IT');
        $this->trp_query->insert_strings( $new_strings, 'it_IT' );
        $this->trp_query->update_strings( $update_strings, 'it_IT' );

        $json_content = json_encode( array(
                                        '<span>Normal strings</span>',
                                        array(
                                            '¿Here we have some characters that can be problematic when they are in the end of beginning¡',
                                            '<p>¡For good measure ¡¿ put some in the middle¿</p>',
                                            ),
                                        'This is regular string placeholder'
                                    ));



        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = 'it_IT';
        $json_translation = $this->trp_render->translate_page( $json_content );

        $this->assertEquals( array(
            '<span>one</span>',
            array(
                '¿Here we have some characters that can be problematic when they are in the end of beginning¡',
                '<p>three</p>',
            ),
            'This is regular string placeholder'
        ), json_decode( $json_translation ) );

    }


    public function test_translatepress_gettext(){

        $gettext_strings = $this->get_gettext_strings();
        $this->trp_query->check_gettext_table('it_IT');
        $this->trp_query->insert_gettext_strings( $gettext_strings, 'it_IT' );

        global $TRP_LANGUAGE;

        $TRP_LANGUAGE = 'it_IT';

        $this->trp_manager->create_gettext_translated_global();
        global $trp_translated_gettext_texts;
        $this->assertTrue( !empty( $trp_translated_gettext_texts ) );

        $proecessed_gettext = $this->trp_manager->process_gettext_strings( 'Translated Gettext String', 'Gettext strings', 'phpunit');
        $this->assertTrue( strpos( $proecessed_gettext, '#!trpst#' ) !== false );

        $TRP_LANGUAGE = 'en_US';
        $proecessed_gettext = $this->trp_manager->process_gettext_strings( 'Translated Gettext String', 'Gettext strings', 'phpunit-two');//changed domain so it won't return the same string as the call above now that we have a global that retains the last processed string
        $this->assertTrue( strpos( $proecessed_gettext, '#!trpst#' ) === false );//I think it does not have tags here because we don not have the table ?

        $string_with_trp_tags = '#!trpst#trp-gettext data-trpgettextoriginal=11#!trpen#Some clean string here#!trpst#/trp-gettext#!trpen#';
        $stripped_tags = TRP_Translation_Manager::strip_gettext_tags( $string_with_trp_tags );
        $this->assertEquals( 'Some clean string here', $stripped_tags );

        $this->assertFalse( TRP_Translation_Manager::is_ajax_on_frontend() );
        define( 'DOING_AJAX', true );
        $this->assertTrue( TRP_Translation_Manager::is_ajax_on_frontend() );

    }

}
