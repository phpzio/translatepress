<?php

/**
 * Class TRP_Translation_Render
 *
 * Translates pages.
 */
class TRP_Translation_Render{
    protected $settings;
    protected $machine_translator;
    protected $trp_query;
    protected $url_converter;

    /**
     * TRP_Translation_Render constructor.
     *
     * @param array $settings       Settings options.
     */
    public function __construct( $settings ){
        $this->settings = $settings;
    }

    /**
     * Start Output buffer to translate page.
     */
    public function start_output_buffer(){
        global $TRP_LANGUAGE;
        if( TRP_Translation_Manager::is_ajax_on_frontend() ){
            //in this case move forward
        }else if( is_admin() ||
            ( $TRP_LANGUAGE == $this->settings['default-language'] && ( ! isset( $_REQUEST['trp-edit-translation'] ) || ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] != 'preview' ) ) )  ||
            ( isset( $_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] == 'true' ) ) {
            return;
        }

        mb_http_output("UTF-8");
        ob_start(array($this, 'translate_page'));
    }

    /**
     * Function to hide php errors and notice and instead log them in debug.log so we don't store the notice strings inside the db if WP_DEBUG is on
     */
    public function trp_debug_mode_off(){
        if ( WP_DEBUG ) {
            global $TRP_LANGUAGE;
            if (is_admin() ||
                ($TRP_LANGUAGE == $this->settings['default-language'] && (!isset($_REQUEST['trp-edit-translation']) || (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] != 'preview'))) ||
                (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] == 'true')
            ) {
                return; //don't do nothing if we are not storing strings
            }

            ini_set('display_errors', 0);
            ini_set('log_errors', 1);
            ini_set('error_log', WP_CONTENT_DIR . '/debug.log');
        }
    }

    /**
     * Forces the language to be the first non default one in the preview translation editor.
     * We're doing this because we need the ID's.
     * Otherwise we're just returning the global $TRP_LANGUAGE
     *
     * @return string       Language code.
     */
    protected function force_language_in_preview(){
        global $TRP_LANGUAGE;
        if ( in_array( $TRP_LANGUAGE, $this->settings['translation-languages'] ) ) {
            if ( $TRP_LANGUAGE == $this->settings['default-language']  ){
                // in the translation editor we need a different language then the default because we need string ID's.
                // so we're forcing it to the first translation language because if it's the default, we're just returning the $output
                if ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview' )  {
                    if( count( $this->settings['publish-languages'] ) > 1 ){
                        foreach ($this->settings['translation-languages'] as $language) {
                            if ($language != $TRP_LANGUAGE) {
                                // return the first language not default. only used for preview mode
                                return $language;
                            }
                        }
                    }
                    else{
                        return $TRP_LANGUAGE;
                    }
                }
            }else {
                return $TRP_LANGUAGE;
            }
        }
        return false;
    }

    /**
     * Trim strings.
     *
     * @param string $string      Raw string.
     * @return string           Trimmed string.
     */
    public function full_trim( $string ) {
        /* Apparently the � char in the trim function turns some strings in an empty string so they can't be translated but I don't really know if we should remove it completely
        Removed chr( 194 ) . chr( 160 ) because it altered some special characters (¿¡)
        Also removed \xA0 (the same as chr(160) for altering special characters */
        //$word = trim($word," \t\n\r\0\x0B\xA0�".chr( 194 ) . chr( 160 ) );

        /* Solution to replace the chr(194).chr(160) from trim function, in order to escape the whitespace character ( \xc2\xa0 ), an old bug that couldn't be replicated anymore. */
	    $prefix = "\xc2\xa0";
	    $prefix_length = strlen($prefix);
	    do{
		    $previous_iteration_string = $string;
		    $string = trim( $string," \t\n\r\0\x0B");
		    if ( substr( $string, 0, $prefix_length ) == $prefix ) {
			    $string = substr( $string, $prefix_length );
		    }
		    if ( substr( $string, - $prefix_length, $prefix_length ) == $prefix ) {
			    $string = substr( $string, 0, - $prefix_length );
		    }
	    }while( $string != $previous_iteration_string );

        if ( strip_tags( $string ) == "" || trim ($string, " \t\n\r\0\x0B\xA0�.,/`~!@#\$€£%^&*():;-_=+[]{}\\|?/<>1234567890'\"" ) == '' ){
	        $string = '';
        }

        return $string;
    }

    /**
     * Preview mode string category name for give node type.
     *
     * @param string $current_node_type         Node type.
     * @return string                           Category name.
     */
    protected function get_node_type_category( $current_node_type ){
        $node_type_categories = apply_filters( 'trp_node_type_categories', array(
            __( 'Meta Information', 'translatepress-multilingual' ) => array( 'meta_desc', 'post_slug', 'page_title' ),
        ));

        foreach( $node_type_categories as $category_name => $node_types ){
            if ( in_array( $current_node_type, $node_types ) ){
                return $category_name;
            }
        }

        return __( 'String List', 'translatepress-multilingual' );

    }

    /**
     * String description to be used in preview mode dropdown list of strings.
     *
     * @param object $current_node          Current node.
     * @return string                       Node description.
     */
    protected function get_node_description( $current_node ){
        $node_type_descriptions = apply_filters( 'trp_node_type_descriptions',
            array(
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'name',
                    'value'         => 'description',
                    'description'   => __( 'Description', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'property',
                    'value'         => 'og:title',
                    'description'   => __( 'OG Title', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'property',
                    'value'         => 'og:site_name',
                    'description'   => __( 'OG Site Name', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'property',
                    'value'         => 'og:description',
                    'description'   => __( 'OG Description', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'name',
                    'value'         => 'twitter:title',
                    'description'   => __( 'Twitter Title', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'meta_desc',
                    'attribute'     => 'name',
                    'value'         => 'twitter:description',
                    'description'   => __( 'Twitter Description', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'post_slug',
                    'attribute'     => 'name',
                    'value'         => 'trp-slug',
                    'description'   => __( 'Post Slug', 'translatepress-multilingual' )
                ),
                array(
                    'type'          => 'page_title',
                    'description'   => __( 'Page Title', 'translatepress-multilingual' )
                ),

            ));

        foreach( $node_type_descriptions as $node_type_description ){
            if ( isset( $node_type_description['attribute'] )) {
                $attribute = $node_type_description['attribute'];
            }
            if ( $current_node['type'] == $node_type_description['type'] &&
                (
                    ( isset( $node_type_description['attribute'] ) && isset( $current_node['node']->$attribute ) && $current_node['node']->$attribute == $node_type_description['value'] ) ||
                    ( ! isset( $node_type_description['attribute'] ) )
                )
            ) {
                return $node_type_description['description'];
            }
        }

        return '';

    }

    /**
     * Finding translateable strings and replacing with translations.
     *
     * Method called for output buffer.
     *
     * @param string $output        Entire HTML page as string.
     * @return string               Translated HTML page.
     */
    public function translate_page( $output ){
        $output = apply_filters('trp_before_translate_content', $output);

        if ( strlen( $output ) < 1 ){
            return $output;
        }

        global $TRP_LANGUAGE;
        $language_code = $this->force_language_in_preview();
        if ($language_code === false) {
            return $output;
        }


        /* if there is an ajax request and we have a json response we need to parse it and only translate the nodes that contain html  */
        if( TRP_Translation_Manager::is_ajax_on_frontend() ) {

            /* if it's one of our own ajax calls don't do nothing */
            if( !empty( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'trp_' ) === 0 ){
                return $output;
            }

            //check if we have a json response
            if (is_array($json_array = json_decode($output, true))) {
                if (!empty($json_array)) {
                    foreach ($json_array as $key => $value) {
                        if (!empty($value)) {
                            if (!is_array($value)) { //if the current element is not an array check if it a html text and translate
                                if (html_entity_decode((string)$value) != strip_tags(html_entity_decode((string)$value))) {
                                    $json_array[$key] = $this->translate_page(stripslashes($value));
                                }
                            } else {//look for the html elements
                                foreach( $value as $k => $v ){
                                    if( !empty( $v ) ) {
                                        if (!is_array($v)) {
                                            if (html_entity_decode((string)$v) != strip_tags(html_entity_decode((string)$v))) {
                                                $json_array[$key][$k] = $this->translate_page(stripslashes($v));
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                return trp_safe_json_encode($json_array);
            }
        }

        /**
         * tries to fix the html document. It is off by default. use at own risk
         * */
        if( apply_filters( 'trp_try_fixing_invalid_html', false ) ) {
            if( class_exists('DOMDocument') ) {
                $dom = new DOMDocument();
                libxml_use_internal_errors(true);//so no warnings will show up for invalid html
                $dom->loadHTML($output, LIBXML_NOWARNING);
                $output = $dom->saveHTML();
            }
        }

        $no_translate_attribute = 'data-no-translation';

        $translateable_strings = array();
        $nodes = array();

        $html = trp_str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        /**
         * When we are in the translation editor: Intercept the trp-gettext that was wrapped around all the gettext texts, grab the attribute data-trpgettextoriginal
         * which contains the original translation id and move it to the parent node if the parent node only contains that string then remove the  wrap trp-gettext, otherwise replace it with another tag.
         * Also set a no-translation attribute.
         * When we are in a live translation case: Intercept the trp-gettext that was wrapped around all the gettext texts, set a no-translation attribute to the parent node if the parent node only contains that string
         * then remove the  wrap trp-gettext, otherwise replace the wrap with another tag and do the same to it
         * We identified two cases: the wrapper trp-gettext can be as a node in the dome or ot can be inside a html attribute ( for example value )
         * and we need to treat them differently
         */

        /* store the nodes in arrays so we can sort the $trp_rows which contain trp-gettext nodes from the DOM according to the number of children and we process the simplest first */
        $trp_rows = array();
        $trp_attr_rows = array();
        foreach ( $html->find("*[!nuartrebuisaexiteatributulasta]") as $k => $row ){
            if( $row->hasAttribute('data-trpgettextoriginal') ){
                $trp_rows[count( $row->children )][] = $row;
            }
            else{
                $trp_attr_rows[] = $row;
            }
        }

        /* sort them here ascending by key where the key is the number of children */
        /* here we add support for gettext inside gettext */
        ksort($trp_rows);
        foreach( $trp_rows as $level ){
            foreach( $level as $row ){
                $original_gettext_translation_id = $row->getAttribute('data-trpgettextoriginal');
                /* Parent node has no other children and no other innertext besides the current node */
                if( count( $row->parent()->children ) == 1 && $row->parent()->innertext == $row->outertext ){
                    $row->outertext = $row->innertext();
                    $row->parent()->setAttribute($no_translate_attribute, '');
                    // we are in the editor
                    if (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] == 'preview') {
                        //move up the data-trpgettextoriginal attribute
                        $row->parent()->setAttribute('data-trpgettextoriginal', $original_gettext_translation_id);
                    }
                }
                else{
                    $row->outertext = '<trp-wrap class="trp-wrap" data-no-translation';
                    if (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] == 'preview') {
                        $row->outertext .= ' data-trpgettextoriginal="'. $original_gettext_translation_id .'"';
                    }
                    $row->outertext .= '>'.$row->innertext().'</trp-wrap>';
                }
            }
        }

        foreach( $trp_attr_rows as $row ){
            $all_attributes = $row->getAllAttributes();
            if( !empty( $all_attributes ) ) {
                foreach ($all_attributes as $attr_name => $attr_value) {
                    if (strpos($attr_value, 'trp-gettext ') !== false) {
                        //if we have json content in the value of the attribute, we don't do anything. The trp-wrap will be removed later in the code
                        if (is_array($json_array = json_decode( html_entity_decode( $attr_value, ENT_QUOTES ), true ) ) ) {
                            continue;
                        }

                        // convert to a node
                        $node_from_value = trp_str_get_html(html_entity_decode(htmlspecialchars_decode($attr_value, ENT_QUOTES)));
                        foreach ($node_from_value->find('trp-gettext') as $nfv_row) {
                            $nfv_row->outertext = $nfv_row->innertext();
                            $row->setAttribute($attr_name, $node_from_value->save() );
                            if( !$row->has_child() ){// if the node doesn't have children set the needed attributes, else it means that there are other nodes inside so probably they are the ones displayed
                                if( empty( $row->innertext ) )// add the no translate attribute only if it does not contain any kind of text
                                    $row->setAttribute($no_translate_attribute, '');
                                // we are in the editor
                                if (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] == 'preview') {
                                    $original_gettext_translation_id = $nfv_row->getAttribute('data-trpgettextoriginal');
                                    $row->setAttribute('data-trpgettextoriginal', $original_gettext_translation_id);
                                }
                            }
                        }
                    }
                }
            }
        }

        /* save it as a string */
        $trpremoved = $html->save();
        /* perform preg replace on the remaining trp-gettext tags */
        $trpremoved = preg_replace( '/(<|&lt;)trp-gettext (.*?)(>|&gt;)/', '', $trpremoved );
        $trpremoved = preg_replace( '/(<|&lt;)(.?)\/trp-gettext(>|&gt;)/', '', $trpremoved );

        $html = trp_str_get_html($trpremoved, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        $no_translate_selectors = apply_filters( 'trp_no_translate_selectors', array( '#wpadminbar' ), $TRP_LANGUAGE );

        /*
         * process the types of strings we can currently have: no-translate, translation-block, text, input, textarea, etc.
         */

        foreach ( $no_translate_selectors as $no_translate_selector ){
            foreach ( $html->find( $no_translate_selector ) as $k => $row ){
                $row->setAttribute( $no_translate_attribute, '' );
            }
        }

        foreach ( $html->find('.translation-block') as $k => $row ){
            if( $this->full_trim($row->outertext)!=""
                && $row->parent()->tag!="script"
                && $row->parent()->tag!="style"
                && !is_numeric($this->full_trim($row->outertext))
                && !preg_match('/^\d+%$/',$this->full_trim($row->outertext))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )
                && $row->parent()->tag != 'title'
                && strpos($row->outertext,'[vc_') === false )
            {
                    array_push( $translateable_strings, $this->full_trim( $row->innertext ) );
                    array_push( $nodes, array('node' => $row, 'type' => 'block'));
            }
        }

        foreach ( $html->find('text') as $k => $row ){
            if( $this->full_trim($row->outertext)!=""
                && $row->parent()->tag!="script"
                && $row->parent()->tag!="style"
                && !is_numeric($this->full_trim($row->outertext))
                && !preg_match('/^\d+%$/',$this->full_trim($row->outertext))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )
                && !$this->has_ancestor_class( $row, 'translation-block')
                && $row->parent()->tag != 'title'
                && strpos($row->outertext,'[vc_') === false )
            {
                    array_push( $translateable_strings, $this->full_trim( $row->outertext ) );
                    if( $row->parent()->tag == 'button') {
                        array_push($nodes, array('node' => $row, 'type' => 'button'));
                    }else{
                        array_push($nodes, array('node' => $row, 'type' => 'text'));
                    }
            }
        }

        foreach ( $html->find('input[type=\'submit\'],input[type=\'button\']') as $k => $row ){
            if( $this->full_trim($row->value)!=""
                && !is_numeric($this->full_trim($row->value))
                && !preg_match('/^\d+%$/',$this->full_trim($row->value))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )
                && !$this->has_ancestor_class( $row, 'translation-block') )
            {
                    array_push( $translateable_strings, html_entity_decode( $row->value ) );
                    array_push( $nodes, array('node'=>$row,'type'=>'submit') );
            }
        }
        foreach ( $html->find('input[type=\'text\'],input[type=\'password\'],input[type=\'search\'],input[type=\'email\'],input:not([type]),textarea') as $k => $row ){
            if( $this->full_trim($row->placeholder)!=""
                && !is_numeric($this->full_trim($row->placeholder))
                && !preg_match('/^\d+%$/',$this->full_trim($row->placeholder))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )
                && !$this->has_ancestor_class( $row, 'translation-block') )
            {
                    array_push( $translateable_strings, html_entity_decode ( $row->placeholder ) );
                    array_push( $nodes, array('node'=>$row,'type'=>'placeholder') );
            }
        }


        $translateable_information = array( 'translateable_strings' => $translateable_strings, 'nodes' => $nodes );
        $translateable_information = apply_filters( 'trp_translateable_strings', $translateable_information, $html, $no_translate_attribute, $TRP_LANGUAGE, $language_code, $this );
        $translateable_strings = $translateable_information['translateable_strings'];
        $nodes = $translateable_information['nodes'];

        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );
        }

        $translated_strings = $this->process_strings( $translateable_strings, $language_code );

        $preview_mode = isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview';
        if ( $preview_mode ) {
            $translated_string_ids = $this->trp_query->get_string_ids($translateable_strings, $language_code);
        }
        $node_accessor = apply_filters( 'trp_node_accessors', array(
            'text' => array(
                'accessor' => 'outertext',
                'attribute' => false
            ),
            'block' => array(
                'accessor' => 'innertext',
                'attribute' => false
            ),
            'page_title' => array(
                'accessor' => 'outertext',
                'attribute' => false
            ),
            'meta_desc' => array(
                'accessor' => 'content',
                'attribute' => true
            ),
            'post_slug' => array(
                'accessor' => 'content',
                'attribute' => false
            ),
            'image_alt' => array(
                'accessor' => 'alt',
                'attribute' => false
            ),
            'submit' => array(
                'accessor' => 'value',
                'attribute' => true
            ),
            'placeholder' => array(
                'accessor' => 'placeholder',
                'attribute' => true
            ),
            'button' => array(
                'accessor' => 'outertext',
                'attribute' => false
            )
        ));

        foreach ( $nodes as $i => $node ) {
            $translation_available = isset( $translated_strings[$i] );
            if ( ! ( $translation_available || $preview_mode ) ){
                continue;
            }
            $current_node_accessor = $node_accessor[ $nodes[$i]['type'] ];
            $accessor = $current_node_accessor[ 'accessor' ];
            if ( $translation_available && isset( $current_node_accessor ) && ! ( $preview_mode && ( $this->settings['default-language'] == $TRP_LANGUAGE ) ) ) {

                $translateable_string = $translateable_strings[$i];
                $alternate_translateable_string = htmlentities($translateable_strings[$i]);

                if ( $current_node_accessor[ 'attribute' ] ){
                    if ( strpos ( $nodes[$i]['node']->getAttribute( $accessor ), $translateable_string ) === false ){
                        $translateable_string = $alternate_translateable_string;
                    }
                    $nodes[$i]['node']->setAttribute( $accessor, str_replace( $translateable_string, esc_attr( $translated_strings[$i] ), $nodes[$i]['node']->getAttribute( $accessor ) ) );
                }else{
                    if ( strpos ( $nodes[$i]['node']->$accessor, $translateable_string ) === false ){
                        $translateable_string = $alternate_translateable_string;
                    }
                    $nodes[$i]['node']->$accessor = str_replace( $translateable_string, $translated_strings[$i], $nodes[$i]['node']->$accessor );
                }

            }

            if ( $preview_mode ) {
                if ( $accessor == 'outertext' && $nodes[$i]['type'] != 'button' ) {
                    $outertext_details = '<translate-press data-trp-translate-id="' . $translated_string_ids[$translateable_strings[$i]]->id . '" data-trp-node-type="' . $this->get_node_type_category( $nodes[$i]['type'] ) . '"';
                    if ( $this->get_node_description( $nodes[$i] ) ) {
                        $outertext_details .= ' data-trp-node-description="' . $this->get_node_description($nodes[$i] ) . '"';
                    }
                    $outertext_details .= '>' . $nodes[$i]['node']->outertext . '</translate-press>';
                    $nodes[$i]['node']->outertext = $outertext_details;
                } else {
                    if( $nodes[$i]['type'] == 'button' ){
                        $nodes[$i]['node'] = $nodes[$i]['node']->parent();
                    }
                    $nodes[$i]['node']->setAttribute('data-trp-translate-id', $translated_string_ids[ $translateable_strings[$i] ]->id );
                    $nodes[$i]['node']->setAttribute('data-trp-node-type', $this->get_node_type_category( $nodes[$i]['type'] ) );

                    if ( $this->get_node_description( $nodes[$i] ) ) {
                        $nodes[$i]['node']->setAttribute('data-trp-node-description', $this->get_node_description($nodes[$i]));
                    }

                }
            }

        }

        if ( ! $this->url_converter ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component('url_converter');
        }

        // force custom links to have the correct language
        foreach( $html->find('a[href!="#"]') as $a_href)  {
            $url = $a_href->href;
            $is_external_link = $this->is_external_link( $url );
            $is_admin_link = $this->is_admin_link($url);

            if ( $this->settings['force-language-to-custom-links'] == 'yes' && !$is_external_link && $this->url_converter->get_lang_from_url_string( $url ) == null && !$is_admin_link && strpos($url, '#TRPLINKPROCESSED') === false ){
                $a_href->href = apply_filters( 'trp_force_custom_links', $this->url_converter->get_url_for_language( $TRP_LANGUAGE, $url ), $url, $TRP_LANGUAGE, $a_href );
                $url = $a_href->href;
            }

            if( $preview_mode && ( $is_external_link || $this->is_different_language( $url ) || $is_admin_link ) ) {
                $a_href->setAttribute( 'data-trp-unpreviewable', 'trp-unpreviewable' );
            }

            $a_href->href = str_replace('#TRPLINKPROCESSED', '', $a_href->href);
        }

        // pass the current language in forms where the action does not contain the language
        // based on this we're filtering wp_redirect to include the proper URL when returning to the current page.
        foreach ( $html->find('form') as $k => $row ){
            $row->innertext .= apply_filters( 'trp_form_inputs', '<input type="hidden" name="trp-form-language" value="'. $this->settings['url-slugs'][$TRP_LANGUAGE] .'"/>', $TRP_LANGUAGE, $this->settings['url-slugs'][$TRP_LANGUAGE] );
        }


        return $html->save();
    }

    /**
     * Whether given url links to an external domain.
     *
     * @param string $url           Url.
     * @return bool                 Whether given url links to an external domain.
     */
    public function is_external_link( $url ){
        // Abort if parameter URL is empty
        if( empty($url) ) {
            return false;
        }
        if ( strpos( $url, '#' ) === 0 || strpos( $url, '/' ) === 0){
            return false;
        }

        // Parse home URL and parameter URL
        $link_url = parse_url( $url );
        $home_url = parse_url( home_url() );

        // Decide on target
        if( !isset ($link_url['host'] ) || $link_url['host'] == $home_url['host'] ) {
            // Is an internal link
            return false;

        } else {
            // Is an external link
            return true;
        }
    }

    /**
     * Whether given url links to a different language than the current one.
     *
     * @param string $url           Url.
     * @return bool                 Whether given url links to a different language than the current one.
     */
    protected function is_different_language( $url ){
        global $TRP_LANGUAGE;
        if ( ! $this->url_converter ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component('url_converter');
        }
        $lang = $this->url_converter->get_lang_from_url_string( $url );
        if ( $lang == null ){
            $lang = $this->settings['default-language'];
        }
        if ( $lang == $TRP_LANGUAGE ){
            return false;
        }else{
            return true;
        }
    }

    /**
     * Whether given url links to an admin page.
     *
     * @param string $url           Url.
     * @return bool                 Whether given url links to an admin page.
     */
    protected function is_admin_link( $url ){

        if ( strpos( $url, admin_url() ) !== false || strpos( $url, wp_login_url() ) !== false ){
            return true;
        }
        return false;

    }

    /**
     * Return translations for given strings in given language code.
     *
     * Also stores new strings, calls automatic translations and stores new translations.
     *
     * @param $translateable_strings
     * @param $language_code
     * @return array
     */
    public function process_strings( $translateable_strings, $language_code ){
        $translated_strings = array();

        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );
        }

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

        if ( ! $this->machine_translator ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->machine_translator = $trp->get_component('machine_translator');
        }

        // machine translate new strings
        if ( $this->machine_translator->is_available() ) {
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

    /**
     * Whether given node has ancestor with given attribute.
     *
     * @param object $node          Html Node.
     * @param string $attribute     Attribute to search for.
     * @return bool                 Whether given node has ancestor with given attribute.
     */
    public function has_ancestor_attribute($node,$attribute) {
        $currentNode = $node;
        if ( isset( $node->$attribute ) ){
            return true;
        }
        while($currentNode->parent() && $currentNode->parent()->tag!="html") {
            if(isset($currentNode->parent()->$attribute))
                return true;
            else
                $currentNode = $currentNode->parent();
        }
        return false;
    }

    /**
     * Whether given node has ancestor with given class.
     *
     * @param object $node           Html Node.
     * @param string $class     class to search for
     * @return bool                 Whether given node has ancestor with given class.
     */
    public function has_ancestor_class($node, $class) {
        $currentNode = $node;

        while($currentNode->parent() && $currentNode->parent()->tag!="html") {
            if(isset($currentNode->parent()->class) && strpos($currentNode->parent()->class, $class) !== false) {
                return true;
            } else {
                $currentNode = $currentNode->parent();
            }
        }
        return false;
    }

    /**
     * Enqueue dynamic translation script.
     */
    public function enqueue_dynamic_translation(){
        $enable_dynamic_translation = apply_filters( 'trp_enable_dynamic_translation', true );
        if ( ! $enable_dynamic_translation ){
            return;
        }

        global $TRP_LANGUAGE;

        if ( $TRP_LANGUAGE != $this->settings['default-language'] || ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview' ) ) {
            $language_to_query = $TRP_LANGUAGE;
            if ( $TRP_LANGUAGE == $this->settings['default-language']  ) {
                foreach ($this->settings['translation-languages'] as $language) {
                    if ( $language != $this->settings['default-language'] ) {
                        $language_to_query = $language;
                        break;
                    }
                }
            }
            $trp_data = array(
                'trp_ajax_url' => apply_filters('trp_ajax_url', TRP_PLUGIN_URL . 'includes/trp-ajax.php' ),
                'trp_wp_ajax_url' => apply_filters('trp_wp_ajax_url', admin_url('admin-ajax.php')),
                'trp_language_to_query' => $language_to_query,
                'trp_original_language' => $this->settings['default-language'],
                'trp_current_language' => $TRP_LANGUAGE
            );
            if ( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview' ) {
                $trp_data['trp_ajax_url'] = $trp_data['trp_wp_ajax_url'];
            }
            wp_enqueue_script('trp-dynamic-translator', TRP_PLUGIN_URL . 'assets/js/trp-translate-dom-changes.js', array('jquery', 'trp-language-switcher'), TRP_PLUGIN_VERSION );
            wp_localize_script('trp-dynamic-translator', 'trp_data', $trp_data);
        }
    }


    /**
     * Add a filter on the wp_mail function so we allow shortcode usage and run it through our translate function so it cleans it up nice and maybe even replace some strings
     * @param $args
     * @return array
     */
    public function wp_mail_filter( $args ){
        $trp_wp_mail = array(
            'to'          => $args['to'],
            'subject'     => $this->translate_page( do_shortcode( $args['subject'] ) ),
            'message'     => $this->translate_page( do_shortcode( $args['message'] ) ),
            'headers'     => $args['headers'],
            'attachments' => $args['attachments'],
        );

        return $trp_wp_mail;
    }

    /**
     * Filters the location redirect to add the preview parameter to the next page
     * @param $location
     * @param $status
     * @return string
     * @since 1.0.8
     */
    public function force_preview_on_url_redirect( $location, $status ){
        if( isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview' ){
            $location = add_query_arg( 'trp-edit-translation', 'preview', $location );
        }
        return $location;
    }

    /**
     * Filters the location redirect to add the current language based on the trp-form-language parameter
     * @param $location
     * @param $status
     * @return string
     * @since 1.1.2
     */
    public function force_language_on_form_url_redirect( $location, $status ){
        if( isset( $_REQUEST[ 'trp-form-language' ] ) && !empty($_REQUEST[ 'trp-form-language' ]) ){
            $form_language_slug = esc_attr($_REQUEST[ 'trp-form-language' ]);
            $form_language = array_search($form_language_slug, $this->settings['url-slugs']);
            if ( ! $this->url_converter ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->url_converter = $trp->get_component('url_converter');
            }

            $location = $this->url_converter->get_url_for_language( $form_language, $location );
        }
        return $location;
    }

    /**
     * Filters the output buffer of ajax calls that return json and adds the preview arg to urls
     * @param $output
     * @return string
     * @since 1.0.8
     */
    public function force_preview_on_url_in_ajax( $output ){
        if ( TRP_Translation_Manager::is_ajax_on_frontend() && isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] === 'preview' ) {
            $result = json_decode($output, TRUE);
            if ( json_last_error() === JSON_ERROR_NONE) {
                array_walk_recursive($result, array($this, 'callback_add_preview_arg'));
                $output = trp_safe_json_encode($result);
            } //endif
        } //endif
        return $output;
    }

    /**
     * Adds preview query arg to links that are url's. callback specifically for the array_walk_recursive function
     * @param $item
     * @param $key
     * @return string
     * @internal param $output
     * @since 1.0.8
     */
    function callback_add_preview_arg(&$item, $key){
        if ( filter_var($item, FILTER_VALIDATE_URL) !== FALSE ) {
            $item = add_query_arg( 'trp-edit-translation', 'preview', $item );
        }
    }

    /**
     * Filters the output buffer of ajax calls that return json and adds the preview arg to urls
     * @param $output
     * @return string
     * @since 1.1.2
     */
    public function force_form_language_on_url_in_ajax( $output ){
        if ( TRP_Translation_Manager::is_ajax_on_frontend() && isset( $_REQUEST[ 'trp-form-language' ] ) && !empty( $_REQUEST[ 'trp-form-language' ] ) ) {
            $result = json_decode($output, TRUE);
            if ( json_last_error() === JSON_ERROR_NONE) {
                array_walk_recursive($result, array($this, 'callback_add_language_to_url'));
                $output = trp_safe_json_encode($result);
            } //endif
        } //endif
        return $output;
    }

    /**
     * Adds preview query arg to links that are url's. callback specifically for the array_walk_recursive function
     * @param $item
     * @param $key
     * @return string
     * @internal param $output
     * @since 1.1.2
     */
    function callback_add_language_to_url(&$item, $key){
        if ( filter_var($item, FILTER_VALIDATE_URL) !== FALSE ) {
            $form_language_slug = esc_attr($_REQUEST[ 'trp-form-language' ]);
            $form_language = array_search($form_language_slug, $this->settings['url-slugs']);
            if ( ! $this->url_converter ) {
                $trp = TRP_Translate_Press::get_trp_instance();
                $this->url_converter = $trp->get_component('url_converter');
            }

            $item = $this->url_converter->get_url_for_language( $form_language, $item );
        }
    }

    /**
     * Function that reverses CDATA string replacement from the content because it breaks the renderer
     * @param $output
     * @return mixed
     */
    public function handle_cdata( $output ){
        $output = str_replace( ']]&gt;', ']]>', $output );
        return $output;
    }

    /**
     * Function always renders the default language wptexturize characters instead of the translated ones for secondary languages.
     * @param string
     * @param string
     * @param string
     * @param string
     * @return string
     */
    function fix_wptexturize_characters( $translated, $text, $context, $domain ){
        global $TRP_LANGUAGE;
        $trp = TRP_Translate_Press::get_trp_instance();
        $trp_settings = $trp->get_component( 'settings' );
        $settings = $trp_settings->get_settings();

        $default_language= $settings["default-language"];

        // it's reversed because the same string &#8217; is replaced differently based on context and we can't have the same key twice on an array
        $list_of_context_text = array(
            'opening curly double quote' => '&#8220;',
            'closing curly double quote' => '&#8221;',
            'apostrophe' => '&#8217;',
            'prime' => '&#8242;',
            'double prime' => '&#8243;',
            'opening curly single quote' => '&#8216;',
            'closing curly single quote' => '&#8217;',
            'en dash' => '&#8211;',
            'em dash' => '&#8212;',
            'Comma-separated list of words to texturize in your language' => "'tain't,'twere,'twas,'tis,'twill,'til,'bout,'nuff,'round,'cause,'em",
            'Comma-separated list of replacement words in your language' => '&#8217;tain&#8217;t,&#8217;twere,&#8217;twas,&#8217;tis,&#8217;twill,&#8217;til,&#8217;bout,&#8217;nuff,&#8217;round,&#8217;cause,&#8217;em'
        );

        if( $default_language != $TRP_LANGUAGE && array_key_exists($context, $list_of_context_text) && in_array($text, $list_of_context_text) ){
            return trp_x( $text, $context, '', $default_language );
        }

        return $translated;
    }
}
