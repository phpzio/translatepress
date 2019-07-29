<?php

class TRP_Translation_Memory {
    protected $db;
    protected $settings;
    /* @var TRP_Query */
    protected $trp_query;

    /**
     * TRP_Translation_Memory constructor.
     * @param $settings
     */
    public function __construct( $settings ){
        global $wpdb;
        $this->db = $wpdb;
        $this->settings = $settings;

        $trp = TRP_Translate_Press::get_trp_instance();
        if ( ! $this->trp_query ) {
            $this->trp_query = $trp->get_component( 'query' );
        }
    }

    /**
     * Finding similar strings in the database and returning an array with possible translations.
     *
     *
     * @param string    $string         The original string we're searching a similar one.
     * @param string    $language       The language in which we want to search for the similar translated string.
     * @param int       $number         The number of similar strings we want to return.
     * @return array                    Array with (original => translated ) pairs based on the number of strings we should account for. Empty array if nothing is found.
     */
    public function get_similar_string_translation( $string, $language, $number ){
        return array();
    }

    /**
     * Finding similar strings in the database and returning an array with possible translations for each individual string sent.
     * Only similar strings with high similarity(90% and higher) and longer then 20 characters are returned.
     * We need to process multiple strings in a single query.
     *
     *
     * @param array     $strings        The original strings we're searching for a similar one.
     * @param string    $language       The language in which we want to search for the similar translated string.
     * @return array                    Array with (original => translated ) pairs based on the number of strings we should account for. Empty array if nothing is found.
     */
    public function get_similar_string_translation_multiple( $strings, $language ){

    }

    /**
     * Finding similar gettext strings in the database and returning an array with possible translations.
     *
     *
     * @param string    $string         The original string we're searching a similar one.
     * @param string    $language       The original string we're searching a similar one.
     * @param int       $number         The original string we're searching a similar one.
     * @return array                    Array with (original => translated ) pairs based on the number of strings we should account for. Empty array if nothing is found.
     */
    public function get_similar_gettext_translation( $string, $language, $number ){
        return array();
    }

}