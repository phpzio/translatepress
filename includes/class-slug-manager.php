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
                    $query_vars['name'] = TRP_Slug_Manager::get_original_slug($query_vars['name']);
                    $query_vars[$query_vars['post_type']] = TRP_Slug_Manager::get_original_slug($query_vars['name']);
                } else {
                    $query_vars['name'] = TRP_Slug_Manager::get_original_slug($query_vars['name']);
                }
            } else if (!empty($query_vars['pagename'])) {
                $query_vars['pagename'] = TRP_Slug_Manager::get_original_slug($query_vars['pagename']);
            }
        }
        return $query_vars;
    }

    /* change the slug in permalinks for posts and post types */
    public function translate_slug_for_posts( $permalink, $post, $leavename ){
        $translated_slug = TRP_Slug_Manager::get_translated_slug( $post );
        if( !empty( $translated_slug ) ){
            $permalink = str_replace('/'.$post->post_name.'/', '/'.$translated_slug.'/', $permalink );
        }

        return $permalink;
    }

    /* change the slug for pages in permalinks */
    public function translate_slugs_for_pages( $uri, $page ){
        $translated_slug = TRP_Slug_Manager::get_translated_slug( $page );
        if( !empty( $translated_slug ) )
            $uri = $translated_slug;

        return $uri;
    }

    /**
     * @param $post the post object
     * @param string $language optional parameter for language. if it's not present it will grab it from the $TRP_LANGUAGE global
     * @return mixed|string an empty string or the translated slug
     */
    protected function get_translated_slug( $post, $language = null ){
        if( $language == null ){
            global $TRP_LANGUAGE;
            if( !empty( $TRP_LANGUAGE ) )
                $language = $TRP_LANGUAGE;
        }

        $translated_slug = get_post_meta( $post->ID, $this->translated_slug_meta.$language, true );
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
    protected function save_translated_slug(){
        // todo "current user can" check
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            if ( isset( $_POST['action'] ) && $_POST['action'] === 'trp_save_slug_translation' ) {

                /* get our posted parameters here and sanitize them */
                if( !empty( $_POST['original_slug'] ) )
                    $original_slug = sanitize_text_field( $_POST['original_slug'] );
                if( !empty( $_POST['translated_slug'] ) )
                    $translated_slug = sanitize_text_field( $_POST['translated_slug'] );
                if( !empty( $_POST['language'] ) )
                    $language = sanitize_text_field( $_POST['language'] );

                if( !empty( $original_slug ) && !empty( $translated_slug ) && !empty( $language )  ){
                    global $wpdb;
                    $post_id = $wpdb->get_results( $wpdb->prepare(
                        "
                    SELECT ID 
                    FROM $wpdb->posts
                    WHERE post_name = '%s'                        
                    ", $original_slug ) );

                    if( !empty( $post_id ) ){
                        $post_id = $post_id[0]->ID;
                        if( is_numeric( $post_id ) ){
                            update_post_meta( $post_id, $this->translated_slug_meta.$language, $translated_slug );
                        }
                    }

                }
            }
        }
        die();
    }

}