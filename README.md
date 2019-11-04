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
	$form->loadForm( $conf );
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
		$this->test->loadForm( $conf );
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

## Example form config array for loadForm method
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
			'label'		=> 'My Text'
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
