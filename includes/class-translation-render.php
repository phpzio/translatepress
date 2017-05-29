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
            __( 'Meta Information', TRP_PLUGIN_SLUG ) => array( 'meta_desc', 'post_slug', 'page_title' ),
        ));

        foreach( $node_type_categories as $category_name => $node_types ){
            if ( in_array( $current_node_type, $node_types ) ){
                return $category_name;
            }
        }

        return __( 'String List', TRP_PLUGIN_SLUG );

    }

    protected function get_node_description( $current_node ){
        //todo provide descriptions based on current node for meta title, meta description etc.
        //( $html->find('meta[name="description"],meta[property="og:title"],meta[property="og:description"],meta[property="og:site_name"],meta[name="twitter:title"],meta[name="twitter:description"]') as $k => $row ){
        $node_type_descriptions = apply_filters( 'trp_node_type_descriptions',
            array(
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'name',
                    'value'         => 'description',
                    'description'   => __( 'Description', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'property',
                    'value'         => 'og:title',
                    'description'   => __( 'OG Title', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'property',
                    'value'         => 'og:site_name',
                    'description'   => __( 'OG Site Name', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'property',
                    'value'         => 'og:description',
                    'description'   => __( 'OG Description', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'name',
                    'value'         => 'twitter:title',
                    'description'   => __( 'Twitter Title', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'name',
                    'value'         => 'twitter:description',
                    'description'   => __( 'Twitter Description', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'post_slug',
                    'attribute'     => 'name',
                    'value'         => 'trp-slug',
                    'description'   => __( 'Post Slug', TRP_PLUGIN_SLUG )
                ),
                array(
                    'type'          => 'page_title',
                    'description'   => __( 'Page Title', TRP_PLUGIN_SLUG )
                ),

            ));

        foreach( $node_type_descriptions as $node_type_description ){
            if ( $current_node['type'] == $node_type_description['type'] &&
                (
                    ( isset( $node_type_description['attribute'] ) && isset( $current_node['node']->$node_type_description['attribute'] ) && $current_node['node']->$node_type_description['attribute'] == $node_type_description['value'] ) ||
                    ( ! isset( $node_type_description['attribute'] ) )
                )
            ) {
                return $node_type_description['description'];
            }
        }

        return '';

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
                    if ( $row->parent()->tag == 'title' ) {
                        //todo mark string as title
                        array_push($nodes, array('node' => $row, 'type' => 'page_title'));
                    }else {
                        array_push($nodes, array('node' => $row, 'type' => 'text'));
                    }
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
        foreach ($html->find('meta[name="trp-slug"]' ) as $k => $row ){
            if ( $this->full_trim($row->content)!="" && !is_numeric($this->full_trim($row->content)) && !preg_match('/^\d+%$/',$this->full_trim($row->content ) ) ) {
                array_push( $translateable_strings, $row->content );
                array_push( $nodes, array('node'=>$row,'type'=>'post_slug') );
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
            'page_title' => array(
                'accessor' => 'outertext',
                'attribute' => false
            ),
            'meta_desc' => array(
                'accessor' => 'content',
                'attribute' => false
            ),
            'post_slug' => array(
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
            }

            if ( $preview_mode ) {
                if ( $node_accessor [ $nodes[$i]['type'] ]['accessor'] == 'outertext' ) {
                    $outertext_details = '<translate-press data-trp-translate-id="' . $translated_string_ids[$translateable_strings[$i]]->id . '" data-trp-node-type="' . $this->get_node_type_category( $nodes[$i]['type'] ) . '"';
                    if ( $this->get_node_description( $nodes[$i] ) ) {
                        $outertext_details .= ' data-trp-node-description="' . $this->get_node_description($nodes[$i] ) . '"';
                    }
                    $outertext_details .= '>' . $nodes[$i]['node']->outertext . '</translate-press>';
                    $nodes[$i]['node']->outertext = $outertext_details;
                } else {
                    $nodes[$i]['node']->setAttribute('data-trp-translate-id', $translated_string_ids[ $translateable_strings[$i] ]->id );
                    $nodes[$i]['node']->setAttribute('data-trp-node-type', $this->get_node_type_category( $nodes[$i]['type'] ) );
                    if ( $this->get_node_description( $nodes[$i] ) ) {
                        $nodes[$i]['node']->setAttribute('data-trp-node-description', $this->get_node_description($nodes[$i]));
                    }

                }
            }

        }


        return /*(microtime(true) - $start)  . */ $html->save();
    }

    protected function get_translated_string_ids( $translated_strings, $language_code ){

        return $translated_strings_ids;
    }


    public function process_strings( $translateable_strings, $language_code ){
        $translated_strings = array();

        // get existing translations
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

        // machine translate new strings
        if ( $this->machine_translator->is_available() ) {
            //todo translate page title too
            $machine_strings = $this->machine_translator->translate_array( $new_strings, $language_code );
        }else{
            $machine_strings = false;
        }

        // update existing strings without translation if we have one now. also, do not insert duplicates for existing untranslated strings in db
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

        // insert remaining machine translations into db
        if ( $machine_strings !== false ) {
            foreach ($machine_strings as $i => $string) {
                if ( isset( $translated_strings[$i] ) ){
                    continue;
                }else {
                    $translated_strings[$i] = $machine_strings[$i];
                    array_push ( $update_strings, array(
                        'id' => NULL,
                        'original' => $new_strings[$i],
                        'translated' => sanitize_text_field($machine_strings[$i]),
                        'status' => $this->trp_query->get_constant_machine_translated() ) );
                    unset($new_strings[$i]);
                }
            }
        }

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


    protected function start_output_buffering(){
        $post_type = get_post_type();
        $post_types = array( 'post', 'page' );
        if ( in_array( $post_type, $post_types ) ){
            return true;
        }
        return false;
    }

    public function enqueue_dynamic_translation(){
        global $TRP_LANGUAGE;

        if ( $TRP_LANGUAGE != $this->settings['default-language'] ) {
            wp_enqueue_script('trp-dynamic-translator', TRP_PLUGIN_URL . 'assets/js/trp-translate-dom-changes.js', array('jquery'));

            $trp_data = array(
                'trp_ajax_url' => apply_filters('trp_ajax_url', TRP_PLUGIN_URL . 'includes/trp-ajax.php' ),
                'trp_wp_ajax_url' => apply_filters('trp_wp_ajax_url', admin_url('admin-ajax.php')),
                'trp_language' => $TRP_LANGUAGE
            );
            if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) {
                $trp_data['trp_ajax_url'] = $trp_data['trp_wp_ajax_url'];
            }
            wp_localize_script('trp-dynamic-translator', 'trp_data', $trp_data);
        }
    }
}
