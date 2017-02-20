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
        //TODO API CALL

        //$this->trp_query->insert_strings( $new_strings,$language_code );
        // todo store all the strings in db, regardless of whether translations are available
        return false;
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