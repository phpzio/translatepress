<?php
class TRP_Utils{

  	protected static $languages = array();

	protected static $wp_languages;

	/*
	 * Possible values  $english_or_native_name: 'english_name', 'native_name'
	 */

    public static function get_languages( $english_or_native_name = 'english_name' ){
		if ( empty( self::$languages[$english_or_native_name] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
			$wp_languages = self::get_wp_languages();
			$default = array( 'language'	=> 'en', 'english_name'=> 'English (United States)', 'native_name' => 'English (United States)' );
			self::$languages[$english_or_native_name] = array( $default['language'] => $default[$english_or_native_name] );
			foreach ( $wp_languages as $wp_language ) {
				self::$languages[$english_or_native_name][$wp_language['language']] = $wp_language[$english_or_native_name];
			}
		}

        return apply_filters( 'trp_languages', self::$languages[$english_or_native_name] );
    }

	public static function get_wp_languages(){
		if ( empty( self::$wp_languages ) ){
			self::$wp_languages = wp_get_available_translations();
		}
		return self::$wp_languages;
	}

	public static function get_google_translate_codes( $language_codes ){
		$google_translate_codes = array();
		$wp_languages = self::get_wp_languages();
		$map_wp_codes_to_google = apply_filters( 'trp_map_wp_codes_to_google', array(
			'en_US' => 'en',
			'zh_HK' => 'zh-TW',
			'zh_TW'	=> 'zh-TW',
			'zh_CN'	=> 'zh-CN',

		) );
		foreach ( $language_codes as $language_code ) {
			if ( isset( $map_wp_codes_to_google[$language_code] ) ){
				$google_translate_codes[$language_code] = $map_wp_codes_to_google[$language_code];
			}else {
				foreach ($wp_languages as $wp_language) {
					if ($wp_language['language'] == $language_code) {
						$google_translate_codes[$language_code] = reset($wp_language['iso']);
						break;
					}
				}
			}
		}
		return $google_translate_codes;
	}

	public static function get_language_names( $language_codes, $english_or_native_name = 'english_name' ){
		$return = array();
        $languages = self::get_languages($english_or_native_name);
		foreach ( $language_codes as $language_code ){
			$return[$language_code] = $languages[$language_code];
		}

		return $return;
	}

	public static function get_current_page_url() {
		$pageURL = 'http';

		if ((isset($_SERVER["HTTPS"])) && ($_SERVER["HTTPS"] == "on"))
			$pageURL .= "s";

		$pageURL .= "://";

		if( strpos( $_SERVER["HTTP_HOST"], $_SERVER["SERVER_NAME"] ) !== false ){
			$pageURL .=$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
		}
		else {
			if ($_SERVER["SERVER_PORT"] != "80")
				$pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
			else
				$pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}

		if ( function_exists('apply_filters') ) $pageURL = apply_filters('trp_curpageurl', $pageURL);

		return $pageURL;
	}
}