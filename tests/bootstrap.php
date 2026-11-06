<?php
/**
 * PHPUnit bootstrap.
 *
 * @package wasp
 */

// Wasp files return early unless they run inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant, defined for tests only.
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
