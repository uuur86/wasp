<?php
/**
 * Test Class: BaseTestCase
 * Summary: Unit tests for BaseTestCase class.
 *
 * @package wasp
 */

namespace WaspCreators\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;

/**
 * Summary of BaseTestCase
 */
class BaseTestCase extends TestCase {

	/**
	 * Summary of setUp
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$_GET     = [];
		$_POST    = [];
		$_REQUEST = [];

		// Mock common translation functions.
		Monkey\Functions\when( '__' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_html__' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_attr__' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_html' )->returnArg( 1 );
		Monkey\Functions\when( 'esc_attr' )->returnArg( 1 );
		Monkey\Functions\when( 'sanitize_key' )->returnArg( 1 );
		Monkey\Functions\when( 'wp_unslash' )->returnArg( 1 );
		Monkey\Functions\when( 'wp_kses' )->returnArg( 1 );
	}

	/**
	 * Summary of tearDown
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
