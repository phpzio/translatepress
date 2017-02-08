<?php

class TRP_Translation_Render{
    protected $version;
    protected $settings;

    public function __construct( $version, $settings ){
        $this->version = $version;
        $this->settings = $settings;
    }

    public function start_object_cache(){
        if( is_admin() )
            return;

        ob_start( array( $this, 'translate_page' ) );
        //$this->translate_page('<html><body><h1>tttteeexxt</h1></body></html>');

    }

    private function get_language(){
        //todo add all possible ways of determining language: cookies, global define etc.
        if ( ! empty ( $_GET['lang'] ) ){
            $language_code = esc_attr( $_GET['lang'] );
            if ( in_array( $language_code, $this->settings['translation-languages'] ) ) {
                return $language_code;
            }
        }
        return false;
    }

    private function full_trim( $word ) {
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

        $output = microtime(true).$output;

        $table_name = $wpdb->prefix . 'trp_dictionary_' . $this->settings['default-language'] . '_' . $language_code;
        $dictionary = $wpdb->get_results("SELECT original,translated FROM `" . $table_name . "` WHERE original IN ('".implode( "','", $translatable_strings )."')");
        foreach( $dictionary as $string ){
            $output = str_replace($string->original, $string->translated, $output);
        }
        //var_dump($translation);
        $output .= microtime(true);

        return $output;
    }
}