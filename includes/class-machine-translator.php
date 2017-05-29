<?php

class TRP_Machine_Translator{
    protected $settings;
    protected $trp_query;

    public function __construct( $settings, $trp_query ){
        $this->settings = $settings;
        $this->trp_query = $trp_query;
        // and probably other variable initialized to facilitate API call
        add_action('init', array( $this, 'translate_array' ));
    }

    public function is_available(){
        if( !empty( $this->settings['g-translate'] ) && $this->settings['g-translate'] == 'yes' )
            return true;
        else
            return false;
    }

    public function translate_array( $new_strings=null, $language_code=null ){

        if( empty( $this->settings['g-translate-key'] ) )
            return $new_strings;

        if( empty($new_strings) )
            $new_strings = array('Hello Dolly', 'I am a happy little camper');

        $translation_url = "https://www.googleapis.com/language/translate/v2";
        $translation_url = add_query_arg( array( 'key' => $this->settings['g-translate-key'] ), $translation_url );
        $translation_url = add_query_arg( array( 'source' => "en" ), $translation_url );
        $translation_url = add_query_arg( array( 'target' => "fr" ), $translation_url );
        foreach( $new_strings as $new_string ){
            $translation_url .= '&q='.rawurlencode($new_string);
        }

        $response = wp_remote_get( $translation_url );

        if ( is_array( $response ) && ! is_wp_error( $response ) ) {
            $translation_response = json_decode( $response['body'] );
            if( !empty( $translation_response->error ) ){
                return $new_strings;
            }
            else{
                $translations = $translation_response->data->translations;
                foreach( $translations as $key => $translation ){
                    $new_strings[$key] = $translation->translatedText;
                }
            }
        }

        /*$translated_strings = $new_strings;
                    foreach ( $translated_strings as $key => $string ){
                        if ( $string == 'Archives'){
                            $translated_strings[$key] = 'aaaaaaaa';
                        }

                        if ( $string == "\nI can tell, Dolly"){
                            $translated_strings[$key] = 'Imi dau seama Dolly!!!';
                        }

                        if ( $string == "\nWhile the band&#8217;s playin&#8217;" ){
                            $translated_strings[$key] = 'Cand canta&#8217; lautarii';
                        }

                    }*/

        // will have the same indexes as $new_string
        return $new_strings;
    }
}