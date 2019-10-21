<?php

class TRP_Advanced_Tab {

    private $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

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

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $submitted_settings ){
		$registered_settings = $this->get_registered_advanced_settings();
		$prev_settings = get_option('trp_advanced_settings', array());

        $settings = array();
		foreach ( $registered_settings as $registered_setting ){

		    // checkboxes are not set so we're setting them up as false
            if( !isset( $submitted_settings[$registered_setting['name']] ) ){
                $submitted_settings[$registered_setting['name']] = false;
            }

			if ( isset( $submitted_settings[$registered_setting['name']] ) ){
				switch ($registered_setting['type'] ) {
					case 'checkbox': {
						$settings[ $registered_setting['name'] ] = ( $submitted_settings[ $registered_setting['name'] ] === 'yes' ) ? 'yes' : 'no';
						break;
					}
                    case 'select':
                    case 'input': {
                        $settings[ $registered_setting['name'] ] = sanitize_text_field($submitted_settings[ $registered_setting['name'] ]);
                        break;
                    }
                    case 'number': {
                        $settings[ $registered_setting['name'] ] = sanitize_text_field(intval($submitted_settings[ $registered_setting['name'] ] ) );
                        break;
                    }
                    case 'list': {
						$settings[ $registered_setting['name'] ] = array();
						foreach ( $registered_setting['columns'] as $column => $column_name ) {
							$one_column = $column;
							$settings[ $registered_setting['name'] ][ $column ] = array();
							if ( isset($submitted_settings[ $registered_setting['name'] ][ $column ] ) ) {
                                foreach ($submitted_settings[$registered_setting['name']][$column] as $key => $value) {
                                    $settings[$registered_setting['name']][$column][] = sanitize_text_field($value);
                                }
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
			} //endif

            // not all settings are updated by the user. Some are modified by the program and used as storage.
            // This is somewhat bad from a data model kind of way, but it's easy to pass the $settings variable around between classes.
            if( isset($registered_setting['data_model'])
                && $registered_setting['data_model'] == 'not_updatable_by_user'
                && isset($prev_settings[$registered_setting['name']])
            )
            {
                $settings[ $registered_setting['name'] ] = $prev_settings[$registered_setting['name']];
            }

		} //endforeach
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
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/disable-dynamic-translation.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/enable-auto-translate-slug.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/exclude-dynamic-selectors.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/exclude-gettext-strings.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/exclude-selectors.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/fix-broken-html.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/fix-invalid-space-between-html-attr.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/show-dynamic-content-before-translation.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/enable-hreflang-xdefault.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/strip-gettext-post-content.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/strip-gettext-post-meta.php');
        include_once(TRP_PLUGIN_DIR . 'includes/advanced-settings/exclude-words-from-auto-translate.php');
	}

	/*
	 * Get array of registered options from custom code to display in Advanced Settings page
	 */
	public function get_registered_advanced_settings(){
		return apply_filters( 'trp_register_advanced_settings', array() );
	}

	/*
	 * Hooked to trp_settings_navigation_tabs
	 */
	public function output_advanced_options(){
		$advanced_settings_array = $this->get_registered_advanced_settings();
		foreach( $advanced_settings_array as $setting ){
			switch( $setting['type'] ){
				case 'checkbox':
					echo $this->checkbox_setting( $setting );
					break;
                case 'radio':
                    echo $this->radio_setting( $setting );
                    break;
                case 'input':
                    echo $this->input_setting( $setting );
                    break;
                case 'number':
                    echo $this->input_setting( $setting, 'number' );
                    break;
                case 'select':
                    echo $this->select_setting( $setting );
                    break;
                case 'separator':
                    echo $this->separator_setting( $setting );
                    break;
				case 'list':
					echo $this->add_to_list_setting( $setting );
					break;
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
        $adv_option = $this->settings['trp_advanced_settings'];
		$checked = ( isset( $adv_option[ $setting['name'] ] ) && $adv_option[ $setting['name'] ] === 'yes' ) ? 'checked' : '';
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
     * Return HTML of a radio button type setting
     *
     * @param $setting
     *
     * @return 'string'
     */
    public function radio_setting( $setting ){
        $adv_option = $this->settings['trp_advanced_settings'];

        $html = "
             <tr>
                <th scope='row'>" . $setting['label'] . "</th>
                <td class='trp-adst-radio'>";

        foreach($setting[ 'options' ] as $key => $option ){
            $checked = ( isset( $adv_option[ $setting['name'] ] ) && $adv_option[ $setting['name'] ] === $option ) ? 'checked' : '';
            $setting_name  = $setting['name'];
            $label  = $setting[ 'labels' ][$key];
            $html .= "<label>
	                    <input type='radio' id='$setting_name' name='trp_advanced_settings[$setting_name]' value='$option' $checked>
	                    $label
			          </label>";
        }

        $html .= "  <p class='description'>
                        " . $setting['description'] . "
                    </p>
                </td>
            </tr>";
        return apply_filters('trp_advanced_setting_checkbox', $html );
    }

    /**
     * Return HTML of a input type setting
     *
     * @param array $setting
     * @param string $type
     *
     * @return 'string'
     */
    public function input_setting( $setting, $type = 'text'){
        $adv_option = $this->settings['trp_advanced_settings'];
        $default = ( isset( $setting['default'] )) ? $setting['default'] : '';
        $value = ( isset( $adv_option[ $setting['name'] ] ) ) ? $adv_option[ $setting['name'] ] : $default;
        $html = "
             <tr>
                <th scope='row'>" . $setting['label'] . "</th>
                <td>
	                <label>
	                    <input type='{$type}' id='{$setting['name']}' name='trp_advanced_settings[{$setting['name']}]' value='{$value}'>
			        </label>
                    <p class='description'>
                        {$setting['description']}
                    </p>
                </td>
            </tr>";
        return apply_filters('trp_advanced_setting_input', $html );
    }

    /**
     * Return HTML of a input type setting
     *
     * @param array $setting
     * @param string $type
     *
     * @return 'string'
     */
    public function select_setting( $setting ){
        $option = get_option( 'trp_advanced_settings', true );
        $default = ( isset( $setting['default'] )) ? $setting['default'] : '';
        $value = ( isset( $option[ $setting['name'] ] ) ) ? $option[ $setting['name'] ] : $default;

        $options = '';
        foreach ($setting['options'] as $lang => $label) {
            ($value == $lang) ? $selected = 'selected' : $selected = '' ;
            $options .= "<option value='{$lang}' $selected>{$label}</option>";
        }

        $html = "
             <tr>
                <th scope='row'>" . $setting['label'] . "</th>
                <td>
	                <label>
	                    <select id='{$setting['name']}' name='trp_advanced_settings[{$setting['name']}]' style='width: 200px;'>
	                        {$options}
	                    </select>
			        </label>
                    <p class='description'>
                        {$setting['description']}
                    </p>
                </td>
            </tr>";
        return apply_filters('trp_advanced_setting_select', $html );
    }

    /**
     * Return HTML of a separator type setting
     *
     * @param $setting
     *
     * @return 'string'
     */
    public function separator_setting( $setting ){
        $html = "
             <tr style='border-bottom: 1px solid #ccc;'>
                <th scope='row'></th>
                <td></td>
            </tr>";
        return apply_filters('trp_advanced_setting_separator', $html );
    }

	/**
	 * Return HTML of a checkbox type setting
	 *
	 * @param $setting
	 *
	 * @return 'string'
	 */
	public function add_to_list_setting( $setting ){
		$adv_option = $this->settings['trp_advanced_settings'];
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
		if ( isset( $adv_option[ $setting['name'] ] ) && is_array( $adv_option[ $setting['name'] ] ) ) {
			foreach ( $adv_option[ $setting['name'] ][ $first_column ] as $index => $value ) {
				$html .= "<tr class='trp-list-entry'>";
				foreach ( $setting['columns'] as $column => $column_name ) {
					$html .= "<td><textarea name='trp_advanced_settings[" . $setting['name'] . "][" . $column . "][]'>" . $adv_option[ $setting['name'] ][ $column ][ $index ] . "</textarea></td>";
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
		return apply_filters( 'trp_advanced_setting_list', $html );
	}
}
