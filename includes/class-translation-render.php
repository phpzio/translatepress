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
        global $TRP_LANGUAGE;
        if( is_admin() ||
        ( $TRP_LANGUAGE == $this->settings['default-language'] && ( ! isset( $_GET['trp-edit-translation'] ) || ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] != 'preview' ) ) )  ||
        ( isset( $_GET['trp-edit-translation']) && $_GET['trp-edit-translation'] == 'true' ) ) {
            return;
        }

        if ( $this->start_output_buffering() ) {
            mb_http_output("UTF-8");
            ob_start(array($this, 'translate_page'));
        }
    }

    protected function get_language(){
        global $TRP_LANGUAGE;
        if ( in_array( $TRP_LANGUAGE, $this->settings['translation-languages'] ) ) {
            if ( $TRP_LANGUAGE == $this->settings['default-language']  ){
                if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' )  {
                    foreach ($this->settings['translation-languages'] as $language) {
                        if ($language != $TRP_LANGUAGE) {
                            // return the first language not default. only used for preview mode
                            return $language;
                        }
                    }
                }
            }else {
                return $TRP_LANGUAGE;
            }
        }
        return false;
    }

    protected function full_trim( $word ) {
        return trim( $word," \t\n\r\0\x0B\xA0�" );
    }

    protected function get_node_type_category( $current_node_type ){
        $node_type_categories = apply_filters( 'trp_node_type_categories', array(
            __( 'Meta Information', TRP_PLUGIN_SLUG ) => array( 'meta_desc' ),
        ));

        foreach( $node_type_categories as $category_name => $node_types ){
            if ( in_array( $current_node_type, $node_types ) ){
                return $category_name;
            }
        }

        return __( 'String List', TRP_PLUGIN_SLUG );

    }

    public function shutdown(){
        $output = ob_get_clean();
        $this->translate_page($output);
    }

    public function translate_page( $output ){
        global $TRP_LANGUAGE;
        $start = microtime(true);
        $language_code = $this->get_language();
        if ($language_code === false) {
            return $output;
        }

        $no_translate_attribute = 'data-trp-skiptranslate';

        $translateable_strings = array();
        $nodes = array();
        //$output = utf8_encode ($output);
        $html = trp_str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        foreach ( $html->find('text') as $k => $row ){
            if($this->full_trim($row->outertext)!="" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric($this->full_trim($row->outertext)) && !preg_match('/^\d+%$/',$this->full_trim($row->outertext))
                && !$this->hasAncestorAttribute( $row, $no_translate_attribute )){
                if(strpos($row->outertext,'[vc_') === false) {
                    array_push( $translateable_strings, $this->full_trim( $row->outertext ) );
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

        $preview_mode = isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview';
        if ( $preview_mode ) {
            $translated_string_ids = $this->trp_query->get_string_ids($translateable_strings, $language_code);
        }
        //error_log(json_encode($translated_strings));
        $node_accessor = apply_filters( 'trp_node_accessors', array(
            'text' => array(
                'accessor' => 'outertext',
                'attribute' => false
            ),
            'meta_desc' => array(
                'accessor' => 'content',
                'attribute' => false
            ),
            'iframe_src' => array(
                'accessor' => 'src',
                'attribute' => false
            ),
            'image_alt' => array(
                'accessor' => 'alt',
                'attribute' => false
            ),
            'image_src' => array(
                'accessor' => 'src',
                'attribute' => false
            ),
            'a_pdf' => array(
                'accessor' => 'href',
                'attribute' => false
            ),
            'submit' => array(
                'accessor' => 'value',
                'attribute' => true
            ),
            'placeholder' => array(
                'accessor' => 'placeholder',
                'attribute' => true
            )
        ));

        foreach ( $nodes as $i => $node ) {
            $translation_available = isset( $translated_strings[$i] );
            if ( ! ( $translation_available || $preview_mode ) ){
                    continue;
            }

            if ( $translation_available && isset( $node_accessor[ $nodes[$i]['type'] ] ) && ! ( $preview_mode && ( $this->settings['default-language'] == $TRP_LANGUAGE ) ) ) {
                if ( $node_accessor[ $nodes[$i]['type'] ][ 'attribute' ] ){
                    $nodes[$i]['node']->setAttribute( $node_accessor[ $nodes[$i]['type'] ][ 'accessor' ], str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->getAttribute( $node_accessor[ $nodes[$i]['type'] ][ 'accessor' ] ) ) );
                }else{
                    $nodes[$i]['node']->$node_accessor[ $nodes[$i]['type'] ][ 'accessor' ] = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->$node_accessor[ $nodes[$i]['type'] ][ 'accessor' ] );
                }

                if ( $nodes[$i]['type'] == 'image_src' && $nodes[$i]['node']->hasAttribute("srcset") && $nodes[$i]['node']->srcset !=  "" && $translated_strings[$i] != $translateable_strings[$i]) {
                    $nodes[$i]['node']->srcset = "";
                }
/*
                switch ( $nodes[$i]['type'] ){
                    case 'text':
                        $nodes[$i]['node']->outertext = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->outertext);
                        break;
                    case 'meta_desc':
                        $nodes[$i]['node']->content = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->content );
                        break;
                    case 'iframe_src':
                        $nodes[$i]['node']->src = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->src );
                        break;
                    case 'image_alt':
                        $nodes[$i]['node']->alt = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->alt );
                        break;
                    case 'a_pdf':
                        $nodes[$i]['node']->href = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->href );
                        break;
                    case 'image_src':
                        $nodes[$i]['node']->src = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->src );
                        if($nodes[$i]['node']->hasAttribute("srcset") && $nodes[$i]['node']->srcset !=  "" && $translated_strings[$i]!=$translateable_strings[$i]) {
                            $nodes[$i]['node']->srcset = "";
                        }
                        break;
                    case 'submit':
                        $nodes[$i]['node']->setAttribute( 'value', str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->getAttribute('value') ) );
                        break;
                    case 'placeholder':
                        $nodes[$i]['node']->setAttribute( 'placeholder', str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->getAttribute('placeholder') ) );
                        break;
                }*/
            }

            if ( $preview_mode ) {
                if ($nodes[$i]['type'] == 'text') {
                    $nodes[$i]['node']->outertext = '<translate-press data-trp-translate-id="' . $translated_string_ids[$translateable_strings[$i]]->id . '" data-trp-node-type="' . $this->get_node_type_category( $nodes[$i]['type'] ) . '">' . $nodes[$i]['node']->outertext . '</translate-press>';
                } else {
                    $nodes[$i]['node']->setAttribute('data-trp-translate-id', $translated_string_ids[ $translateable_strings[$i] ]->id );
                    $nodes[$i]['node']->setAttribute('data-trp-node-type', $this->get_node_type_category( $nodes[$i]['type'] ) );
                }
            }

            /*if ( $nodes[$i]['type'] == 'text' ) {
                if ( $translation_available && ! ( $preview_mode && ( $this->settings['default-language'] == $TRP_LANGUAGE ) ) ) {
                    // keeps whitespaces of the original string.
                    $translated_strings[$i] = str_replace( $translateable_strings[$i], $translated_strings[$i], $nodes[$i]['node']->outertext);
                    if ( $preview_mode ) {
                        $nodes[$i]['node']->outertext = '<translate-press data-trp-translate-id="' . $translated_string_ids[$translateable_strings[$i]]->id . '">' . $translated_strings[$i] . '</translate-press>';
                    }else{
                        $nodes[$i]['node']->outertext = $translated_strings[$i];
                    }
                }else{
                    $nodes[$i]['node']->outertext = '<translate-press data-trp-translate-id="' . $translated_string_ids[$translateable_strings[$i]]->id . '">' . $nodes[$i]['node']->outertext . '</translate-press>';
                }
            }

            if ( $nodes[$i]['type']=='submit' ) {
                $nodes[$i]['node']->setAttribute('value',$translated_strings[$i]);
            }
            if($nodes[$i]['type']=='placeholder') {
                $nodes[$i]['node']->setAttribute('placeholder',$translated_strings[$i]);
            }
            if($nodes[$i]['type']=='meta_desc') {
                $nodes[$i]['node']->content = $translated_strings[$i];
            }
            if($nodes[$i]['type']=='iframe_src') {
                $nodes[$i]['node']->src = $translated_strings[$i];
            }
            if($nodes[$i]['type']=='image_alt') {
                $nodes[$i]['node']->alt = $translated_strings[$i];
            }
            if($nodes[$i]['type']=='image_src') {
                $nodes[$i]['node']->src = $translated_strings[$i];
                if($nodes[$i]['node']->hasAttribute("srcset") && $nodes[$i]['node']->srcset !=  "" && $translated_strings[$i]!=$translateable_strings[$i]) {
                    $nodes[$i]['node']->srcset = "";
                }
            }
            if($nodes[$i]['type']=='a_pdf') {
                $nodes[$i]['node']->href =  $translated_strings[$i];
            }*/

        }


        return /*(microtime(true) - $start)  . */ $html->save();
        //return '<html><body><h1>OK</h1></body></html>';


    }

    protected function get_translated_string_ids( $translated_strings, $language_code ){

        return $translated_strings_ids;
    }


    public function process_strings( $translateable_strings, $language_code ){
        $translated_strings = array();

        $dictionary = $this->trp_query->get_existing_translations( array_values($translateable_strings), $language_code );

        $new_strings = array();
        foreach( $translateable_strings as $i => $string ){
            //strings existing in database,
            if ( isset( $dictionary[$this->full_trim($string)]->translated ) ){
                $translated_strings[$i] = $dictionary[$this->full_trim($string)]->translated;
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
                        'original' => sanitize_text_field($untranslated_list[$string]->original),
                        'translated' => sanitize_text_field($machine_strings[$i]),
                        'status' => $this->trp_query->get_constant_machine_translated() ) );
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


 /*   public function ttranslate_page( $output ){
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
    }*/

    protected function start_output_buffering(){
        $post_type = get_post_type();
        $post_types = array( 'post', 'page' );
        if ( in_array( $post_type, $post_types ) ){
            return true;
        }
        return false;
    }
}
