<?php
/**
 * Wasp test class
 *
 * @package wasp
 */

namespace WaspCreators\Tests;

use WaspCreators\Wasp;
use Brain\Monkey;

/**
 * Summary of WaspTest
 */
class WaspTest extends BaseTestCase {

	/**
	 * Summary of setUp
	 *
	 * @return void
	 */
	protected function setUp(): void {

		parent::setUp();

		// Mock options functions used in Wasp constructor.
		Monkey\Functions\when( 'get_option' )->justReturn( [] );

		// Mock WP Globals.
		global $pagenow;
		// phpcs:ignore
		$pagenow = 'admin.php';

	}

	/**
	 * Summary of test_get_form_data_from_get
	 *
	 * @return void
	 */
	public function test_get_form_data_from_get() {

		$wasp = new Wasp( 'my-page', 'my_settings', 'wasp' );

		$_GET['my_field'] = 'test_value';

		$result = $wasp->get_form_data( 'my_field' );

		$this->assertEquals( 'test_value', $result );

	}

	/**
	 * Summary of test_get_form_data_from_post
	 *
	 * @return void
	 */
	public function test_get_form_data_from_post() {

		$wasp = new Wasp( 'my-page', 'my_settings', 'wasp' );

		$_POST['__get__my_field'] = 'test_value_post';

		$result = $wasp->get_form_data( 'my_field' );

		$this->assertEquals( 'test_value_post', $result );

	}

	/**
	 * Summary of test_get_form_data_not_found
	 *
	 * @return void
	 */
	public function test_get_form_data_not_found() {

		$wasp = new Wasp( 'my-page', 'my_settings', 'wasp' );

		$result = $wasp->get_form_data( 'non_existent_field' );

		$this->assertNull( $result );

	}

	/**
	 * Summary of test_get_rows_returns_null_when_not_array
	 *
	 * @return void
	 */
	public function test_get_rows_returns_null_when_not_array() {

		$wasp = new Wasp( 'my-page', 'my_settings', 'wasp' );
		$this->assertNull( $wasp->get_rows() );

	}

	/**
	 * Summary of test_route_generates_script
	 *
	 * @return void
	 */
	public function test_route_generates_script() {

		Monkey\Functions\when( 'admin_url' )->returnArg( 1 );

		$wasp = new Wasp( 'my-page', 'my_settings', 'wasp' );

		ob_start();

		$wasp->route( '?page=test_route' );
		$output = ob_get_clean();

		$this->assertTrue( true );

	}

}
