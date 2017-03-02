<?php

class TRP_Query{

    protected $table_name;
    protected $db;
    protected $settings;
    //todo version in constant defined, it does not have to be everywhere separately
    const NOT_TRANSLATED = 0;
    const MACHINE_TRANSLATED = 1;
    const HUMAN_REVIEWED = 2;

        //todo maybe set current language here.
    public function __construct( $settings ){
        global $wpdb;
        $this->db = $wpdb;
        $this->settings =$settings;
    }

    public function get_existing_translations( $strings_array, $language_code ){

        //todo what happens if a string has quotes in it. it might break.
        $dictionary = $this->db->get_results("SELECT original,translated FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", $strings_array )."') AND status != " . self::NOT_TRANSLATED, OBJECT_K );

        return $dictionary;
    }

    public function get_constant_not_translated(){
        return self::NOT_TRANSLATED;
    }
    public function get_constant_machine_translated(){
        return self::MACHINE_TRANSLATED;
    }
    public function get_constant_human_reviewed(){
        return self::HUMAN_REVIEWED;
    }

    public function check_table( $language_code ){
        $table_name = $this->db->prefix . 'trp_dictionary_' . $language_code;
        if ( $this->db->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            // table not in database. Create new table
            $charset_collate = $this->db->get_charset_collate();

            // todo different charset collation for each language?
            $sql = "CREATE TABLE `" . $table_name . "`(
                                    id bigint(20) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                                    original  longtext NOT NULL,
                                    translated  longtext,
                                    status int(20),
                                    UNIQUE KEY id (id) )
                                     $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        }

    }

    //todo make everyone know what current language there is
    public function insert_strings( $new_strings, $update_strings, $language_code ){

        if ( count( $new_strings ) == 0 && count( $update_strings ) == 0 ){
            return;
        }

        $values = array();
        $place_holders = array();

        //todo if not exist maybe?
        //todo name of column to be constant
        $query = "INSERT INTO `" . $this->get_table_name( $language_code ) . "` ( id, original, translated, status ) VALUES ";
/*
        foreach ( $strings as $string ) {
            array_push( $values, $string, NULL, self::NOT_TRANSLATED );
            $place_holders[] = "('%s', '%s', '%d')";
        }*/

        $values = array();
        $place_holders = array();
        //error_log('newstrings' . json_encode($new_strings));
        $new_strings = array_unique( $new_strings );
        foreach ( $new_strings as $string ) {
            //error_log( '[new]' . $string );
            array_push( $values, NULL, $string, NULL, self::NOT_TRANSLATED );
            $place_holders[] = "( '%d', '%s', '%s', '%d')";

            /*
            $values .= $this->format_value( NULL, $string, NULL, self::NOT_TRANSLATED );
            $place_holders .= "( '%d', '%s', '%s', '%d'),";*/
        }
        foreach ( $update_strings as $string ) {
           // $values .= $this->format_value( $string['id'], $string['original'], $string['traslated'], $string['status'] );
            array_push( $values, $string['id'], $string['original'], $string['traslated'], $string['status'] );
            $place_holders[] = "( '%d', '%s', '%s', '%d')";
        }

        // rtrim? virgula
        $on_duplicate = ' ON DUPLICATE KEY UPDATE original=VALUES(original),translated=VALUES(translated)';
        //error_log($values);

        /*INSERT into `wp_trp_dictionary_el` (id,original,translated) VALUES (1,1,1),(null,2,3),(null,9,3),(4,10,12) ON DUPLICATE KEY UPDATE original=VALUES(original),translated=VALUES(translated)*/

        $query .= implode(', ', $place_holders);

        /*$query .= rtrim( $place_holders, ',' );
        $query .= ' ' . rtrim( $values, ',' );*/

        $this->db->query( $this->db->prepare("$query ", $values) );//, rtrim( $values, ',' ) ));
        // you cannot insert multiple rows at once using insert() method.
        // but by using prepare you cannot insert NULL values.
    }

    public function get_translation_ids( $translated_strings, $language_code ){
        $dictionary = $this->db->get_results("SELECT translated, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE translated IN ('".implode( "','", $translated_strings )."')", OBJECT_K );

        return $dictionary;
    }


    protected function format_value( $id, $original, $translated, $status ){
        return '(' . $id . ',' . $original . ',' . $translated . ',' . $status . '),';
    }

    public function get_untranslated_strings( $strings_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", $strings_array )."') AND status = " . self::NOT_TRANSLATED, OBJECT_K );

        return $dictionary;
    }

    protected function get_table_name( $language_code ){
        return $this->db->prefix . 'trp_dictionary_' . $language_code;
    }

}