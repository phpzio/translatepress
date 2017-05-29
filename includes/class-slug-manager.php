<?php

class TRP_Slug_Manager {

    protected $settings;
    protected $trp_query;
    protected $url_converter;
    protected $translated_slug_meta = '_trp_translated_slug_';

    public function __construct( $settings, $url_converter, $trp_query ){
        $this->settings = $settings;
        $this->url_converter = $url_converter;
        $this->trp_query = $trp_query;
    }

    /**
     * Change the query_vars if we find a translated slug in the database
     */
    public function change_slug_var_in_request( $query_vars ){
        global $TRP_LANGUAGE;

        if( !empty($TRP_LANGUAGE) && $this->settings["default-language"] != $TRP_LANGUAGE ){
            if (!empty($query_vars['name'])) {
                if (!empty($query_vars['post_type'])) {
                    /* we can have an hierarchical structure for post types */
                    $postnames = explode( '/', $query_vars['name'] );
                    $translated_postnames = array();
                    foreach( $postnames as $postname ){
                        $translated_postnames[] = $this->get_original_slug( $postname );
                    }
                    $query_vars['name'] = implode( '/', $translated_postnames );
                    $query_vars[$query_vars['post_type']] = implode( '/', $translated_postnames );
                } else {
                    $query_vars['name'] = $this->get_original_slug($query_vars['name']);
                }
            } else if (!empty($query_vars['pagename'])) {
                /* we can have an hierarchical structure for pages */
                $translated_pagenames = array();
                $pagenames = explode( '/', $query_vars['pagename'] );
                foreach ( $pagenames as $pagename ){
                    $translated_pagenames[] = $this->get_original_slug( $pagename );
                }
                $query_vars['pagename'] = implode( '/', $translated_pagenames );
            }
        }
        return $query_vars;
    }

    /* change the slug in permalinks for posts and post types */
    public function translate_slug_for_posts( $permalink, $post, $leavename ){
        if( $post->post_parent == 0 ){
            $translated_slug = $this->get_translated_slug( $post );
            if( !empty( $translated_slug ) ){
                $permalink = str_replace('/'.$post->post_name.'/', '/'.$translated_slug.'/', $permalink );
            }
        }
        else{
            $posts_hierarchy = get_post_ancestors( $post->ID );
            $posts_hierarchy[] = $post->ID;
            foreach( $posts_hierarchy as $post_id ){
                $translated_slug = $this->get_translated_slug( $post_id );
                if( !empty( $translated_slug ) ){
                    $post_object = get_post( $post_id );
                    $permalink = str_replace('/'.$post_object->post_name.'/', '/'.$translated_slug.'/', $permalink );
                }
            }

        }

        return $permalink;
    }

    /* change the slug for pages in permalinks */
    public function translate_slugs_for_pages( $uri, $page ){
        $old_uri = $uri;
        if( strpos( $uri, '/' ) === false ){//means we do not have any page ancestors in the link so proceed
            $uri = $this->get_translated_slug( $page );
        }
        else{
            $uri_parts = explode( '/', $uri );
            $page_ancestors = array_reverse( get_post_ancestors( $page->ID ) );//this returns an array of ancestors the first element in the array is the closest ancestor so we need it reversed
            $translated_uri_parts = array();
            if( !empty( $uri_parts ) && !empty( $page_ancestors ) ) {
                foreach ($uri_parts as $key => $uri_part) {
                    if( !empty( $page_ancestors[$key] ) )
                        $translated_slug = $this->get_translated_slug($page_ancestors[$key]);
                    else
                        $translated_slug = $this->get_translated_slug($page);

                    if (!empty($translated_slug))
                        $translated_uri_parts[] = $translated_slug;
                    else
                        $translated_uri_parts[] = $uri_part;
                }

                if (!empty($translated_uri_parts))
                    $uri = implode('/', $translated_uri_parts);
            }
        }
        if ( empty ( $uri ) ){
            $uri = $old_uri;
        }

        return $uri;
    }

    /**
     * @param $post the post object or post id
     * @param string $language optional parameter for language. if it's not present it will grab it from the $TRP_LANGUAGE global
     * @return mixed|string an empty string or the translated slug
     */
    protected function get_translated_slug( $post, $language = null ){
        if( $language == null ){
            global $TRP_LANGUAGE;
            if( !empty( $TRP_LANGUAGE ) )
                $language = $TRP_LANGUAGE;
        }

        if( is_object( $post ) )
            $post = $post->ID;

        $translated_slug = get_post_meta( $post, $this->translated_slug_meta.$language, true );
        if( !empty( $translated_slug ) )
            return $translated_slug;
        else
            return '';
    }

    /**
     * @param $slug the translated slug
     * @return string the original slug if we can find it
     */
    protected function get_original_slug( $slug ){
        global $TRP_LANGUAGE, $wpdb;

        if( !empty( $TRP_LANGUAGE ) ){

            $translated_slug = $wpdb->get_results($wpdb->prepare(
                "
                SELECT * 
                FROM $wpdb->postmeta
                WHERE meta_key = '%s' 
                    AND meta_value = '%s'
                ", $this->translated_slug_meta.$TRP_LANGUAGE, $slug
            ) );

            if( !empty( $translated_slug ) ){
                $post_id = $translated_slug[0]->post_id;
                $post = get_post( $post_id );
                if( !empty( $post ) )
                    $slug = $post->post_name;
            }
        }

        return $slug;
    }

    /**
     * Function on ajax hook to save the slug translation. 
     */
    public function save_translated_slug(){
        // todo "current user can" check
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            if ( isset( $_POST['action'] ) && $_POST['action'] === 'trp_save_slug_translation' && !empty( $_POST['strings'] ) ) {

                $slugs = json_decode(stripslashes($_POST['strings']));
                $update_slugs = array();
                foreach ( $slugs as $language => $language_slugs ) {
                    if ( in_array( $language, $this->settings['translation-languages'] ) && $language != $this->settings['default-language'] ) {
                        foreach( $language_slugs as $slug ) {
                            if ( isset( $slug->id ) && is_numeric( $slug->id ) ) {
                                $update_slugs[ $language ] = array();
                                array_push($update_slugs[ $language ], array(
                                    'id' => (int)$slug->id,
                                    'original' => sanitize_text_field($slug->original),
                                    'translated' => sanitize_text_field($slug->translated),
                                    'status' => (int)$slug->status
                                ));
                            }
                        }
                    }
                }

                global $wpdb;
                $post_id = '';
                foreach( $update_slugs as $language => $update_slugs_array ) {
                    if (empty($post_id)) {
                        $post_id = $wpdb->get_results($wpdb->prepare(
                            "
                    SELECT ID 
                    FROM $wpdb->posts
                    WHERE post_name = '%s'                        
                    ", $update_slugs_array['original']));
                    }

                    if( !empty( $post_id ) ){
                        $postid = $post_id[0]->ID;
                        if( is_numeric( $postid ) ){
                            update_post_meta( $postid, $this->translated_slug_meta.$language, $update_slugs_array['translated'] );
                        }
                    }
                }
            }
        }
        die();
    }

}