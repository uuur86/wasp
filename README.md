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

$form = new Wasp( 'testplugin', 'testplugin_set', 'domain_name' );
			
if( $form->is_ready() ) {
	$form->wp_form_init( 'config_func' );
}
			
add_action( 'admin_menu', 'admin_menu' );

	
function config_func( $form ) {
	$form->section( 'section1_id', 'Section 1 Title', 'Desc for Section 1' )->add();
	$form->field( 'text_input', 'test_id', 'Test ID' )->add();

	$form->register();
}
	
function admin_menu() {
	add_menu_page( 'TestPlugin', esc_html__( 'TestPlugin', 'testplugin' ), 'manage_options', 'testplugin', 'admin_page', 'dashicons-forms' );
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
		$this->test = new Wasp( 'testplugin', 'testplugin_set', 'domain_name' );
		
		if( $this->test->is_ready() ) {
			$this->test->wp_form_init( [ $this, 'config_func' ] );
		}
		
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}
	
	function config_func() {
		$this->test->section( 'section1_id', 'Section 1 Title', 'Desc for Section 1' )->add();
		$this->test->field( 'text_input', 'test_id', 'Test ID' )->add();

		$this->test->register();
	}
	
	function admin_menu() {
		add_menu_page( 'TestPlugin', esc_html__( 'TestPlugin', 'testplugin' ), 'manage_options', 'testplugin', array( $this, 'admin_page' ), 'dashicons-forms' );
	}
	
	function admin_page() {
        echo "<p>Embedded form will shown in here!</p>";
        
        // Echo form output
		$this->test->run();
	}
}

new TestPlugin();

```

