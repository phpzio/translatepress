<?php



class TRP_Ajax{

    protected $connection;
    protected $table_prefix;

    public function __construct( ){

        if ( !isset( $_POST['action'] ) || $_POST['action'] !== 'trp_get_translations' || empty( $_POST['strings'] ) || empty( $_POST['language'] ) ) {
            die();
        }

        if ( $this->connect_to_db() ){

            $this->output_translations( $this->sanitize_strings( $_POST['strings'] ), filter_var( $_POST['language'], FILTER_SANITIZE_STRING ) );
            //error_log( 'Successful connection to DB' );
            mysqli_close($this->connection);
        }else{
            //todo make sure it gets error so that it tries via regular wp-ajax
            //error_log( 'Error connecting to DB' );
            $this->return_error();

        }

    }

    protected function sanitize_strings( $posted_strings){
        $strings = json_decode(stripslashes( $posted_strings ));
        $original_array = array();
        if ( is_array( $strings ) ) {
            foreach ($strings as $key => $string) {
                if ( isset($string->original ) ) {
                    // todo replace sanitize
                    $original_array[$key] = filter_var( $string->original, FILTER_SANITIZE_STRING );
                }
            }
        }
        return $original_array;
    }

    protected function connect_to_db(){

        $file = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-config.php';

        try {
            $content = @file_get_contents($file);
            if ($content == false) {
                return false;
            }
        } catch (Exception $e) {
            return false;
        }


        // remove single line and multi-line /* Comments */
        $content = preg_replace('!/\*.*?\*/!s', '', $content);
        $content = preg_replace('/\n\s*\n/', "\n", $content);

        // remove single line double slashes
        $content = preg_replace('#^\s*//.+$#m', "", $content);

        $credentials = array(
            'db_name'       => 'DB_NAME',
            'db_user'       => 'DB_USER',
            'db_password'   => 'DB_PASSWORD',
            'db_host'       => 'DB_HOST' );

        foreach ( $credentials as $credential => $constant_name ) {
            if ( preg_match_all( "/define\s*\(\s*['\"]" . $constant_name . "['\"]\s*,\s*['\"](.*?)['\"]\s*\)/", $content, $result ) ) {
                //error_log($result[1][0]);
                $credentials[ $credential ] = $result[1][0];
            } else {
                return false;
            }
        }


        $this->connection = mysqli_connect( $credentials['db_host'], $credentials['db_user'], $credentials['db_password'], $credentials['db_name'] );

        // Check connection
        if ( mysqli_connect_errno() ) {
            //Failed to connect to MySQL.
            return false;
        }

        if ( preg_match_all( '/\$table_prefix\s*=\s*[\'"](.*?)[\'"]/', $content, $results ) ) {
            $this->table_prefix = end( $results[1] );
        }else{
            $this->table_prefix = $this->sql_find_table_prefix();
            if ( $this->table_prefix === false ){
                return false;
            }
        }

        return true;
    }

    protected function sql_find_table_prefix(){
        $sql = "SELECT DISTINCT SUBSTRING(`TABLE_NAME` FROM 1 FOR ( LENGTH(`TABLE_NAME`)-8 ) ) as prefix FROM information_schema.TABLES WHERE `TABLE_NAME` LIKE '%postmeta'";
        $result = mysqli_query( $this->connection, $sql );
        if ( mysqli_num_rows( $result ) > 0 ) {
            $result_object = mysqli_fetch_assoc($result);
            $this->table_prefix = $result_object['prefix'];
        } else {
            return false;
        }
    }

    protected function full_trim( $word ) {
        return trim( $word," \t\n\r\0\x0B\xA0�" );
    }

    protected function output_translations( $strings, $language ){
        //$language = 'ff';
        $sql = 'SELECT original, translated FROM ' . $this->table_prefix . 'trp_dictionary_' . $language . ' WHERE original IN (\'' . implode( "','", array_map( array( $this, 'full_trim' ), $strings ) ).'\') AND status != 0';
        //error_log($sql);
        $result = mysqli_query( $this->connection, $sql );
        if ( $result === false ){
            $this->return_error();
        }else {
            //error_log(json_encode($result));
            //error_log(phpversion());
            $dictionaries[$language] = array();
            while ($row = mysqli_fetch_assoc($result)) {
                //$result_object = mysqli_fetch_all( $result, MYSQLI_ASSOC );
                $dictionaries[$language][] = $row;
            }

            //error_log(json_encode($dictionaries));
            echo json_encode($dictionaries);
        }

        //SELECT original,translated FROM `" . $this->get_table_name( $language_code ) . "` WHERE original IN ('".implode( "','", array_map( array( $this, 'full_trim' ), $strings_array ) )."') AND status != " . self::NOT_TRANSLATED, OBJECT_K );

    }

    protected function return_error(){
        echo json_encode( 'error' );
        exit;
    }
}

new TRP_Ajax;

die();

