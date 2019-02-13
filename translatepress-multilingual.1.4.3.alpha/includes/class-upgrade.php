<?php

/**
 * Class TRP_Upgrade
 *
 * When changing plugin version, do the necessary checks and database upgrades.
 */
class TRP_Upgrade {

	protected $settings;
	/* @var TRP_Query */
	protected $trp_query;

	/**
	 * TRP_Upgrade constructor.
	 *
	 * @param $settings
	 */
	public function __construct( $settings ){
		$this->settings = $settings;
	}

	/**
	 * Register Settings subpage for TranslatePress
	 */
	public function register_menu_page(){
		add_submenu_page( 'TRPHidden', 'TranslatePress Remove Duplicate Rows', 'TRPHidden', 'manage_options', 'trp_remove_duplicate_rows', array($this, 'trp_remove_duplicate_rows') );
		add_submenu_page( 'TRPHidden', 'TranslatePress Update Database', 'TRPHidden', 'manage_options', 'trp_update_database', array( $this, 'trp_full_trim_originals' ) );
	}

	/**
	 * When changing plugin version, call certain database upgrade functions.
	 *
	 */
	public function check_for_necessary_updates(){
		$trp = TRP_Translate_Press::get_trp_instance();
		if( ! $this->trp_query ) {
			$this->trp_query = $trp->get_component( 'query' );
		}
		$stored_database_version = get_option('trp_plugin_version');
		if( empty($stored_database_version) || version_compare( TRP_PLUGIN_VERSION, $stored_database_version, '>' ) ){
			$this->check_if_gettext_tables_exist();
			$this->trp_query->check_for_block_type_column();
		}
		if( !empty( $stored_database_version ) ) {
			if ( version_compare( '1.4.0', $stored_database_version, '>' ) ) {
				update_option( 'trp_updated_database_full_trim_originals_140', 'no' );
			}
		}

		update_option( 'trp_plugin_version', TRP_PLUGIN_VERSION );
	}

	/**
	 * Iterates over all languages to call gettext table checking
	 */
	public function check_if_gettext_tables_exist(){
		$trp = TRP_Translate_Press::get_trp_instance();
		if( ! $this->trp_query ) {
			$this->trp_query = $trp->get_component( 'query' );
		}
		if( !empty( $this->settings['translation-languages'] ) ){
			foreach( $this->settings['translation-languages'] as $site_language_code ){
				$this->trp_query->check_gettext_table($site_language_code);
			}
		}
	}


	/**
	 * Show admin notice about updating database
	 */
	public function show_admin_notice(){
		$option = get_option( 'trp_updated_database_full_trim_originals_140', 'is not set' );
		// show admin notice if option is set to false AND we are not on the update database page
		if ( $option === 'no' && !( isset( $_GET[ 'page'] ) && $_GET['page'] == 'trp_update_database' ) ){
			add_action( 'admin_notices', array( $this, 'admin_notice_update_database_full_trim' ) );
		}
	}

	/**
	 * Print admin notice message
	 */
	public function admin_notice_update_database_full_trim() {
		$url = add_query_arg( array(
			'page'                      => 'trp_update_database',
		), site_url('wp-admin/admin.php') );

		// maybe change notice color to blue #28B1FF
		$html = '<div id="message" class="updated">';
		$html .= '<p><strong>' . esc_html__( 'TranslatePress data update', 'translatepress-multilingual' ) . '</strong> &#8211; ' . esc_html__( 'We need to update your translations database to the latest version.', 'translatepress-multilingual' ) . '</p>';
		$html .= '<p class="submit"><a href="' . esc_url( $url ) . '" class="button-primary">' . esc_html__( 'Run the updater', 'translatepress-multilingual' ) . '</a></p>';
		$html .= '</div>';
		echo $html;
	}

	/**
	 * Remove duplicate rows from DB for trp_dictionary tables.
	 * Removes untranslated strings if there is a translated version.
	 *
	 * Iterates over languages. Each language is iterated in batches of 10 000
	 *
	 * Not accessible from anywhere else
	 * http://example.com/wp-admin/admin.php?page=trp_remove_duplicate_rows
	 */
	public function trp_full_trim_originals(){
		$start_time = microtime(true);
		if ( ! current_user_can( 'manage_options' ) ){
			return;
		}
		// prepare page structure
		require_once TRP_PLUGIN_DIR . 'partials/trp-update-database.php';

		if ( empty( $_GET['trp_updb_lang'] ) ){
			// iteration not started
			return;
		}
		if ( $_GET['trp_updb_lang'] === 'done' ){
			// iteration finished
			$this->print_queried_tables();
			echo __( '<p><strong>Successfully updated database!</strong></p>', 'translatepress-multilingual' ) . '<br><a href="' . site_url( 'wp-admin/options-general.php?page=translate-press' ) . '"> <input type="button" value="' . __( 'Back to TranslatePress Settings page', 'translatepress-multilingual' ) . '" class="button-primary"></a>';
			return;
		}
		$nonce = wp_verify_nonce( $_GET['trp_updb_nonce'], 'tpupdatedatabase' );
		if ( $nonce === false ){
			echo __('Invalid nonce.', 'translatepress-multilingual' ) . '<br><br><a href="' . site_url('wp-admin/options-general.php?page=translate-press') . '"> <input type="button" value="' . __('Back to TranslatePress Settings page', 'translatepress-multilingual' ) . '" class="button-primary"></a>';
			return;
		}

		$next_get_batch = 0;
		$batch_size = apply_filters( 'trp_updb_batch_size', 200 );
		if ( !empty( $_GET['trp_updb_batch_size'] )  && (int) $_GET['trp_updb_batch'] >= 0 ){
			$batch_size = (int) $_GET['trp_updb_batch_size'];
		}
		if ( in_array( $_GET['trp_updb_lang'], $this->settings['translation-languages'] ) ) {
			// language code found in array
			$language_code = $_GET['trp_updb_lang'];
			// skip default language since it doesn't have a table
			$finished_with_language = true;
			if ( $language_code != $this->settings['default-language'] ) {
				if ( ! $this->trp_query ) {
					$trp = TRP_Translate_Press::get_trp_instance();
					/* @var TRP_Query */
					$this->trp_query = $trp->get_component( 'query' );
				}

				if ( !empty( $_GET['trp_updb_batch'] ) && (int) $_GET['trp_updb_batch'] > 0 ) {
					$get_batch = (int)$_GET['trp_updb_batch'];
				}else{
					$get_batch = 0;
				}

				$this->print_queried_tables( $language_code );

				$start_time = microtime(true);
				$duration = 0;
				while( $duration < 2 ){
					$inferior_limit = $batch_size * $get_batch;
					$finished_with_language = $this->execute_full_trim( $language_code, $inferior_limit, $batch_size );
					if ( $finished_with_language ) {
						break;
					}else {
						$get_batch = $get_batch + 1;
					}
					$stop_time = microtime( true );
					$duration = $stop_time - $start_time;
				}
				if ( ! $finished_with_language ) {
					$next_get_batch = $get_batch + 1;
				}
			}

			if ( $finished_with_language ) {
				// finished with the current language
				$index = array_search( $language_code, $this->settings['translation-languages'] );
				if ( isset ( $this->settings['translation-languages'][ $index + 1 ] ) ) {
					// next language code in array
					$next_language = $this->settings['translation-languages'][ $index + 1 ];
				} else {
					// finish iteration due to completing all the translation languages
					$next_language = 'done';
					// this will stop showing the admin notice
					update_option( 'trp_updated_database_full_trim_originals_140', 'yes' );
				}
			}else{
				$next_language = $language_code;
			}
		}else{
			// finish iteration due to incorrect translation language
			$next_language = 'done';
		}

		// construct and redirect to next url
		$url = add_query_arg( array(
			'page'                      => 'trp_update_database',
			'trp_updb_lang'             => $next_language,
			'trp_updb_batch'            => $next_get_batch,
			'trp_updb_batch_size'       => $batch_size,
			'trp_updb_nonce'            => wp_create_nonce('tpupdatedatabase')
		), site_url('wp-admin/admin.php') );
		echo "<meta http-equiv='refresh' content='0; url={$url}' />";
		echo "<br> " . __( 'If the page does not redirect automatically', 'translatepress-multilingual' ) . " <a href='$url' >" . __( 'click here', 'translatepress-multilingual' ) . ".</a>";
		exit;
	}

	/**
	 * Prints all the tables in the translation languages array until the current language with Done.
	 *
	 * If current language is given, do not print Done for it.
	 *
	 * @param bool | string $current_language_code
	 */
	public function print_queried_tables( $current_language_code = false ){
		if ( ! $this->trp_query ) {
			$trp = TRP_Translate_Press::get_trp_instance();
			/* @var TRP_Query */
			$this->trp_query = $trp->get_component( 'query' );
		}
		foreach ( $this->settings['translation-languages'] as $language_code ){
			if ( $language_code == $this->settings['default-language'] ){
				continue;
			}
			$table_name = $this->trp_query->get_table_name( $language_code );
			$html = '<div>' . sprintf( __( 'Querying table <strong>%s</strong>... ', 'translatepress-multilingual' ), $table_name );
			if ( $language_code != $current_language_code ) {
				$html .= __( 'Done.', 'translatepress-multilingual' );
			}
			$html .= '</div>';
			echo $html;
			if ( $language_code == $current_language_code ) {
				break;
			}
		}
	}

	/**
	 * Get all originals from the table, trim them and update originals back into table
	 *
	 * @param string $language_code     Language code of the table
	 * @param int $inferior_limit       Omit first X rows
	 * @param int $batch_size           How many rows to query
	 *
	 * @return bool
	 */
	public function execute_full_trim( $language_code, $inferior_limit, $batch_size ){
		if ( ! $this->trp_query ) {
			$trp = TRP_Translate_Press::get_trp_instance();
			/* @var TRP_Query */
			$this->trp_query = $trp->get_component( 'query' );
		}
		$strings = $this->trp_query->get_rows_from_location( $language_code, $inferior_limit, $batch_size, array( 'id', 'original' ) );
		if ( count( $strings ) == 0 ) {
			return true;
		}
		foreach( $strings as $key => $string ){
			$strings[$key]['original'] = trp_full_trim( $strings[$key]['original'] );
		}

		// overwrite original only
		$this->trp_query->update_strings( $strings, $language_code, array( 'id', 'original' ), array( '%d', '%s' ) );
		return false;
	}

	/**
	 * Remove duplicate rows from DB for trp_dictionary tables.
	 * Removes untranslated strings if there is a translated version.
	 *
	 * Iterates over languages. Each language is iterated in batches of 10 000
	 *
	 * Not accessible from anywhere else
	 * http://example.com/wp-admin/admin.php?page=trp_remove_duplicate_rows
	 */
	public function trp_remove_duplicate_rows(){
		if ( ! current_user_can( 'manage_options' ) ){
			return;
		}
		// prepare page structure
		require_once TRP_PLUGIN_DIR . 'partials/trp-remove-duplicate-rows.php';

		if ( empty( $_GET['trp_rm_duplicates'] ) ){
			// iteration not started
			return;
		}
		if ( $_GET['trp_rm_duplicates'] === 'done' ){
			// iteration finished
			echo __('Done.', 'translatepress-multilingual' ) . '<br><br><a href="' . site_url('wp-admin/options-general.php?page=translate-press') . '"> <input type="button" value="' . __('Back to TranslatePress Settings page', 'translatepress-multilingual' ) . '" class="button-primary"></a>';
			return;
		}
		$nonce = wp_verify_nonce( $_GET['trp_rm_nonce'], 'tpremoveduplicaterows' );
		if ( $nonce === false ){
			echo __('Invalid nonce.', 'translatepress-multilingual' ) . '<br><br><a href="' . site_url('wp-admin/options-general.php?page=translate-press') . '"> <input type="button" value="' . __('Back to TranslatePress Settings page', 'translatepress-multilingual' ) . '" class="button-primary"></a>';
			return;
		}

		$next_get_batch = 1;
		$batch_size = apply_filters( 'trp_rm_duplicate_batch_size', 10000 );
		if ( !empty( $_GET['trp_rm_batch_size'] )  && (int) $_GET['trp_rm_batch'] > 0 ){
			$batch_size = (int) $_GET['trp_rm_batch_size'];
		}
		if ( in_array( $_GET['trp_rm_duplicates'], $this->settings['translation-languages'] ) ) {
			// language code found in array
			$language_code = $_GET['trp_rm_duplicates'];
			// skip default language since it doesn't have a table
			if ( $language_code != $this->settings['default-language'] ) {
				if ( ! $this->trp_query ) {
					$trp = TRP_Translate_Press::get_trp_instance();
					/* @var TRP_Query */
					$this->trp_query = $trp->get_component( 'query' );
				}
				$table_name = $this->trp_query->get_table_name( $language_code );
				echo '<div>' . sprintf( __( 'Querying table <strong>%s</strong>', 'translatepress-multilingual' ), $table_name ) . '</div>';

				$last_id = $this->trp_query->get_last_id( $table_name );
				if ( !empty( $_GET['trp_rm_batch'] ) && (int) $_GET['trp_rm_batch'] > 0 ) {
					$get_batch = (int)$_GET['trp_rm_batch'];
				}else{
					$get_batch = 1;
				}
				$batch = $batch_size * $get_batch;

				/* Execute this query only for string with ID < $batch. This ensures that the query is fast.
				 * Deleting duplicate rows for the first 20k rows might take too long.
				 * As a solution we are deleting the duplicates of the first 10k rows ( 1 to 10 000),
				 * then delete duplicates of the first 20k rows( 1 to 20 000, not 10 000 to 20 000 because we there could still be duplicates).
				 * Same goes for higher numbers.
				 */
				$result1 = $this->trp_query->remove_duplicate_rows_in_dictionary_table( $language_code, $batch );
				$result2 = 0;
				if ( $batch > $last_id ){
					// execute this query only when we do not have any more duplicate rows
					$result2 = $this->trp_query->remove_untranslated_strings_if_translation_available( $language_code );
				}else{
					$next_get_batch = $get_batch + 1;
				}

				if ( ( $result1 === false ) || ( $result2 === false ) ) {
					// if query outputted error do not continue iteration
					return;
				}else{
					$result = $result1 + $result2;
					echo '<div>' . sprintf( __( '%s duplicates removed', 'translatepress-multilingual' ), $result ) . '</div>';
				}
			}
			if ( $next_get_batch == 1 ) {
				// finished with the current language
				$index = array_search( $language_code, $this->settings['translation-languages'] );
				if ( isset ( $this->settings['translation-languages'][ $index + 1 ] ) ) {
					// next language code in array
					$next_language = $this->settings['translation-languages'][ $index + 1 ];
				} else {
					// finish iteration due to completing all the translation languages
					$next_language = 'done';
				}
			}else{
				$next_language = $language_code;
			}
		}else{
			// finish iteration due to incorrect translation language
			$next_language = 'done';
		}

		// construct and redirect to next url
		$url = add_query_arg( array(
			'page'                      => 'trp_remove_duplicate_rows',
			'trp_rm_duplicates'         => $next_language,
			'trp_rm_batch'              => $next_get_batch,
			'trp_rm_batch_size'         => $batch_size,
			'trp_rm_nonce'              => wp_create_nonce('tpremoveduplicaterows')
		), site_url('wp-admin/admin.php') );
		echo "<meta http-equiv='refresh' content='0; url={$url}' />";
		echo "<br> " . __( 'If the page does not redirect automatically', 'translatepress-multilingual' ) . " <a href='$url' >" . __( 'click here', 'translatepress-multilingual' ) . ".</a>";
		exit;
	}
}
