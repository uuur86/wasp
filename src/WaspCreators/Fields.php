<?php

/**
 * @package wasp
 */

namespace WaspCreators;

class Fields {



	public static function filter_output( $html, $before = null, $after = null ) {
		$output = '';

		$default_attr	= [
 			'id'	=> [],
 			'name'	=> [],
 			'href'	=> [],
 			'src'	=> [],
 			'type'	=> [],
 			'class'	=> [],
 			'style'	=> [],
 		];

 		$allowed_tags	= [
 			'input'		=> $default_attr,
 			'select'	=> $default_attr,
 			'option'	=> $default_attr,
 			'radio'		=> $default_attr,
 			'a'			=> $default_attr,
 			'img'		=> $default_attr,
 			'br'		=> $default_attr,
 			'div'		=> $default_attr,
 			'hr'		=> $default_attr,
 		];

 		if ( ! empty( $before ) ) {
			$output .= \wp_kses( $before, $allowed_tags );
		}

 		$output .= $html;

 		if ( ! empty( $after ) ) {
			$output .= \wp_kses( $after, $allowed_tags );
		}

 		return $output;
	}



	public static function prepare( $args ) {

		if ( ! isset( $args[ 'type' ] ) ) return;

		if ( ! empty( $args[ 'not_found' ] ) ) {
			return $args[ 'not_found' ];
		}

		$type	= $args[ 'type' ];
		$before	= null;
		$after	= null;

		if ( isset( $args[ 'html_before' ] ) ) {
			$before = $args[ 'html_before' ];
		}

		if ( isset( $args[ 'html_after' ] ) ) {
			$after = $args[ 'html_after' ];
		}

		if ( empty( $type ) ) return;

		$type_arr = [
			'text_input'		=> 'Text',
			'multi_text_input'	=> 'Multitext',
			'file_input'		=> 'File',
		];

		if ( isset( $type_arr[ $type ] ) ) {
			$type = $type_arr[ $type ];
		} else {
			$type = ucfirst( $type );
		}

		$obj_name = "\\WaspCreators\\Fields\\" . $type;

		if ( ! method_exists( $obj_name, "get" ) ) {
			return;
		}

		// Flushes unnecessary array data from ram
		unset( $args[ 'html_before' ], $args[ 'html_after' ], $args[ 'type' ] );

		$html = call_user_func( $obj_name . "::get", $args );

		if ( ! $html ) return;

		return self::filter_output( $html, $before, $after );
	}



	/**
	 * @param array $args [ 'type', 'name', 'value', 'options', 'html_after',
	 *  'html_before', 'settings_name' ]
	 */
	public static function callback( $args ) {
		echo self::prepare( $args );
	}
}
