<?php
/**
 * WaspCreators\Templates class.
 *
 * @package wasp
 */

namespace WaspCreators;

/**
 * Class Templates
 */
class Templates {

	/**
	 * Template file path.
	 *
	 * @var string
	 */
	protected static $path = null;

	/**
	 * Template array.
	 *
	 * @var array
	 */
	protected static $templates = null;



	/**
	 * Set template file path.
	 *
	 * @param string $path Template file path.
	 * @return bool Always returns false.
	 */
	public static function set( $path = null ) {
		if ( ! empty( $path ) ) {
			self::$path = $path;
		}

		return false;
	}



	/**
	 * Get template by type.
	 *
	 * @param string $type Template type.
	 * @return object|bool Template object or false if not found.
	 */
	public static function get( $type ) {
		if ( empty( self::$templates ) ) {
			$ds = DIRECTORY_SEPARATOR;

			if ( empty( self::$path ) ) {
				$temp_file = dirname( __DIR__, 2 ) . '/templates/default';
			} else {
				$temp_file = dirname( __DIR__, 4 ) . self::$path;
			}

			// Windows slash compatibility and security.
			$temp_file = strtr(
				$temp_file,
				[
					'/'   => $ds,
					'../' => '',
				]
			);
			$temp_file .= '.wasp.php';

			if ( ! file_exists( $temp_file ) ) {
				return false;
			}

			self::$templates = include $temp_file;
		}

		if ( isset( self::$templates[ $type ] ) ) {
			return (object) self::$templates[ $type ];
		}

		return false;
	}



	/**
	 * Appends the parameters to HTML Template.
	 *
	 * @param string $html HTML template code of field.
	 * @param array  $args parameters.
	 * @return string|bool
	 */
	public static function append( $html, $args ) {
		if ( ! $args || ! is_array( $args ) ) {
			return null;
		}
		preg_match_all( '#\[[a-z0-9_]+\]#siu', $html, $attr );

		$excludes   = [ 'options', 'text', 'checked', 'selected', 'disabled', 'presets' ];
		$attr       = $attr[0];
		$attr       = array_combine( $attr, array_fill( 0, count( $attr ), '' ) );
		$new_args   = $attr;

		foreach ( $args as $key => $val ) {
			$keyname = '[' . $key . ']';

			if ( ! isset( $new_args[ $keyname ] ) ) {
				continue;
			}

			if ( ! in_array( $key, $excludes, true ) && ! is_array( $val ) ) {
				$val = \esc_attr( $val );
			} elseif ( 'text' === $key ) {
				$val = \esc_html( $val );
			}

			$new_args[ $keyname ] = $val;
		}

		// Special handling for value - encode newlines for HTML attribute compatibility.
		// Using "|" as separator instead of "&#10;" to avoid "&" being encoded by esc_attr().
		if ( isset( $new_args['[value]'] ) && ! empty( $new_args['[value]'] ) ) {
			$new_args['[value]'] = str_replace( [ "\r\n", "\n", "\r" ], '|', $new_args['[value]'] );
		}

		return strtr( $html, $new_args );
	}

	/**
	 * Filter function for taginput value - restore newlines from pipe separator.
	 *
	 * @param string $value Value to filter.
	 * @return string Filtered value.
	 */
	public static function taginput_value_filter( $value ) {
		return str_replace( '|', "\n", $value );
	}
}
