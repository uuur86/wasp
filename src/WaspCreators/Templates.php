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
	 * Template path set with set(). Used when a field does not name its own theme.
	 *
	 * @var string|null
	 */
	protected static $path = null;

	/**
	 * Loaded template sets, keyed by resolved file path.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	protected static $sets = [];



	/**
	 * Sets the global template path.
	 *
	 * Kept for backward compatibility. Prefer Wasp::set_theme(), which applies the
	 * theme only to that form, so two forms on the same request can use different
	 * themes.
	 *
	 * @param string $path Template file path (absolute, or relative to the folder that contains vendor/).
	 * @return bool Always returns false.
	 */
	public static function set( $path = null ) {
		if ( ! empty( $path ) ) {
			self::$path = $path;
		}

		return false;
	}



	/**
	 * Resolves a theme path to a template file.
	 *
	 * Accepted forms:
	 * - null: the bundled default theme.
	 * - An absolute path to a "*.wasp.php" file, with or without the ".wasp.php" suffix.
	 * - Legacy (before 3.1.0): a path relative to the folder that contains vendor/,
	 *   without the ".wasp.php" suffix.
	 *
	 * @since 3.1.0
	 *
	 * @param string|null $path Theme path.
	 * @return string|false Resolved file path, or false if it does not exist.
	 */
	public static function resolve( $path = null ) {
		if ( empty( $path ) ) {
			$file = dirname( __DIR__, 2 ) . '/templates/default.wasp.php';

			return is_file( $file ) ? $file : false;
		}

		$path = (string) $path;
		$file = self::with_suffix( $path );

		// Absolute path.
		if ( is_file( $file ) ) {
			$real = realpath( $file );

			return ( false !== $real ) ? $real : $file;
		}

		// Legacy relative path. Strip traversal segments before resolving.
		$relative = str_replace( [ '../', '..\\' ], '', $path );
		$file     = self::with_suffix( dirname( __DIR__, 4 ) . '/' . ltrim( $relative, '/\\' ) );
		$file     = strtr( $file, [ '/' => DIRECTORY_SEPARATOR ] );

		return is_file( $file ) ? $file : false;
	}



	/**
	 * Appends the ".wasp.php" suffix when it is missing.
	 *
	 * @param string $path File path.
	 * @return string
	 */
	protected static function with_suffix( $path ) {
		if ( substr( $path, -9 ) === '.wasp.php' ) {
			return $path;
		}

		return $path . '.wasp.php';
	}



	/**
	 * Loads a template set once per request.
	 *
	 * @param string|false $file Resolved template file.
	 * @return array
	 */
	protected static function load( $file ) {
		if ( empty( $file ) ) {
			return [];
		}

		if ( ! array_key_exists( $file, self::$sets ) ) {
			$set                 = include $file;
			self::$sets[ $file ] = is_array( $set ) ? $set : [];
		}

		return self::$sets[ $file ];
	}



	/**
	 * Gets a template by type.
	 *
	 * A custom theme only needs to define the templates it changes: every type it
	 * leaves out falls back to the bundled default theme.
	 *
	 * @param string      $type  Template type, e.g. "Text" or "Radio".
	 * @param string|null $theme Optional theme path for this field. Defaults to the path set with set().
	 * @return object|bool Template object or false if not found.
	 */
	public static function get( $type, $theme = null ) {
		if ( empty( $theme ) ) {
			$theme = self::$path;
		}

		if ( ! empty( $theme ) ) {
			$custom = self::load( self::resolve( $theme ) );

			if ( isset( $custom[ $type ] ) && is_array( $custom[ $type ] ) ) {
				return (object) $custom[ $type ];
			}
		}

		$default = self::load( self::resolve( null ) );

		if ( isset( $default[ $type ] ) && is_array( $default[ $type ] ) ) {
			return (object) $default[ $type ];
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
