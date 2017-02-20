<?php

class TRP_Translation_Render{
    protected $settings;
    protected $machine_translator;
    protected $trp_query;


    public function __construct( $settings, $machine_translator, $trp_query ){
        $this->settings = $settings;
        $this->machine_translator = $machine_translator;
        $this->trp_query = $trp_query;
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

    public function shutdown(){
        $output = ob_get_clean();
        $this->translate_page($output);
    }

    public function translate_page( $output ){
        $start = microtime(true);
        $language_code = $this->get_language();
        if ($language_code === false) {
            return $output;
        }

        $no_translate_attribute = 'data-trp-skiptranslate';

        $translateable_strings = array();
        $nodes = array();
        $html = trp_str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        foreach ( $html->find('text') as $k => $row ){
            if($this->full_trim($row->outertext)!="" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric($this->full_trim($row->outertext)) && !preg_match('/^\d+%$/',$this->full_trim($row->outertext))
                && !$this->hasAncestorAttribute( $row, $no_translate_attribute )){
                if(strpos($row->outertext,'[vc_') === false) {
                    array_push( $translateable_strings, $row->outertext );
                    array_push($nodes, array('node'=>$row,'type'=>'text') );
                }
            }
        }
        foreach ( $html->find('input[type=\'submit\'],input[type=\'button\']') as $k => $row ){
            if($this->full_trim($row->value)!="" && !is_numeric($this->full_trim($row->value)) && !preg_match('/^\d+%$/',$this->full_trim($row->value))
                && !$this->hasAncestorAttribute( $row, $no_translate_attribute )) {
                array_push( $translateable_strings, html_entity_decode( $row->value ) );
                array_push( $nodes, array('node'=>$row,'type'=>'submit') );
            }
        }
        foreach ( $html->find('input[type=\'text\'],input[type=\'password\'],input[type=\'search\'],input[type=\'email\'],input:not([type]),textarea') as $k => $row ){
            if($this->full_trim($row->placeholder)!="" && !is_numeric($this->full_trim($row->placeholder)) && !preg_match('/^\d+%$/',$this->full_trim($row->placeholder))
                && !$this->hasAncestorAttribute( $row, $no_translate_attribute )){
                array_push( $translateable_strings, html_entity_decode ( $row->placeholder ) );
                array_push( $nodes, array('node'=>$row,'type'=>'placeholder') );
            }
        }
        foreach ( $html->find('meta[name="description"],meta[property="og:title"],meta[property="og:description"],meta[property="og:site_name"],meta[name="twitter:title"],meta[name="twitter:description"]') as $k => $row ){
            if($this->full_trim($row->content)!="" && !is_numeric($this->full_trim($row->content)) && !preg_match('/^\d+%$/',$this->full_trim($row->content))
                && !$this->hasAncestorAttribute( $row, $no_translate_attribute )){
                array_push( $translateable_strings, $row->content );
                array_push( $nodes, array('node'=>$row,'type'=>'meta_desc') );
            }
        }
        foreach ( $html->find('iframe') as $k => $row ) {
            if($this->full_trim($row->src)!="" && strpos($this->full_trim($row->src),'.youtube.') !== false	&& !$this->hasAncestorAttribute( $row, $no_translate_attribute )) {
                array_push( $translateable_strings, $row->src );
                array_push( $nodes, array('node'=>$row,'type'=>'iframe_src') );
            }
        }
        foreach ( $html->find('img') as $k => $row ) {
            if($this->full_trim($row->src)!="" && !$this->hasAncestorAttribute( $row, $no_translate_attribute ))
            {
                array_push( $translateable_strings, $row->src );
                array_push( $nodes, array('node'=>$row,'type'=>'image_src') );
            }
            if($this->full_trim($row->alt)!="" && !$this->hasAncestorAttribute( $row, $no_translate_attribute ))
            {
                array_push( $translateable_strings, $row->alt );
                array_push( $nodes, array('node'=>$row,'type'=>'image_alt') );
            }
        }
        foreach ( $html->find('a') as $k => $row ) {
            if($this->full_trim($row->href)!="" && substr($this->full_trim($row->href),-4)==".pdf" &&  !$this->hasAncestorAttribute( $row, $no_translate_attribute ))
            {
                array_push( $translateable_strings, $row->href );
                array_push( $nodes, array('node'=>$row,'type'=>'a_pdf') );
            }
        }

        $translated_strings = $this->process_strings( $translateable_strings, $language_code );

        foreach ( $nodes as $i => $node ) {
            if ( !isset( $translated_strings[$i] ) ){
                    continue;
            }

            if($nodes[$i]['type']=='text') {
                $nodes[$i]['node']->outertext = $translated_strings[$i];
            }
            if($nodes[$i]['type']=='submit') {
                $nodes[$i]['node']->setAttribute('value',$translated_strings[$i]);
            }
            if($nodes[$i]['type']=='placeholder') {
                $nodes[$i]['node']->setAttribute('placeholder',$translated_strings[$i]);
            }
            if($nodes[$i]['type']=='meta_desc') {
                $nodes[$i]['node']->content =  $translated_strings[$i];
            }
            if($nodes[$i]['type']=='iframe_src') {
                $nodes[$i]['node']->src =  $translated_strings[$i];
            }
            if($nodes[$i]['type']=='image_alt') {
                $nodes[$i]['node']->alt =  $translated_strings[$i];
            }
            if($nodes[$i]['type']=='image_src') {
                $nodes[$i]['node']->src =  $translated_strings[$i];
                if($nodes[$i]['node']->hasAttribute("srcset") && $nodes[$i]['node']->srcset !=  "" && $translated_strings[$i]!=$translateable_strings[$i]) {
                    $nodes[$i]['node']->srcset = "";
                }
            }
            if($nodes[$i]['type']=='a_pdf') {
                $nodes[$i]['node']->href =  $translated_strings[$i];
            }
        }


        return (microtime(true) - $start)  . $html->save();
        //return '<html><body><h1>OK</h1></body></html>';


    }


    protected function process_strings( $translateable_strings, $language_code ){
        $translated_strings = array();
        $dictionary = $this->trp_query->get_existing_translations( $translateable_strings, $language_code );

        $new_strings = array();
        foreach( $translateable_strings as $i => $string ){
            //strings existing in database,
            if ( isset( $dictionary[$string]->translated ) ){
                $translated_strings[$i] = $dictionary[$string]->translated;
            }else{
                $new_strings[$i] = $translateable_strings[$i];
            }
        }


        if ( $this->machine_translator->is_available() ) {
            //todo translate page title too
            $machine_strings = $this->machine_translator->translate_array( $new_strings, $language_code );
        }else{
            $machine_strings = false;
        }

        $untranslated_list = $this->trp_query->get_untranslated_strings( $new_strings, $language_code );

        $update_strings = array();
        foreach( $new_strings as $i => $string ){
            if ( isset( $untranslated_list[$string] ) ){
                //string exists as not translated, thus need to be updated if we have translation
                if ( isset( $machine_strings[$i] ) ) {
                    // we have a translation
                    array_push ( $update_strings, array(
                        'id' => $untranslated_list[$string]->id,
                        'original' => $untranslated_list[$string]->original,
                        'translated' => $machine_strings[$i],
                        'status' => $this->trp_query->get_constant_machine_translated ) );
                    $translated_strings[$i] = $machine_strings[$i];
                }
                unset( $new_strings[$i] );
            }
        }

        /*
         *
         get all NOT TRANSLATED.
         new strings (if new string == $not_translated){
                            if ( we have translation, update ){
                                create a new array for updating
                            }
                            else {  skip insert adica remove from new_strings   }

        insert new strings
        */

        // maybe start a new thread for this

        $this->trp_query->insert_strings( $new_strings, $update_strings, $language_code );

        return $translated_strings;
    }


    protected function hasAncestorAttribute($node,$attribute) {
        $currentNode = $node;
        while($currentNode->parent() && $currentNode->parent()->tag!="html") {
            if(isset($currentNode->parent()->$attribute))
                return true;
            else
                $currentNode = $currentNode->parent();
        }
        return false;
    }























    public function ttranslate_page( $output ){
        $language_code = $this->get_language();
        if ( $language_code === false ){
            return $output;
        }

        $translatable_strings = array();

        //$output = $this->get_dummy_html();

        $html = trp_str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        foreach ($html->find('text') as $k => $row){
            $trimmed_text = $this->full_trim($row->outertext);
            if( $trimmed_text != "" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric( $trimmed_text ) && !preg_match('/^\d+%$/', $trimmed_text ) )
                $translatable_strings[] = $row->outertext;
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

        return (microtime(true) - $start)  . '<!-- ' . json_encode($translatable_strings) . ' -->' . $output;
    }

    public function get_dummy_html(){
        return "<!DOCTYPE html>
<html>
<body>

<h1>My First Heading</h1>

<p>My first paragraph.</p>

</body>
</html>
";
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