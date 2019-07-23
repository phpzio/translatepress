<?php

class TRP_Advanced_Tab {

	/*
	 * Add new tab to TP settings
	 *
	 * Hooked to trp_settings_tabs
	 */
	public function add_advanced_tab_to_settings( $tab_array ){
		$tab_array[] =  array(
			'name'  => __( 'Advanced', 'translatepress-multilingual' ),
			'url'   => admin_url( 'admin.php?page=trp_advanced_page' ),
			'page'  => 'trp_advanced_page'
		);
		return $tab_array;
	}

	/*
	 * Add submenu for advanced page tab
	 *
	 * Hooked to admin_menu
	 */
	public function add_submenu_page_advanced() {
		add_submenu_page( 'TRPHidden', 'TranslatePress Advanced Settings', 'TRPHidden', 'manage_options', 'trp_advanced_page', array(
			$this,
			'advanced_page_content'
		) );
	}

	/**
	 * Register setting
	 *
	 * Hooked to admin_init
	 */
	public function register_setting(){
		register_setting( 'trp_advanced_settings', 'trp_advanced_settings', array( $this, 'sanitize_settings' ) );
	}

	/**
	 * Output admin notices after saving settings.
	 */
	public function admin_notices(){
		settings_errors( 'trp_advanced_settings' );
	}

	/*
	 * Sanitize settings
	 */
	public function sanitize_settings( $submitted_settings ){
		$registered_settings = $this->get_settings();
		$settings = array();
		foreach ( $registered_settings as $registered_setting ){
			if ( isset( $submitted_settings[$registered_setting['name']] ) ){
				switch ($registered_setting['type'] ) {
					case 'checkbox': {
						$settings[ $registered_setting['name'] ] = ( $submitted_settings[ $registered_setting['name'] ] === 'yes' ) ? 'yes' : 'no';
						break;
					}
					case 'list': {
						$settings[ $registered_setting['name'] ] = array();
						foreach ( $registered_setting['columns'] as $column => $column_name ) {
							$one_column = $column;
							$settings[ $registered_setting['name'] ][ $column ] = array();
							foreach ( $submitted_settings[ $registered_setting['name'] ][ $column ] as $key => $value ) {
								$settings[ $registered_setting['name'] ][ $column ][] = sanitize_text_field( $value );
							}
						}

						// remove empty rows
						foreach ( $settings[ $registered_setting['name'] ][ $one_column ] as $key => $value ) {
							$is_empty = true;
							foreach ( $registered_setting['columns'] as $column => $column_name ) {
								if ( $settings[ $registered_setting['name'] ][$column][$key] != "" ) {
									$is_empty = false;
									break;
								}
							}
							if ( $is_empty ){
								foreach ( $registered_setting['columns'] as $column => $column_name ) {
									unset( $settings[ $registered_setting['name'] ][$column][$key] );
								}
							}
						}

						foreach ( $settings[ $registered_setting['name'] ] as $column => $value ) {
							$settings[ $registered_setting['name'] ][ $column ] = array_values( $settings[ $registered_setting['name'] ][ $column ] );
						}
						break;
					}
				}
			}
		}
		add_settings_error( 'trp_advanced_settings', 'settings_updated', __( 'Settings saved.' ), 'updated' );

		return apply_filters( 'trp_extra_sanitize_advanced_settings', $settings, $submitted_settings );
	}

	/*
	 * Advanced page content
	 */
	public function advanced_page_content(){
		require_once TRP_PLUGIN_DIR . 'partials/advanced-settings-page.php';
	}

	/*
	 * Require the custom codes from the specified folder
	 */
	public function include_custom_codes(){
		$paths = apply_filters( 'trp_custom_code_path_folder', array( TRP_PLUGIN_DIR . 'includes/advanced-settings/*.php' ) );

		foreach( $paths as $path ) {
			$path = glob( $path );
			foreach ( $path as $file ) {
				require( $file );
			}
		}
	}

	/*
	 * Get array of registered options from custom code to display in Advanced Settings page
	 */
	public function get_settings(){
		return apply_filters( 'trp_register_advanced_settings', array() );
	}

	/*
	 * Hooked to trp_settings_navigation_tabs
	 */
	public function output_advanced_options(){
		$advanced_settings_array = $this->get_settings();
		foreach( $advanced_settings_array as $setting ){
			if ( $setting['type'] === 'checkbox' ){
				echo $this->checkbox_setting( $setting );
			}
		}
		foreach( $advanced_settings_array as $setting ){
			if ( $setting['type'] === 'list' ){
				echo $this->add_to_list_setting( $setting );
			}
		}
	}

	/**
	 * Return HTML of a checkbox type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function checkbox_setting( $setting ){
		$option = get_option( 'trp_advanced_settings', true );
		$checked = ( isset( $option[ $setting['name'] ] ) && $option[ $setting['name'] ] === 'yes' ) ? 'checked' : '';
		$html = "
             <tr>
                <th scope='row'>" . $setting['label'] . "</th>
                <td>
	                <label>
	                    <input type='checkbox' id='" . $setting['name'] . "' name='trp_advanced_settings[" . $setting['name'] . "]' value='yes' " . $checked . ">
	                    " . __('Yes', 'translatepress-multilingual' ). "
			        </label>
                    <p class='description'>
                        " . $setting['description'] . "
                    </p>
                </td>
            </tr>";
		return apply_filters('trp_advanced_setting_checkbox', $html );
	}

	/**
	 * Return HTML of a checkbox type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function add_to_list_setting( $setting ){
		$option = get_option( 'trp_advanced_settings', true );
		$html = "
             <tr>
                <th scope='row'>" . $setting['label'] . "</th>
                <td>
	                <table class='trp-adst-list-option'>
						<thead>
							";
		foreach( $setting['columns'] as $key => $value ){
			$html .= '<th><strong>' . $value . '</strong></th>';
		}
		//"Remove" button
		$html .= "<th></th>";

		// list existing entries
		$html .= "		</thead>";

		$first_column = '';
		foreach( $setting['columns'] as $column => $column_name ) {
			$first_column = $column;
			break;
		}
		if ( isset( $option[ $setting['name'] ] ) && is_array( $option[ $setting['name'] ] ) ) {
			foreach ( $option[ $setting['name'] ][ $first_column ] as $index => $value ) {
				$html .= "<tr class='trp-list-entry'>";
				foreach ( $setting['columns'] as $column => $column_name ) {
					$html .= "<td><textarea name='trp_advanced_settings[" . $setting['name'] . "][" . $column . "][]'>" . $option[ $setting['name'] ][ $column ][ $index ] . "</textarea></td>";
				}
				$html .= "<td><span class='trp-adst-remove-element' data-confirm-message='" . __('Are you sure you want to remove this item?', 'translatepress-multilingual') . "'>" . __( 'Remove', 'translatepress-multilingual' ) . "</span></td>";
				$html .= "</tr>";
			}
		}

		// add new entry to list
		$html .= "<tr class='trp-add-list-entry trp-list-entry'>";
		foreach( $setting['columns'] as $column => $column_name ) {
			$html .= "<td><textarea id='new_entry_" . $setting['name'] . "_" . $column . "' data-name='trp_advanced_settings[" . $setting['name'] . "][" . $column . "][]' data-setting-name='" . $setting['name'] . "' data-column-name='" . $column . "'></textarea></td>";
		}
		$html .= "<td><input type='button' class='button-secondary trp-adst-button-add-new-item' value='" . __( 'Add', 'translatepress-multilingual' ) . "'><span class='trp-adst-remove-element' style='display: none;' data-confirm-message='" . __('Are you sure you want to remove this item?', 'translatepress-multilingual') . "'>" . __( 'Remove', 'translatepress-multilingual' ) . "</span></td>";
		$html .= "</tr></table>";

		$html .= "<p class='description'>
                        " . $setting['description'] . "
                    </p>
                </td>
            </tr>";
		return apply_filters( 'trp_advanced_setting_checkbox', $html );
	}

}