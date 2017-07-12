<?php

class TRP_Query{

    protected $table_name;
    protected $db;
    protected $settings;

    const NOT_TRANSLATED = 0;
    const MACHINE_TRANSLATED = 1;
    const HUMAN_REVIEWED = 2;

    public function __construct( $settings ){
        global $wpdb;
        $this->db = $wpdb;
        $this->settings =$settings;
    }

    //todo maybe move to UTILS to avoid duplicate
    protected function full_trim( $word ) {
        return trim( addslashes($word)," \t\n\r\0\x0B\xA0�" );
    }

    protected function int_trim( $int ){
        return intval( $int );
    }

    public function get_existing_translations( $strings_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT original,translated FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $strings_array ) )."') AND status != " . self::NOT_TRANSLATED, OBJECT_K );
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
        $table_name = $this->get_table_name($language_code);
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

            $sql_index = "CREATE INDEX index_name ON `" . $table_name . "` (original(100));";
            $this->db->query( $sql_index );
        }

    }

    public function insert_strings( $new_strings, $update_strings, $language_code ){
        if ( count( $new_strings ) == 0 && count( $update_strings ) == 0 ){
            return;
        }

        $query = "INSERT INTO `" . $this->get_table_name( $language_code ) . "` ( id, original, translated, status ) VALUES ";

        $values = array();
        $place_holders = array();
        $new_strings = array_unique( $new_strings );

        foreach ( $new_strings as $string ) {
            array_push( $values, NULL, $this->full_trim( $string ), NULL, self::NOT_TRANSLATED );
            $place_holders[] = "( '%d', '%s', '%s', '%d')";
        }
        foreach ( $update_strings as $string ) {
            array_push( $values, $string['id'], $string['original'], $string['translated'], $string['status'] );
            $place_holders[] = "( '%d', '%s', '%s', '%d')";
        }

        $on_duplicate = ' ON DUPLICATE KEY UPDATE translated=VALUES(translated), status=VALUES(status)';

        $query .= implode(', ', $place_holders);
        $query .= $on_duplicate;

        $this->db->query( $this->db->prepare("$query ", $values) );
        // you cannot insert multiple rows at once using insert() method.
        // but by using prepare you cannot insert NULL values.
    }


    public function get_string_ids( $original_strings, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','",  array_map( array( $this, 'full_trim' ), $original_strings )  )."')", OBJECT_K );
        return $dictionary;
    }


    public function get_untranslated_strings( $strings_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $strings_array ) )."') AND status = " . self::NOT_TRANSLATED, OBJECT_K );

        return $dictionary;
    }

    protected function get_table_name( $language_code ){

        return $this->db->prefix . 'trp_dictionary_' . strtolower( $this->settings['default-language'] ) . '_'. strtolower( $language_code );
    }

    public function get_string_rows( $id_array, $original_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, status FROM `" . $this->get_table_name( $language_code ) . "` WHERE id IN ('".implode( "','", array_map( 'intval',  $id_array ) )."') OR original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $original_array ) )."')", OBJECT_K );
        return $dictionary;
    }

}