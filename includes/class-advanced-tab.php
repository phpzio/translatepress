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

	/*
	 * Advanced page content
	 */
	public function advanced_page_content(){
		require_once TRP_PLUGIN_DIR . 'partials/advanced-settings-page.php';
	}

	public function include_custom_codes(){
		$files = glob(TRP_PLUGIN_DIR . 'includes/advanced-settings/*.php' );

		foreach ($files as $file) {
			require($file);
		}
	}

	/*
	 * Hooked to trp_settings_navigation_tabs
	 */
	public function output_advanced_options(){
		$advanced_settings_array = apply_filters( 'trp_register_advanced_settings', array() );
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
			foreach ( $option[ $setting['name'] ][ $first_column ] as $index ) {
				$html .= "<tr>";
				foreach ( $setting['columns'] as $column => $column_name ) {
					$html .= "<td><textarea disabled value='" . $option[ $setting['name'] ][ $column ][ $index ] . "' name='trp_advanced_settings[" . $setting['name'] . "][" . $column . "][]'></textarea></td>";
				}
				$html .= "<td><span>" . __( 'Remove', 'translatepress-multilingual' ) . "</span></td>";
				$html .= "</tr>";
			}
		}

		// add new entry to list
		$html .= "<tr class='trp-add-list-entry'>";
		foreach( $setting['columns'] as $column => $column_name ) {
			$html .= "<td><textarea id='new_entry_" . $setting['name'] . "_" . $column . "' data-setting-name='" . $setting['name'] . "' data-column-name='" . $column . "'></textarea></td>";
		}
		$html .= "<td><input type='button' class='button-secondary trp-adst-button-add-new-item' value='" . __( 'Add', 'translatepress-multilingual' ) . "'></td>";
		$html .= "</tr></table>";

		$html .= "<p class='description'>
                        " . $setting['description'] . "
                    </p>
                </td>
            </tr>";
		return apply_filters( 'trp_advanced_setting_checkbox', $html );
	}

}