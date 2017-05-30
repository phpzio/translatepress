<?php

class TRP_Machine_Translator{
    protected $settings;
    protected $trp_query;

    public function __construct( $settings, $trp_query ){
        $this->settings = $settings;
        $this->trp_query = $trp_query;
        // and probably other variable initialized to facilitate API call
    }

    public function is_available(){
        return false;
    }

    public function translate_array( $new_strings, $language_code ){
        //error_log(json_encode($new_strings));
        // strings are saved in the database encoded as the HTML DOM parser outputs them. e.g. While the band&#8217;s playin&#8217;
        // maybe run a decode function before sending them for translation.
        $strings = array();
        foreach( $new_strings as $new_string ){
            $strings[] = html_entity_decode( $new_string,  ENT_QUOTES );
        }



        //TODO API CALL

        //dummy
        $translated_strings = array();
        foreach ( $new_strings as $key => $string ){
            $translated_strings[$key] = 'something';
            if ( $string == 'Dolly'){
                error_log('aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa');
                $translated_strings[$key] = 'Molly';
            }

        }

        // will have the same indexes as $new_string
        return $translated_strings;
    }
}