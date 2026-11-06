<?php
/**
 * PHPUnit Bootstrap
 */

// Wasp files return early unless they run inside WordPress.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
