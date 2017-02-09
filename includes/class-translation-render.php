<?php

class TRP_Translation_Render{
    protected $version;
    protected $settings;
    protected $machine_translator;

    public function __construct( $version, $settings, $machine_translator ){
        $this->version = $version;
        $this->settings = $settings;
        $this->machine_translator = $machine_translator;
    }

    public function start_object_cache(){
        if( is_admin() )
            return;

        if ( $this->start_output_buffering() ) {
            ob_start(array($this, 'translate_page'));
        }
    }

    protected function get_language(){
        //todo add all possible ways of determining language: cookies, global define etc.
        if ( ! empty ( $_GET['lang'] ) ){
            $language_code = esc_attr( $_GET['lang'] );
            if ( in_array( $language_code, $this->settings['translation-languages'] ) ) {
                return $language_code;
            }
        }
        return false;
    }

    protected function full_trim( $word ) {
        return trim( $word," \t\n\r\0\x0B\xA0�" );
    }

    public function translate_page( $output ){
        $language_code = $this->get_language();
        if ( $language_code === false ){
            return $output;
        }

        $translatable_strings = array();
        $html = trp_str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        foreach ($html->find('text') as $k => $row){
            $trimmed_text = $this->full_trim($row->outertext);
            if( $trimmed_text != "" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric( $trimmed_text ) && !preg_match('/^\d+%$/', $trimmed_text ) )
                $translatable_strings[] = $row;
        }

        global $wpdb;
        $start = microtime(true);

        $table_name = $wpdb->prefix . 'trp_dictionary_' . $this->settings['default-language'] . '_' . $language_code;


        //todo what happens if a string has quotes in it. it might break.
        $dictionary = $wpdb->get_results("SELECT original,translated FROM `" . $table_name . "` WHERE original IN ('".implode( "','", $translatable_strings )."')");

        $existing_strings = array();
        foreach( $dictionary as $string ){
            $existing_strings[] = $string->original;
            $output = str_replace( $string->original, $string->translated, $output );
        }

        $new_strings = array_diff( $translatable_strings, $existing_strings );

        $translated_strings = $this->machine_translator->translate_array( $new_strings );

        foreach ( $translated_strings as $key => $translated_string ){
            $output = str_replace( $new_strings[$key], $translated_string, $output );
        }

        return (microtime(true) - $start)  . '<!-- ' . serialize($new_strings) . ' -->' . $output;
    }

    protected function start_output_buffering(){
        $post_type = get_post_type();
        $post_types = array( 'post', 'page' );
        if ( in_array( $post_type, $post_types ) ){
            return true;
        }
        return false;
    }
}