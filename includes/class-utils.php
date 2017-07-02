<?php
class TRP_Utils{

    protected static $google_translate_languages = array(
        'af'     => 'Afrikaans',
		'sq'     => 'Albanian',
		'am'     => 'Amharic',
		'ar'     => 'Arabic',
		'hy'     => 'Armenian',
		'az'     => 'Azeerbaijani',
		'eu'     => 'Basque',
		'be'     => 'Belarusian',
		'bn'     => 'Bengali',
		'bs'     => 'Bosnian',
		'bg'     => 'Bulgarian',
		'ca'     => 'Catalan',
		'ceb'    => 'Cebuano',
		'ny'     => 'Chichewa',
		'zh-CN'  => 'Chinese (Simplified)',
		'zh-TW'  => 'Chinese (Traditional)',
		'co'     => 'Corsican',
		'hr'     => 'Croatian',
		'cs'     => 'Czech',
		'da'     => 'Danish',
		'nl'     => 'Dutch',
		'en'     => 'English',
		'eo'     => 'Esperanto',
		'et'     => 'Estonian',
		'tl'     => 'Filipino',
		'fi'     => 'Finnish',
		'fr'     => 'French',
		'fy'     => 'Frisian',
		'gl'     => 'Galician',
		'ka'     => 'Georgian',
		'de'     => 'German',
		'el'     => 'Greek',
		'gu'     => 'Gujarati',
		'ht'     => 'Haitian Creole',
		'ha'     => 'Hausa',
		'haw'    => 'Hawaiian',
		'iw'     => 'Hebrew',
		'hi'     => 'Hindi',
        'hmn'    => 'Hmong',
		'hu'     => 'Hungarian',
		'is'     => 'Icelandic',
		'ig'     => 'Igbo',
		'id'     => 'Indonesian',
		'ga'     => 'Irish',
		'it'     => 'Italian',
		'ja'     => 'Japanese',
		'jw'     => 'Javanese',
		'kn'     => 'Kannada',
		'kk'     => 'Kazakh',
		'km'     => 'Khmer',
		'ko'     => 'Korean',
		'ku'     => 'Kurdish',
		'ky'     => 'Kyrgyz',
		'lo'     => 'Lao',
		'la'     => 'Latin',
		'lv'     => 'Latvian',
		'lt'     => 'Lithuanian',
		'lb'     => 'Luxembourgish',
		'mk'     => 'Macedonian',
		'mg'     => 'Malagasy',
		'ms'     => 'Malay',
		'ml'     => 'Malayalam',
		'mt'     => 'Maltese',
		'mi'     => 'Maori',
		'mr'     => 'Marathi',
		'mn'     => 'Mongolian',
		'my'     => 'Burmese',
		'ne'     => 'Nepali',
		'no'     => 'Norwegian',
		'ps'     => 'Pashto',
		'fa'     => 'Persian',
		'pl'     => 'Polish',
		'pt'     => 'Portuguese',
		'ma'     => 'Punjabi',
		'ro'     => 'Romanian',
		'ru'     => 'Russian',
		'sm'     => 'Samoan',
		'gd'     => 'Scots',
		'sr'     => 'Serbian',
		'st'     => 'Sesotho',
		'sn'     => 'Shona',
		'sd'     => 'Sindhi',
		'si'     => 'Sinhala',
		'sk'     => 'Slovak',
		'sl'     => 'Slovenian',
		'so'     => 'Somali',
		'es'     => 'Spanish',
		'su'     => 'Sundanese',
		'sw'     => 'Swahili',
		'sv'     => 'Swedish',
		'tg'     => 'Tajik',
		'ta'     => 'Tamil',
		'te'     => 'Telugu',
		'th'     => 'Thai',
		'tr'     => 'Turkish',
		'uk'     => 'Ukrainian',
		'ur'     => 'Urdu',
		'uz'     => 'Uzbek',
		'vi'     => 'Vietnamese',
		'cy'     => 'Welsh',
		'xh'     => 'Xhosa',
		'yi'     => 'Yiddish',
		'yo'     => 'Yoruba',
		'zu'     => 'Zulu',
    );

	protected static $languages = array();

	/*
	 * Possible values  $english_or_native_name: 'english_name', 'native_name'
	 */

    public static function get_languages( $english_or_native_name = 'english_name' ){
		if ( empty( self::$languages[$english_or_native_name] ) ) {
			require_once( ABSPATH . 'wp-admin/includes/translation-install.php' );
			$wp_languages = wp_get_available_translations();
			$default = array( 'language'	=> 'en_US', 'english_name'=> 'English (United States)', 'native_name' => 'English (United States)' );
			self::$languages[$english_or_native_name] = array( $default['language'] => $default[$english_or_native_name] );
			foreach ( $wp_languages as $wp_language ) {
				self::$languages[$english_or_native_name][$wp_language['language']] = $wp_language[$english_or_native_name];
			}
		}

        return apply_filters( 'trp_languages', self::$languages[$english_or_native_name] );
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