<?php

/**
 * Settings framework for Wordpress
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wpsettings
 * @author Uğur Biçer <uuur86@yandex.com>
 * @license GPLv3 or later
 * @version 1.1.4
 */

class wp_admin_setting_pages {

	// String form title
	protected $title;

	// String form description
	protected $desc;

	// String wp language domain name
	protected $domain;

	// String current page name
	protected $page_name;

	// Array setting values
	protected $setting_values = null;

	// String current settings option name
	protected $settings_name;

	// String current settings row
	protected $settings_row;

	// String current index name
	protected $index_name;

	// Array all indexes
	protected $indexes;

	// Array All input fields
	protected $input_fields = null;

	// The other hidden fields
	protected $hidden_fields;

	// Array sanitize info
	protected $sanitize;

	// Array all sections index
	protected $sections;

	// String section name
	protected $section;

	// String return_url
	protected $return_url;



	public function __construct( $page, $setting_name, $domain, $row = false ) {

		if( !isset( $page ) || $page === false || empty( $page ) )
			return false;

		$this->index_name = $setting_name . '_' . 'index';

		if( $row !== false ) {
			$row = $this->_get( $row );

			if( ( $this->indexes = $this->get_indexes() ) !== false && is_array( $this->indexes ) ) {
				$last_index = array_flip( $this->indexes );
				$last_index = end( $last_index );
				$last_settings = get_option( $this->indexes[ $last_index ] );

				if( $last_settings !== false && empty( $row ) ) {
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

		$this->prepare_get_vars();

		if( false === get_option( $this->settings_name, false ) ) {
			add_option( $this->settings_name, [] );
		}
		else {
			$this->setting_values = get_option( $this->settings_name );
		}

		return $this;
	}



	protected function prepare_get_vars() {

		foreach( $_GET as $get_key => $get_val ) {
			$this->add_hidden_field( '__get__' . $get_key, $get_val );
		}
	}



	public function _get( $name ) {

		if( isset( $_GET[ $name ] ) )
			return $_GET[ $name ];

		if( isset( $_POST[ '__get__' . $name ] ) )
			return $_POST[ '__get__' . $name ];

		return null;
	}



	public function route( $link ) {

		if( is_array( $link ) )
			$link = add_query_arg( $link );

		$this->add_hidden_field( '_wp_http_referer', $link );
	}



	/**
	 * @param $name
	 * @param $title
	 * @param $desc
	 */
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
			'',
			array( $this, 'settings_field_section_callback' ), // Admin page to add section to
			$this->section . ''
		);
	}



	/**
	 * This function prints out the settings field section html code
	 */
	public function settings_field_section_callback() {
		$current = current( $this->sections );
		echo '<h2 class="title">' . esc_html__( $current[ 'title' ], $this->domain ) . '</h2>';
		echo '<p class="description">' . esc_html__( $current[ 'desc' ], $this->domain ) . '</p>';
		next( $this->sections );
	}



	public function get_settings() {

		if( empty( $this->setting_values ) )
			return null;

		return $this->setting_values;
	}



	/**
	 * This function gets the wordpress options about defined setting
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function get_setting( $name ) {

		if( isset( $this->setting_values[ $name ] ) )
			return $this->setting_values[ $name ];

		return null;
	}



	/**
	 * It registers the values to sanitize array for using in middleware controls
	 *
	 * @param string $type
	 * @param string $id
	 * @param array|string $options
	 * @param string $sanitize [ options, regex, text_field, textarea_field, email, file_name etc.. ]
	 */
	function register_sanitize( $type, $id, $options, $sanitize ) {

		$this->input_fields[] = $id;

		if( in_array( $type, array( 'radio', 'select' ) ) ) {

			if( is_array( $options ) ) {
				$opt_arr_val = array_values( $options );

				if( is_array( $opt_arr_val[ 0 ] ) ) {
					$new_options = [];

					foreach( $opt_arr_val as $opt_arr_val_ ) {
						$new_options +=  $opt_arr_val_;
					}

					$options = $new_options;
				}

				$options = array_keys( $options );
				$this->sanitize[ $id ] = array( 'type' => 'options', 'values' => $options );
			}
		}
		else if( $sanitize == 'regex' ) {
			$this->sanitize[ $id ] = array( 'type' => $sanitize, 'pattern' => $options );
		}
		else {
			$this->sanitize[ $id ] = array( 'type' => $sanitize, 'value' => $options );
		}
	}



	/**
	 * This function adds new field to defined setting options
	 *
	 * @param string $type
	 * @param string $id
	 * @param string $label
	 * @param null|array $options
	 * @param string $sanitize
	 */
	public function add_new_field( $type, $id, $label, $options = null, $sanitize = 'text_field' ) {
		$field_type_callback = 'settings_field_' . $type . '_callback';

		$this->register_sanitize( $type, $id, $options, $sanitize );

		$field_args = [
			'label' => esc_html__( $label, $this->domain ),
			'name' => esc_html( $id )
		];

		if( is_array( $options ) ) {
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



	/**
	 * This function registers hidden field for moving variable data
	 * to options page without creating an option
	 *
	 * @param $id
	 * @param $value
	 * @param bool $save
	 */
	public function add_hidden_field( $id, $value, $save = false ) {
		$id     = esc_html( $id );
		$value  = esc_html( $value );

		if( $save ) {
			$this->register_sanitize( 'text', $id, $value, '' );
		}

		$this->hidden_fields[ $id ] = $value;
	}



	public function settings_field_text_input_callback( $args ) {
		$value = $this->get_setting( $args[ 'name' ] );

		if( !empty( $value ) ) {
			$value = esc_html( $value );
		}
		echo '<input type="text" id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']" value="' . $value . '" placeholder="' . $args[ 'label' ] . '"/>';
	}



	public function settings_field_select_callback( $args ) {
		$value      = $this->get_setting( $args[ 'name' ] );
		$html_inner = '';

		if( !empty( $value ) ) {
			$value = esc_html( $value );
		}

		$set_options = function( $options, $value ) use ( &$set_options ) {
			$return = '';

			foreach( $options as $opt_key => $opt_val ) {

				if( is_array( $opt_val ) ) {
					$return .= '<optgroup label="' . $opt_key . '">' . $set_options( $opt_val, $value ) . '</optgroup>';
				}
				else {
					$return .= '<option value="' . $opt_key . '" ' . selected( $opt_key, $value, false ) . '>' . $opt_val . '</option>';
				}
			}

			return $return;
		};

		if( is_array( $args[ 'options' ] ) ) {
			$options    = $args[ 'options' ];
			$html_inner = $set_options( $options, $value );
		}

		echo '<select id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']">';
		echo $html_inner;
		echo '</select>';
	}



	public function settings_field_checkbox_callback( $args ) {
		$value      = $this->get_setting( $args[ 'name' ] );
		$options    = '<fieldset>';

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

		$options .= '</fieldset>';

		echo $options;
	}



	public function settings_field_radio_callback( $args ) {
		$value = $this->get_setting( $args[ 'name' ] );

		if( !empty( $value ) ) {
			$value = esc_html( $value );
		}

		$options = '<fieldset>';

		if( is_array( $args[ 'options' ] ) ) {

			foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
				$id     = $this->settings_name . '_' . $args[ 'name' ] . '_input_radio_' . $opt_key;
				$name   = $this->settings_name . '[' . $args[ 'name' ] . ']';

				$options .= '<label for="' . $id . '">';
				$options .= '<input type="radio"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . checked( $opt_key, $value, false ) . '/>';
				$options .= $opt_val . '</label> &nbsp;<br/>';
			}
		}

		$options .= '</fieldset>';

		echo $options;
	}



	/**
	 * This function sanitizes defined setting options
	 *
	 * @param $inputs
	 *
	 * @return mixed
	 */
	public function settings_input_middleware( $inputs ) {
		global $pagenow;

		foreach( $this->sanitize as $input_key => $input_value ) {

			if( !isset( $inputs[ $input_key ] ) )
				$inputs[ $input_key ] = sanitize_title( $_POST[ $input_key ] );

			if( !isset( $input_value[ 'type' ] ) ) {
				continue;
			}

			if( $input_value[ 'type' ] == 'regex' && isset( $input_value[ 'pattern' ] ) ) {

				if( preg_match( "#^" . $input_value[ 'pattern' ] . "$#ui", $inputs[ $input_key ], $matched ) )
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

		// Adding the old values
		if( ( !empty( $this->input_fields ) ) && ( !empty( $this->setting_values ) ) ) {
			$missed_fields = array_diff( array_keys( $this->setting_values ), $this->input_fields );

			foreach( $missed_fields as $missed_input ) {
				$inputs[ $missed_input ] = $this->setting_values[ $missed_input ];
			}


		}
/*
		if( $pagenow == 'options.php' )
			print_r( $this->setting_values ); exit;*/

		return $inputs;
	}



	/**
	 * This function registers setting options
	 */
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



	/**
	 * This function adds the hidden fields to the setting form
	 */
	public function put_hidden_fields() {

		foreach ( $this->hidden_fields as $name => $value ) {
			echo '<input type="hidden" name="' . $name . '" value="' . $value . '"/>';
		}
	}



	/**
	 * This function runs all setting options. Also prints out as html output all of them.
	 *
	 * @return bool
	 */
	public function run() {

		if( ( empty( $this->page_name ) ) || ( !isset( $this->sections ) ) || ( !is_array( $this->sections ) ) )
			return false;

		// Display necessary hidden fields for settings
		settings_fields( $this->page_name );

		// Display the settings sections for the page
		foreach( $this->sections as $sec_name => $sec_value ) {
			do_settings_sections( $sec_name );
		}

		if( !empty( $this->segment ) )
			$this->add_hidden_field( 'segment_name', $this->segment );

		$this->add_hidden_field( 'setting_name', $this->settings_name );

		$this->put_hidden_fields();

		// Default Submit Button
		submit_button();

		return true;
	}



	function set_return_route() {

		if( ( $row_index = $this->last_index() ) !== false ) {
			$this->route( [ 'edit' => $row_index ] );
		}
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
