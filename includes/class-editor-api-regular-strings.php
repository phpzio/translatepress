<?php

class TRP_Editor_Api_Regular_Strings {

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
	 * Returns translations based on original strings and ids.
	 *
	 * Hooked to wp_ajax_trp_get_translations_regular
	 *       and wp_ajax_nopriv_trp_get_translations_regular.
	 */
	public function get_translations() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			check_ajax_referer( 'get_translations', 'security' );
			if ( isset( $_POST['action'] ) && $_POST['action'] === 'trp_get_translations_regular' && !empty( $_POST['language'] ) && in_array( $_POST['language'], $this->settings['translation-languages'] ) ) {
				$originals = (empty($_POST['originals']) )? array() : json_decode(stripslashes($_POST['originals']));
				$ids = (empty($_POST['string_ids']) )? array() : json_decode(stripslashes($_POST['string_ids']));
				if ( is_array( $ids ) || is_array( $originals) ) {
					$trp = TRP_Translate_Press::get_trp_instance();
					if (!$this->trp_query) {
						$this->trp_query = $trp->get_component('query');
					}
					if (!$this->translation_manager) {
						$this->translation_manager = $trp->get_component('translation_manager');
					}
					$block_type = $this->trp_query->get_constant_block_type_regular_string();
					$dictionaries = $this->get_translation_for_strings( $ids, $originals, $block_type );

					$localized_text = $this->translation_manager->string_groups();
					$string_group = __('Others', 'translatepress-multilingual'); // this type is not registered in the string types because it will be overwritten by the content in data-trp-node-type
					if ( isset( $_POST['dynamic_strings'] ) && $_POST['dynamic_strings'] === 'true'  ){
						$string_group = $localized_text['dynamicstrings'];
					}
					$dictionary_by_original = trp_sort_dictionary_by_original( $dictionaries, 'regular', $string_group, $_POST['language'] );

					echo trp_safe_json_encode( $dictionary_by_original );
				}
			}
		}

		die();
	}
	/**
	 * Return dictionary with translated strings.
	 *
	 * @param $strings
	 * @param null $block_type
	 *
	 * @return array
	 */
	protected function get_translation_for_strings( $ids, $originals, $block_type = null ){
		$id_array = array();
		$original_array = array();
		$dictionaries = array();
		$slug_info = false;
		//			if ( isset( $string->slug ) && $string->slug === true ){
//				$slug_info = array(
//					'post_id'   => (int)$string->slug_post_id,
//					'id'        => (int)$string->id,
//					'original'  => sanitize_text_field( $string->original ) );
//				continue;
//			}
		foreach ( $ids as $id ) {
			if ( isset( $id ) && is_numeric( $id ) ) {
				$id_array[] = (int) $id;
			}
		}
		foreach( $originals as $original ){
			if ( isset( $original ) ) {
				$original_array[] = trp_full_trim( trp_sanitize_string( $original ) );
			}
		}

		$current_language = sanitize_text_field( $_POST['language'] );

		$trp = TRP_Translate_Press::get_trp_instance();
		if ( ! $this->trp_query ) {
			$this->trp_query = $trp->get_component( 'query' );
		}
		if ( ! $this->slug_manager ) {
			$this->slug_manager = $trp->get_component('slug_manager');
		}
		if ( ! $this->translation_render ) {
			$this->translation_render = $trp->get_component('translation_render');
		}

		// necessary in order to obtain all the original strings
		if ( $this->settings['default-language'] != $current_language ) {
			if ( !empty ( $original_array ) && current_user_can ( apply_filters( 'trp_translating_capability', 'manage_options' ) ) ) {
				$this->translation_render->process_strings($original_array, $current_language, $block_type);
			}
			$dictionaries[$current_language] = $this->trp_query->get_string_rows( $id_array, $original_array, $current_language );
			if ( $slug_info !== false ) {
				$dictionaries[$current_language][$slug_info['id']] = array(
					'id'         => $slug_info['id'],
					'original'   => $slug_info['original'],
					'translated' => apply_filters( 'trp_translate_slug', $slug_info['original'], $slug_info['post_id'], $current_language ),
				);
			}

		}else{
			$dictionaries[$current_language] = array();
		}

		if ( isset( $_POST['all_languages'] ) && $_POST['all_languages'] === 'true' ) {
			foreach ($this->settings['translation-languages'] as $language) {
				if ($language == $this->settings['default-language']) {
					$dictionaries[$language]['default-language'] = true;
					continue;
				}

				if ($language == $current_language) {
					continue;
				}
				if (empty($original_strings)) {
					$original_strings = $this->extract_original_strings($dictionaries[$current_language], $original_array, $id_array);
				}
				if (current_user_can(apply_filters( 'trp_translating_capability', 'manage_options' ))) {
					$this->translation_render->process_strings($original_strings, $language, $block_type);
				}
				$dictionaries[$language] = $this->trp_query->get_string_rows(array(), $original_strings, $language);
				if ( $slug_info !== false ) {
					$dictionaries[$language][0] = array(
						'id'         => 0,
						'original'   => $slug_info['original'],
						'translated' => apply_filters( 'trp_translate_slug', $slug_info['original'], $slug_info['post_id'], $language ),
					);
				}
			}
		}

		return $dictionaries;
	}

	/**
	 * Return array of original strings given their db ids.
	 *
	 * @param array $strings            Strings object to extract original
	 * @param array $original_array     Original strings array to append to.
	 * @param array $id_array           Id array to extract.
	 * @return array                    Original strings array + Extracted strings from ids.
	 */
	protected function extract_original_strings( $strings, $original_array, $id_array ){
		if ( count( $strings ) > 0 ) {
			foreach ($id_array as $id) {
				if ( is_object( $strings[$id] ) ){
					$original_array[] = $strings[ $id ]->original;
				}
			}
		}
		return array_values( $original_array );
	}
}