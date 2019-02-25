# wpsettings
Settings field framework for wordpress

# Usages

```php
new wp_admin_setting_pages( 'settings_name( string )', 'page_name( string )', 'localization_domain_name( string )' );
```

## In a function 
```php
$settings = '';

function admin_settings() {
    global $settings;
    $settings = new wp_admin_setting_pages( 'wploginexample_login', 'wploginexample_login_page', 'wploginexample' );
    // Add new section
    $settings->add_section( 'wploginexample_first', 'User Login Settings', 'Fill the below gaps about your login informations' );
    // Add new input text field
    $settings->add_new_field( 'text_input', 'login_id', 'Your login ID' );
    // Add new select field
    $settings->add_new_field( 'select', 'login_select', 'Your login select', array( 'key_value' => 'Label Value' ) );

    // Add another section
    $settings->add_section( 'wploginexample_tweaks', 'Login Tweak Settings', 'Fill the below gaps..' );
    // Add new radio
    $settings->add_new_field( 'radio', 'tweak_radio', 'Tweak Radio Field' );
    // Add new checkbox 
    $settings->add_new_field( 'checkbox', 'tweak_checkbox', 'Tweak Checkbox Multi Select Field', [ 'c1' => 'C1', 'c2' => 'C2' ]  );
    // Save all settings
    $settings->register();
}

function admin_menu_example_page() {
    // Prints html code to screen
    $settings->run();
}


add_action( 'admin_init', 'admin_settings' );
```

## In a class
```php

class wploginexample {

protected $settings;

    function __construct() {
        add_action( 'admin_init', array( $this, 'admin_settings' ) );
    }

    // Register function
    function admin_settings() {
        $this->settings = new wp_admin_setting_pages( 'wploginexample_login', 'wploginexample_login_page', 'wploginexample' );
        $settings->add_section( 'wploginexample_tweaks', 'Login Tweak Settings', 'Fill the below gaps..' );
        $settings->add_new_field( 'checkbox', 'tweak_checkbox', 'Tweak Checkbox Multi Select Field', [ 'c1' => 'C1', 'c2' => 'C2' ]  );
        $settings->register();
    }
    
    // Display function
    function admin_settings_page() {
        $settings->run();// print out
    }
}

```

# Conclusion
## WARNINGS - Pay your attention here please..
Please look at these rules

- This library must be registered by admin_init hook!! otherwise wordpress' methods won't be exists.. so, it does not work.
- Run function will be print on screen, you can use in a menu page callback function
- Be careful while giving names for these methods. Don't use any special character except a-z0-9_ and maybe - example : wploginexample_login
