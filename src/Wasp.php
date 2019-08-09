<?php

/**
 * Wasp - WP Admin Settings Page
 * Settings Page Creator for Wordpress
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wasp
 * @author Uğur Biçer <uuur86@yandex.com>
 * @license GPLv3 or later
 * @version 2.1.2
 */

namespace WaspCreators;

if( class_exists( "\\WaspCreators\\Wasp" ) || !defined( 'ABSPATH' ) ) { return; }

class Wasp {

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

	// Array default values
	protected $default_values;

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

	/**
	* @since 1.2.0
	* Array latest field
	*/
	protected $current_field = null;

	/**
	* @since 1.2.0
	* Array latest section
	*/
	protected $current_section = null;

	/**
	* @since 1.2.0
	*/
	protected $form_updated = false;

	/**
	* @since 1.2.0
	*/
	protected $option_names = null;

	/**
	* @since 1.2.0
	*/
	protected $submit_name = null;

	/**
	* @since 1.2.0
	*/
	protected $is_ready = false;

	/**
	* @since 1.2.0
	*/
	protected $has_media = false;

	/**
	* @since 1.2.0
	*/
	protected $errors = [];

	/**
	* @since 2.0.2
	*/
	protected $user_func = null;



	public function __construct( $page, $setting_name, $domain, $row = false ) {
		global $pagenow;

		$this->page_name		= $page;
		$this->domain			= $domain;
		$this->settings_name	= $setting_name . '_settings';
		$this->index_name		= $setting_name . '_index';
		$this->option_names		= [
			'updated' 	=> $this->domain . '_form_updated_' . $this->settings_name,
			'errors'	=> $this->domain . '_form_errors_' . $this->settings_name,
		];

		if( !isset( $page ) || $page === false || empty( $page ) || !\is_admin() ) return;

		$this->is_ready = true;

		if( $pagenow !== 'options.php' ) {

			if( $pagenow !== 'admin.php' ) {
				$this->is_ready = false;
			}
			elseif( $_REQUEST[ 'page' ] !== $page ) {
				$this->is_ready = false;
			}
		}
		else if( !isset( $_REQUEST[ 'option_page' ] ) || $_REQUEST[ 'option_page' ] !== $page ) {
			$this->is_ready = false;

			return false;
		}

		if( $row !== false ) {
			$row = $this->_get( $row );

			if( ( $this->indexes = $this->get_indexes() ) !== false && is_array( $this->indexes ) ) {
				$last_index		= array_flip( $this->indexes );
				$last_index		= end( $last_index );
				$last_settings	= \get_option( $this->indexes[ $last_index ] );

				if( $last_settings !== false && empty( $row ) ) {
					$row = intval( $last_index );

					if( is_array( $last_settings ) && count( $last_settings ) > 0 ) $row++;
				}
			}

			if( $row < 1 ) $row = 1;

			$this->settings_row		= $row;
			$this->settings_name	= $setting_name . '_' . $this->settings_row . '_settings';

			if( false === \get_option( $this->index_name ) ) {
				\add_option( $this->index_name, [ $this->settings_name => $this->settings_row ] );
			}
			else {
				$this->indexes[ $this->settings_name ] = $this->settings_row;
				\update_option( $this->index_name, $this->indexes );
			}
		}

		$this->prepare_get_vars();

		if( false === \get_option( $this->settings_name, false ) ) {
			\add_option( $this->settings_name, [] );
		}
		else {
			$this->setting_values = \get_option( $this->settings_name );
		}

		// Checks whether the form is updated
		if( !$this->check_errors() && \get_option( $this->option_names[ 'updated' ] ) === '1' ) {
			$this->form_updated = true;
			\delete_option( $this->option_names[ 'updated' ] );
		}

		return $this;
	}


	function __call( $method, $args ) {
		echo 'Medhod name ' . $method . ' does not exists!';
	}



	/**
	* @since 1.2.0
	*/
	function is_ready() {
		return $this->is_ready;
	}



	function user_func_hook() {
		call_user_func( $this->user_func, $this );
	}



	/**
	* @since 1.2.0
	*/
	function wp_form_init( $hook ) {

		if( empty( $hook ) ) return;

		if( !is_array( $hook ) ) {
			$this->user_func = $hook;
			\add_action( "admin_init", [ $this, 'user_func_hook' ] );

			return;
		}

		\add_action( "admin_init", $hook );
	}



	protected function prepare_get_vars() {

		foreach( $_GET as $get_key => $get_val ) {
			$this->add_hidden_field( '__get__' . $get_key, $get_val );
		}
	}



	public function _get( $name ) {

		if( isset( $_GET[ $name ] ) ) return $_GET[ $name ];

		if( isset( $_POST[ '__get__' . $name ] ) ) return $_POST[ '__get__' . $name ];

		return null;
	}



	public function route( $link ) {

		if( is_array( $link ) ) $link = \add_query_arg( $link );

		$this->add_hidden_field( '_wp_http_referer', $link );
	}



	/**
	 * @param $name
	 * @param $title
	 * @param $desc
	 */
	public function add_section( $name, $title, $desc ) {

		if( empty( $name ) ) return;

		$this->section( $name, $title, $desc )->add();
	}



	public function section( $name, $title = null, $desc = null ) {
		$this->section = $name;

		$this->sections[ $this->section ] = [
			'title'	=> $title,
			'desc'	=> $desc,
		];

		$this->current_section			= $this->sections[ $this->section ];
		$this->current_section[ 'id' ]	= $this->section;

		return $this;
	}



	/**
	 * This function prints out the settings field section html code
	 */
	 public function settings_field_section_callback( $args ) {

		if( !isset( $args[ 'id' ] ) ) return;

		if( ( $sec_pos = strpos( $args[ 'id' ], '_section' ) ) === -1 ) return;

		$current = substr( $args[ 'id' ], 0, $sec_pos );
		$current = $this->sections[ $current ];

		if( !empty( $current[ 'html_before' ] ) ) echo $current[ 'html_before' ];

		echo '<h2 class="title">' . \esc_html__( $current[ 'title' ], $this->domain ) . '</h2>';
		echo '<p class="description">' . \esc_html__( $current[ 'desc' ], $this->domain ) . '</p>';

		if( !empty( $current[ 'html_after' ] ) ) echo $current[ 'html_after' ];
	}



	/**
	 * @since 1.2.0
	 * Delete setting which is named as $name or current setting
	 *
	 * @param string $name
	 */
	public function delete_setting( $name = null ) {

		if( empty( $name ) ) $name = $this->settings_name;

		// If setting has multiple rows then delete from settings index record
		if( ( $indexes = $this->get_indexes() ) !== false ) {
			$row = $indexes[ $name ];
			unset( $indexes[ $row ] );

			\update_option( $this->index_name, $indexes );
		}

		if( !empty( \get_option( $name, null ) ) ) {
			\delete_option( $name );
		}
	}



	/**
	 * @since 1.2.0
	 * Get particular setting value
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function get_value( $name ) {
		$value = $this->get_setting( $name );

		if( !isset( $this->default_values[ $name ] ) ) return $value;

		if( !empty( $this->default_values[ $name ] ) ) {
			$value = $this->default_values[ $name ];
		}

		return $value;
	}



	/**
	 * @since 1.2.0
	 * Get settings name in wp options table
	 *
	 * @return string
	 */
	public function get_settings_name() {
		return $this->settings_name;
	}



	/**
	 * Get all settings
	 *
	 * @return array|null
	 */
	public function get_settings() {

		if( empty( $this->setting_values ) ) return null;

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

		if( isset( $this->setting_values[ $name ] ) ) return $this->setting_values[ $name ];

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
		$this->input_fields[]	= $id;
		$sanitize_arr			= array( 'name' => $sanitize );

		if( $sanitize === 'options' ) {

			if( is_array( $options ) ) {
				$opt_arr_val = array_values( $options );

				if( is_array( $opt_arr_val[ 0 ] ) ) {
					$new_options = [];

					foreach( $opt_arr_val as $opt_arr_val_ ) {
						$new_options +=  $opt_arr_val_;
					}

					$options = $new_options;
				}

				$sanitize_arr[ 'values' ]	= array_keys( $options );
			}
		}
		else if( $sanitize === 'file' ) {
			$sanitize_arr[ 'mimes' ] = $options;
		}
		else if( $sanitize === 'regex' ) {
			$sanitize_arr[ 'pattern' ] = $options;
		}
		else {
			$sanitize_arr[ 'value' ] = $options;
		}

		$this->sanitize[ $id ] = $sanitize_arr;
	}



	/**
	 * This function adds new field to defined setting options
	 *
	 * @param string $type
	 * @param string $id
	 * @param string $label
	 * @param null|array $options
	 * @param string $sanitize
	 *
	 * @since 1.2.0
	 * DEPRECATED
	 * add_new_field function renamed as new_field
	 */
	public function add_new_field( $type, $id, $label, $options = null, $sanitize = 'text_field' ) {
		$this->field( $type, $id, $label, $options, $sanitize )->add();
	}



	/**
	* @since 1.2.0
	* This function adds new field to defined setting options
	*
	* @param string $type
	* @param string $id
	* @param string $label
	* @param null|array $options
	* @param string $sanitize
	*/
	public function field( $type, $id, $label, $options = null, $sanitize = 'text_field' ) {

		if( !empty( $this->current_field ) ) $this->add();

		if( $type === 'file_input' ) {
			$this->has_media	= true;
			$sanitize			= 'file';
		}

		if( in_array( $type, [ 'checkbox', 'radio', 'select' ] ) ) {
			$sanitize = 'options';
		}

		if( $type === 'multi_text_input' ) {
			$sanitize = 'multi_input';
		}

		$this->current_field = [
			'type'		=> $type,
			'id'		=> $id,
			'label'		=> \esc_attr__( $label, $this->domain ),
			'options'	=> $options,
			'sanitize'	=> $sanitize,
		];

		return $this;
	}



	/**
	* @since 1.2.0
	*/
	public function add() {

		if( !empty( $this->current_section[ 'id' ] ) ) {
			\add_settings_section(
				$this->current_section[ 'id' ] . '_section', // Section ID
				'',
				array( $this, 'settings_field_section_callback' ), // Admin page to add section to
				$this->current_section[ 'id' ] // Page
			);

			$this->current_section = null;
		}

		if( empty( $this->current_field[ 'id' ] ) ) return false;

		$args = $this->current_field;

		$field_type_callback = 'settings_field_' . $args[ 'type' ] . '_callback';

		$this->register_sanitize( $args[ 'type' ], $args[ 'id' ], $args[ 'options' ], $args[ 'sanitize' ] );

		$field_args = [
			'label' => $args[ 'label' ],
			'name' => $args[ 'id' ],
		];

		if( is_array( $args[ 'options' ] ) && !in_array( $args[ 'type' ], [ 'file_input' ] ) ) {
			$field_args[ 'options' ] = $args[ 'options' ];
		}

		\add_settings_field(
			$this->settings_name . '_' . $args[ 'id' ],
			$args[ 'label' ],
			array( $this, $field_type_callback ),
			$this->section,
			$this->section . '_section',
			$field_args
		);

		$this->current_field = null;
	}



	/**
	 * @since 1.2.0
	 * This function registers default value for any field
	 *
	 * @param string $id
	 * @param string $value
	 *
	 * @return bool
	 */
	public function default_value( $value ) {

		if( empty( $this->current_field[ 'id' ] ) ) return false;

		$id = $this->current_field[ 'id' ];

		if( !empty( $value ) ) $this->default_values[ $id ] = $value;

		return $this;
	}



	/**
	 * @since 1.2.0
	 * This function registers html code as manual
	 *
	 * @param string $id
	 * @param string $before
	 * @param string $after
	 */
	public function set_html( $before = null, $after = null ) {

		if( empty( $this->current_field ) && empty( $this->current_section ) ) return $this;

		if( !empty( $this->current_section[ 'id' ] ) ) {
			$this->sections[ $this->current_section[ 'id' ] ][ 'html_before' ]	= $before;
			$this->sections[ $this->current_section[ 'id' ] ][ 'html_after' ]	= $after;
		}

		$id = $this->current_field[ 'id' ];

		if( !empty( $before ) ) {
			$this->html_before[ $id ] = $before;
		}

		if( !empty( $after ) ) {
			$this->html_after[ $id ] = $after;
		}

		return $this;
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
		$id		= \esc_html( $id );
		$value	= \esc_attr( $value );

		if( $save ) {
			$this->register_sanitize( 'text', $id, $value, '' );
		}

		$this->hidden_fields[ $id ] = $value;
	}



	public function form_success() {
		return $this->form_updated;
	}



	/**
	 *
	 * @since 1.2.0
	 */
	public function callback_output( $html, $id = null ) {
		$default_attr	= [
			'id'	=> [],
			'name'	=> [],
			'href'	=> [],
			'src'	=> [],
			'type'	=> [],
			'class'	=> [],
			'style'	=> [],
		];
		$output			= '';
		$allowed_tags	= [
			'input'		=> $default_attr,
			'select'	=> $default_attr,
			'option'	=> $default_attr,
			'radio'		=> $default_attr,
			'a'			=> $default_attr,
			'img'		=> $default_attr,
			'br'		=> $default_attr,
			'div'		=> $default_attr,
			'hr'		=> $default_attr,
		];

		if( !empty( $this->current_field[ 'id' ] ) ) {
			$id = $this->current_field[ 'id' ];
		}

		$before	= isset( $this->html_before[ $id ] ) ? $this->html_before[ $id ] : null;
		$after	= isset( $this->html_after[ $id ] ) ? $this->html_after[ $id ] : null;

		if( !empty( $before ) ) $output .= \wp_kses( $before, $allowed_tags );

		$output .= $html;

		if( !empty( $after ) ) $output .= \wp_kses( $after, $allowed_tags );

		$this->html_before[ $id ]	= null;
		$this->html_after[ $id ]	= null;

		return $output;
	}



	public function settings_field_text_input_callback( $args ) {
		$value		= $this->get_value( $args[ 'name' ] );
		$field_id	= $this->settings_name . '_' . $args[ 'name' ] . '_input_text';
		$field_name	= $this->settings_name . '[' . $args[ 'name' ] . ']';

		if( !empty( $value ) ) {
			$value = \esc_attr( $value );
		}

		echo $this->callback_output( '<input type="text" id="' . $field_id . '" name="' . $field_name . '" value="' . $value . '" placeholder="' . $args[ 'label' ] . '"/>', $args[ 'name' ] );
	}



	public function settings_field_file_input_callback( $args ) {
		$field_id	= $this->settings_name . '_' . $args[ 'name' ] . '_input_text';
		$field_name	= $this->settings_name . '[' . $args[ 'name' ] . ']';

		echo $this->callback_output( '<input type="file" id="' . $field_id . '" name="' . $field_name . '"/>', $args[ 'name' ] );
	}



	public function settings_field_multi_text_input_callback( $args ) {
		$value		= $this->get_value( $args[ 'name' ] );
		$options	= '<fieldset>';

		if( is_array( $args[ 'options' ] ) ) {

			foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
				$value_		= ( empty( $value[ $opt_key ] ) ) ? $opt_key : \esc_attr( $value[ $opt_key ] );

				$id			= $this->settings_name . '_' . $args[ 'name' ] . '_input_checkbox_' . $opt_key;
				$name		= $this->settings_name . '[' . $args[ 'name' ] . '][' . $opt_key . ']';

				$options	.= '<span>';
				$options	.= '	<input type="text"  name="' . $name . '" id="' . $id . '" value="' . $value_ . '" />';
				$options	.= '	<label for="' . $id . '">' . $opt_val . '</label>';
				$options	.= '</span>';
			}
		}

		$options .= '</fieldset>';

		echo $this->callback_output( $options, $args[ 'name' ] );
	}



	public function settings_field_select_callback( $args ) {
		$value		= $this->get_value( $args[ 'name' ] );
		$value		= ( empty( $value ) ) ? : \esc_attr( $value );
		$html_inner	= '';

		$set_options = function( $options, $value ) use ( &$set_options ) {
			$return = '';

			foreach( $options as $opt_key => $opt_val ) {

				if( is_array( $opt_val ) ) {
					$return .= '<optgroup label="' . $opt_key . '">' . $set_options( $opt_val, $value ) . '</optgroup>';
				}
				else {
					$return .= '<option value="' . $opt_key . '" ' . \selected( $opt_key, $value, false ) . '>' . $opt_val . '</option>';
				}
			}

			return $return;
		};

		if( is_array( $args[ 'options' ] ) ) {
			$options	= $args[ 'options' ];
			$html_inner = $set_options( $options, $value );
		}

		$html	= '<select id="' . $this->settings_name . '_' . $args[ 'name' ] . '_input_text" name="' . $this->settings_name . '[' . $args[ 'name' ] . ']">';
		$html	.= $html_inner;
		$html	.= '</select>';

		echo $this->callback_output( $html, $args[ 'name' ] );
	}



	public function settings_field_checkbox_callback( $args ) {
		$value		= $this->get_value( $args[ 'name' ] );
		$options	= '<fieldset>';

		if( is_array( $args[ 'options' ] ) ) {

			foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
				$value_ = '';

				if( !empty( $value[ $opt_key ] ) ) {
					$value_ = \esc_attr( $value[ $opt_key ] );
				}

				$id		= $this->settings_name . '_' . $args[ 'name' ] . '_input_checkbox_' . $opt_key;
				$name	= $this->settings_name . '[' . $args[ 'name' ] . '][' . $opt_key . ']';

				$options .= '<span>';
				$options .= '	<input type="checkbox"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . \checked( $opt_key, $value_, false ) . '/>';
				$options .= '	<label for="' . $id . '">' . $opt_val . '</label>';
				$options .= '</span>';
			}
		}

		$options .= '</fieldset>';

		echo $this->callback_output( $options, $args[ 'name' ] );
	}



	public function settings_field_radio_callback( $args ) {
		$value		= $this->get_value( $args[ 'name' ] );
		$value		= ( empty( $value ) ) ? : \esc_attr( $value );
		$options	= '<fieldset>';

		if( is_array( $args[ 'options' ] ) ) {

			foreach( $args[ 'options' ] as $opt_key => $opt_val ) {
				$id		= $this->settings_name . '_' . $args[ 'name' ] . '_input_radio_' . $opt_key;
				$name	= $this->settings_name . '[' . $args[ 'name' ] . ']';

				$options .= '<span>';
				$options .= '	<input type="radio"  name="' . $name . '" id="' . $id . '" value="' . $opt_key . '" ' . \checked( $opt_key, $value, false ) . '/>';
				$options .= '	<label for="' . $id . '">' . $opt_val . '</label>';
				$options .= '</span>';
			}
		}

		$options .= '</fieldset>';

		echo $this->callback_output( $options, $args[ 'name' ] );
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

		foreach( $this->sanitize as $input_key => $prop ) {
			// Sanitize and set external hidden post values into setting form values
			if( !isset( $inputs[ $input_key ] ) ){

				if( isset( $_POST[ $input_key ] ) ) {
					$inputs[ $input_key ] = \sanitize_post( $_POST[ $input_key ] );

					continue;
				}
			}

			// No sanitization
			if( !isset( $prop[ 'name' ] ) ) continue;

			// Sanitize by regular expression rules - regex
			if( $prop[ 'name' ] === 'regex' && isset( $prop[ 'pattern' ] ) ) {

				if( preg_match( "#^" . $prop[ 'pattern' ] . "$#ui", $inputs[ $input_key ], $matched ) ) {
					$inputs[ $input_key ] = $matched[ 0 ];
				}
				else $inputs[ $input_key ] = '';

				continue;
			}

			// Sanitize options which has predetermined values
			if( $prop[ 'name' ] === 'options' && is_array( $prop[ 'values' ] ) ) {
				$opt_val = $inputs[ $input_key ];

				if( is_array( $opt_val ) ) {
					$opt_val = array_intersect( $prop[ 'values' ], $opt_val );

					if( is_array( $opt_val ) ) {
						$inputs[ $input_key ] = array_combine( $opt_val, $opt_val );
					}
				}
				elseif( !in_array( $opt_val, $prop[ 'values' ] ) ) {
					unset( $inputs[ $input_key ] );
				}

				continue;
			}

			// Sanitize multi-text input
			if( $prop[ 'name' ] === 'multi_input' ) {
				$opt_val 			= $inputs[ $input_key ];
				$sanitized_inputs	= [];

				foreach( $opt_val as $key => $value ) {
					$sanitized_inputs[ $key ] = \sanitize_post( $value );
				}

				$inputs[ $input_key ] = $sanitized_inputs;

				continue;
			}

			// Sanitize file uploads
			if( $prop[ 'name' ] === 'file' ) {

				if( isset( $_FILES[ $this->settings_name ] ) ) {
					$file_handler	= $_FILES[ $this->settings_name ];
				}

				if( !isset( $file_handler[ 'name' ][ $input_key ] ) || empty( $file_handler[ 'name' ][ $input_key ] ) ) {
					$inputs[ $input_key ] = null;
					continue;
				}

				$mimes = $prop[ 'mimes' ];

				if( empty( $mimes ) ) {
					$mimes = 'text/*';
				}

				$new_handler	= [
					'name'		=> $file_handler[ 'name' ][ $input_key ],
					'type'		=> $file_handler[ 'type' ][ $input_key ],
					'tmp_name'	=> $file_handler[ 'tmp_name' ][ $input_key ],
					'error'		=> $file_handler[ 'error' ][ $input_key ],
					'size'		=> $file_handler[ 'size' ][ $input_key ],
				];

				if ( !function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$form_file = \wp_handle_upload( $new_handler, array( 'test_form' => false ) );

				if( isset( $form_file[ 'error' ] ) ) {
					$this->add_error( "<p>" . $form_file[ 'error' ] . "</p>" );
					$form_file = null;
				}
				elseif( $form_file && \wp_match_mime_types( $mimes, $form_file[ 'type' ] ) ) {
					// ...
				}
				else {
					$this->add_error( "<p>" . \esc_html__( 'UPLOAD ERROR : Unsupported mime type', $this->domain ) . "</p>" );
					$form_file = null;
				}

				$inputs[ $input_key ] = $form_file;

				continue;
			}

			// Other sanitization functions for example sanitize_text_field
			$func_name = 'sanitize_' . $prop[ 'name' ];

			if( !function_exists( $func_name ) ) continue;

			if( isset( $inputs[ $input_key ] ) ) {
				$inputs[ $input_key ] = call_user_func( $func_name, $inputs[ $input_key ] );
			}
		}

		// Mark it as updated ( settiings saved )
		\update_option( $this->option_names[ 'updated' ], '1' );

		$this->save_errors();

		// Merge with prior saved settings
		$inputs += $this->setting_values;

		return $inputs;
	}



	protected function add_error( $error ) {

		if( !is_array( $this->errors ) ) {
			$this->errors = [];
		}

		$this->errors[] = $error;
	}



	/**
	 *
	 *
	 * @return bool
	 */
	public function check_errors() {
		$errors = $this->get_errors();

		return ( is_array( $errors ) && count( $errors ) > 0 );
	}



	/**
	 * Gets saved errors from the wp options
	 *
	 * @return array
	 */
	public function get_errors() {

		if( $this->errors !== false && empty( $this->errors ) ) {
			$this->errors = \get_option( $this->option_names[ 'errors' ] );
			\delete_option( $this->option_names[ 'errors' ] );
		}

		return $this->errors;
	}



	/**
	 * Saves occured errors to option table
	 */
	protected function save_errors() {

		if( !empty( $this->errors ) ) {
			\update_option( $this->option_names[ 'errors' ], $this->errors );
		}
	}



	/**
	 * This function registers setting options
	 */
	public function register() {
		global $_POST;

		if( ( $all_settings = $this->get_indexes() ) !== false && isset( $_POST[ 'setting_name' ] ) ) {
			$requested_option_page = $_POST[ 'setting_name' ];

			if( array_key_exists( $requested_option_page, $all_settings ) ) {
				\register_setting( $this->page_name, $requested_option_page, array( $this, 'settings_input_middleware' ) );
			}
		}
		else {
			\register_setting( $this->page_name, $this->settings_name, array( $this, 'settings_input_middleware' ) );
		}
	}



	/**
	 * This function adds the hidden fields to the setting form
	 */
	public function put_hidden_fields() {

		foreach( $this->hidden_fields as $name => $value ) {
			echo '<input type="hidden" name="' . $name . '" value="' . $value . '"/>';
		}
	}



	/**
	 * This function runs all setting options. Also prints out as html output all of them.
	 */
	 public function run( $section = null, $button_text = null ) {
 		$this->form_start( $section );
 		$this->run_section( $section );
 		$this->form_end( $section );
 	}



	/**
	 * Form starter
	 */
	public function form() {

		if( $this->has_media ) echo '<form method="post" action="options.php" enctype="multipart/form-data">';
		else echo '<form method="post" action="options.php">';
	}



	/**
	 * Manual way to running form. This function runs all or particular setting section.
	 *
	 * @param string $section
	 *
	 * @since 1.3.0
	 */
	public function run_section( $section = null ) {

		if( empty( $section ) ) {

			if( !is_array( $this->sections ) ) return;

			// Display the settings sections for the page
			foreach( $this->sections as $sec_name => $sec_value ) {
				\do_settings_sections( $sec_name );
			}
		}
		else {
			\do_settings_sections( $section );
		}
	}



	/**
	 * Manual way to running form. That prints all html form starter tags and fires all start process.
	 * @since 1.3.0
	 */
	public function form_start( $section = null ) {

		if( ( empty( $this->page_name ) ) || ( !isset( $this->sections ) ) || ( !is_array( $this->sections ) ) ) {
			return false;
		}

		// Print HTML form tag
		$this->form();

		// Display necessary hidden fields for settings
		\settings_fields( $this->page_name );

		$this->add_hidden_field( 'setting_name', $this->settings_name );
		$this->put_hidden_fields();
	}



	/**
	 * Manual way to running form. That prints all html form ender tags and fires all end process.
	 * @since 1.3.0
	 */
	public function form_end( $section = null ) {
		$button_text = null;

		if( empty( $section ) ) {

			if( is_array( $this->submit_name ) ) $button_text = end( $this->submit_name );
			else $button_text = $this->submit_name;
		}
		elseif( isset( $this->submit_name[ $section ] ) ) {
			$button_text = $this->submit_name[ $section ];
		}

		$this->submit( $button_text );

		echo "</form>";
	}



	/**
	 * @since 1.2.0
	 */
	public function submit( $name, $echo = true ) {

		if( !$echo ) return \get_submit_button( $name );

		echo \submit_button( $name );
	}



	public function submit_name( $name ) {
		$this->submit_name[ $this->section ] = $name;
	}



	function set_return_route() {

		if( ( $row_index = $this->last_index() ) !== false ) {
			$this->route( [ 'edit' => $row_index ] );
		}
	}



	/**
	 * Gets the index_name if exists therefore return as false
	 *
	 * @return string|bool
	 */
	public function get_indexes() {

		if( empty( $this->index_name ) ) return false;

		return \get_option( $this->index_name );
	}



	/**
	 * Removes all setting fields from the database
	 *
	 * @since 2.1.2
	 */
	public function remove_settings() {
		// If setting has multiple rows then delete from settings index record
		if( ( $indexes = $this->get_indexes() ) !== false ) {

			foreach( $indexes as $key => $value ) {
				\delete_option( $value );
			}

			\delete_option( $this->index_name );
		}

		foreach( $this->option_names as $key => $value ) {
			\delete_option( $value );
		}

		if( \get_option( $this->settings_name ) !== false ) {
			\delete_option( $this->settings_name );
		}
	}



	/**
	 * Gets the latest setting row name if exists therefore return as false
	 *
	 * @return string|bool
	 */
	public function last_index() {

		if( $this->settings_row > -1 ) return $this->settings_row;

		return false;
	}
}
