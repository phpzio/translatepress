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

    public function setUp() {
        parent::setUp();
    }

	/**
	 * Test urls
	 */
	public function test_translatepress_urls() {

        $post_id = self::factory()->post->create( array(
            'post_title' => 'This is a translation post test',
        ) );


        //initialize Translate Press Components
        $trp = TRP_Translate_Press::get_trp_instance();
        $trp_url_converter = $trp->get_component('url_converter');
        $trp_settings = $trp->get_component('settings');
        $settings = $trp_settings->get_settings();

        //get the secondary language
        $secondary_language = $settings['translation-languages'][1];


        global $TRP_LANGUAGE;
        $TRP_LANGUAGE_copy = $TRP_LANGUAGE;
        $default_home_url = home_url();
        $this->assertEquals( $default_home_url, 'http://example.org/en/' ) ;
        $TRP_LANGUAGE = $secondary_language;
        $secondary_home_url = home_url();
        $this->assertEquals( $secondary_home_url, 'http://example.org/it/' ) ;
        $TRP_LANGUAGE = $TRP_LANGUAGE_copy;

        $default_language_post_url = $trp_url_converter->get_url_for_language( $settings['default-language'], get_permalink($post_id) );
        $this->assertEquals( $default_language_post_url, 'http://example.org/en/this-is-a-translation-post-test/' ) ;

        $secondary_language_post_url = $trp_url_converter->get_url_for_language( $secondary_language, get_permalink($post_id) );
        $this->assertEquals( $secondary_language_post_url, 'http://example.org/it/this-is-a-translation-post-test/' ) ;

        $default_home_url_from_secondary_language = $trp_url_converter->get_url_for_language( $settings['default-language'], $secondary_home_url );
        $this->assertEquals( $default_home_url_from_secondary_language, 'http://example.org/en/' ) ;


        $secondary_language_custom_url = $trp_url_converter->get_url_for_language( $secondary_language, 'http://example.org/en/this-is-a-custom-link/' );
        $this->assertEquals( $secondary_language_custom_url, 'http://example.org/it/this-is-a-custom-link/' ) ;

        $secondary_language_custom_url_with_param = $trp_url_converter->get_url_for_language( $secondary_language, 'http://example.org/en/this-is-a-custom-link/?param=true' );
        $this->assertEquals( $secondary_language_custom_url_with_param, 'http://example.org/it/this-is-a-custom-link/?param=true' );

        $secondary_language_custom_url_with_parameters = $trp_url_converter->get_url_for_language( $secondary_language, 'http://example.org/en/this-is-a-custom-link/?param=true&param2=false' );
        $this->assertEquals( $secondary_language_custom_url_with_parameters, 'http://example.org/it/this-is-a-custom-link/?param=true&param2=false' );

	}


    public function test_translatepress_translations() {

        $this->set_permalink_structure('%postname%');

        //initialize Translate Press Components
        $trp = TRP_Translate_Press::get_trp_instance();
        $trp_render = $trp->get_component('translation_render');


        $post = self::factory()->post->create_and_get( array(
            'post_title' => 'Translation testing',
            'post_content' => '<p>This page is for testing. (Plugin <strong>TranslatePress - Testing</strong> should be active)</p>
<h3>Normal strings</h3>'. __('some gettest text') .'
<p>¿Here we have some characters that can be problematic when they are in the end of beginning¡</p>
<p>¡For good measure ¡¿ put some in the middle¿</p>
<p>"Here there are quotes"</p>
<p>\'Single quotes\'</p>
<p>`Back ticks`</p>
<p>Some whitespace  in the middle after the word whitespace.</p>
<p>A lot of whitespaces         everywhere        both in middle and on edges</p>
<p>Random stuff  ¿┴╢  ï▒¿¡/ // ///   \</p>
<h3>Strings in attributes - to be added in future versions</h3>
<p><img src="https://upload.wikimedia.org/wikipedia/en/7/7b/Image_in_Glossographia.png" alt="this is alt text of mac image" /></p>
<p><input type="text" placeholder="This is regular string placeholder" /></p>
<p><input type="text" value="This is regular string value" /></p>
<p><input type="button" value="This is regular string button" /></p>
<p><input type="submit" value="This is regular string submit" /></p>
<p><button>This is button.</button></p>
<p><select>
<option>Option one</option>
<option>Option two</option>
<option>Option three</option>
</select></p>
<p> </p>'
        ));

        global $TRP_LANGUAGE;
        $TRP_LANGUAGE = 'it_IT';
        $translation = $trp_render->translate_page( $post->post_content );
        var_dump($translation);

        //ob_start();
        //$this->go_to(get_permalink($post_id));
        //$this->go_to('/');
        //$out1 = ob_get_contents();
        //ob_end_clean();



        //$response = file_get_contents( '.' );
        //$response = get_http_origin();
        //var_dump($_SERVER['REQUEST_URI']);
        //var_dump($response);
        $this->assertEquals( $trp_render->translate_page( 'this is a text' ), 'this is a text' ) ;
    }

}
