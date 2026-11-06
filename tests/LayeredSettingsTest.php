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

		if ( function_exists( 'Brain\Monkey\Functions\when' ) ) {

			Monkey\Functions\when( 'get_option' )->alias(
				function ( $name, $fallback = false ) use ( &$opts ) {
					return array_key_exists( $name, $opts ) ? $opts[ $name ] : $fallback;
				}
			);
			Monkey\Functions\when( 'add_option' )->alias(
				function ( $name, $value = '' ) use ( &$opts ) {
					if ( array_key_exists( $name, $opts ) ) {
							return false;
					}
					$opts[ $name ] = $value;
					return true;
				}
			);
			Monkey\Functions\when( 'update_option' )->alias(
				function ( $name, $value ) use ( &$opts ) {
					$opts[ $name ] = $value;
					return true;
				}
			);
			Monkey\Functions\when( 'delete_option' )->alias(
				function ( $name ) use ( &$opts ) {
					unset( $opts[ $name ] );
					return true;
				}
			);
			Monkey\Functions\when( 'get_site_option' )->alias(
				function ( $name, $fallback = false ) use ( &$sopts ) {
					return array_key_exists( $name, $sopts ) ? $sopts[ $name ] : $fallback;
				}
			);
			Monkey\Functions\when( 'add_site_option' )->alias(
				function ( $name, $value ) use ( &$sopts ) {
					if ( array_key_exists( $name, $sopts ) ) {
							return false;
					}
					$sopts[ $name ] = $value;
					return true;
				}
			);
			Monkey\Functions\when( 'update_site_option' )->alias(
				function ( $name, $value ) use ( &$sopts ) {
					$sopts[ $name ] = $value;
					return true;
				}
			);
			Monkey\Functions\when( 'add_settings_field' )->alias(
				function ( $id, $label, $cb, $page, $section, $args ) use ( &$flds ) {
					$flds[ $id ] = $args;
				}
			);
			Monkey\Functions\when( 'add_settings_section' )->justReturn( null );
			Monkey\Functions\when( 'register_setting' )->justReturn( null );
			Monkey\Functions\when( 'sanitize_text_field' )->alias( 'trim' );
			Monkey\Functions\when( 'esc_attr' )->alias(
				function ( $s ) {
					return htmlspecialchars( (string) $s, ENT_QUOTES );
				}
			);
			Monkey\Functions\when( 'checked' )->alias(
				function ( $a, $b ) {
					return ( (string) $a === (string) $b ) ? ' checked="checked"' : '';
				}
			);
			Monkey\Functions\when( 'is_multisite' )->justReturn( true );

		} else {
			$this->markTestSkipped( 'Brain Monkey is not available.' );
		}
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
						[
							'value' => 'a',
							'label' => 'A',
						],
						[
							'value' => 'b',
							'label' => 'B',
						],
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
		$wasp->set_fallback_values(
			[
				'title' => 'network',
				'mode'  => 'a',
			],
			[ 'locked' => [ 'title' ] ]
		);
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
		$wasp->set_fallback_values(
			[
				'title' => 'network',
				'mode'  => 'a',
			],
			[ 'locked' => [ 'title' ] ]
		);
		$wasp->load_form( $this->conf() );

		$saved = $wasp->settings_input_middleware(
			[
				'mode'             => 'b',
				'title'            => 'posted anyway',
				'own'              => 'mine',
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
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture outside WordPress.
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

	/**
	 * The inherit control shows the option label, not the stored value.
	 */
	public function test_inherit_control_shows_option_label() {
		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values( [ 'mode' => 'b' ] );
		$wasp->load_form( $this->conf() );

		$html = Fields::prepare( $this->fields['site_settings_mode'] );

		$this->assertStringContainsString( '<code>B</code>', $html );
	}

	/**
	 * Auto override keeps the field editable and carries the inherited value.
	 */
	public function test_auto_override_marks_wrapper() {
		Monkey\Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values(
			[
				'title' => 'network',
				'mode'  => 'a',
			],
			[
				'locked'        => [ 'title' ],
				'auto_override' => true,
			]
		);
		$wasp->load_form( $this->conf() );

		$mode  = Fields::prepare( $this->fields['site_settings_mode'] );
		$title = Fields::prepare( $this->fields['site_settings_title'] );

		$this->assertStringContainsString( 'data-wasp-auto="1"', $mode );
		$this->assertStringContainsString( 'data-wasp-inherited="&quot;a&quot;"', $mode );
		$this->assertStringNotContainsString( 'data-wasp-auto', $title, 'Locked fields never switch to an override.' );
	}

	/**
	 * Without auto override the wrapper has no inherited value attribute.
	 */
	public function test_auto_override_is_off_by_default() {
		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values( [ 'mode' => 'a' ] );
		$wasp->load_form( $this->conf() );

		$html = Fields::prepare( $this->fields['site_settings_mode'] );

		$this->assertStringNotContainsString( 'data-wasp-auto', $html );
		$this->assertStringNotContainsString( 'data-wasp-inherited', $html );
	}

	/**
	 * Option labels work for scalars, lists and grouped options; unknown values pass through.
	 */
	public function test_option_labels() {
		$options = [
			[
				'value' => '0',
				'label' => 'No',
			],
			[
				'value' => '1',
				'label' => 'Yes',
			],
			[
				'label'   => 'Group',
				'options' => [
					[
						'value' => 'g',
						'label' => 'Grouped',
					],
				],
			],
		];

		$this->assertSame( 'No', Fields::option_labels( '0', $options ) );
		$this->assertSame( 'Yes', Fields::option_labels( true, $options ) );
		$this->assertSame( 'Grouped', Fields::option_labels( 'g', $options ) );
		$this->assertSame( [ 'Yes', 'other' ], Fields::option_labels( [ '1', 'other' ], $options ) );
		$this->assertSame( 'raw', Fields::option_labels( 'raw', $options ) );
		$this->assertSame( 'raw', Fields::option_labels( 'raw', null ) );
	}

	/**
	 * Auto override stores changed values even when the script did not switch the toggle on.
	 */
	public function test_auto_override_saves_changed_values_without_toggle() {
		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values(
			[
				'title' => 'network',
				'mode'  => 'a',
			],
			[ 'auto_override' => true ]
		);
		$wasp->load_form( $this->conf() );

		$saved = $wasp->settings_input_middleware(
			[
				'mode'             => 'b',
				'title'            => 'network',
				Wasp::OVERRIDE_KEY => [ Wasp::OVERRIDE_KEY => '1' ],
			]
		);

		$this->assertSame( 'b', $saved['mode'] );
		$this->assertArrayNotHasKey( 'title', $saved, 'A value equal to the inherited one is not stored.' );
	}

	/**
	 * Auto override never stores locked fields.
	 */
	public function test_auto_override_keeps_locked_fields_out() {
		$wasp = new Wasp( 'page', 'site', 'wasp' );
		$wasp->set_fallback_values(
			[ 'title' => 'network' ],
			[
				'locked'        => [ 'title' ],
				'auto_override' => true,
			]
		);
		$wasp->load_form( $this->conf() );

		$saved = $wasp->settings_input_middleware(
			[
				'title'            => 'changed',
				Wasp::OVERRIDE_KEY => [ Wasp::OVERRIDE_KEY => '1' ],
			]
		);

		$this->assertArrayNotHasKey( 'title', $saved );
	}

	/**
	 * Values are compared like the script compares them.
	 */
	public function test_differs_from_inherited() {
		$this->assertFalse( Wasp::differs_from_inherited( 'a|b', "b\na" ) );
		$this->assertFalse( Wasp::differs_from_inherited( '', '0' ) );
		$this->assertFalse( Wasp::differs_from_inherited( '10', 10 ) );
		$this->assertFalse( Wasp::differs_from_inherited( [ 'x', 'y' ], [ 'y', 'x' ] ) );
		$this->assertTrue( Wasp::differs_from_inherited( '1', '0' ) );
		$this->assertTrue( Wasp::differs_from_inherited( 'a', 'a|b' ) );
	}

	/**
	 * Asset URLs follow the URL set by the consuming plugin.
	 */
	public function test_asset_url_uses_the_configured_base() {
		Wasp::set_assets_url( 'https://example.org/wp-content/plugins/demo/vendor/uuur86/wasp' );

		$url = Wasp::asset_url( '/assets/js/wasp-inherit.js' );

		Wasp::set_assets_url( '' );

		$this->assertSame( 'https://example.org/wp-content/plugins/demo/vendor/uuur86/wasp/assets/js/wasp-inherit.js', $url );
	}
}
