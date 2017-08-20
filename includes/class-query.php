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

    protected function full_trim( $word ) {

        $word = addslashes( trim($word," \t\n\r\0\x0B\xA0�" ) );
        if ( htmlentities( $word ) == "" ){
            $word = '';
        }
        return $word;
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
    
    public function check_gettext_table( $language_code ){
        $table_name = $this->get_gettext_table_name($language_code);
        if ( $this->db->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
            // table not in database. Create new table
            $charset_collate = $this->db->get_charset_collate();

            $sql = "CREATE TABLE `" . $table_name . "`(
                                    id bigint(20) AUTO_INCREMENT NOT NULL PRIMARY KEY,
                                    original  longtext NOT NULL,
                                    translated  longtext,
                                    domain  longtext,
                                    status int(20),
                                    UNIQUE KEY id (id) )
                                     $charset_collate;";
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            $sql_index = "CREATE INDEX index_name ON `" . $table_name . "` (original(100));";
            $this->db->query( $sql_index );
        }
    }

    public function check_for_necessary_updates(){
        $stored_database_version = get_option('trp_plugin_version');
        if( empty($stored_database_version) || version_compare( TRP_PLUGIN_VERSION, $stored_database_version, '>' ) ){
            $this->check_if_gettext_tables_exist();
        }
        update_option( 'trp_plugin_version', TRP_PLUGIN_VERSION );
    }

    public function check_if_gettext_tables_exist(){
        if( !empty( $this->settings['translation-languages'] ) ){
            foreach( $this->settings['translation-languages'] as $site_language_code ){
                $this->check_gettext_table($site_language_code);
            }
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
        $this->db->query( $this->db->prepare($query . ' ', $values) );
    }

    public function insert_gettext_strings( $new_strings, $language_code ){
        if ( count( $new_strings ) == 0  ){
            return;
        }
        $query = "INSERT INTO `" . $this->get_gettext_table_name( $language_code ) . "` ( id, original, translated, domain, status ) VALUES ";

        $values = array();
        $place_holders = array();

        foreach ( $new_strings as $string ) {
            if( $string['original'] == $string['translated'] || $string['translated'] == '' ){
                $translated = NULL;
                $status = self::NOT_TRANSLATED;
            }
            else{
                $translated = $string['translated'];
                $status = self::HUMAN_REVIEWED;
            }
                
            array_push( $values, NULL, $string['original'], $translated, $string['domain'], $status );
            $place_holders[] = "( '%d', '%s', '%s', '%s', '%d')";
        }



        $query .= implode(', ', $place_holders);
        $this->db->query( $this->db->prepare($query . ' ', $values) );
        
        if( count( $new_strings ) == 1 )
            return $this->db->insert_id;
        else
            return null;
    }

    public function update_gettext_strings( $updated_strings, $language_code ){
        if ( count( $updated_strings ) == 0  ){
            return;
        }
        $query = "INSERT INTO `" . $this->get_gettext_table_name( $language_code ) . "` ( id, original, translated, domain, status ) VALUES ";

        $values = array();
        $place_holders = array();

        foreach ( $updated_strings as $string ) {
            array_push( $values, $string['id'], $string['original'], $string['translated'], $string['domain'], $string['status'] );
            $place_holders[] = "( '%d', '%s', '%s', '%s', '%d')";
        }

        $on_duplicate = ' ON DUPLICATE KEY UPDATE translated=VALUES(translated), status=VALUES(status)';

        $query .= implode(', ', $place_holders);
        $query .= $on_duplicate;
        $this->db->query( $this->db->prepare($query . ' ', $values) );
    }


    public function get_string_ids( $original_strings, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','",  array_map( array( $this, 'full_trim' ), $original_strings ) )."')", OBJECT_K );
        return $dictionary;
    }


    public function get_untranslated_strings( $strings_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT original, id FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $strings_array ) )."') AND status = " . self::NOT_TRANSLATED, OBJECT_K );

        return $dictionary;
    }

    public function get_all_gettext_strings(  $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, domain FROM `" . $this->get_gettext_table_name( $language_code ) . "`" , ARRAY_A );

        return $dictionary;
    }

    public function get_all_gettext_translated_strings(  $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, domain FROM `" . $this->get_gettext_table_name( $language_code ) . "` WHERE translated <>'' AND status != " . self::NOT_TRANSLATED, ARRAY_A );

        return $dictionary;
    }

    protected function get_table_name( $language_code ){

        return $this->db->prefix . 'trp_dictionary_' . strtolower( $this->settings['default-language'] ) . '_'. strtolower( $language_code );
    }

    protected function get_gettext_table_name( $language_code ){

        return $this->db->prefix . 'trp_gettext_' . strtolower( $language_code );
    }

    public function get_string_rows( $id_array, $original_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, status FROM `" . $this->get_table_name( $language_code ) . "` WHERE id IN ('".implode( "','", array_map( 'intval',  $id_array ) )."') OR original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $original_array ) )."')", OBJECT_K );
        return $dictionary;
    }

    public function get_gettext_string_rows_by_ids( $id_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, domain, status FROM `" . $this->get_gettext_table_name( $language_code ) . "` WHERE id IN ('".implode( "','", array_map( 'intval',  $id_array ) )."')", ARRAY_A );
        return $dictionary;
    }

    public function get_gettext_string_rows_by_original( $original_array, $language_code ){
        $dictionary = $this->db->get_results("SELECT id, original, translated, domain, status FROM `" . $this->get_gettext_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $original_array ) )."')", ARRAY_A );
        return $dictionary;
    }

}