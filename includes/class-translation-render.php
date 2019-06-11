<?php

/**
 * Class TRP_Translation_Render
 *
 * Translates pages.
 */
class TRP_Translation_Render{
    protected $settings;
    protected $machine_translator;
    /* @var TRP_Query */
    protected $trp_query;
	/* @var TRP_Url_Converter */
    protected $url_converter;
    /* @var TRP_Translation_Manager */
	protected $translation_manager;

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

        //when we check if is an ajax request in frontend we also set proper REQUEST variables and language global so we need to run this for every buffer
        $ajax_on_frontend = TRP_Translation_Manager::is_ajax_on_frontend();//TODO refactor this function si it just checks and does not set variables

        if( ( is_admin() && !$ajax_on_frontend ) || trp_is_translation_editor( 'true' ) ){
            return;//we have two cases where we don't do anything: we are on the admin side and we are not in an ajax call or we are in the left side of the translation editor
        }
        else {
            mb_http_output("UTF-8");
            if ( $TRP_LANGUAGE == $this->settings['default-language'] && !trp_is_translation_editor() ) {
                ob_start(array($this, 'clear_trp_tags'), 4096);//on default language when we are not in editor we just need to clear any trp tags that could still be present
            } else {
                ob_start(array($this, 'translate_page'));//everywhere else translate the page
            }
        }
    }

    /**
     * Function to hide php errors and notice and instead log them in debug.log so we don't store the notice strings inside the db if WP_DEBUG is on
     */
    public function trp_debug_mode_off(){
        if ( WP_DEBUG ) {
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
                    if( count( $this->settings['translation-languages'] ) > 1 ){
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
	 * This function is kept for backwards compatibility for earlier versions of SEO Pack Add-on
	 *
	 * @deprecated
	 * @param string $string      Raw string.
	 * @return string           Trimmed string.
	 */
	public function full_trim( $string ) {
		return trp_full_trim( $string );
	}

    /**
     * Preview mode string category name for give node type.
     *
     * @param string $current_node_type         Node type.
     * @return string                           Category name.
     */
    protected function get_node_type_category( $current_node_type ){
	    $trp = TRP_Translate_Press::get_trp_instance();
	    if ( ! $this->translation_manager ) {
		    $this->translation_manager = $trp->get_component( 'translation_manager' );
	    }
	    $string_groups = $this->translation_manager->string_groups();

        $node_type_categories = apply_filters( 'trp_node_type_categories', array(
            $string_groups['metainformation'] => array( 'meta_desc', 'page_title' ),
            $string_groups['images']          => array( 'image_src' )
        ));

        foreach( $node_type_categories as $category_name => $node_groups ){
            if ( in_array( $current_node_type, $node_groups ) ){
                return $category_name;
            }
        }

        return $string_groups['stringlist'];
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
	 * Specific trim made for translation block string
	 *
	 * Problem especially for nbsp; which gets saved like that in DB. Then, in translation-render, the string arrives with nbsp; rendered to actual space character.
	 * Used before inserting in db, and when trying to match on translation-render.
	 *
	 * @param $string
	 *
	 * @return string
	 */
    public function trim_translation_block( $string ){
	    return preg_replace('/\s+/', ' ', strip_tags( html_entity_decode( htmlspecialchars_decode( trp_full_trim( $string ), ENT_QUOTES ) ) ));
    }

    /**
     * Recursive function that checks if a DOM node contains certain tags or not
     * @param $row
     * @param $tags
     * @return bool
     */
    public function check_children_for_tags( $row, $tags ){
        foreach( $row->children as $child ){
            if( in_array( $child->tag, $tags ) ){
                return true;
            }
            else{
                $this->check_children_for_tags( $child, $tags );
            }
        }
    }

	/**
	 * Return translation block if matches any existing translation block from db
	 *
	 * Return null if not found
	 *
	 * @param $row
	 * @param $all_existing_translation_blocks
	 * @param $merge_rules
	 *
	 * @return bool
	 */
    public function find_translation_block( $row, $all_existing_translation_blocks, $merge_rules ){
    	if ( in_array( $row->tag, $merge_rules['top_parents'] ) ){
            //$row->innertext is very intensive on dom nodes that have a lot of children so we try here to eliminate as many as possible here
            // the ideea is that if a dom node contains any top parent tags for blocks it can't be a block itself so we skip it
            $skip = $this->check_children_for_tags( $row, $merge_rules['top_parents'] );
            if( !$skip ) {
                $trimmed_inner_text = $this->trim_translation_block($row->innertext);
                foreach ($all_existing_translation_blocks as $existing_translation_block) {
                    if ($existing_translation_block->trimmed_original == $trimmed_inner_text) {
                        return $existing_translation_block;
                    }
                }
            }
	    }
	    return null;
    }

    /**
     * Function that translates the content excerpt and post title in the REST API
     * @param $response
     * @return mixed
     */
    public function handle_rest_api_translations($response){
        $response->data['title']['rendered'] = $this->translate_page( $response->data['title']['rendered'] );
        $response->data['excerpt']['rendered'] = $this->translate_page( $response->data['excerpt']['rendered'] );
        $response->data['content']['rendered'] = $this->translate_page( $response->data['content']['rendered'] );
        return $response;
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
    	if ( apply_filters( 'trp_stop_translating_page', false, $output ) ){
    		return $output;
	    }

    	global $trp_editor_notices;

        /* replace our special tags so we have valid html */
        $output = str_replace('#!trpst#', '<', $output);
        $output = str_replace('#!trpen#', '>', $output);

        $output = apply_filters('trp_before_translate_content', $output);

        if ( strlen( $output ) < 1 || $output == false ){
            return $output;
        }

        if ( ! $this->url_converter ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component('url_converter');
        }

        /* make sure we only translate on the rest_prepare_$post_type filter in REST requests and not the whole json */
        if( strpos( $this->url_converter->cur_page_url(), get_rest_url() ) !== false && strpos( current_filter(), 'rest_prepare_' ) !== 0){
            return $output;
        }

        global $TRP_LANGUAGE;
        $language_code = $this->force_language_in_preview();
        if ($language_code === false) {
            return $output;
        }
        if ( $language_code == $this->settings['default-language'] ){
        	// Don't translate regular strings (non-gettext) when we have no other translation languages except default language ( count( $this->settings['publish-languages'] ) > 1 )
        	$translate_normal_strings = false;
        }else{
	        $translate_normal_strings = true;
        }

	    $preview_mode = isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] == 'preview';

        $json_array = json_decode( $output, true );
	    /* If we have a json response we need to parse it and only translate the nodes that contain html
	     *
	     * Removed is_ajax_on_frontend() check because we need to capture custom ajax events.
		 * Decided that if $output is json decodable it's a good enough check to handle it this way.
		 * We have necessary checks so that we don't get to this point when is_admin(), or when language is not default.
	     */
	    if( $json_array && $json_array != $output ) {
		    /* if it's one of our own ajax calls don't do nothing */
	        if ( ! empty( $_REQUEST['action'] ) && strpos( $_REQUEST['action'], 'trp_' ) === 0 && $_REQUEST['action'] != 'trp_split_translation_block' ) {
		        return $output;
	        }

	        //check if we have a json response
	        if ( ! empty( $json_array ) ) {
	            if( !is_array( $json_array ) )//make sure we send an array as json_decode even with true parameter might not return one
                    $json_array = array( $json_array );
                array_walk_recursive( $json_array, array( $this, 'translate_json' ) );
	        }

	        return trp_safe_json_encode( $json_array );
        }

        /**
         * Tries to fix the HTML document. It is off by default. Use at own risk.
         * Solves the problem where a duplicate attribute inside a tag causes the plugin to remove the duplicated attribute and all the other attributes to the right of the it.
         */
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
	    $skip_machine_translating_strings = array();
        $nodes = array();

	    $trp = TRP_Translate_Press::get_trp_instance();
	    if ( ! $this->trp_query ) {
		    $this->trp_query = $trp->get_component( 'query' );
	    }
	    if ( ! $this->translation_manager ) {
		    $this->translation_manager = $trp->get_component( 'translation_manager' );
	    }

	    $html = TranslatePress\str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);
	    if ( $html === false ){
		    $trpremoved = preg_replace( '/(<|&lt;)trp-gettext (.*?)(>|&gt;)/', '', $output );
		    $trpremoved = preg_replace( '/(<|&lt;)(\\\\)*\/trp-gettext(>|&gt;)/', '', $trpremoved );
		    return $trpremoved;
	    }

	    $count_translation_blocks = 0;
	    if ( $translate_normal_strings ) {
		    $all_existing_translation_blocks = $this->trp_query->get_all_translation_blocks( $language_code );
		    // trim every translation block original now, to avoid over-calling trim function later
		    foreach ( $all_existing_translation_blocks as $key => $existing_tb ) {
			    $all_existing_translation_blocks[ $key ]->trimmed_original = $this->trim_translation_block( $all_existing_translation_blocks[ $key ]->original );
		    }

		    /* Try to find if there are any blocks in the output for translation.
		     * If the output is an actual html page, use only the innertext of body tag
		     * Else use the entire output (ex. the output is from JSON REST API content, or just a string)
		     */
		    $html_body = $html->find('body', 0 );
		    $output_to_translate = ( $html_body ) ?  $html_body->innertext : $output;

		    $trimmed_html_body = $this->trim_translation_block( $output_to_translate );
            foreach( $all_existing_translation_blocks as $key => $existing_translation_block ){
                if (  strpos( $trimmed_html_body, $existing_translation_block->trimmed_original ) === false ){
                    unset($all_existing_translation_blocks[$key] );//if it isn't present remove it, this way we don't look for them on pages that don't contain blocks
                }
            }
            $count_translation_blocks = count( $all_existing_translation_blocks );//see here how many remain on the current page

		    $merge_rules = $this->translation_manager->get_merge_rules();
	    }

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
                if( $row->nodetype !== 5 && $row->nodetype !== 3 )//add all tags that are not root or text, text nodes can't have attributes
                    $trp_attr_rows[] = $row;

	            if ( $translate_normal_strings && $count_translation_blocks > 0 ) {
		            $translation_block = $this->find_translation_block( $row, $all_existing_translation_blocks, $merge_rules );
		            if ( $translation_block ) {
			            $existing_classes = $row->getAttribute( 'class' );
			            if ( $translation_block->block_type == 1 ) {
				            $found_inner_translation_block = false;
				            foreach ( $row->children() as $child ) {
					            if ( $this->find_translation_block( $child, array( $translation_block ), $merge_rules ) != null ) {
						            $found_inner_translation_block = true;
						            break;
					            }
				            }
				            if ( ! $found_inner_translation_block ) {
					            // make sure we find it later exactly the way it is in DB
					            $row->innertext = $translation_block->original;
					            $row->setAttribute( 'class', $existing_classes . ' translation-block' );
				            }
			            } else if ( $preview_mode && $translation_block->block_type == 2 && $translation_block->status != 0 ) {
				            // refactor to not do this for each
				            $row->setAttribute( 'data-trp-translate-id', $translation_block->id );
				            $row->setAttribute( 'data-trp-translate-id-deprecated', $translation_block->id );
				            $row->setAttribute( 'class', $existing_classes . 'trp-deprecated-tb' );
			            }
		            }
	            }

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
                        $node_from_value = TranslatePress\str_get_html(html_entity_decode(htmlspecialchars_decode($attr_value, ENT_QUOTES)), true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);
	                    if ( $node_from_value === false ){
		                    continue;
	                    }
                        foreach ($node_from_value->find('trp-gettext') as $nfv_row) {
                            $nfv_row->outertext = $nfv_row->innertext();
                            $row->setAttribute($attr_name, $node_from_value->save() );
                            $row->setAttribute($no_translate_attribute . '-' . $attr_name, '');
                            // we are in the editor
                            if (isset($_REQUEST['trp-edit-translation']) && $_REQUEST['trp-edit-translation'] == 'preview') {
                                $original_gettext_translation_id = $nfv_row->getAttribute('data-trpgettextoriginal');
                                $row->setAttribute('data-trpgettextoriginal-' . $attr_name, $original_gettext_translation_id);
                            }

                        }
                    }
                }
            }
        }


	    if ( ! $translate_normal_strings ) {
            /* save it as a string */
            $trpremoved = $html->save();
            /* perform preg replace on the remaining trp-gettext tags */
            $trpremoved = $this->remove_trp_html_tags($trpremoved );
		    return $trpremoved;
	    }

        $no_translate_selectors = apply_filters( 'trp_no_translate_selectors', array( '#wpadminbar' ), $TRP_LANGUAGE );

        /*
         * process the types of strings we can currently have: no-translate, translation-block, text, input, textarea, etc.
         */

        foreach ( $no_translate_selectors as $no_translate_selector ){
            foreach ( $html->find( $no_translate_selector ) as $k => $row ){
                $row->setAttribute( $no_translate_attribute, '' );
            }
        }
        foreach ( $html->find('.translation-block') as $row ){
            $trimmed_string = trp_full_trim($row->innertext);
            $parent = $row->parent();
            if( $trimmed_string!=""
                && $parent->tag!="script"
                && $parent->tag!="style"
                && $parent->tag != 'title'
                && strpos($row->outertext,'[vc_') === false
                && !is_numeric($trimmed_string)
                && !preg_match('/^\d+%$/',$trimmed_string)
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute ) )
            {
                array_push( $translateable_strings, $trimmed_string );
                array_push( $nodes, array('node' => $row, 'type' => 'block'));
            }
        }

        foreach ( $html->find('text') as $row ){
            $outertext = $row->outertext;
            $parent = $row->parent();
            $trimmed_string = trp_full_trim($outertext);
            if( $trimmed_string!=""
                && $parent->tag!="script"
                && $parent->tag!="style"
                && $parent->tag != 'title'
                && strpos($outertext,'[vc_') === false
                && !is_numeric($trimmed_string)
                && !preg_match('/^\d+%$/',$trimmed_string)
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )
                && !$this->has_ancestor_class( $row, 'translation-block') )
            {
                // $translateable_strings array needs to be in sync in $nodes array
                array_push( $translateable_strings, $trimmed_string );
                if( $parent->tag == 'button') {
                    array_push($nodes, array('node' => $row, 'type' => 'button'));
                }
                else {
                    if ( $parent->tag == 'option' ) {
                        array_push( $nodes, array( 'node' => $row, 'type' => 'option' ) );
                    } else {
                        array_push( $nodes, array( 'node' => $row, 'type' => 'text' ) );
                    }
                }
            }
        }
	    //set up general links variables
	    $home_url = home_url();
	    $admin_url = admin_url();
	    $wp_login_url = wp_login_url();

	    $node_accessors = $this->get_node_accessors();
	    foreach( $node_accessors as $node_accessor_key => $node_accessor ){
	    	if ( isset( $node_accessor['selector'] ) ){
			    foreach ( $html->find( $node_accessor['selector'] ) as $k => $row ){
			    	$current_node_accessor_selector = $node_accessor['accessor'];
				    $trimmed_string = trp_full_trim($row->$current_node_accessor_selector);
			    	if ( $current_node_accessor_selector === 'href' ) {
					    $trimmed_string = ( $this->is_external_link( $trimmed_string, $home_url ) || $this->url_converter->url_is_file( $trimmed_string ) ) ? $trimmed_string : '';
				    }

				    if( $trimmed_string!=""
				        && !is_numeric($trimmed_string)
				        && !preg_match('/^\d+%$/',$trimmed_string)
				        && !$this->has_ancestor_attribute( $row, $no_translate_attribute )
				        && !$this->has_ancestor_attribute( $row, $no_translate_attribute . '-' . $current_node_accessor_selector )
				        && !$this->has_ancestor_class( $row, 'translation-block') )
				    {
					    $entity_decoded_trimmed_string = html_entity_decode( $trimmed_string );
					    array_push( $translateable_strings, $entity_decoded_trimmed_string );
					    array_push( $nodes, array( 'node'=>$row, 'type' => $node_accessor_key ) );
					    if ( ! apply_filters( 'trp_allow_machine_translation_for_string', true, $entity_decoded_trimmed_string, $current_node_accessor_selector, $node_accessor ) ){
					    	array_push( $skip_machine_translating_strings, $entity_decoded_trimmed_string );
					    }
				    }
			    }
		    }
	    }

        $translateable_information = array( 'translateable_strings' => $translateable_strings, 'nodes' => $nodes );
        $translateable_information = apply_filters( 'trp_translateable_strings', $translateable_information, $html, $no_translate_attribute, $TRP_LANGUAGE, $language_code, $this );
        $translateable_strings = $translateable_information['translateable_strings'];
        $nodes = $translateable_information['nodes'];

        $translated_strings = $this->process_strings( $translateable_strings, $language_code, null, $skip_machine_translating_strings );

        do_action('trp_translateable_information', $translateable_information, $translated_strings, $language_code);

        if ( $preview_mode ) {
            $translated_string_ids = $this->trp_query->get_string_ids($translateable_strings, $language_code);
        }

        foreach ( $nodes as $i => $node ) {
            $translation_available = isset( $translated_strings[$i] );
            if ( ! ( $translation_available || $preview_mode ) || !isset( $node_accessors [$nodes[$i]['type']] )){
                continue;
            }
            $current_node_accessor = $node_accessors[ $nodes[$i]['type'] ];
            $accessor = $current_node_accessor[ 'accessor' ];
            if ( $translation_available && isset( $current_node_accessor ) && ! ( $preview_mode && ( $this->settings['default-language'] == $TRP_LANGUAGE ) ) ) {

                $translateable_string = $translateable_strings[$i];
                $alternate_translateable_string = htmlentities($translateable_strings[$i]);

                if ( $current_node_accessor[ 'attribute' ] ){
                    if ( strpos ( $nodes[$i]['node']->getAttribute( $accessor ), $translateable_string ) === false ){
                        $translateable_string = $alternate_translateable_string;
                    }
                    $nodes[$i]['node']->setAttribute( $accessor, str_replace( $translateable_string, esc_attr( $translated_strings[$i] ), $nodes[$i]['node']->getAttribute( $accessor ) ) );
                    do_action( 'trp_set_translation_for_attribute', $nodes[$i]['node'], $accessor, $translated_strings[$i] );
                }else{
                    if ( strpos ( $nodes[$i]['node']->$accessor, $translateable_string ) === false ){
                        $translateable_string = $alternate_translateable_string;
                    }
                    $nodes[$i]['node']->$accessor = str_replace( $translateable_string, $translated_strings[$i], $nodes[$i]['node']->$accessor );
                }

            }

            if ( $preview_mode ) {
                if ( $accessor == 'outertext' && $nodes[$i]['type'] != 'button' ) {
                    $outertext_details = '<translate-press data-trp-translate-id="' . $translated_string_ids[$translateable_strings[$i]]->id . '" data-trp-node-group="' . $this->get_node_type_category( $nodes[$i]['type'] ) . '"';
                    if ( $this->get_node_description( $nodes[$i] ) ) {
                        $outertext_details .= ' data-trp-node-description="' . $this->get_node_description($nodes[$i] ) . '"';
                    }
                    $outertext_details .= '>' . $nodes[$i]['node']->outertext . '</translate-press>';
                    $nodes[$i]['node']->outertext = $outertext_details;
                } else {
                    if( $nodes[$i]['type'] == 'button' || $nodes[$i]['type'] == 'option' ){
                        $nodes[$i]['node'] = $nodes[$i]['node']->parent();
                    }
	                $nodes[$i]['node']->setAttribute('data-trp-translate-id-' . $accessor, $translated_string_ids[ $translateable_strings[$i] ]->id );
                    $nodes[$i]['node']->setAttribute('data-trp-node-group-' . $accessor, $this->get_node_type_category( $nodes[$i]['type'] ) );

                    if ( $this->get_node_description( $nodes[$i] ) ) {
                        $nodes[$i]['node']->setAttribute('data-trp-node-description-' . $accessor, $this->get_node_description($nodes[$i]));
                    }

                }
            }

        }


        // We need to save here in order to access the translated links too.
        if( apply_filters('tp_handle_custom_links_in_translation_blocks', false) ) {
            $html_string = $html->save();
            $html = TranslatePress\str_get_html($html_string, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);
            if ( $html === false ){
                return $html_string;
            }
        }


        // force custom links to have the correct language
        foreach( $html->find('a[href!="#"]') as $a_href)  {
            $a_href->href = apply_filters( 'trp_href_from_translated_page', $a_href->href, $this->settings['default-language'] );

            $url = $a_href->href;

            $url = $this->maybe_is_local_url($url, $home_url);

            $is_external_link = $this->is_external_link( $url, $home_url );
            $is_admin_link = $this->is_admin_link($url, $admin_url, $wp_login_url);

	        if( $preview_mode && ! $is_external_link ){
				$a_href->setAttribute( 'data-trp-original-href', $url );
	        }

            if ( $TRP_LANGUAGE != $this->settings['default-language'] && $this->settings['force-language-to-custom-links'] == 'yes' && !$is_external_link && $this->url_converter->get_lang_from_url_string( $url ) == null && !$is_admin_link && strpos($url, '#TRPLINKPROCESSED') === false ){
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
            $row->setAttribute( 'data-trp-original-action', $row->action );
            $row->innertext .= apply_filters( 'trp_form_inputs', '<input type="hidden" name="trp-form-language" value="'. $this->settings['url-slugs'][$TRP_LANGUAGE] .'"/>', $TRP_LANGUAGE, $this->settings['url-slugs'][$TRP_LANGUAGE] );
            $form_action = $row->action;

            $is_external_link = $this->is_external_link( $form_action, $home_url );
            $is_admin_link = $this->is_admin_link($form_action, $admin_url, $wp_login_url );

            if ( !empty($form_action)
                && $this->settings['force-language-to-custom-links'] == 'yes'
                && !$is_external_link
                && !$is_admin_link
                && strpos($form_action, '#TRPLINKPROCESSED') === false)
            {
                $row->action =  $this->url_converter->get_url_for_language( $TRP_LANGUAGE, $form_action );
            }
            $row->action = str_replace('#TRPLINKPROCESSED', '', $row->action);
        }

        foreach ( $html->find('link') as $link ){
            $link->href = str_replace('#TRPLINKPROCESSED', '', $link->href);
        }

	    // Append an html table containing the errors
	    $trp_editor_notices = apply_filters( 'trp_editor_notices', $trp_editor_notices );
	    if ( trp_is_translation_editor('preview') && $trp_editor_notices != '' ){
		    $body = $html->find('body', 0 );
		    $body->innertext = '<div data-no-translation class="trp-editor-notices">' . $trp_editor_notices . "</div>" . $body->innertext;
	    }
	    $final_html = $html->save();

        /* perform preg replace on the remaining trp-gettext tags */
        $final_html = $this->remove_trp_html_tags( $final_html );

	    return apply_filters( 'trp_translated_html', $final_html, $TRP_LANGUAGE, $language_code );
    }

    /*
     * Update other image attributes (srcset) with the translated image
     *
     * Hooked to trp_set_translation_for_attribute
     */
    public function translate_image_srcset_attributes( $node, $accessor, $translated_string){
	    if( $accessor === 'src' ) {
		    if ( $node->getAttribute( 'srcset' ) ) {
			    $attachment_id = attachment_url_to_postid( $translated_string );
			    if ( $attachment_id ) {
				    $translated_srcset = wp_get_attachment_image_srcset( $attachment_id );
				    if ( $translated_srcset ) {
					    $node->setAttribute( 'srcset', $translated_srcset );
				    } else {
					    $node->setAttribute( 'srcset', '' );
				    }
			    } else {
				    $node->setAttribute( 'srcset', '' );
			    }
		    }
		    if ( $node->getAttribute( 'data-src' ) ) {
			    $node->setAttribute( 'data-src', $translated_string );
		    }
	    }

    }

    /*
     * Do not translate src and href attributes
     *
     * Hooked to trp_allow_machine_translation_for_string
     */
    public function allow_machine_translation_for_string( $allow, $entity_decoded_trimmed_string, $current_node_accessor_selector, $node_accessor ){
    	$skip_attributes = apply_filters( 'trp_skip_machine_translation_for_attr', array( 'href', 'src' ) );
	    if ( in_array( $current_node_accessor_selector, $skip_attributes ) ){
	    	// do not machine translate href and src
	    	return false;
	    }
	    return $allow;
    }

    /**
     * function that removes any unwanted leftover <trp-gettext> tags
     * @param $string
     * @return string|string[]|null
     */
    function remove_trp_html_tags( $string ){
        $string = preg_replace( '/(<|&lt;)trp-gettext (.*?)(>|&gt;)/', '', $string );
        $string = preg_replace( '/(<|&lt;)(\\\\)*\/trp-gettext(>|&gt;)/', '', $string );
        return $string;
    }

    /**
     * Callback for the array_walk_recursive to translate json. It translates the values in the resulting json array if they contain html
     * @param $value
     */
    function translate_json (&$value) {
        //check if it a html text and translate
        $html_decoded_value = html_entity_decode( (string) $value );
        if ( $html_decoded_value != strip_tags( $html_decoded_value ) ) {
            $value =   $this->translate_page( stripslashes( $value ) );
            /*the translate-press tag can appear on a gettext string without html and should not be left in the json
            as we don't know how it will be inserted into the page by js */
            $value = preg_replace( '/(<|&lt;)translate-press (.*?)(>|&gt;)/', '', $value );
            $value = preg_replace( '/(<|&lt;)(\\\\)*\/translate-press(>|&gt;)/', '', $value );
        }
    }

    /**
     * Function that should be called only on the default language and when we are not in the editor mode and it is designed as a fallback to clear
     * any trp gettext tags that we added and for some reason show up  although they should not
     * @param $output
     * @return mixed
     */
    function clear_trp_tags( $output ){
        return TRP_Translation_Manager::strip_gettext_tags($output);
    }

    /**
     * Whether given url links to an external domain.
     *
     * @param string $url           Url.
     * @param string $home_url      Optional home_url so we avoid calling the home_url() inside loops.
     * @return bool                 Whether given url links to an external domain.
     */
    public function is_external_link( $url, $home_url = '' ){
        // Abort if parameter URL is empty
        if( empty($url) ) {
            return false;
        }
        if ( strpos( $url, '#' ) === 0 || strpos( $url, '/' ) === 0){
            return false;
        }

        // Parse home URL and parameter URL
        $link_url = parse_url( $url );
        if( empty( $home_url ) )
            $home_url = home_url();
        $home_url = parse_url( $home_url );

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
     * Checks to see if the user didn't incorrectly formated a url that's different from the home_url
     * Takes into account http, https, www and all the possible combinations between them.
     *
     * @param string $url           Url.
     * @param string $home_url      Optional home_url so we avoid calling the home_url() inside loops.
     * @return string               Correct URL that's the same structure as home_url
     */
    public function maybe_is_local_url( $url, $home_url='' ){

        if ( apply_filters('disable_maybe_is_local_url', false) ){
            return $url;
        }

        // Abort if parameter URL is empty
        if( empty($url) ) {
            return $url;
        }
        if ( strpos( $url, '#' ) === 0 || strpos( $url, '/' ) === 0){
            return $url;
        }

        // Parse home URL and parameter URL
        $link_url = parse_url( $url );
        if( empty( $home_url ) )
            $home_url = home_url();
        $home_url = parse_url( $home_url );

        // Decide on target
        if( !isset ($link_url['host'] ) || $link_url['host'] == $home_url['host'] ) {
            // Is an internal link
            return $url;
        } else {

            // test out possible local urls that the user might have mistyped
            $valid_local_prefix = array('http://', 'https://', 'http://www.', 'https://www.');
            foreach ($valid_local_prefix as $prefix){
                foreach ($valid_local_prefix as $replacement_prefix){
                    if( str_replace($prefix, $replacement_prefix, $link_url['scheme'] . '://' . $link_url['host']) == $home_url['scheme'] . '://' .$home_url['host'] ){
                        return str_replace($prefix, $replacement_prefix, $url);
                    }
                }
            }

            // Is an external link
            return $url;
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
    protected function is_admin_link( $url, $admin_url = '', $wp_login_url = '' ){

        if( empty( $admin_url ) )
            $admin_url = admin_url();

        if( empty( $wp_login_url ) )
            $wp_login_url = wp_login_url();

        if ( strpos( $url, $admin_url ) !== false || strpos( $url, $wp_login_url ) !== false ){
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
    public function process_strings( $translateable_strings, $language_code, $block_type = null, $skip_machine_translating_strings = array() ){
	    if ( ! $this->machine_translator ) {
		    $trp = TRP_Translate_Press::get_trp_instance();
		    $this->machine_translator = $trp->get_component('machine_translator');
	    }

        $translated_strings = array();
	    $machine_translation_available = $this->machine_translator->is_available();

        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );
        }

        // get existing translations
        $dictionary = $this->trp_query->get_existing_translations( array_values($translateable_strings), $language_code );
        if ( $dictionary === false ){
        	return array();
        }
        $new_strings = array();
	    $machine_translatable_strings = array();
        foreach( $translateable_strings as $i => $string ){
        	// prevent accidentally machine translated strings from db such as for src to be displayed
	        $skip_string = in_array( $string, $skip_machine_translating_strings );
	        if ( isset( $dictionary[$string]->translated ) && $dictionary[$string]->status == $this->trp_query->get_constant_machine_translated() && $skip_string ){
	        	continue;
	        }
	        //strings existing in database,
            if ( isset( $dictionary[$string]->translated ) ){
                $translated_strings[$i] = $dictionary[$string]->translated;
            }else{
                $new_strings[$i] = $translateable_strings[$i];
                // if the string is not a url then allow machine translation for it
                if ( $machine_translation_available && !$skip_string && filter_var($new_strings[$i], FILTER_VALIDATE_URL) === false ){
	                $machine_translatable_strings[$i] = $new_strings[$i];
                }
            }
        }


        // machine translate new strings
        if ( $machine_translation_available ) {
            $machine_strings = $this->machine_translator->translate_array( $machine_translatable_strings, $language_code );
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
                        'original' => trp_sanitize_string($untranslated_list[$string]->original),
                        'translated' => trp_sanitize_string($machine_strings[$i]),
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
                        'translated' => trp_sanitize_string($machine_strings[$i]),
                        'status' => $this->trp_query->get_constant_machine_translated() ) );
                    unset($new_strings[$i]);
                }
            }
        }

        $this->trp_query->insert_strings( $new_strings, $language_code, $block_type );
        $this->trp_query->update_strings( $update_strings, $language_code, array( 'id','original', 'translated', 'status' ) );

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
	 * Which attributes to translate and how to access them
	 *
	 * Nodes with "selector" attribute are automatically searched for in PHP translate_page and in JS translate-dom-changes
	 *
	 * @return array
	 */
    public function get_node_accessors(){
	    return apply_filters( 'trp_node_accessors', array(
		    'text' => array(
			    'accessor' => 'outertext',
			    'attribute' => false
		    ),
		    'block' => array(
			    'accessor' => 'innertext',
			    'attribute' => false
		    ),
		    'image_src' => array(
		    	'selector' => 'img[src]',
			    'accessor' => 'src',
			    'attribute' => true
		    ),
		    'submit' => array(
		    	'selector' => 'input[type=\'submit\'],input[type=\'button\']',
			    'accessor' => 'value',
			    'attribute' => true
		    ),
		    'placeholder' => array(
		    	'selector' => 'input[type=\'text\'][placeholder],input[type=\'password\'][placeholder],input[type=\'search\'][placeholder],input[type=\'email\'][placeholder],input[placeholder]:not([type]),textarea[placeholder]',
			    'accessor' => 'placeholder',
			    'attribute' => true
		    ),
		    'title' => array(
		    	'selector' => '[title]:not(link)',
			    'accessor' => 'title',
			    'attribute' => true
		    ),
		    'a_href' => array(
		    	'selector' => 'a[href]',
			    'accessor' => 'href',
			    'attribute' => true
		    ),
		    'button' => array(
			    'accessor' => 'outertext',
			    'attribute' => false
		    ),
		    'option' => array(
			    'accessor' => 'innertext',
			    'attribute' => false
		    )
	    ));
    }

    public function get_accessors_array( $prefix = '' ){
    	$accessor_array = array();
	    $node_accessors_array = $this->get_node_accessors();
	    foreach ( $node_accessors_array as $node_accessor ){
	    	if ( isset ( $node_accessor['accessor'] ) ){
			    $accessor_array[] = $prefix . $node_accessor['accessor'];
		    }
	    }

	    return array_values( array_unique( $accessor_array ) );
    }

    /*
     * Enqueue scripts on all pages
     */
	public function enqueue_scripts() {

		// so far only when woocommerce is active we need to enqueue this script on all pages
		if ( class_exists( 'WooCommerce' ) ){
			wp_enqueue_script('trp-frontend-compatibility', TRP_PLUGIN_URL . 'assets/js/trp-frontend-compatibility.js', array(), TRP_PLUGIN_VERSION );
		}

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
	        $language_to_query = ( count ( $this->settings['translation-languages'] ) < 2 ) ? '' : $language_to_query;

	        $trp = TRP_Translate_Press::get_trp_instance();
	        if ( ! $this->translation_manager ) {
		        $this->translation_manager = $trp->get_component( 'translation_manager' );
	        }
	        $nonces = $this->translation_manager->editor_nonces();
            $trp_data = array(
                'trp_custom_ajax_url'                 => apply_filters('trp_custom_ajax_url', TRP_PLUGIN_URL . 'includes/trp-ajax.php' ),
				'trp_wp_ajax_url'                     => apply_filters('trp_wp_ajax_url', admin_url('admin-ajax.php')),
				'trp_language_to_query'               => $language_to_query,
				'trp_original_language'               => $this->settings['default-language'],
				'trp_current_language'                => $TRP_LANGUAGE,
				'trp_skip_selectors'                  => apply_filters( 'trp_skip_selectors_from_dynamic_translation', array( '[data-no-translation]', '[data-no-dynamic-translation]', '[data-trp-translate-id-innertext]', 'script', 'style', 'head', 'trp-span', 'translate-press' ), $TRP_LANGUAGE, $this->settings ), // data-trp-translate-id-innertext refers to translation block and it shouldn't be detected
				'trp_base_selectors'                  => $this->get_base_attribute_selectors(),
				'trp_attributes_selectors'            => $this->get_node_accessors(),
				'trp_attributes_accessors'            => $this->get_accessors_array(),
				'gettranslationsnonceregular'         => $nonces['gettranslationsnonceregular'],
				'showdynamiccontentbeforetranslation' => apply_filters( 'trp_show_dynamic_content_before_translation', false )
            );

            wp_enqueue_script('trp-dynamic-translator', TRP_PLUGIN_URL . 'assets/js/trp-translate-dom-changes.js', array('jquery'), TRP_PLUGIN_VERSION, true );
            wp_localize_script('trp-dynamic-translator', 'trp_data', $trp_data);
        }
    }

	/**
	 * Skip base selectors (data-trp-translate-id, data-trpgettextoriginal etc.)
	 *
	 * The base selectors (without any suffixes) are placed only if their children do not contain any nodes that are translatable
	 *
 	 * hooked to trp_skip_selectors_from_dynamic_translation
	 *
	 * @param $skip_selectors
	 *
	 * @return array
	 */
    public function skip_base_attributes_from_dynamic_translation( $skip_selectors ){
	    $base_attributes = $this->get_base_attribute_selectors();
	    $selectors_to_skip = array();
	    foreach( $base_attributes as $base_attribute ){
		    $selectors_to_skip[] = '[' . $base_attribute . ']';
	    }
	    return array_merge( $skip_selectors, $selectors_to_skip );
    }

	/*
	 * Get base attribute selectors
	 */
	public function get_base_attribute_selectors(){
		return apply_filters( 'trp_base_attribute_selectors', array( 'data-trp-translate-id', 'data-trpgettextoriginal', 'data-trp-post-slug' ) );
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
        if ( TRP_Translation_Manager::is_ajax_on_frontend() && isset( $_REQUEST['trp-edit-translation'] ) && $_REQUEST['trp-edit-translation'] === 'preview' && $output != false ) {
            $result = json_decode($output, TRUE);
            if ( json_last_error() === JSON_ERROR_NONE) {
                if( !is_array( $result ) )//make sure we send an array as json_decode even with true parameter might not return one
                    $result = array($result);
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
