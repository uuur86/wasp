<?php

/**
 * WP Settings framework
 *
 * @author Uğur Biçer <uuur86@yandex.com>
 * @copyright 2018 - 2019, Uğur Biçer
 * @license GPLv3
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * https://github.com/uuur86/wpsettings
 */

namespace wpsettings;

class wp_admin_setting_pages {

    // String form title
    protected $title;

    // String form description
    protected $desc;

    // String wp language domain name
    protected $domain;

    // String current page name
    protected $page_name;

    // String current settings option name
    protected $settings_name;

    // String current settings row
    protected $settings_row;

    // String current index name
    protected $index_name;

    // Array all indexes
    protected $indexes;

    // Array All input fields
    protected $fields;

    // Array sanitize info
    protected $sanitize;

    // Array all sections index
    protected $sections;

    // String section name
    protected $section;

    // String segment name
    protected $segment;



    public function __construct( $page, $setting_name, $domain, $row = false ) {

        if( !isset( $page ) || $page === false || empty( $page ) )
            return false;

        $this->index_name = $setting_name . '_' . 'index';

        if( $row !== false ) {

            if( ( $this->indexes = $this->get_indexes() ) !== false && is_array( $this->indexes ) ) {
                $last_index = array_flip( $this->indexes );
                $last_index = end( $last_index );
                $last_settings = get_option( $this->indexes[ $last_index ] );

                if( $last_settings !== false && $row === true ) {
                    $row = intval( $last_index );

                    if( is_array( $last_settings ) && count( $last_settings ) > 0 )
                        $row++;
                }
            }

            $this->settings_row = $row;
            $setting_name .= '_' . $this->settings_row;

            if( false === get_option( $this->index_name ) ) {
                add_option( $this->index_name, [ $this->settings_row => $setting_name . '_settings' ] );
            }
            else {
                $this->indexes[ $this->settings_row ] = $setting_name . '_settings';
                update_option( $this->index_name, $this->indexes );
            }
        }

        $this->page_name = $page;
        $this->settings_name = $setting_name . '_settings'; // TODO : will be change soon
        $this->domain = $domain;

        if( false === get_option( $this->settings_name ) ) {
            add_option( $this->settings_name, [] );
        }

        return $this;
    }



    public function set_segment( $name ) {
        if( !empty( $name ) )
            $this->segment = $name;
    }



    public function if_segment( $name ) {
        if( isset( $_POST[ 'segment_name' ] ) ) {
            if( $name == $_POST[ 'segment_name' ] )
                return true;
        }

        if( isset( $this->segment ) ) {
            if( $name == $this->segment )
                return true;
        }

        return false;
    }



    public function add_section( $name, $title, $desc ) {

        if( empty( $name ) )
            return;

        $this->section = $name;

        $this->sections[ $this->section ] = [
            'title' => $title,
            'desc' => $desc,
        ];

        add_settings_section(
            $this->section . '_section', // Section ID
            '',//esc_html__( $title, $this->domain ), // Callback for an optional title
            array( $this, 'settings_field_section_callback' ), // Admin page to add section to
            $this->section . ''
        );
    }



    public function settings_field_section_callback() {
        $current = current( $this->sections );
        echo '<h2 class="title">' . esc_html__( $current[ 'title' ], $this->domain ) . '</h2>';
        echo '<p class="description">' . esc_html__( $current[ 'desc' ], $this->domain ) . '</p>';
        next( $this->sections );
    }



    protected function get_settings( $name ) {
        if( ( $options = get_option( $this->settings_name, false ) ) !== false ) {
            if( isset( $options[ $name ] ) )
                return $options[ $name ];
        }
        return '';
    }



    public function add_new_field( $type, $id, $label, $options = null, $sanitize = 'text_field' ) {
        $field_type_callback = 'settings_field_' . $type . '_callback';

        if( in_array( $type, array( 'radio', 'select' ) ) ) {

            if( is_array( $options ) ) {
                $this->sanitize[ $id ] = array( 'type' => 'options', 'values' => array_keys( $options ) );
            }
        }
        else {
            $this->sanitize[ $id ] = array( 'type' => $sanitize, 'regex' => $options );
        }

        $field_args = [
            'label' => esc_html__( $label, $this->domain ),
            'name' => esc_html( $id )
        ];

        if( !empty( $options ) ) {
            $field_args[ 'options' ] = $options;
        }

        add_settings_field(
            $this->settings_name . '_' . $id,
            esc_html__( $label, $this->domain ),
            array( $this, $field_type_callback ),
            $this->section,
            $this->section . '_section',
            $field_args
        );
    }



    public function add_hidden_field( $id, $value ) {
        $field_args = [
            'value' => esc_html( $value ),
            'name' => esc_html( $id )
        ];

        add_settings_field(
            $this->settings_name . '_' . $id,
            '',
            array( $this, 'settings_field_hidden_input_callback' ),
            $this->section,
            $this->section . '_section',
            $field_args
        );
    }



    public function settings_field_hidden_input_callback( $args ) {
        $value = $args[ 'value' ];

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }
        echo '<input type="hidden" id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']" value="' . $value . '"/>';
    }



    public function settings_field_text_input_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }
        echo '<input type="text" id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']" value="' . $value . '" placeholder="' . $args[ 'label' ] . '"/>';
    }



    public function settings_field_select_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }

        $options = '';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $options .= '<option value="' . $opt_key . '" ' . selected( $opt_key, $value, false ) . '>' . $opt_val . '</option>';
            }
        }

        echo '<select id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']">';
        echo $options;
        echo '</select>';
    }



    public function settings_field_checkbox_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        $options = '<fieldset>';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $value_ = '';
                if( !empty( $value[ $opt_key ] ) ) {
                    $value_ = esc_html( $value[ $opt_key ] );
                }

                $id = $this->settings_name . '_' . $args[ 'name' ] . '_input_checkbox_' . $opt_key;
                $name = $this->settings_name . '[' . $args[ 'name' ] . '][' . $opt_key . ']';

                $options .= '<label for="' . $id . '">';
                $options .= '<input type="checkbox"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . checked( $opt_key, $value_, false ) . '/>';
                $options .= $opt_val . '</label> &nbsp;<br/>';
            }
        }
        else {
            $value_ = '';
            if( !empty( $value ) ) {
                $value_ = esc_html( $value );
            }

            $id = $this->settings_name . '_' . $args[ 'name' ] . '_input_checkbox';
            $name = $this->settings_name . '[' . $args[ 'name' ] . ']';

            $options .= '<label for="' . $id . '">';
            $options .= '<input type="checkbox"  name="' . $name . '" id="' . $id . '" value="' . $args[ 'options' ] . '" ' . checked( $args[ 'options' ], $value_, false ) . '/>';
            $options .= '</label> &nbsp;<br/>';
        }

        $options .= '</fieldset>';

        echo $options;
    }


    public function settings_field_radio_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }

        $options = '<fieldset>';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $id = $this->settings_name . '_' . $args[ 'name' ] . '_input_radio_' . $opt_key;
                $name = $this->settings_name . '[' . $args[ 'name' ] . ']';

                $options .= '<label for="' . $id . '">';
                $options .= '<input type="radio"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . checked( $opt_key, $value, false ) . '/>';
                $options .= $opt_val . '</label> &nbsp;<br/>';
            }
        }

        $options .= '</fieldset>';

        echo $options;
    }


    public function settings_input_middleware( $inputs ) {

        foreach( $this->sanitize as $input_key => $input_value ) {

            if( !isset( $input_value[ 'type' ] ) )
                continue;

            if( $input_value[ 'type' ] == 'regex' && isset( $input_value[ 'regex' ] ) ) {
                if( preg_match( "#^" . $input_value[ 'regex' ] . "$#ui", $inputs[ $input_key ], $matched ) )
                    $inputs[ $input_key ] = $matched[ 0 ];
                else
                    $inputs[ $input_key ] = '';
                continue;
            }

            if( $input_value[ 'type' ] == 'options' && is_array( $input_value[ 'values' ] ) ) {
                if( !in_array( $inputs[ $input_key ], $input_value[ 'values' ] ) ) {
                    unset( $inputs[ $input_key ] );
                }
                continue;
            }

            $func_name = 'sanitize_' . $input_value;

            if( !function_exists( $func_name ) )
                continue;

            $inputs[ $input_key ] = call_user_func( $func_name, $inputs[ $input_key ] );
        }

        return $inputs;
    }



    public function register() {
        global $_POST;

        if( ( $all_settings = $this->get_indexes() ) !== false && isset( $_POST[ 'setting_name' ] ) ) {
            $requested_option_page = $_POST[ 'setting_name' ];
            if( in_array( $requested_option_page, $all_settings ) )
                register_setting( $this->page_name, $requested_option_page, array( $this, 'settings_input_middleware' ) );
        }
        else {
            register_setting( $this->page_name, $this->settings_name, array( $this, 'settings_input_middleware' ) );
        }
    }



    public function put_hidden_fields() {
        echo '<input type="hidden" name="segment_name" value="' . $this->segment . '"/>';
        echo '<input type="hidden" name="setting_name" value="' . $this->settings_name . '"/>';
    }



    public function run() {
        // Display necessary hidden fields for settings
        settings_fields( $this->page_name );
        // Display the settings sections for the page
        foreach( $this->sections as $sec_name => $sec_value ) {
            do_settings_sections( $sec_name );
        }

        $this->put_hidden_fields();
        // Default Submit Button
        submit_button();
    }



    public function get_indexes() {
        return get_option( $this->index_name );
    }



    public function last_index() {
        if( $this->settings_row > -1 )
            return $this->settings_row;

        return false;
    }

}

