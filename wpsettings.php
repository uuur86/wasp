<?php

/**
 * WP Settings framework
 *
 * @author Uğur Biçer <uuur86@yandex.com>
 * @copyright 2018, Uğur Biçer
 * @license GPLv3
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class admin_settings_page {

    protected $title;

    protected $desc;

    protected $domain;

    protected $page_name;

    protected $settings_name;

    protected $fields;

    protected $sanitize;



    public function __construct( $page, $domain ) {

        if( !isset( $page ) || $page === false || empty( $page ) )
            return false;

        $this->page_name = $page;
        $this->settings_name = $page . '_settings'; // TODO : will be change soon
        $this->domain = $domain;

        if( false == get_option( $this->settings_name ) ) {
            add_option( $this->settings_name, [] );
        }

        add_settings_section(
            $this->settings_name . '_section', // Section Title
            esc_html__( $this->title, $this->domain ), // Callback for an optional title
            array( $this, 'settings_field_section_callback' ), // Admin page to add section to
            $this->page_name
        );

        return $this;
    }


    public function add_section( $title, $desc ) {
        $this->title = empty( $title ) ? '' : $title;
        $this->desc = empty( $desc ) ? '' : $desc;
    }



    public function settings_field_section_callback() {
        echo '<h2 class="title">' . esc_html__( $this->title, $this->domain ) . '</h2>';
        echo '<p class="description">' . esc_html__( $this->desc, $this->domain ) . '</p>';
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
        $this->sanitize[ $id ] = $sanitize;

        $field_args = [
            'label' => esc_html__( $label, $this->domain ),
            'name' => $id
        ];

        if( is_array( $options ) ) {
            $field_args[ 'options' ] = $options;
        }

        add_settings_field(
            $this->settings_name . '_' . $id,
            esc_html__( $label, $this->domain ),
            array( $this, $field_type_callback ),
            $this->page_name,
            $this->settings_name . '_section',
            $field_args
        );
    }



    public function register() {

        register_setting(
            $this->settings_name . '',
            $this->settings_name . '',
            array( $this, 'settings_input_middleware' )
        );
    }



    public function settings_field_text_input_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }
        echo '<input type="text" id="' . $this->page_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->page_name . '_settings[' . $args[ 'name' ] . ']" value="' . $value . '" placeholder="' . $args[ 'label' ] . '"/>';
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

        echo '<select id="' . $this->page_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->page_name . '_settings[' . $args[ 'name' ] . ']">';
        echo $options;
        echo '</select>';
    }



    public function settings_field_radio_callback( $args ) {
        $value = $this->get_settings( $args[ 'name' ] );

        if( !empty( $value ) ) {
            $value = esc_html( $value );
        }

        $options = '';

        if( is_array( $args[ 'options' ] ) ) {
            foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
                $id = $this->page_name . '_' . $args[ 'name' ] . '_input_radio_' . $opt_key;
                $name = $this->settings_name . '[' . $args[ 'name' ] . ']';
                $options .= '<input type="radio"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . checked( $opt_key, $value, false ) . '/>';
                $options .= '<label for="' . $id . '">' . $opt_val . '</label> &nbsp;';
            }
        }

        echo $options;
    }


    public function settings_input_middleware( $inputs ) {

        foreach( $this->sanitize as $input_key => $input_value ) {
            $func_name = 'sanitize_' . $input_value;

            if( !function_exists( $func_name ) )
                continue;

            $inputs[ $input_key ] = call_user_func( $func_name, $inputs[ $input_key ] );
        }

        return $inputs;
    }

}


