<?php

class TRP_Machine_Translator{
    protected $version;

    public function __construct( $version, $settings ){
        $this->version = $version;
        $this->settings = $settings;
        // and probably other variable initialized to facilitate API call
    }

    public function translate_array( $new_strings ){
        //TODO API CALL

        //dummy
        $translated_strings = $new_strings;
        foreach ( $translated_strings as $key => $string ){
            if ( $string == 'Dolly'){
                $translated_strings[$key] = 'Molly';
            }

            if ( $string == 'I can tell, Dolly'){
                $translated_strings[$key] = 'Imi dau seama Dolly!!!';
            }
        }

        // will have the same indexes as $new_string
        return $translated_strings;
    }
}