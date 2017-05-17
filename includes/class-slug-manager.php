<?php

class TRP_Slug_Manager {

    protected $settings;
    protected $trp_query;
    protected $url_converter;
    protected $translated_slug_meta = 'trp_translated_slug';

    public function __construct( $settings, $url_converter, $trp_query ){
        $this->settings = $settings;
        $this->url_converter = $url_converter;
        $this->trp_query = $trp_query;
    }

    public function change_slug_var_in_request($query_vars){
        return $query_vars;
    }

    public function translate_slug_for_posts( $permalink, $post, $leavename ){
        return $permalink;
    }

    public function translate_slugs_for_pages( $uri, $page ){
        return $uri;
    }

}