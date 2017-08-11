<?php

/**
 * Class TRP_Query
 *
 * Queries for translations in custom trp tables.
 *
 */
class TRP_Query{

    protected $table_name;
    protected $db;
    protected $settings;

    const NOT_TRANSLATED = 0;
    const MACHINE_TRANSLATED = 1;
    const HUMAN_REVIEWED = 2;

    /**
     * TRP_Query constructor.
     * @param $settings
     */
    public function __construct( $settings ){
        global $wpdb;
        $this->db = $wpdb;
        $this->settings =$settings;
    }

    /**
     * Trim unwanted characters from string.
     *
     * @param string $word      String to trim.
     * @return string           Trimmed string.
     */
    protected function full_trim( $word ) {

        $word = addslashes( trim($word," \t\n\r\0\x0B\xA0�" ) );

        // make sure to skip weird characters
        if ( htmlentities( $word ) == "" ){
            $word = '';
        }
        return $word;
    }

    /**
     * Returns the translations for the provided strings.
     *
     * Only returns results where there actually is a translation ( != NOT_TRANSLATED )
     *
     * @param array $strings_array      Array of original strings to search for.
     * @param string $language_code     Language code to query for.
     * @return object                   Associative Array of objects with translations where key is original string.
     */
    public function get_existing_translations( $strings_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT original,translated FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $strings_array ) )."') AND status != " . self::NOT_TRANSLATED, OBJECT_K );

        return $dictionary;
    }

    /**
     * Return constant used for entries without translations.
     *
     * @return int
     */
    public function get_constant_not_translated(){
        return self::NOT_TRANSLATED;
    }

    /**
     * Return constant used for entries with machine translation.
     *
     * @return int
     */
    public function get_constant_machine_translated(){
        return self::MACHINE_TRANSLATED;
    }

    /**
     * Return constant used for entries edited by humans.
     *
     * @return int
     */
    public function get_constant_human_reviewed(){
        return self::HUMAN_REVIEWED;
    }

    /**
     * Check if table for specific language exists.
     *
     * If the table does not exists it is created.
     *
     * @param string $language_code
     */
    public function check_table( $language_code ){
        $table_name = $this->get_table_name($language_code);
        if ( $this->db->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            // table not in database. Create new table
            $charset_collate = $this->db->get_charset_collate();

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

    /**
     * Insert translations and new strings in DB.
     *
     * @param array $new_strings            Array of strings for which we do not have a translation. Only inserts original.
     * @param array $update_strings         Array of arrays, each containing an entry to update.
     * @param string $language_code         Language code of table where it should be inserted.
     */
    public function insert_strings( $new_strings, $update_strings, $language_code ){
        if ( count( $new_strings ) == 0 && count( $update_strings ) == 0 ){
            return;
        }
        $query = "INSERT INTO `" . $this->get_table_name( $language_code ) . "` ( id, original, translated, status ) VALUES ";

        $values = array();
        $place_holders = array();
        $new_strings = array_unique( $new_strings );

        foreach ( $new_strings as $string ) {
            array_push( $values, NULL, $string, NULL, self::NOT_TRANSLATED );
            $place_holders[] = "( '%d', '%s', '%s', '%d')";
        }
        foreach ( $update_strings as $string ) {
            array_push( $values, $string['id'], $string['original'], $string['translated'], $string['status'] );
            $place_holders[] = "( '%d', '%s', '%s', '%d')";
        }

        $on_duplicate = ' ON DUPLICATE KEY UPDATE translated=VALUES(translated), status=VALUES(status)';

        $query .= implode(', ', $place_holders);
        $query .= $on_duplicate;

        // you cannot insert multiple rows at once using insert() method.
        // but by using prepare you cannot insert NULL values.
        $this->db->query( $this->db->prepare($query . ' ', $values) );
    }

    /**
     * Returns the DB ids of the provided original strings
     *
     * @param array $original_strings       Array of original strings to search for.
     * @param string $language_code         Language code to query for.
     * @return object                       Associative Array of objects with translations where key is original string.
     */
    public function get_string_ids( $original_strings, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','",  array_map( array( $this, 'full_trim' ), $original_strings ) )."')", OBJECT_K );
        return $dictionary;
    }

    /**
     * Returns the entries for the provided strings.
     *
     * Only returns results where there is no translation ( == NOT_TRANSLATED )
     *
     * @param array $strings_array      Array of original strings to search for.
     * @param string $language_code     Language code to query for.
     * @return object                   Associative Array of objects with translations where key is original string.
     */
    public function get_untranslated_strings( $strings_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $strings_array ) )."') AND status = " . self::NOT_TRANSLATED, OBJECT_K );

        return $dictionary;
    }

    /**
     * Return custom table name for given language code.
     *
     * @param string $language_code         Language code.
     * @return string                       Table name.
     */
    protected function get_table_name( $language_code ){

        return $this->db->prefix . 'trp_dictionary_' . strtolower( $this->settings['default-language'] ) . '_'. strtolower( $language_code );
    }

    /**
     * Return entire rows for given ids or original strings.
     *
     * @param array $id_array           Int array of db ids.
     * @param array $original_array     String array of originals.
     * @param string $language_code     Language code of table.
     * @return object                   Associative Array of objects with translations where key is id.
     */
    public function get_string_rows( $id_array, $original_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, status FROM `" . $this->get_table_name( $language_code ) . "` WHERE id IN ('".implode( "','", array_map( 'intval',  $id_array ) )."') OR original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $original_array ) )."')", OBJECT_K );
        return $dictionary;
    }

}