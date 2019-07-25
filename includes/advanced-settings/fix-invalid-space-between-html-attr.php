<?php

add_filter( 'trp_register_advanced_settings', 'trp_register_fix_invalid_space_between_html_attr', 40 );
function trp_register_fix_invalid_space_between_html_attr( $settings_array ){
	$settings_array[] = array(
		'name'          => 'fix_invalid_space_between_html_attr',
		'type'          => 'checkbox',
		'label'         => esc_html__( 'Fix spaces between HTML attributes', 'translatepress-multilingual' ),
		'description'   => wp_kses( __( 'Fixes attributes without spaces between them because they are not valid HTML.<br> May help fix missing or broken content from the HTML on translated pages.', 'translatepress-multilingual' ), array( 'br' => array()) ),
	);
	return $settings_array;
}

add_filter('trp_before_translate_content', 'trp_fix_html_attributes_without_spaces');
function trp_fix_html_attributes_without_spaces($output){

	$option = get_option( 'trp_advanced_settings', true );
	if ( isset( $option['fix_invalid_space_between_html_attr'] ) && $option['fix_invalid_space_between_html_attr'] === 'yes' ){
		$size = strlen($output);
		$pos = 0;
		$pos_c = 0;

		while ($pos_c < $size){

			$len = strcspn($output, '<' , $pos);
			$pos += $len;

			$len_c = strcspn($output, '>' , $pos);
			$pos_c += $len_c;

			$even_detection_apostrophe = 0;
			$even_detection_quote = 0;
			for ($i = $pos; $i < $pos_c; $i++){
				$char = $output[$i]; //next
				if ($char == '"' ){
					$even_detection_quote ++;
					if ( $even_detection_quote % 2 == 0){
						$output = substr_replace( $output, ' ', $i+1, 0 );
					}
				}

				if ($char == "'" ){
					$even_detection_apostrophe ++;
					if ( $even_detection_apostrophe % 2 == 0){
						$output = substr_replace( $output, ' ', $i+1, 0 );
					}
				}
			}

			$pos_c ++;
			$pos = $pos_c;
		}
	}

	return $output;
}