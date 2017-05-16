<?php

class TRP_Slug_Manager {

    protected $settings;
    protected $trp_query;
    protected $url_converter;

    public function __construct( $settings, $url_converter, $trp_query ){
        $this->settings = $settings;
        $this->url_converter = $url_converter;
        $this->trp_query = $trp_query;
    }

}