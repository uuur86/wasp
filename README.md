# wpsettings
Settings field framework for wordpress

# Usages

```php
require_once( 'vendor/autoload.php' );

use WaspCreators\Wasp;

$form = new Wasp( 'page_name( string )', 'settings_name( string )', 'localization_domain_name( string )' );
```

## In a function
```php
require_once( 'vendor/autoload.php' );

use WaspCreators\Wasp;

// multi row
$form = new Wasp(
	'prefix-admin-page-name',
	'wp_options_name',
	'plugindomain',
	'row' // gets row number from $_GET[ 'row' ]
);
// or single row
$form = new Wasp(
	'prefix-admin-page-name',
	'wp_options_name',
	'plugindomain'
);

if( $form->is_ready() ) {
	$form->wp_form_init( 'config_func' );
}

add_action( 'admin_menu', 'admin_menu' );


function config_func( $form ) {
	$conf = [
		// form sections and items array
	];
	$form->load_form( $conf );
	$form->register();
}

function admin_menu() {
	add_menu_page(
		'TestPlugin',
		esc_html__( 'TestPlugin', 'testplugin' ),
		'manage_options',
		'testplugin',
		'admin_page',
		'dashicons-forms'
	);
}

function admin_page() {
	global $form;

	echo "<p>Embedded form will shown in here!</p>";

	// Echo form output
	$form->run();
}

```

## In a class
```php

require_once( 'vendor/autoload.php' );

use WaspCreators\Wasp;


class TestPlugin {
	public $test;

	function __construct() {
		$this->test = new Wasp(
			'prefix-admin-page-name',
			'wp_options_name',
			'plugindomain'
		);

		if( $this->test->is_ready() ) {
			$this->test->wp_form_init( [ $this, 'config_func' ] );
		}

		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	function config_func() {
		$conf = [
			// form sections and items array
		];
		$this->test->load_form( $conf );
		$this->test->register();
	}

	function admin_menu() {
		add_menu_page(
			'TestPlugin',
			esc_html__( 'TestPlugin', 'testplugin' ),
			'manage_options',
			'testplugin',
			array( $this, 'admin_page' ),
			'dashicons-forms'
		);
	}

	function admin_page() {
		echo "<p>Embedded form will shown in here!</p>";

		// Echo form output
		$this->test->run();
	}
}

new TestPlugin();

```

## Example form config array for load_form() method
```php

// For Single Section
$conf = [
	// Section info
	'name'	=> 'prefix_section',
	'title'	=> 'Form Section Title',
	'desc'	=> 'Form Section Description',
	// Form items
	'items' => [
		[
			'cond'		=> ( ! $isHidden ), // Appears when true, otherwise disappeared
			'type'		=> 'text_input',
			'name'		=> 'my_text',
			'label'		=> 'My Text',
			'after'		=> 'html code which displayed after input',
			'before'	=> 'html code which displayed before input',
		],
		[
			'cond'		=> ( ! $isHidden ), // Applies to all item types
			'type'		=> 'select',
			'name'		=> 'my_options',
			'label'		=> 'Select One',
			'options' => [
				[
					'label' => 'Option 1',
					'value'	=> 'option1',
				],
				[
					'label' 		=> 'Option 2',
					'value'			=> 'option2',
					'disabled'	=> true,
				],
			]
		],
		[
			'cond'		=> ( ! $isHidden ), // Applies to all item types
			'type'		=> 'radio',
			'name'		=> 'my_radio',
			'label'		=> 'Choose one',
			'options' => [
				[
					'label' => 'Switch Off',
					'value'	=> '0',
				],
				[
					'label' => 'Switch On',
					'value'	=> '1',
				],
			]
		],
		[
			'type'		=> 'select',
			'name'		=> 'grouped_select',
			'label'		=> 'Select in Groups',
			// Grouped options
			'options' => [
				'My Group 1' => [
					[
						'label' 		=> 'Option 1-1',
						'value'			=> 'option11',
						'disabled'	=> true,
					],
					[
						'label' => 'Option 1-2',
						'value'	=> 'option12',
					],
				],
				'My Group 2' => [
					[
						'label' => 'Option 2-1',
						'value'	=> 'option21',
					],
					[
						'label' => 'Option 2-2',
						'value'	=> 'option22',
					],
				]
			]
		],
		[
			'type'		=> 'hidden', // hidden text field
			'name'		=> 'passthis',
			'value' 	=> $passthis,
			'save'		=> true, // save in options
		],
		[
			'type'		=> 'onecheckbox',// only one checkbox ( 0 or 1 )
			'name'		=> 'onoff',
			'label'		=> 'On / Off',
			'option' 	=> '1',
			'checked'	=> false // default checked
		],
	]
];

// For Multi Sections
$conf = [
	[
		// Section info
		'name'	=> 'prefix_section1',
		'title'	=> 'Form Section Title',
		'desc'	=> 'Form Section Description',
		// Form items
		'items' => []
	],
	[
		// Section info
		'name'	=> 'prefix_section2',
		'title'	=> 'Form Section Title',
		'desc'	=> 'Form Section Description',
		// Form items
		'items' => []
	]
];
```

# 3.1.0 features

All new features are opt-in. Forms that don't use them behave exactly as before.

## Network-wide settings (multisite)

```php
$form = new Wasp(
	'prefix-network-page',
	'prefix_options',
	'plugindomain',
	null,
	[
		'storage'    => 'network',                // get_site_option() / update_site_option()
		'capability' => 'manage_network_options', // default for network storage
	]
);

add_action( 'network_admin_menu', 'prefix_network_menu' ); // register the page in the network admin
```

On multisite the form posts to `network/edit.php?action=<page>` and is saved by Wasp
(`network_admin_edit_<page>`, nonce and capability checked). On a single site it falls back
to the normal `options.php` flow.

## Default values from another layer, with per-field override

Show values inherited from another layer (for example network defaults on a sub-site, or a
global default on a single item) and save only the fields the user overrides.

```php
function config_func( $form ) {
	$form->set_fallback_values(
		get_site_option( 'prefix_options_settings', [] ), // the layer to inherit from
		[
			'locked'       => [ 'api_key' ],              // always inherited, cannot be overridden
			'label'        => __( 'Override', 'plugindomain' ),
			'source_label' => __( 'Network default', 'plugindomain' ),
		]
	);

	$form->load_form( $conf ); // call set_fallback_values() before load_form()
	$form->register();
}
```

- Each field gets an "Override" toggle (template `Inherit`, so themes can restyle it).
- Unchecked: the field shows the inherited value and nothing is stored for it.
- Checked: the field's own value is stored, even an empty one.
- Opt a field out with `'inherit' => false` in its config.
- `'auto_override' => true` keeps fields editable: changing a value turns its override on, and
  getting back to the inherited value turns it off. A theme can add a revert control: any element
  with `data-wasp-inherit-revert` inside the `Inherit` template (label: `[revert_label]`).
- Saving does not depend on the script: with `auto_override` the server stores every value that differs
  from the inherited one.
- Fields with options show the option label of the inherited value ("Yes", not "1").
- `$form->get_resolved_values()` returns defaults + fallback + own values merged.

## Themes per form

`$form->set_theme( __DIR__ . '/templates/my-theme' )` now accepts an absolute path (with or
without `.wasp.php`) and applies to that form only. A theme only needs the templates it
changes; missing types fall back to the default theme. Relative paths from 3.0 still work.

## Asset URLs

wasp serves a few scripts and styles from its own `assets/` folder. If the library is not reachable through
`plugins_url()` (for example a symlinked or shared vendor folder), tell it where it is served from:

```php
\WaspCreators\Wasp::set_assets_url( plugin_dir_url( __FILE__ ) . 'vendor/uuur86/wasp/' );
```
