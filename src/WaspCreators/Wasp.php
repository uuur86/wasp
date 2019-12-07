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
 * @version 2.3.1
 */

namespace WaspCreators;

if ( class_exists( "\\WaspCreators\\Wasp" ) || ! defined( 'ABSPATH' ) ) {
	return;
}

use WaspCreators\Ajax;
use WaspCreators\Fields;
use WaspCreators\Templates;


class Wasp {
	const VERSION = '2.3.1';

	/**
	 * Form title
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Form description
	 *
	 * @var string
	 */
	protected $desc;

	/**
	 * WP language domain name
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * Current page name
	 *
	 * @var string
	 */
	protected $pageName;

	/**
	 * Contains all saved setting values
	 *
	 * @var array
	 */
	protected $settingValues = null;

	/**
	 * Default values
	 *
	 * @var array
	 */
	protected $defaultValues;

	/**
	 * Current settings form name
	 *
	 * @var string
	 */
	protected $settingsName;

	/**
	 * Current settings row number
	 *
	 * @var string
	 */
	protected $settingsRow;

	/**
	 * Current index name
	 *
	 * @var string
	 */
	protected $indexName;

	/**
	 * All indexes
	 *
	 * @var array
	 */
	protected $indexes;

	/**
	 * All input fields will be sanitized
	 *
	 * @var array
	 */
	protected $inputFields = null;

	/**
	 * The other hidden form fields
	 *
	 * @var array
	 */
	protected $hiddenFields;

	/**
	 * Sanitize info
	 *
	 * @var array
	 */
	protected $sanitize;

	/**
	 * All sections index
	 *
	 * @var array
	 */
	protected $sections;

	/**
	 * Section name
	 *
	 * @var string
	 */
	protected $section;

	/**
	* @since 1.2.0
	* Current field
	*
	* @var array
	*/
	protected $currentField = null;

	/**
	* @since 1.2.0
	* Current section
	*
	* @var array
	*/
	protected $currentSection = null;

	/**
	* @since 1.2.0
	* Is form updated
	*
	* @var boolean
	*/
	protected $formUpdated = false;

	/**
	* @since 1.2.0
	* Option names
	*
	* @var array
	*/
	protected $optionNames = null;

	/**
	* @since 1.2.0
	* Submit name(s)
	*
	* @var string|array
	*/
	protected $submitName = null;

	/**
	* @since 1.2.0
	* Is form ready?
	*
	* @var string
	*/
	protected $isReady = false;

	/**
	* @since 1.2.0
	* Does form has include any file uploading?
	*
	* @var boolean
	*/
	protected $hasMedia = false;

	/**
	* @since 1.2.0
	* Form errors
	*
	* @var array
	*/
	protected $errors = [];

	/**
	* @since 2.0.2
	* Method array or string function name for form init
	*
	* @var string|array
	*/
	protected $userFunc = null;

	/**
	* @since 2.2.0
	* Form items for ajax fields
	*
	* @var array
	*/
	protected $ajaxLoads = null;

	/**
	* @since 2.2.0
	* Ajax object container
	*
	* @var Ajax
	*/
	protected $ajax = null;

	/**
	* @since 2.2.0
	* After HTML code container for current form item
	*
	* @var string
	*/
	protected $htmlAfter = null;

	/**
	* @since 2.2.0
	* Before HTML code container for current form item
	*
	* @var string
	*/
	protected $htmlBefore = null;

	/**
	* @since 2.2.1
	* Contains setting rows if multi row settings is enabled
	*
	* @var array
	*/
	protected $rows = null;



	/**
	 * @param string $page Unique page name
	 * @param string $setting_name Setting name will be saved in options
	 * @param string $domain Domain for localize
	 * @param string $row Get variable name for multiple form row
	 */
	public function __construct( $page, $setting_name, $domain, $row = null )
	{
		global $pagenow;

		if ( ! isset( $page ) || empty( $page ) ) return;

		$this->ajax				= new Ajax( $domain );
		$this->pageName			= $page;
		$this->domain			= $domain;
		$this->settingsName		= $setting_name . '_settings';
		$this->indexName		= $setting_name . '_index';
		$this->optionNames		= [
			'updated'	=> $this->settingsName . '_form_updated',
			'errors'	=> $this->settingsName . '_form_errors',
		];

		// for multiple settings field
		if ( ! empty( $row ) ) {
			$row = $this->_get( $row );
			$this->indexes = $this->_getIndexes();

			// Find the latest row value
			if ( ! empty( $this->indexes ) && is_array( $this->indexes ) ) {
				$indexes		= $this->indexes;
				$last_row		= end( $indexes );
				$indexes		= array_flip( $indexes );
				$last_settings	= \get_option( $indexes[ $last_row ] );

				// If row value hasn`t set by get value
				if ( empty( $row ) ) {
					$row = intval( $last_row );

					// Increases row value when the latest setting has been setted
					if ( is_array( $last_settings ) && count( $last_settings ) > 0 ) {
						$row++;
					}
				}
			}

			// Make sure to row value equal to least 1
			if( $row < 1 ) $row = 1;

			// Append the setting row
			$this->settingsRow	= $row;

			// Append the new multiple settingsName which is affected by row
			$this->settingsName	= $setting_name . '_' . $this->settingsRow . '_settings';

			// Save index data
			if ( false === \get_option( $this->indexName ) ) {
				// Add index option value for first time
				\add_option( $this->indexName, [ $this->settingsName => $this->settingsRow ] );
			} else {
				// Update indexes
				$this->indexes[ $this->settingsName ] = $this->settingsRow;

				// Update index option value
				\update_option( $this->indexName, $this->indexes );
			}
		}

		// Append settingValues
		$settings = \get_option( $this->settingsName );

		if ( false === $settings ) {
			\add_option( $this->settingsName, [] );
		} else {
			$this->settingValues = $settings;
		}

		$this->isReady = true;

		// returns if in wp option backend process
		if ( $pagenow === 'options.php' ) {
			if ( ! isset( $_REQUEST[ 'option_page' ] ) || $_REQUEST[ 'option_page' ] !== $page ) {
				$this->isReady = false;
			}

			return;
		}

		if ( $pagenow !== 'admin.php' ) {
			$this->isReady = false;
		} elseif ( $_REQUEST[ 'page' ] !== $page ) {
			$this->isReady = false;
		}

		// Prepare the form
		$this->_prepareGetVars();

		// Checks whether the form is updated
		$updated_name		= $this->optionNames[ 'updated' ];
		$option_updated	= \get_option( $updated_name );

		if ( ! $this->check_errors() && $option_updated === '1' ) {
			$this->formUpdated = true;
			\delete_option( $updated_name );
		}
	}


	/**
	 * Catches wrong method names
	 */
	public function __call( $method, $args )
	{
		echo 'Medhod name ' . $method . ' does not exists!';
	}



	/**
	 * @since 1.2.0
	 */
	public function is_ready()
	{
		return $this->isReady;
	}



	/**
	 * WP Hook
	 */
	public function userFuncHook()
	{
		call_user_func( $this->userFunc, $this );
	}



	/**
	 * Inıts the form actions
	 * @since 1.2.0
	 *
	 * @param string|array
	 */
	public function wp_form_init( $hook )
	{
		if ( empty( $hook ) ) return;

		if ( ! is_array( $hook ) ) {
			$this->userFunc = $hook;
			\add_action( "admin_init", [ $this, 'userFuncHook' ] );

			return;
		}

		\add_action( "admin_init", $hook );
	}


	/**
	 * Passes all get variables as hidden inputs in form
	 */
	protected function _prepareGetVars()
	{
		foreach ( $_GET as $get_key => $get_val ) {
			$this->add_hidden_field( '__get__' . $get_key, $get_val );
		}
	}



	/**
	 * for fetching data from post or get form data
	 *
	 * @param string $name
	 *
	 * @return string|null
	 */
	public function _get( $name )
	{
		if ( isset( $_GET[ $name ] ) ) {
			return $_GET[ $name ];
		}

		if ( isset( $_POST[ '__get__' . $name ] ) ) {
			return $_POST[ '__get__' . $name ];
		}

		return null;
	}



	/**
	 * Sets route url to redirect
	 *
	 * @param string $link url of the redirect address
	 */
	public function route( $link )
	{
		if ( is_array( $link ) ) {
			$link = \add_query_arg( $link );
		}

		$this->add_hidden_field( '_wp_http_referer', $link );
	}



	/**
	 * @param string $name
	 * @param string $title
	 * @param string $desc
	 */
	public function section( $args )
	{
		if ( ! is_array( $args ) ) {
			return $this;
		}

		// Defaults
		$accepted = [
			'cond'		=> true,
			'name'		=> null,
			'title'		=> null,
			'desc'		=> null,
			'after'		=> null,
			'before'	=> null,
		];

		$args = array_intersect_key( $args, $accepted );
		$args = array_merge( $accepted, $args );
		extract( $args );

		if ( empty( $name ) ) {
			return $this;
		}

		if ( ! $cond ) {
			return $this;
		}

		$this->section = $name;

		$this->sections[ $this->section ] = [
			'title'	=> $title,
			'desc'	=> $desc,
		];

		$this->currentSection = $this->sections[ $this->section ];
		$this->currentSection[ 'id' ] = $this->section;

		if ( ! empty( $after ) || ! empty( $before ) ) {
			$this->_setHtml( $before, $after );
		}

		return $this;
	}



	/**
	 * This function prints out the section html code
	 *
	 * @param array $args callback paramters
	 */
	 public function sectionCallback( $args )
	 {
		if ( ! isset( $args[ 'id' ] ) ) return;

		$sec_pos = strpos( $args[ 'id' ], '_section' );

		if ( $sec_pos === -1 ) return;

		$current = substr( $args[ 'id' ], 0, $sec_pos );
		$current = $this->sections[ $current ];

		if ( ! empty( $current[ 'html_before' ] ) ) {
			echo $current[ 'html_before' ];
		}

		echo '<h2 class="title">' . \esc_html__( $current[ 'title' ], $this->domain ) . '</h2>';
		echo '<p class="description">' . \esc_html__( $current[ 'desc' ], $this->domain ) . '</p>';

		if ( ! empty( $current[ 'html_after' ] ) ) {
			echo $current[ 'html_after' ];
		}
	}



	/**
	 * @since 1.2.0
	 * Delete setting which is named as $name or current setting
	 *
	 * @param string $name
	 */
	public function delete_setting( $name = null )
	{
		if ( empty( $name ) ) {
			$name = $this->settingsName;
		}

		$indexes = $this->_getIndexes();

		// If setting has multiple rows then delete from settings index record
		if ( $indexes !== false ) {
			unset( $indexes[ $name ] );
			\update_option( $this->indexName, $indexes );
		}

		if ( ! empty( \get_option( $name, null ) ) ) {
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
	public function get_value( $name )
	{
		$value = $this->get_setting( $name );

		if ( ! isset( $this->defaultValues[ $name ] ) ) {
			return $value;
		}

		if ( ! empty( $this->defaultValues[ $name ] ) ) {
			$value = $this->defaultValues[ $name ];
		}

		return $value;
	}



	/**
	 * @since 1.2.0
	 * Get settings name in wp options table
	 *
	 * @return string
	 */
	public function get_settings_name()
	{
		return $this->settingsName;
	}



	/**
	 * Get all settings
	 *
	 * @return array|null
	 */
	public function get_settings()
	{
		if ( empty( $this->settingValues ) ) return null;

		return $this->settingValues;
	}



	/**
	 * This function gets the wordpress options about defined setting
	 *
	 * @param $name
	 *
	 * @return string
	 */
	public function get_setting( $name )
	{
		if ( isset( $this->settingValues[ $name ] ) ) {
			return $this->settingValues[ $name ];
		}

		return null;
	}



	/**
	 * It registers all form sections and fields
	 * @since 2.2.1
	 *
	 * @param array $args
	 * @return bool
	 */
	public function loadForm( $args )
	{
		if ( ! is_array( $args ) ) {
			return false;
		}

		if ( isset( $args[ 'name' ] ) ) {
			$args = [ $args ];
		}

		foreach ( $args as $section ) {
			// Section definitions
			$this->section( $section )->add();

			foreach ( $section[ 'items' ] as $item ) {
				$this->field( $item )->add();
			}
		}

		return true;
	}



	/**
	 * It registers the values to sanitize array for using in middleware controls
	 *
	 * @param string $type
	 * @param string $id
	 * @param array|string $options
	 * @param string $sanitize [ options, regex, text_field, textarea_field, email, file_name etc.. ]
	 */
	protected function _registerSanitize( $type, $id, $options, $sanitize )
	{
		$this->inputFields[] = $id;
		$sanitize_arr = array( 'name' => $sanitize );

		if ( $sanitize === 'options' ) {
			if ( is_array( $options ) ) {
				$opt_arr_val = array_values( $options );

				if ( is_array( $opt_arr_val[ 0 ] ) ) {
					$new_options = [];

					foreach ( $opt_arr_val as $opt_arr_val_ ) {
						$new_options +=  $opt_arr_val_;
					}

					$options = $new_options;
				}

				$sanitize_arr[ 'values' ]	= array_keys( $options );
			}
		} elseif ( $sanitize === 'file' ) {
			$sanitize_arr[ 'mimes' ] = $options;
		} elseif ( $sanitize === 'regex' ) {
			$sanitize_arr[ 'pattern' ] = $options;
		} else {
			$sanitize_arr[ 'value' ] = $options;
		}

		$this->sanitize[ $id ] = $sanitize_arr;
	}



	/**
	* This function adds new field to defined setting options
	* @since 1.2.0
	*
	* @param array $args
	* @return $this
	*/
	public function field( $args )
	{
		if ( ! is_array( $args ) ) {
			return $this;
		}

		// Defaults
		$accepted = [
			'cond'		=> true,
			'type'		=> null,
			'name'		=> null,
			'label'		=> null,
			'value'		=> null,
			'default'	=> null,
			'option'	=> null,
			'options'	=> null,
			'after'		=> null,
			'before'	=> null,
			'ajax'		=> null,
			'sanitize'	=> 'text_field',
			'save'		=> null,
		];

		$args = array_intersect_key( $args, $accepted );
		$args = array_merge( $accepted, $args );
		extract( $args );

		if ( ! $cond ) {
			return $this;
		}

		if ( $type === 'hidden' ) {
			$this->add_hidden_field(
				$name,
				$value,
				$save
			);

			return $this;
		}

		if ( ! empty( $this->currentField ) ) {
			$this->add();
		}

		if ( ! empty( $ajax ) ) {
			$sanitize = $type === 'regex' ? 'regex' : '';
		} elseif ( $type === 'file_input' ) {
			$this->hasMedia = true;
			$sanitize = 'file';
		} elseif ( in_array( $type, [ 'radio', 'select' ] ) ) {
			$sanitize = 'options';
		} elseif ( in_array( $type, [ 'checkbox', 'onecheckbox' ] ) ) {
			$sanitize = 'checkbox';
		} elseif ( $type === 'multi_text_input' ) {
			$sanitize = 'multi_input';
		}

		$item_id	= $this->settingsName . '_' . $name . '_' . $type;
		$item_name	= $this->settingsName . '[' . $name . ']';

		$this->currentField = [
			'key'		=> $name,
			'type'		=> $type,
			'id'		=> $item_id,
			'name'		=> $item_name,
			'label'		=> \esc_attr__( $label, $this->domain ),
			'option'	=> $option,
			'options'	=> $options,
			'sanitize'	=> $sanitize,
		];

		if ( ! empty( $default ) ) {
			$this->default_value( $default );
		}

		if ( ! empty( $after ) || ! empty( $before ) ) {
			$this->_setHtml( $before, $after );
		}

		if ( ! empty( $ajax ) ) {
			$this->_ajaxLoader( $ajax );
		}

		return $this;
	}



	/**
	* @since 1.2.0
	*/
	public function add()
	{
		if ( ! empty( $this->currentSection[ 'id' ] ) ) {
			$section_id = $this->currentSection[ 'id' ];

			if ( ! empty( $this->htmlBefore ) ) {
				$this->sections[ $section_id ] = $this->htmlBefore;
				$this->htmlBefore = null;
			}

			if ( ! empty( $this->htmlAfter ) ) {
				$this->sections[ $section_id ] = $this->htmlAfter;
				$this->htmlAfter = null;
			}

			\add_settings_section(
				$section_id . '_section', // Section ID
				'',
				array( $this, 'sectionCallback' ), // Admin page to add section to
				$section_id // Page
			);

			$this->currentSection = null;
		}

		if ( empty( $this->currentField[ 'id' ] ) ) return false;

		$args = $this->currentField;

		$this->_registerSanitize(
			$args[ 'type' ],
			$args[ 'key' ],
			$args[ 'options' ],
			$args[ 'sanitize' ]
		);

		$field_args = [
			'type'		=> $args[ 'type' ],
			'id'		=> $args[ 'id' ],
			'name'		=> $args[ 'name' ],
			'value'		=> $this->get_value( $args[ 'key' ] ),
		];

		if ( isset( $args[ 'option' ] ) ) {
			$field_args[ 'option' ] = $args[ 'option' ];
		}

		if ( is_array( $args[ 'options' ] ) && ! in_array( $args[ 'type' ], [ 'file_input' ] ) ) {
			$field_args[ 'options' ] = $args[ 'options' ];
		}

		if ( ! empty( $this->htmlBefore ) ) {
			$field_args[ 'html_before' ] = $this->htmlBefore;
			$this->htmlBefore = null;
		}

		if ( ! empty( $this->htmlAfter ) ) {
			$field_args[ 'html_after' ] = $this->htmlAfter;
			$this->htmlAfter = null;
		}

		if ( ! empty( $this->ajaxLoads[ $args[ 'key' ] ] ) ) {
			$field_args[ 'options' ] = [ '' ];
		}

		\add_settings_field(
			$this->settingsName . '_' . $args[ 'key' ],
			$args[ 'label' ],
			'\WaspCreators\Fields::callback',
			$this->section,
			$this->section . '_section',
			$field_args
		);

		$this->currentField = null;
	}



	/**
	 * @since 1.2.0
	 * This function registers default value for any field
	 *
	 * @param string $value
	 *
	 * @return Wasp
	 */
	public function default_value( $value )
	{
		if ( empty( $this->currentField[ 'key' ] ) ) return false;

		$key = $this->currentField[ 'key' ];

		if ( ! empty( $value ) ) {
			$this->defaultValues[ $key ] = $value;
		}

		return $this;
	}



	/**
	 * @since 1.2.0
	 * This function registers html code as manual
	 *
	 * @param string $before
	 * @param string $after
	 *
	 * @return Wasp
	 */
	protected function _setHtml( $before = null, $after = null )
	{
		if ( empty( $this->currentField ) && empty( $this->currentSection ) ) {
			return $this;
		}

		if ( ! empty( $before ) ) {
			$this->htmlBefore = $before;
		}

		if ( ! empty( $after ) ) {
			$this->htmlAfter = $after;
		}

		return $this;
	}



	/**
	 * This function registers hidden field for moving variable data
	 * to options page without creating an option
	 *
	 * @param string $id
	 * @param string $value
	 * @param bool $save
	 */
	public function add_hidden_field( $id, $value, $save = false )
	{
		$id		= \esc_attr( $id );
		$value	= \esc_attr( $value );

		if ( $save ) {
			$this->_registerSanitize( 'text', $id, $value, '' );
		}

		$this->hiddenFields[ $id ] = $value;
	}



	public function form_success()
	{
		return $this->formUpdated;
	}



	/**
	 * This function sanitizes defined setting options - WP Hook
	 *
	 * @param $inputs
	 *
	 * @return mixed
	 */
	public function settingsInputMiddleware( $inputs )
	{
		global $pagenow;

		foreach ( $this->sanitize as $input_key => $prop ) {
			// Sanitize and set external hidden post values into setting form values
			if ( ! isset( $inputs[ $input_key ] ) ) {
				if ( isset( $_POST[ $input_key ] ) ) {
					$inputs[ $input_key ] = \sanitize_post( $_POST[ $input_key ] );

					continue;
				}
			}

			// No sanitization
			if ( ! isset( $prop[ 'name' ] ) ) continue;

			if ( $prop[ 'name' ] === 'checkbox' ) {
				if ( ! isset( $inputs[ $input_key ] ) ) {
					$inputs[ $input_key ] = null;
				}

				continue;
			}

			// Sanitize by regular expression rules - regex
			if ( $prop[ 'name' ] === 'regex' && isset( $prop[ 'pattern' ] ) ) {

				if ( preg_match( "#^" . $prop[ 'pattern' ] . "$#ui", $inputs[ $input_key ], $matched ) ) {
					$inputs[ $input_key ] = $matched[ 0 ];
				} else {
					$inputs[ $input_key ] = '';
				}

				continue;
			}

			// Sanitize options which has predetermined values
			if ( $prop[ 'name' ] === 'options' && is_array( $prop[ 'values' ] ) ) {
				$opt_val = $inputs[ $input_key ];

				if ( is_array( $opt_val ) ) {
					$opt_val = array_intersect( $prop[ 'values' ], $opt_val );

					if ( is_array( $opt_val ) ) {
						$inputs[ $input_key ] = array_combine( $opt_val, $opt_val );
					}
				} else {
					$inputs[ $input_key ] = $opt_val;
				}

				continue;
			}

			// Sanitize multi-text input
			if ( $prop[ 'name' ] === 'multi_input' ) {
				$opt_val = $inputs[ $input_key ];
				$sanitized_inputs	= [];

				foreach ( $opt_val as $key => $value ) {
					$sanitized_inputs[ $key ] = \sanitize_post( $value );
				}

				$inputs[ $input_key ] = $sanitized_inputs;

				continue;
			}

			// Sanitize file uploads
			if ( $prop[ 'name' ] === 'file' ) {
				if ( isset( $_FILES[ $this->settingsName ] ) ) {
					$file_handler	= $_FILES[ $this->settingsName ];
				}

				if ( ! isset( $file_handler[ 'name' ][ $input_key ] ) || empty( $file_handler[ 'name' ][ $input_key ] ) ) {
					$inputs[ $input_key ] = null;

					continue;
				}

				// user defined accepted file mime types
				$mimes = $prop[ 'mimes' ];

				if ( empty( $mimes ) ) {
					$mimes = null;
				}

				$new_handler	= [
					'name'		=> $file_handler[ 'name' ][ $input_key ],
					'type'		=> $file_handler[ 'type' ][ $input_key ],
					'tmp_name'	=> $file_handler[ 'tmp_name' ][ $input_key ],
					'error'		=> $file_handler[ 'error' ][ $input_key ],
					'size'		=> $file_handler[ 'size' ][ $input_key ],
				];

				if ( ! function_exists( 'wp_handle_upload' ) ) {
					require_once( ABSPATH . 'wp-admin/includes/file.php' );
				}

				$form_file = \wp_handle_upload(
					$new_handler,
					array(
						'test_form'	=> false,
						'test_type' => false,
					)
				);

				if ( ! $form_file || isset( $form_file[ 'error' ] ) ) {
					$this->add_error( "<p>" . $form_file[ 'error' ] . "</p>" );
					$form_file = null;
				} elseif( !empty( $mimes ) ) {
					$file_type = mime_content_type( $form_file[ 'file' ] );

					if ( ! \wp_match_mime_types( $mimes, $file_type ) ) {
						// Remove file
						unlink( $form_file[ 'file' ] );

						$this->add_error( "<p>" . \esc_html__( 'UPLOAD ERROR : Unsupported mime type', $this->domain ) . "</p>" );
						$form_file = null;
					}
				}

				$inputs[ $input_key ] = $form_file;

				continue;
			}

			// Other sanitization functions for example sanitize_text_field
			$func_name = 'sanitize_' . $prop[ 'name' ];

			if ( ! function_exists( $func_name ) ) continue;

			if ( isset( $inputs[ $input_key ] ) ) {
				$inputs[ $input_key ] = call_user_func( $func_name, $inputs[ $input_key ] );
			}

		}

		// Mark it as updated ( settiings saved )
		\update_option( $this->optionNames[ 'updated' ], '1' );

		$this->save_errors();

		// Merge with prior saved settings
		$inputs += $this->settingValues;

		return $inputs;
	}



	protected function add_error( $error )
	{
		if ( ! is_array( $this->errors ) ) {
			$this->errors = [];
		}

		$this->errors[] = $error;
	}



	/**
	 * Checks form errors
	 *
	 * @return bool
	 */
	public function check_errors()
	{
		$errors = $this->get_errors();

		return ( is_array( $errors ) && count( $errors ) > 0 );
	}



	/**
	 * Gets saved errors from the wp options
	 *
	 * @return array
	 */
	public function get_errors()
	{
		if ( $this->errors !== false && empty( $this->errors ) ) {
			$this->errors = \get_option( $this->optionNames[ 'errors' ], null );

			if ( ! empty( $this->errors ) ) {
				\delete_option( $this->optionNames[ 'errors' ] );
			}
		}

		return $this->errors;
	}



	/**
	 * Saves occured errors to option table
	 */
	protected function save_errors()
	{
		if ( ! empty( $this->errors ) ) {
			\update_option( $this->optionNames[ 'errors' ], $this->errors );
		}
	}



	/**
	 * This function registers setting options
	 */
	public function register()
	{
		global $_POST;

		$all_settings = $this->_getIndexes();

		if ( ! empty( $all_settings ) && isset( $_POST[ 'setting_name' ] ) ) {
			$requested_option_page = $_POST[ 'setting_name' ];

			if ( array_key_exists( $requested_option_page, $all_settings ) ) {
				\register_setting( $this->pageName, $requested_option_page, array( $this, 'settingsInputMiddleware' ) );
			}
		} else {
			\register_setting( $this->pageName, $this->settingsName, array( $this, 'settingsInputMiddleware' ) );
		}

		$this->ajax->load_handler( $this->ajaxLoads );
	}



	/**
	 * This function adds the hidden fields to the setting form
	 */
	public function put_hidden_fields()
	{
		foreach ( $this->hiddenFields as $name => $value ) {
			echo '<input type="hidden" name="' . $name . '" value="' . $value . '"/>';
		}
	}



	/**
	 * This function runs all setting options. Also prints out as html output all of them.
	 */
	public function run( $section = null, $button_text = null )
	{
 		$this->form_start( $section );
 		$this->run_section( $section );
 		$this->form_end( $section );
 	}



	/**
	 * Form starter
	 */
	public function form()
	{
		if ( $this->hasMedia ) {
			echo '<form method="post" action="options.php" enctype="multipart/form-data">';
		} else {
			echo '<form method="post" action="options.php">';
		}
	}



	/**
	 * Manual way to running form. This function runs all or particular setting section.
	 *
	 * @param string $section
	 *
	 * @since 1.3.0
	 */
	public function run_section( $section = null )
	{
		if ( empty( $section ) ) {
			if ( ! is_array( $this->sections ) ) return;

			// Display the settings sections for the page
			foreach ( $this->sections as $sec_name => $sec_value ) {
				\do_settings_sections( $sec_name );
			}
		} else {
			\do_settings_sections( $section );
		}
	}



	/**
	 * Manual way to running form. That prints all html form starter tags and fires all start process.
	 * @since 1.3.0
	 *
	 * @param string $section
	 */
	public function form_start( $section = null )
	{
		if ( empty( $this->pageName ) || ! isset( $this->sections ) || ! is_array( $this->sections ) ) {
			return false;
		}

		// Print HTML form tag
		$this->form();

		// Display necessary hidden fields for settings
		\settings_fields( $this->pageName );

		$this->add_hidden_field( 'setting_name', $this->settingsName );
		$this->put_hidden_fields();
	}



	/**
	 * Manual way to running form. That prints all html form ender tags and fires all end process.
	 * @since 1.3.0
	 *
	 * @param string $section
	 */
	public function form_end( $section = null )
	{
		$button_text = null;

		if ( empty( $section ) ) {
			if ( is_array( $this->submitName ) ) {
				$button_text = end( $this->submitName );
			} else {
				$button_text = $this->submitName;
			}
		} elseif( isset( $this->submitName[ $section ] ) ) {
			$button_text = $this->submitName[ $section ];
		}

		$this->submit( $button_text );

		echo "</form>";
	}



	/**
	 * Gets submit button html code
	 * @since 1.2.0
	 *
	 * @param string $name
	 * @param bool $echo
	 *
	 * @return string or echo
	 */
	public function submit( $name, $echo = true )
	{
		if ( ! $echo ) {
			return \get_submit_button( $name );
		}

		echo \submit_button( $name );
	}



	/**
	* Defines submit button name
	*
	* @param string $name
	*/
	public function submit_name( $name )
	{
		$this->submitName[ $this->section ] = $name;
	}



	/**
	 * Sets return url for redirecting
	 */
	function set_return_route()
	{
		$row_index = $this->last_index();

		if ( $row_index !== false ) {
			$this->route( [ 'edit' => $row_index ] );
		}
	}



	public function getRows()
	{
		if ( is_array( $this->rows ) ) {
			return $this->rows;
		}

		$indexes	= $this->_getIndexes();

		if ( $indexes && is_array( $indexes ) ) {
			foreach ( $indexes as $name => $id ) {
				$this->rows[ $id ] = [
					'name'		=> $name,
					'fields'	=> \get_option( $name )
				];
			}
		}

		return $this->rows;
	}



	/**
	 * Gets the indexName if exists therefore return as false
	 *
	 * @return string|bool
	 */
	protected function _getIndexes()
	{
		if ( empty( $this->indexName ) ) return false;

		return \get_option( $this->indexName );
	}



	/**
	 * Removes all setting fields from the database
	 *
	 * @since 2.1.2
	 */
	public function remove_settings()
	{
		$indexes = $this->_getIndexes();

		// If setting has multiple rows then delete from settings index record
		if ( $indexes !== false ) {

			foreach ( $indexes as $key => $value ) {
				\delete_option( $value );
			}

			\delete_option( $this->indexName );
		}

		foreach ( $this->optionNames as $key => $value ) {
			\delete_option( $value );
		}

		if ( \get_option( $this->settingsName ) !== false ) {
			\delete_option( $this->settingsName );
		}
	}



	/**
	 * Gets the latest setting row name if exists therefore return as false
	 *
	 * @return string|bool
	 */
	public function last_index()
	{
		if ( $this->settingsRow > -1 ) {
			return $this->settingsRow;
		}

		return false;
	}



	/**
	 * The Ajax data loader function
	 * @since 2.2.0
	 *
	 * @param array $args [ string static method hook, mixed parameters... ]
	 * @param array $args2 [ key_name, value_name ]
	 * @param string $loading_text text message to be displayed while page is loading
	 *
	 * @return object $this
	 */
	protected function _ajaxLoader( array $args, $loading_text = null )
	{
		$field = $this->currentField;

		if ( empty( $loading_text ) ) {
			$loading_text = 'Please wait...';
		}

		$hook	= array_shift( $args );

		$conf[ 'hook' ]		= $hook;
		$conf[ 'params' ]	= $args;
		$conf[ 'field' ]	= $field;

		$conf[ 'field' ][ 'loading_text' ] = $loading_text;
		$conf[ 'field' ][ 'value' ] = $this->get_value( $field[ 'key' ] );

		$this->ajaxLoads[ $field[ 'key' ] ] = $conf;

		return $this;
	}



	/**
	 * Set template file url
	 * @since 2.2.0
	 *
	 * @param string $path
	 */
	public function set_theme( $path )
	{
		Templates::set( $path );
	}
}
