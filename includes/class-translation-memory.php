<?php

class TRP_Translation_Memory {
    protected $db;
    protected $settings;
    /* @var TRP_Query */
    protected $trp_query;

    const MIN_NUMBER_OF_CHARS_FOR_FULLTEXT = 20;

    /**
     * TRP_Translation_Memory constructor.
     * @param $settings
     */
    public function __construct( $settings ){
        global $wpdb;
        $this->db = $wpdb;
        $this->settings = $settings;
    }

    /**
     * Finding similar strings in the database and returning an array with possible translations.
     *
     *
     * @param string    $string         The original string we're searching a similar one.
     * @param string    $language_code  The language in which we want to search for the similar translated string.
     * @param int       $number         The number of similar strings we want to return.
     * @return array                    Array with (original => translated ) pairs based on the number of strings we should account for. Empty array if nothing is found.
     */
    public function get_similar_string_translation( $string, $language_code, $number ){
        if ( !isset( $this->settings['advanced_settings']['enable_translation_memory'] ) || $this->settings['advanced_settings']['enable_translation_memory'] !== "yes" ){
            return array();
        }

        $trp = TRP_Translate_Press::get_trp_instance();
        if ( ! $this->trp_query ) {
            $this->trp_query = $trp->get_component( 'query' );
        }

        $query = '';
        $query .= "SELECT original,translated, status FROM `"
                 . sanitize_text_field( $this->trp_query->get_table_name( $language_code ) )
                 . "` WHERE status != " . TRP_Query::NOT_TRANSLATED . " AND MATCH(original) AGAINST ('%s' IN NATURAL LANGUAGE MODE ) LIMIT " . $number;


        $query = $this->db->prepare( $query, $string );
        $dictionary = $this->db->get_results( $query, ARRAY_A );

        return $dictionary;
    }

    /**
     * Finding similar gettext strings in the database and returning an array with possible translations.
     *
     *
     * @param string    $string         The original string we're searching a similar one.
     * @param string    $language_code  The original string we're searching a similar one.
     * @param int       $number         The original string we're searching a similar one.
     * @return array                    Array with (original => translated ) pairs based on the number of strings we should account for. Empty array if nothing is found.
     */
    public function get_similar_gettext_translation( $string, $language_code, $number ){
        return array();
    }

}