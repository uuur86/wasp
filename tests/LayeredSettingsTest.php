<?php
/**
 * Tests for 3.1.0: storage modes, network forms, fallback values, themes and reset safety.
 *
 * @package wasp
 */

namespace WaspCreators\Tests;

use WaspCreators\Wasp;
use WaspCreators\Fields;
use WaspCreators\FormReset;
use WaspCreators\Templates;
use Brain\Monkey;

/**
 * Layered settings test case.
 */
class LayeredSettingsTest extends BaseTestCase {

	/**
	 * In-memory site options.
	 *
	 * @var array
	 */
	protected $options = [];

	/**
	 * In-memory network options.
	 *
	 * @var array
	 */
	protected $site_options = [];

	/**
	 * Registered settings fields.
	 *
	 * @var array
	 */
	protected $fields = [];

	/**
	 * Set up WordPress function doubles.
	 */
	protected function setUp(): void {
		parent::setUp();

		global $pagenow;
		// phpcs:ignore
		$pagenow = 'admin.php';

		$this->options      = [];
		$this->site_options = [];
		$this->fields       = [];

		$opts  = &$this->options;
		$sopts = &$this->site_options;
		$flds  = &$this->fields;

		Monkey\Functions\when( 'get_option' )->alias( function ( $name, $default = false ) use ( &$opts ) {
			return array_key_exists( $name, $opts ) ? $opts[ $name ] : $default;
		} );
		Monkey\Functions\when( 'add_option' )->alias( function ( $name, $value = '' ) use ( &$opts ) {
			if ( array_key_exists( $name, $opts ) ) {
				return false;
			}
			$opts[ $name ] = $value;
			return true;
		} );
		Monkey\Functions\when( 'update_option' )->alias( function ( $name, $value ) use ( &$opts ) {
			$opts[ $name ] = $value;
			return true;
		} );
		Monkey\Functions\when( 'delete_option' )->alias( function ( $name ) use ( &$opts ) {
			unset( $opts[ $name ] );
			return true;
		} );
		Monkey\Functions\when( 'get_site_option' )->alias( function ( $name, $default = false ) use ( &$sopts ) {
			return array_key_exists( $name, $sopts ) ? $sopts[ $name ] : $default;
		} );
		Monkey\Functions\when( 'add_site_option' )->alias( function ( $name, $value ) use ( &$sopts ) {
			if ( array_key_exists( $name, $sopts ) ) {
				return false;
			}
			$sopts[ $name ] = $value;
			return true;
		} );
		Monkey\Functions\when( 'update_site_option' )->alias( function ( $name, $value ) use ( &$sopts ) {
			$sopts[ $name ] = $value;
			return true;
		} );
		Monkey\Functions\when( 'add_settings_field' )->alias( function ( $id, $label, $cb, $page, $section, $args ) use ( &$flds ) {
			$flds[ $id ] = $args;
		} );
		Monkey\Functions\when( 'add_settings_section' )->justReturn( null );
		Monkey\Functions\when( 'register_setting' )->justReturn( null );
		Monkey\Functions\when( 'sanitize_text_field' )->alias( 'trim' );
		Monkey\Functions\when( 'esc_attr' )->alias( function ( $s ) {
			return htmlspecialchars( (string) $s, ENT_QUOTES );
		} );
		Monkey\Functions\when( 'checked' )->alias( function ( $a, $b ) {
			return ( (string) $a === (string) $b ) ? ' checked="checked"' : '';
		} );
		Monkey\Functions\when( 'is_multisite' )->justReturn( true );
	}

	/**
	 * Form config used by the tests.
	 *
	 * @return array
	 */
	protected function conf() {
		return [
			'name'  => 'sec',
			'title' => 'Section',
			'desc'  => '',
			'items' => [
				[
					'type'    => 'text_input',
					'name'    => 'title',
					'default' => 'factory',
				],
				[
					'type'    => 'radio',
					'name'    => 'mode',
					'options' => [
						[ 'value' => 'a', 'label' => 'A' ],
						[ 'value' => 'b', 'label' => 'B' ],
					],
				],
				[
					'type'    => 'text_input',
					'name'    => 'own',
					'inherit' => false,
				],
			],
		];
	}

	/**
	 * Network storage writes site options only.
	 */
	public function test_network_storage_uses_site_options() {
		$wasp = new Wasp( 'page', 'net', 'wasp', null, [ 'storage' => 'network' ] );

		$this->assertArrayHasKey( 'net_settings', $this->site_options );
		$this->assertArrayNotHasKey( 'net_settings', $this->options );
		$this->assertSame( 'manage_network_options', $wasp->get_capability() );
	}

	/**
	 * Fallback values resolve in the right order.
	 */
	public function test_fallback_resolution() {
		$this->options['site_settings'] = [
			'mode'  => 'b',
			'title' => 'stale',
		];

		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values( [ 'title' => 'network', 'mode' => 'a' ], [ 'locked' => [ 'title' ] ] );
		$wasp->load_form( $this->conf() );

		$this->assertSame( 'b', $wasp->get_value( 'mode' ) );
		$this->assertSame( 'network', $wasp->get_value( 'title' ) );
		$this->assertTrue( $wasp->is_inheritable( 'mode' ) );
		$this->assertFalse( $wasp->is_inheritable( 'own' ) );
	}

	/**
	 * Only overridden, unlocked values are saved.
	 */
	public function test_only_overrides_are_saved() {
		$this->options['site_settings'] = [ 'title' => 'stale' ];

		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values( [ 'title' => 'network', 'mode' => 'a' ], [ 'locked' => [ 'title' ] ] );
		$wasp->load_form( $this->conf() );

		$saved = $wasp->settings_input_middleware(
			[
				'mode'                  => 'b',
				'title'                 => 'posted anyway',
				'own'                   => 'mine',
				Wasp::OVERRIDE_KEY => [
					'mode'  => '1',
					'title' => '1',
				],
			]
		);

		$this->assertSame( 'b', $saved['mode'] );
		$this->assertSame( 'mine', $saved['own'] );
		$this->assertArrayNotHasKey( 'title', $saved );
		$this->assertArrayNotHasKey( Wasp::OVERRIDE_KEY, $saved );
	}

	/**
	 * The inherit control escapes the inherited value.
	 */
	public function test_inherit_control_escapes_value() {
		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values( [ 'mode' => '<b>a</b>' ] );
		$wasp->load_form( $this->conf() );

		$html = Fields::prepare( $this->fields['site_settings_mode'] );

		$this->assertStringContainsString( 'site_settings[' . Wasp::OVERRIDE_KEY . '][mode]', $html );
		$this->assertStringContainsString( '&lt;b&gt;a&lt;/b&gt;', $html );
		$this->assertStringNotContainsString( '<b>a</b>', $html );
	}

	/**
	 * A custom theme only needs the templates it changes.
	 */
	public function test_theme_falls_back_to_default_templates() {
		$theme = sys_get_temp_dir() . '/wasp-phpunit-theme';
		file_put_contents( $theme . '.wasp.php', "<?php return [ 'Text' => [ 'main' => '<input class=\"custom\" id=\"[id]\"/>' ] ];" );

		$this->assertNotFalse( Templates::get( 'Text', $theme ) );
		$this->assertStringContainsString( 'custom', Templates::get( 'Text', $theme )->main );
		$this->assertStringContainsString( '<textarea', Templates::get( 'Textarea', $theme )->main );
		$this->assertFalse( Templates::resolve( '/does/not/exist' ) );
	}

	/**
	 * Reset requests that name another option are ignored.
	 */
	public function test_reset_ignores_foreign_settings_name() {
		$this->options['active_plugins'] = [ 'keep/me.php' ];

		Monkey\Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Monkey\Functions\when( 'current_user_can' )->justReturn( true );

		$wasp  = new Wasp( 'page', 'mine', 'wasp' );
		$reset = new FormReset( $wasp );

		$_POST = [
			'nonce'         => 'x',
			'settings_name' => 'active_plugins',
		];

		$reset->ajax_reset_all_fields();

		$this->assertSame( [ 'keep/me.php' ], $this->options['active_plugins'] );
	}
}
