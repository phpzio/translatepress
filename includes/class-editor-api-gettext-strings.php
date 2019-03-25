<?php

class TRP_Editor_Api_Gettext_Strings {

	/* @var TRP_Query */
	protected $trp_query;
	/* @var TRP_SP_Slug_Manager*/
	protected $slug_manager;
	/* @var TRP_Translation_Render */
	protected $translation_render;
	/* @var TRP_Translation_Manager */
	protected $translation_manager;

	/**
	 * TRP_Translation_Manager constructor.
	 *
	 * @param array $settings       Settings option.
	 */
	public function __construct( $settings ){
		$this->settings = $settings;
	}

	/**
	 * Hooked to wp_ajax_trp_get_translations_gettext
	 */
	public function gettext_get_translations(){
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if (isset($_POST['action']) && $_POST['action'] === 'trp_get_translations_gettext' && !empty($_POST['string_ids']) && !empty($_POST['language']) && in_array($_POST['language'], $this->settings['translation-languages'])) {
				check_ajax_referer( 'gettext_get_translations', 'security' );
				if (!empty($_POST['string_ids']))
					$gettext_string_ids = json_decode(stripslashes($_POST['string_ids']));
				else
					$gettext_string_ids = array();

				$current_language = sanitize_text_field( $_POST['language'] );
				$dictionaries = array();

				if ( is_array( $gettext_string_ids ) ) {

					$trp = TRP_Translate_Press::get_trp_instance();
					if ( ! $this->trp_query ) {
						$this->trp_query = $trp->get_component( 'query' );
					}
					if (!$this->translation_manager) {
						$this->translation_manager = $trp->get_component('translation_manager');
					}

					/* build the current language dictionary */
					$dictionaries[$current_language] = $this->trp_query->get_gettext_string_rows_by_ids( $gettext_string_ids, $current_language );

					/* build the other languages dictionaries */

					$original_strings = array();
					$original_string_details = array();
					if( !empty( $dictionaries[$current_language] ) ){
						foreach( $dictionaries[$current_language] as $current_language_string ){
							$original_strings[] = $current_language_string['original'];
							$original_string_details[] = array( 'original' => $current_language_string['original'], 'domain' => $current_language_string['domain'] );
						}
					}

					foreach ($this->settings['translation-languages'] as $language) {
						if ($language == $current_language) {
							continue;
						}

						$lang_original_string_details = $original_string_details;
						if( !empty( $original_strings ) && !empty( $lang_original_string_details ) ){
							$dictionaries[$language] = $this->trp_query->get_gettext_string_rows_by_original( $original_strings, $language );
							if( empty( $dictionaries[$language] ) )
								$dictionaries[$language]  = array();

							$search_strings_array = array();

							foreach( $dictionaries[$language] as $lang_string ){
								$search_strings_array[] = array( 'original' => $lang_string['original'], 'domain' => $lang_string['domain']  );
							}

							if( !empty( $search_strings_array ) ){
								foreach( $search_strings_array as $search_key => $search_string ){
									if( in_array( $search_string, $lang_original_string_details ) ) {
										$remove_original_key = array_search($search_string, $lang_original_string_details );
										unset($lang_original_string_details[$remove_original_key]);
									}
									else{
										unset($dictionaries[$language][$search_key]);
									}
								}
							}

							/* add here in the db the strings that are not there and after that add them in the dictionary */
							switch_to_locale( $language );
							if( !empty( $lang_original_string_details ) ){
								foreach( $lang_original_string_details as $lang_original_string_detail ){

									$translations = get_translations_for_domain( $lang_original_string_detail['domain'] );
									$translated  = $translations->translate( $lang_original_string_detail['original'] );

									$db_id = $this->trp_query->insert_gettext_strings( array( array('original' => $lang_original_string_detail['original'], 'translated' => $translated, 'domain' => $lang_original_string_detail['domain']) ), $language );
									$dictionaries[$language][] = array('id' => $db_id, 'original' => $lang_original_string_detail['original'], 'translated' => ( $translated != $lang_original_string_detail['original'] ) ? $translated : '', 'domain' => $lang_original_string_detail['domain']);
								}
							}
							restore_current_locale();

							$dictionaries[$language] = array_values($dictionaries[$language]);
						}
					}

				}

				/* html entity decode the strings so we display them properly in the textareas  */
				foreach( $dictionaries as $lang => $dictionary ){
					foreach( $dictionary as $key => $string ){
						$string = array_map('html_entity_decode', $string );
						$dictionaries[$lang][$key] = (object)$string;
					}
				}
				$localized_text = $this->translation_manager->string_groups();
				$dictionary_by_original = trp_sort_dictionary_by_original( $dictionaries, 'gettext', $localized_text['gettextstrings'], $_POST['language'] );
				die( trp_safe_json_encode( $dictionary_by_original ) );

			}
		}
	}

	/*
	 * Save gettext translations
	 */
	public function gettext_save_translations(){
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX && current_user_can( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ) {
			if (isset($_POST['action']) && $_POST['action'] === 'trp_save_translations_gettext' && !empty($_POST['strings'])) {
				check_ajax_referer( 'gettext_save_translations', 'security' );
				$strings = json_decode(stripslashes($_POST['strings']));
				$update_strings = array();
				foreach ( $strings as $language => $language_strings ) {
					if ( in_array( $language, $this->settings['translation-languages'] ) ) {
						$update_strings[ $language ] = array();
						foreach( $language_strings as $string ) {
							if ( isset( $string->id ) && is_numeric( $string->id ) ) {
								array_push($update_strings[ $language ], array(
									'id' => (int)$string->id,
									'translated' => trp_sanitize_string( $string->translated ),
									'domain' => sanitize_text_field( $string->domain ),
									'status' => (int)$string->status
								));
							}
						}
					}
				}

				if ( ! $this->trp_query ) {
					$trp = TRP_Translate_Press::get_trp_instance();
					$this->trp_query = $trp->get_component( 'query' );
				}

				foreach( $update_strings as $language => $update_string_array ) {
					$this->trp_query->update_gettext_strings( $update_string_array, $language, array('id','translated', 'status') );
				}
			}
		}
		echo trp_safe_json_encode( array() );
		wp_die();
	}
}
