<?php

/**
 * @package wasp
 */

namespace WaspCreators;

class Templates {

	protected static $path = null;

	protected static $templates = null;



	public static function set( $path = null ) {

		if( !empty( $path ) ) {
			self::$path	= $path;
		}

		return false;
	}



	public static function get( $type ) {

		if( empty( self::$templates ) ) {
			$ds = DIRECTORY_SEPARATOR;

			if( empty( self::$path ) ) {
				$tempFile = dirname( __FILE__, 3 ) . '/templates/default';
			}
			else {
				$tempFile =  dirname( __FILE__, 5 ) . self::$path;
			}

			// windows slash compatibility and security
			$tempFile = strtr( $tempFile, [ '/' => $ds, '../' => '' ] );
			$tempFile .= '.wasp.php';

			if( !file_exists( $tempFile ) ) {
				return false;
			}

			self::$templates = include $tempFile;
		}

		if( isset( self::$templates[ $type ] ) ) {
			return (object)self::$templates[ $type ];
		}
	}



	public static function append( $html, $args ) {
		$excludes	= [ 'options', 'text', 'checked', 'selected' ];

		preg_match_all( '#\[[a-z0-9_]+\]#siu', $html, $attr );

		$attr		= $attr[ 0 ];
		$attr 		= array_combine( $attr, array_fill( 0, count( $attr ), '' ) );
		$new_args 	= $attr;

		foreach( $args as $key => $val ) {
			$keyname = '[' . $key . ']';

			if( !isset( $new_args[ $keyname ] ) ) continue;

			if( !in_array( $key, $excludes ) && !is_array( $val ) ) {
				$val = \esc_attr( $val );
			}

			$new_args[ $keyname ] = $val;
		}

		return strtr( $html, $new_args );
	}
}
