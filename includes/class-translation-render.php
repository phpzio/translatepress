<?php

class TRP_Translation_Render{
    protected $settings;
    protected $machine_translator;
    protected $trp_query;
    protected $url_converter;

    public function __construct( $settings ){
        $this->settings = $settings;
    }

    public function start_object_cache(){
        global $TRP_LANGUAGE;
        if( is_admin() ||
        ( $TRP_LANGUAGE == $this->settings['default-language'] && ( ! isset( $_GET['trp-edit-translation'] ) || ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] != 'preview' ) ) )  ||
        ( isset( $_GET['trp-edit-translation']) && $_GET['trp-edit-translation'] == 'true' ) || defined( 'WC_DOING_AJAX' ) ) {
            return;
        }

        mb_http_output("UTF-8");
        ob_start(array($this, 'translate_page'));
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

    public function full_trim( $word ) {
        $word = trim($word," \t\n\r\0\x0B\xA0�" );
        if ( htmlentities( $word ) == "" ){
            $word = '';
        }
        if ( strip_tags( $word ) == "" ){
            $word = '';
        }
        return $word;
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

    public function translate_page( $output ){
        if ( strlen( $output ) < 1 ){
            return $output;
        }
        global $TRP_LANGUAGE;
        $language_code = $this->get_language();
        if ($language_code === false) {
            return $output;
        }

        $no_translate_attribute = 'data-no-translation';

        $translateable_strings = array();
        $nodes = array();

        $html = trp_str_get_html($output, true, true, TRP_DEFAULT_TARGET_CHARSET, false, TRP_DEFAULT_BR_TEXT, TRP_DEFAULT_SPAN_TEXT);

        $no_translate_selectors = apply_filters( 'trp_no_translate_selectors', array( '#wpadminbar' ), $TRP_LANGUAGE );

        foreach ( $no_translate_selectors as $no_translate_selector ){
            foreach ( $html->find( $no_translate_selector ) as $k => $row ){
                $row->setAttribute( $no_translate_attribute, '' );
            }
        }

        foreach ( $html->find('text') as $k => $row ){
            if($this->full_trim($row->outertext)!="" && $row->parent()->tag!="script" && $row->parent()->tag!="style" && !is_numeric($this->full_trim($row->outertext)) && !preg_match('/^\d+%$/',$this->full_trim($row->outertext))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )){
                if(strpos($row->outertext,'[vc_') === false) {
                    if ( $row->parent()->tag == 'title' ) {
                        //array_push($nodes, array('node' => $row, 'type' => 'page_title'));
                        //array_push( $translateable_strings, $this->full_trim( $row->outertext ) );
                    }else {
                        array_push( $translateable_strings, $this->full_trim( $row->outertext ) );
                        array_push($nodes, array('node' => $row, 'type' => 'text'));
                    }
                }
            }
        }
        foreach ( $html->find('input[type=\'submit\'],input[type=\'button\']') as $k => $row ){
            if($this->full_trim($row->value)!="" && !is_numeric($this->full_trim($row->value)) && !preg_match('/^\d+%$/',$this->full_trim($row->value))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )) {
                array_push( $translateable_strings, html_entity_decode( $row->value ) );
                array_push( $nodes, array('node'=>$row,'type'=>'submit') );
            }
        }
        foreach ( $html->find('input[type=\'text\'],input[type=\'password\'],input[type=\'search\'],input[type=\'email\'],input:not([type]),textarea') as $k => $row ){
            if($this->full_trim($row->placeholder)!="" && !is_numeric($this->full_trim($row->placeholder)) && !preg_match('/^\d+%$/',$this->full_trim($row->placeholder))
                && !$this->has_ancestor_attribute( $row, $no_translate_attribute )){
                array_push( $translateable_strings, html_entity_decode ( $row->placeholder ) );
                array_push( $nodes, array('node'=>$row,'type'=>'placeholder') );
            }
        }

        foreach ( $html->find('img') as $k => $row ) {
            if($this->full_trim($row->alt)!="" && !$this->has_ancestor_attribute( $row, $no_translate_attribute ))
            {
                array_push( $translateable_strings, $row->alt );
                array_push( $nodes, array('node'=>$row,'type'=>'image_alt') );
            }
        }

        $translateable_information = array( 'translateable_strings' => $translateable_strings, 'nodes' => $nodes );
        $translateable_information = apply_filters( 'trp_translateable_strings', $translateable_information, $html, $no_translate_attribute, $TRP_LANGUAGE, $language_code, $this );
        $translateable_strings = $translateable_information['translateable_strings'];
        $nodes = $translateable_information['nodes'];

        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );;
        }

        $translated_strings = $this->process_strings( $translateable_strings, $language_code );

        $preview_mode = isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview';
        if ( $preview_mode ) {
            $translated_string_ids = $this->trp_query->get_string_ids($translateable_strings, $language_code);
        }
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
                    $nodes[$i]['node']->setAttribute( $accessor, str_replace( $translateable_string, $translated_strings[$i], $nodes[$i]['node']->getAttribute( $accessor ) ) );
                }else{
                    if ( strpos ( $nodes[$i]['node']->$accessor, $translateable_string ) === false ){
                        $translateable_string = $alternate_translateable_string;
                    }
                    $nodes[$i]['node']->$accessor = str_replace( $translateable_string, $translated_strings[$i], $nodes[$i]['node']->$accessor );
                }

                if ( $nodes[$i]['type'] == 'image_src' && $nodes[$i]['node']->hasAttribute("srcset") && $nodes[$i]['node']->srcset !=  "" && $translated_strings[$i] != $translateable_strings[$i]) {
                    $nodes[$i]['node']->srcset = "";
                }
            }

            if ( $preview_mode ) {
                if ( $accessor == 'outertext' ) {
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

        if ( ! $this->url_converter ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->url_converter = $trp->get_component('url_converter');
        }

        foreach( $html->find('a[href!="#"]') as $a_href)  {
            $url = $a_href->href;
            $is_external_link = $this->is_external_link( $url );
            $is_admin_link = $this->is_admin_link($url);
            if ( $this->settings['force-language-to-custom-links'] == 'yes' && !$is_external_link && $this->url_converter->get_lang_from_url_string( $url ) == null && !$is_admin_link ){
                $a_href->href = apply_filters( 'trp_force_custom_links', $this->url_converter->get_url_for_language( $TRP_LANGUAGE, $url ), $url, $TRP_LANGUAGE, $a_href );
                $url = $a_href->href;
            }

            if( $preview_mode && ( $is_external_link || $this->is_different_language( $url ) || $is_admin_link ) ) {
                $a_href->setAttribute( 'data-trp-unpreviewable', 'trp-unpreviewable' );
            }
        }


        return $html->save();
    }

    protected function is_external_link( $url ){
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

    protected function is_admin_link( $url ){

        if ( strpos( $url, admin_url() ) !== false || strpos( $url, wp_login_url() ) !== false ){
            return true;
        }
        return false;

    }

    public function process_strings( $translateable_strings, $language_code ){
        $translated_strings = array();

        if ( ! $this->trp_query ) {
            $trp = TRP_Translate_Press::get_trp_instance();
            $this->trp_query = $trp->get_component( 'query' );;
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

    public function has_ancestor_attribute($node,$attribute) {
        $currentNode = $node;
        while($currentNode->parent() && $currentNode->parent()->tag!="html") {
            if(isset($currentNode->parent()->$attribute))
                return true;
            else
                $currentNode = $currentNode->parent();
        }
        return false;
    }

    public function enqueue_dynamic_translation(){
        $enable_dynamic_translation = apply_filters( 'trp_enable_dynamic_translation', true );
        if ( ! $enable_dynamic_translation ){
            return;
        }

        global $TRP_LANGUAGE;

        if ( $TRP_LANGUAGE != $this->settings['default-language'] || ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) ) {
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
            if ( isset( $_GET['trp-edit-translation'] ) && $_GET['trp-edit-translation'] == 'preview' ) {
                $trp_data['trp_ajax_url'] = $trp_data['trp_wp_ajax_url'];
            }
            wp_enqueue_script('trp-dynamic-translator', TRP_PLUGIN_URL . 'assets/js/trp-translate-dom-changes.js', array('jquery', 'trp-language-switcher'), TRP_PLUGIN_VERSION );
            wp_localize_script('trp-dynamic-translator', 'trp_data', $trp_data);
        }
    }

}
