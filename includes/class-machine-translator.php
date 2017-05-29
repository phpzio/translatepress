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
        if( !empty( $this->settings['g-translate'] ) && $this->settings['g-translate'] == 'yes' )
            return true;
        else
            return false;
    }

    public function translate_array( $new_strings, $language_code ){

        if( empty( $this->settings['g-translate-key'] ) )
            return array();

        $translated_strings = array();

        $translation_url = "https://www.googleapis.com/language/translate/v2";
        $translation_url = add_query_arg( array( 'key' => $this->settings['g-translate-key'] ), $translation_url );
        $translation_url = add_query_arg( array( 'source' => $this->settings['default-language'] ), $translation_url );
        $translation_url = add_query_arg( array( 'target' => $language_code ), $translation_url );

        foreach( $new_strings as $new_string ){
            $translation_url .= '&q='.rawurlencode(html_entity_decode( $new_string, ENT_QUOTES ));
        }

        $response = wp_remote_get( $translation_url );

        if ( is_array( $response ) && ! is_wp_error( $response ) ) {
            $translation_response = json_decode( $response['body'] );
            if( !empty( $translation_response->error ) ){
                return array();
            }
            else{
                $translations = $translation_response->data->translations;
                foreach( $translations as $key => $translation ){
                    $translated_strings[$key] = $translation->translatedText;
                }
            }
        }

        // will have the same indexes as $new_string
        return $translated_strings;
    }
}