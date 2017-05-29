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
        return true;
    }

    public function translate_array( $new_strings, $language_code ){

        // strings are saved in the database encoded as the HTML DOM parser outputs them. e.g. While the band&#8217;s playin&#8217;
        // maybe run a decode function before sending them for translation.
        $strings = array();
        foreach( $new_strings as $new_string ){
            $strings[] = html_entity_decode( $new_string,  ENT_QUOTES );
        }


        
        //TODO API CALL

        //dummy
        $translated_strings = $new_strings;
        foreach ( $translated_strings as $key => $string ){

            if ( $string == 'Dolly'){
                $translated_strings[$key] = 'Molly';
            }

            if ( $string == "\nI can tell, Dolly"){
                $translated_strings[$key] = 'Imi dau seama Dolly!!!';
            }

            if ( $string == "\nWhile the band&#8217;s playin&#8217;" ){
                $translated_strings[$key] = 'Cand canta&#8217; lautarii';
            }

        }

        // will have the same indexes as $new_string
        return $translated_strings;
    }
}