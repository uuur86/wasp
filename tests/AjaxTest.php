<?php
/**
 * Test Class: AjaxTest
 * Summary: Unit tests for Ajax class.
 *
 * @package wasp
 */

namespace WaspCreators\Tests;

use WaspCreators\Ajax;
use Brain\Monkey;

/**
 * Summary of AjaxTest
 */
class AjaxTest extends BaseTestCase {

	/**
	 * Summary of test_constructor_hooks
	 *
	 * @return void
	 */
	public function test_constructor_hooks() {

		Monkey\Functions\expect( 'add_action' )
			->once()
			->with( 'wp_ajax_wasp_ajax_loader_action', \Mockery::type( 'array' ) );

		$ajax = new Ajax( 'my-domain' );

		$this->assertInstanceOf( Ajax::class, $ajax );

	}

	/**
	 * Summary of test_load_handler
	 *
	 * @return void
	 */
	public function test_load_handler() {

		$ajax = new Ajax( 'my-domain' );

		Monkey\Functions\expect( 'add_action' )
			->once()
			->with( 'admin_enqueue_scripts', \Mockery::type( 'array' ) );

		$ajax->load_handler( [ 'my_load' ] );

		$this->assertTrue( true );

	}

}
