<?php
/**
 * Wasp - WP Admin Settings Page
 * Settings Page Creator for WordPress
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package wasp
 * @author Uğur Biçer <uuur86@yandex.com>
 * @license GPLv3 or later
 * @version 3.0.0
 */

namespace WaspCreators;

if ( class_exists( '\\WaspCreators\\Wasp' ) || ! defined( 'ABSPATH' ) ) {
	return;
}

use WaspCreators\Ajax;
use WaspCreators\Fields;
use WaspCreators\Templates;

/**
 * Main class for Wasp plugin.
 * This class will load all other classes.
 */
class Wasp {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = '2.3.2';

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
	protected $page_name;

	/**
	 * Contains all saved setting values
	 *
	 * @var array
	 */
	protected $setting_values = null;

	/**
	 * Default values
	 *
	 * @var array
	 */
	protected $default_values = array();

	/**
	 * Current settings form name
	 *
	 * @var string
	 */
	protected $settings_name;

	/**
	 * Current settings row number
	 *
	 * @var string
	 */
	protected $settings_row;

	/**
	 * Current index name
	 *
	 * @var string
	 */
	protected $index_name;

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
	protected $input_fields = null;

	/**
	 * The other hidden form fields
	 *
	 * @var array
	 */
	protected $hidden_fields;

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
	 * Current field
	 *
	 * @since 1.2.0
	 *
	 * @var array
	 */
	protected $current_field = null;

	/**
	 * Current section name
	 *
	 * @since 1.2.0
	 *
	 * @var string
	 */
	protected $current_section = null;

	/**
	 * Is form updated
	 *
	 * @since 1.2.0
	 *
	 * @var boolean
	 */
	protected $form_updated = false;

	/**
	 * Option names
	 *
	 * @since 1.2.0
	 *
	 * @var array
	 */
	protected $option_names = null;

	/**
	 * Submit name(s)
	 *
	 * @since 1.2.0
	 *
	 * @var string|array
	 */
	protected $submit_name = null;

	/**
	 * Is form ready?
	 *
	 * @since 1.2.0
	 *
	 * @var bool
	 */
	protected $is_ready = false;

	/**
	 * Does form has include any file uploading?
	 *
	 * @since 1.2.0
	 *
	 * @var boolean
	 */
	protected $has_media = false;

	/**
	 * Form errors
	 *
	 * @since 1.2.0
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Method array or string function name for form init
	 *
	 * @since 2.0.2
	 * @var string|array
	 */
	protected $user_func = null;

	/**
	 * Form items for ajax fields
	 *
	 * @since 2.2.0
	 * @var array
	 */
	protected $ajax_loads = [];

	/**
	 * Ajax object container
	 *
	 * @since 2.2.0
	 *
	 * @var Ajax
	 */
	protected $ajax = null;

	/**
	 * After HTML code container for current form item
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	protected $html_after = null;

	/**
	 * Before HTML code container
	 *
	 * @since 2.2.0
	 *
	 * @var string
	 */
	protected $html_before = null;

	/**
	 * Contains setting rows if multi row settings is enabled
	 *
	 * @since 2.2.1
	 *
	 * @var array
	 */
	protected $rows = null;

	/**
	 * Reset feature enabled
	 *
	 * @since 2.3.3
	 *
	 * @var boolean
	 */
	protected $reset_enabled = false;

	/**
	 * Reset feature parameters
	 *
	 * @since 2.3.3
	 *
	 * @var array
	 */
	protected $reset_params = [];

	/**
	 * Initial values stored after defaults are loaded
	 *
	 * @since 2.3.3
	 *
	 * @var array
	 */
	protected $initial_values = null;

	/**
	 * Reset nonce action name
	 *
	 * @since 2.3.3
	 *
	 * @var string
	 */
	protected $reset_nonce_action = 'wasp_reset_field_nonce';



	/**
	 * Constructor - Sets up the Wasp settings page with title, settings name, domain, and optional row handling.
	 *
	 * @param string   $page Unique page name.
	 * @param string   $setting_name Setting name will be saved in options.
	 * @param string   $domain Domain for localize.
	 * @param int|null $row Get variable name for multiple form row.
	 */
	public function __construct( string $page, string $setting_name, string $domain, $row = null ) {
		global $pagenow;

		if ( ! isset( $page ) || empty( $page ) ) {
			return;
		}

		$this->ajax = new Ajax( $domain );
		$this->page_name = $page;
		$this->domain = $domain;
		$this->settings_name = (string) $setting_name . '_settings';
		$this->index_name = (string) $setting_name . '_index';
		$this->option_names = [
			'updated' => (string) $this->settings_name . '_form_updated',
			'errors' => (string) $this->settings_name . '_form_errors',
		];

		// for multiple settings field.
		if ( ! empty( $row ) ) {
			$row = $this->get_form_data( $row );
			$this->indexes = $this->get_indexes();

			// Find the latest row value.
			if ( ! empty( $this->indexes ) && is_array( $this->indexes ) ) {
				$indexes = $this->indexes;
				$last_row = end( $indexes );
				$indexes = array_flip( $indexes );
				$last_settings = \get_option( $indexes[ $last_row ] );

				// If row value hasn`t set by get value.
				if ( empty( $row ) ) {
					$row = intval( $last_row );

					// Increases row value when the latest setting has been setted.
					if ( is_array( $last_settings ) && count( $last_settings ) > 0 ) {
						++$row;
					}
				}
			}

			// Make sure to row value equal to least 1.
			if ( $row < 1 ) {
				$row = 1;
			}

			// Append the setting row.
			$this->settings_row = $row;

			// Append the new multiple settings_name which is affected by row.
			$this->settings_name = $setting_name . '_' . $this->settings_row . '_settings';

			// Save index data.
			if ( false === \get_option( $this->index_name ) ) {
				// Add index option value for first time.
				\add_option( $this->index_name, [ $this->settings_name => $this->settings_row ] );
			} else {
				// Update indexes.
				$this->indexes[ $this->settings_name ] = $this->settings_row;

				// Update index option value.
				\update_option( $this->index_name, $this->indexes );
			}
		}

		// Append setting_values.
		$settings = \get_option( $this->settings_name );

		if ( false === $settings ) {
			\add_option( $this->settings_name, [] );
		} else {
			$this->setting_values = $settings;
		}

		$this->is_ready = true;

		// returns if in wp option backend process.
		if ( $pagenow === 'options.php' ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_REQUEST['option_page'] ) || $_REQUEST['option_page'] !== $page ) {
				$this->is_ready = false;
			}

			return;
		}

		if ( $pagenow !== 'admin.php' ) {
			$this->is_ready = false;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} elseif ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] !== $page ) {
			$this->is_ready = false;
		}

		// Prepare the form.
		$this->prepare_get_vars();

		// Checks whether the form is updated.
		$updated_name = $this->option_names['updated'];
		$option_updated = \get_option( $updated_name );

		if ( ! $this->check_errors() && $option_updated === '1' ) {
			$this->form_updated = true;
			\delete_option( $updated_name );
		}
	}


	/**
	 * Catches wrong method names.
	 *
	 * @param string $method  Method name.
	 * @param array  $args    Arguments.
	 */
	public function __call( $method, $args ) {
		/* translators: %s: Method name. */
		echo esc_html( sprintf( __( 'Method "%s" does not exist!', 'wasp' ), $method ) );
	}



	/**
	 * Returns whether the form is ready.
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function is_ready() {
		return $this->is_ready;
	}



	/**
	 * Calls user defined method.
	 */
	public function user_func_hook() {
		call_user_func( $this->user_func, $this );
	}



	/**
	 * Inıts the form actions
	 *
	 * @since 1.2.0
	 *
	 * @param string|array $hook Form actions.
	 */
	public function wp_form_init( $hook ) {
		if ( empty( $hook ) ) {
			return;
		}

		if ( ! is_array( $hook ) ) {
			$this->user_func = $hook;
			\add_action( 'admin_init', [ $this, 'user_func_hook' ] );

			return;
		}

		\add_action( 'admin_init', $hook );
	}


	/**
	 * Passes all get variables as hidden inputs in form
	 */
	protected function prepare_get_vars() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		foreach ( $_GET as $get_key => $get_val ) {
			$this->add_hidden_field( '__get__' . $get_key, $get_val );
		}
	}



	/**
	 * Gets data from post or get form data
	 *
	 * @param string $name  Get or post variable name.
	 *
	 * @return string|null
	 */
	public function get_form_data( $name ) {
		$name = sanitize_key( $name );

		// phpcs:ignore
		if ( isset( $_GET[ $name ] ) ) {
			// phpcs:ignore
			return wp_unslash( $_GET[ $name ] );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( isset( $_POST[ '__get__' . $name ] ) ) {
			// phpcs:ignore
			return wp_unslash( $_POST[ '__get__' . $name ] );
		}

		return null;
	}



	/**
	 * Sets route url to redirect
	 *
	 * @param array|string $link url of the redirect address.
	 */
	public function route( $link ) {
		if ( is_array( $link ) ) {
			$link = \add_query_arg( $link );
		}

		$this->add_hidden_field( '_wp_http_referer', $link );
	}



	/**
	 * Adds a new section.
	 *
	 * @param array $args  Section arguments.
	 */
	public function section( $args ) {
		if ( ! is_array( $args ) ) {
			return $this;
		}

		// Defaults.
		$accepted = [
			'cond' => true,
			'name' => null,
			'title' => null,
			'desc' => null,
			'after' => null,
			'before' => null,
		];

		$args = array_intersect_key( $args, $accepted );
		$args = array_merge( $accepted, $args );

		$cond   = $args['cond'];
		$name   = $args['name'];
		$title  = $args['title'];
		$desc   = $args['desc'];
		$after  = $args['after'];
		$before = $args['before'];

		if ( empty( $name ) ) {
			return $this;
		}

		if ( ! $cond ) {
			return $this;
		}

		$this->section = $name;

		$this->sections[ $this->section ] = [
			'title' => $title,
			'desc' => $desc,
		];

		$this->current_section = $this->sections[ $this->section ];
		$this->current_section['id'] = $this->section;

		if ( ! empty( $after ) || ! empty( $before ) ) {
			$this->set_html( $before, $after );
		}

		return $this;
	}



	/**
	 * This function prints out the section html code
	 *
	 * @param array $args callback paramters.
	 *
	 * @return void
	 */
	public function section_callback( $args ) {
		if ( ! isset( $args['id'] ) ) {
			return;
		}

		$sec_pos = strpos( $args['id'], '_section' );

		if ( $sec_pos === -1 ) {
			return;
		}

		$current = substr( $args['id'], 0, $sec_pos );
		$current = $this->sections[ $current ];

		if ( ! empty( $current['html_before'] ) ) {
			//phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $current['html_before'];
		}

		echo '<h2 class="title">' . \esc_html( $current['title'] ) . '</h2>';
		echo '<p class="description">' . \esc_html( $current['desc'] ) . '</p>';

		if ( ! empty( $current['html_after'] ) ) {
			//phpcs:ignore
			echo $current['html_after'];
		}
	}



	/**
	 * Delete setting which is named as $name or current setting.
	 *
	 * @since 1.2.0
	 * @param string|null $name Setting name.
	 */
	public function delete_setting( $name = null ) {
		if ( empty( $name ) ) {
			$name = $this->settings_name;
		}

		$indexes = $this->get_indexes();

		// If setting has multiple rows then delete from settings index record.
		if ( $indexes !== false ) {
			unset( $indexes[ $name ] );
			\update_option( $this->index_name, $indexes );
		}

		if ( ! empty( \get_option( $name, null ) ) ) {
			\delete_option( $name );
		}
	}



	/**
	 * Get particular setting value.
	 *
	 * @since 1.2.0
	 * @param string $name Setting name.
	 * @return mixed|null Setting value.
	 */
	public function get_value( $name ) {
		$value = $this->get_setting( $name );

		// If we have a saved value (not null/empty), use it.
		if ( $value !== null && $value !== '' ) {
			return $value;
		}

		// Otherwise use default value if available.
		if ( isset( $this->default_values[ $name ] ) ) {
			return $this->default_values[ $name ];
		}

		return $value;
	}



	/**
	 * Get settings name in wp options table.
	 *
	 * @since 1.2.0
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
		if ( empty( $this->setting_values ) ) {
			return null;
		}

		return $this->setting_values;
	}



	/**
	 * This function gets the WordPress options about defined setting.
	 *
	 * @since 1.2.0
	 * @param string $name Setting name.
	 * @return mixed|null Setting value.
	 */
	public function get_setting( $name ) {
		if ( isset( $this->setting_values[ $name ] ) ) {
			return $this->setting_values[ $name ];
		}

		return null;
	}


	/**
	 * It loads all form sections and fields. Deprecated since 3.0.0. Use load_form() instead.
	 *
	 * @param array $args Form sections and fields.
	 * @return bool
	 *
	 * @see load_form()
	 *
	 * @phpcs:disable
	 */
	public function loadForm( $args ) {
		return $this->load_form( $args );
	}



	/**
	 * It registers all form sections and fields.
	 *
	 * @since 2.2.1
	 * @param array $args Form sections and fields.
	 * @return bool
	 */
	public function load_form( $args ) {
		if ( ! is_array( $args ) ) {
			return false;
		}

		if ( isset( $args['name'] ) ) {
			$args = [ $args ];
		}

		foreach ( $args as $section ) {
			// Section definitions.
			$this->section( $section )->add();

			foreach ( $section['items'] as $item ) {
				$this->field( $item )->add();
			}
		}

		return true;
	}



	/**
	 * It registers the values to sanitize array for using in middleware controls
	 *
	 * @param string       $type     Field type.
	 * @param string       $id       Field identifier.
	 * @param array|string $options  Options for the field.
	 * @param string       $sanitize Sanitize method, e.g. [ options, regex, text_field, textarea_field, email, file_name etc. ].
	 */
	protected function register_sanitize( $type, $id, $options, $sanitize ) {
		$this->input_fields[] = $id;
		$sanitize_arr         = array( 'name' => $sanitize );

		if ( $sanitize === 'options' ) {
			if ( is_array( $options ) ) {
				$opt_arr_val = array_values( $options );

				if ( is_array( $opt_arr_val[0] ) ) {
					$new_options = [];

					foreach ( $opt_arr_val as $opt_arr_val_ ) {
						$new_options += $opt_arr_val_;
					}

					$options = $new_options;
				}

				$sanitize_arr['values'] = array_keys( $options );
			}
		} elseif ( $sanitize === 'file' ) {
			$sanitize_arr['mimes'] = $options;
		} elseif ( $sanitize === 'regex' ) {
			$sanitize_arr['pattern'] = $options;
		} else {
			$sanitize_arr['value'] = $options;
		}

		$this->sanitize[ $id ] = $sanitize_arr;
	}



	/**
	 * This function adds new field to defined setting options
	 *
	 * @since 1.2.0
	 *
	 * @param array $args Field arguments.
	 * @return $this
	 */
	public function field( $args ) {
		if ( ! is_array( $args ) ) {
			return $this;
		}

		// Defaults.
		$accepted = [
			'cond' => true,
			'type' => null,
			'name' => null,
			'label' => null,
			'value' => null,
			'default' => null,
			'option' => null,
			'options' => null,
			'after' => null,
			'before' => null,
			'ajax' => [],
			'sanitize' => 'text_field',
			'save' => false,
			'field_type' => 'url',
			'placeholder' => null,
			'presets' => null,
		];

		$args = array_intersect_key( $args, $accepted );
		$args = array_merge( $accepted, $args );

		$cond       = $args['cond'];
		$type       = $args['type'];
		$name       = $args['name'];
		$label      = $args['label'];
		$value      = $args['value'];
		$default    = $args['default'];
		$option     = $args['option'];
		$options    = $args['options'];
		$after      = $args['after'];
		$before     = $args['before'];
		$ajax       = $args['ajax'];
		$sanitize   = $args['sanitize'];
		$save       = $args['save'];
		$field_type = $args['field_type'];
		$placeholder = $args['placeholder'];
		$presets    = $args['presets'];

		if ( ! $cond ) {
			return $this;
		}

		if ( 'hidden' === $type ) {
			$this->add_hidden_field(
				$name,
				$value,
				$save
			);

			return $this;
		}

		if ( ! empty( $this->current_field ) ) {
			$this->add();
		}

		if ( ! empty( $ajax ) ) {
			$sanitize = $type === 'regex' ? 'regex' : '';
		} elseif ( $type === 'file_input' ) {
			$this->has_media = true;
			$sanitize = 'file';
		} elseif ( in_array( $type, [ 'radio', 'select' ] ) ) {
			$sanitize = 'options';
		} elseif ( in_array( $type, [ 'checkbox', 'onecheckbox' ] ) ) {
			$sanitize = 'checkbox';
		} elseif ( $type === 'multi_text_input' ) {
			$sanitize = 'multi_input';
		} elseif ( $type === 'taginput' ) {
			$sanitize = 'url';

			if ( $field_type === 'regex' ) {
				$sanitize = 'regex';
			}
		}

		$item_id = $this->settings_name . '_' . $name . '_' . $type;
		$item_name = $this->settings_name . '[' . $name . ']';

		$this->current_field = [
			'key' => $name,
			'type' => $type,
			'id' => $item_id,
			'name' => $item_name,
			'label' => \esc_attr( $label ),
			'option' => $option,
			'options' => $options,
			'sanitize' => $sanitize,
			'placeholder' => isset( $placeholder ) ? $placeholder : '',
			'presets' => isset( $presets ) ? $presets : '',
			'field_type' => isset( $field_type ) ? $field_type : 'url',
		];

		if ( ! empty( $default ) ) {
			$this->default_value( $default );
		}

		if ( ! empty( $after ) || ! empty( $before ) ) {
			$this->set_html( $before, $after );
		}

		if ( ! empty( $ajax ) ) {
			$this->ajax_loader( $ajax );
		}

		return $this;
	}



	/**
	 * It adds the current section and field.
	 *
	 * @since 1.2.0
	 * @return mixed|false Returns true on success, false on failure.
	 */
	public function add() {
		if ( ! empty( $this->current_section['id'] ) ) {
			$section_id = $this->current_section['id'];

			if ( ! empty( $this->html_before ) ) {
				$this->sections[ $section_id ] = $this->html_before;
				$this->html_before = null;
			}

			if ( ! empty( $this->html_after ) ) {
				$this->sections[ $section_id ] = $this->html_after;
				$this->html_after = null;
			}

			\add_settings_section(
				(string) $section_id . '_section', // Section ID.
				'',
				array( $this, 'section_callback' ), // Admin page to add section to.
				$section_id // Page.
			);

			$this->current_section = null;
		}

		if ( empty( $this->current_field['id'] ) ) {
			return false;
		}

		$args = $this->current_field;

		$this->register_sanitize(
			$args['type'],
			$args['key'],
			$args['options'],
			$args['sanitize']
		);

		$field_args = [
			'type' => $args['type'],
			'id' => $args['id'],
			'name' => $args['name'],
			'value' => $this->get_value( $args['key'] ),
			'placeholder' => isset( $args['placeholder'] ) ? $args['placeholder'] : '',
			'presets' => isset( $args['presets'] ) ? $args['presets'] : '',
			'field_type' => isset( $args['field_type'] ) ? $args['field_type'] : 'url',
		];

		if ( isset( $args['option'] ) ) {
			$field_args['option'] = $args['option'];
		}

		if ( is_array( $args['options'] ) && ! in_array( $args['type'], [ 'file_input' ] ) ) {
			$field_args['options'] = $args['options'];
		}

		if ( ! empty( $this->html_before ) ) {
			$field_args['html_before'] = $this->html_before;
			$this->html_before = null;
		}

		if ( ! empty( $this->html_after ) ) {
			$field_args['html_after'] = $this->html_after;
			$this->html_after = null;
		}

		if ( ! empty( $this->ajax_loads[ $args['key'] ] ) ) {
			$field_args['options'] = [ '' ];
		}

		// Add reset button if enabled - for taginput, insert before closing div.
		if ( $this->reset_enabled && $this->should_show_reset_button( $args['key'] ) ) {
			$reset_button = $this->render_reset_button( $args['name'], $args['key'], $args['type'] );

			// For taginput types, reset button goes before closing </div>.
			if ( in_array( $args['type'], [ 'taginput', 'taginput_regex' ] ) ) {
				if ( isset( $field_args['html_after'] ) ) {
					$field_args['html_after'] = $reset_button . $field_args['html_after'];
				} else {
					$field_args['html_after'] = $reset_button . '</div>';
				}
			} elseif ( isset( $field_args['html_after'] ) ) {
					$field_args['html_after'] .= $reset_button;
			} else {
				$field_args['html_after'] = $reset_button;
			}
		} elseif ( in_array( $args['type'], [ 'taginput', 'taginput_regex' ] ) ) {
			// For taginput types without reset, still need closing div.
			if ( ! isset( $field_args['html_after'] ) ) {
				$field_args['html_after'] = '</div>';
			}
		}

		// For taginput types, wrap content in taginput div with field_type.
		if ( in_array( $args['type'], [ 'taginput', 'taginput_regex' ] ) ) {
			$taginput_div = '<div class="taginput-wrapper" data-field-type="' . esc_attr( $args['field_type'] ) . '">';
			if ( isset( $field_args['html_before'] ) ) {
				$field_args['html_before'] = $taginput_div . $field_args['html_before'];
			} else {
				$field_args['html_before'] = $taginput_div;
			}
		}

		\add_settings_field(
			$this->settings_name . '_' . $args['key'],
			$args['label'],
			'\WaspCreators\Fields::callback',
			$this->section,
			$this->section . '_section',
			$field_args
		);

		$this->current_field = null;

		return true;
	}



	/**
	 * This function registers default value for any field.
	 *
	 * @since 1.2.0
	 *
	 * @param string $value Field value.
	 *
	 * @return Wasp|bool Returns $this for method chaining, false on failure.
	 */
	public function default_value( $value ) {
		if ( empty( $this->current_field['key'] ) ) {
			return false;
		}

		$key = $this->current_field['key'];

		if ( ! empty( $value ) ) {
			$this->default_values[ $key ] = $value;
		}

		return $this;
	}



	/**
	 * This function sets html code before and after any field.
	 *
	 * @since 1.2.0
	 *
	 * @param string|null $before HTML code to be added before the field.
	 * @param string|null $after  HTML code to be added after the field.
	 *
	 * @return Wasp
	 */
	protected function set_html( $before = null, $after = null ) {
		if ( empty( $this->current_field ) && empty( $this->current_section ) ) {
			return $this;
		}

		if ( ! empty( $before ) ) {
			$this->html_before = $before;
		}

		if ( ! empty( $after ) ) {
			$this->html_after = $after;
		}

		return $this;
	}



	/**
	 * This function registers hidden field for moving variable data
	 * to options page without creating an option
	 *
	 * @param string $id    Unique identifier for the hidden field.
	 * @param string $value Value to be stored in the hidden field.
	 * @param bool   $save  Whether to save the hidden field in options.
	 */
	public function add_hidden_field( $id, $value, $save = false ) {
		$id = \esc_attr( $id );
		$value = \esc_attr( $value );

		if ( $save ) {
			$this->register_sanitize( 'text', $id, $value, '' );
		}

		$this->hidden_fields[ $id ] = $value;
	}



	/**
	 * This function returns true if form was updated, false otherwise
	 *
	 * @since 1.2.0
	 *
	 * @return bool
	 */
	public function form_success() {
		return $this->form_updated;
	}



	/**
	 * This function sanitizes defined setting options - WP Hook.
	 *
	 * @param mixed $inputs Input fields.
	 *
	 * @return mixed Sanitized input fields.
	 */
	public function settings_input_middleware( $inputs ) {
		global $pagenow;

		foreach ( $this->sanitize as $input_key => $prop ) {
			// Sanitize and set external hidden post values into setting form values.
			if ( ! isset( $inputs[ $input_key ] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST[ $input_key ] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing
					$inputs[ $input_key ] = \sanitize_text_field( wp_unslash( $_POST[ $input_key ] ) );

					continue;
				}
			}

			// No sanitization.
			if ( ! isset( $prop['name'] ) ) {
				continue;
			}

			if ( $prop['name'] === 'checkbox' ) {
				if ( ! isset( $inputs[ $input_key ] ) ) {
					$inputs[ $input_key ] = null;
				}

				continue;
			}

			// Sanitize by regular expression rules - regex.
			if ( $prop['name'] === 'regex' && isset( $prop['pattern'] ) ) {

				if ( preg_match( '#^' . $prop['pattern'] . '$#ui', $inputs[ $input_key ], $matched ) ) {
					$inputs[ $input_key ] = $matched[0];
				} elseif ( isset( $this->setting_values[ $input_key ] ) && ! empty( $this->setting_values[ $input_key ] ) ) {
					// Keep existing value if validation fails, don't wipe it.
					$inputs[ $input_key ] = $this->setting_values[ $input_key ];
					/* translators: %s: Input key. */
					$this->add_error( '<p>' . sprintf( esc_html__( 'Invalid format for %s.', 'wasp' ), $input_key ) . '</p>' );
				} else {
					$inputs[ $input_key ] = '';
				}

				continue;
			}

			// Sanitize options which has predetermined values.
			if ( $prop['name'] === 'options' && is_array( $prop['values'] ) ) {
				$opt_val = $inputs[ $input_key ];

				if ( is_array( $opt_val ) ) {
					$opt_val = array_intersect( $prop['values'], $opt_val );

					if ( is_array( $opt_val ) ) {
						$inputs[ $input_key ] = array_combine( $opt_val, $opt_val );
					}
				} else {
					$inputs[ $input_key ] = $opt_val;
				}

				continue;
			}

			// Sanitize multi-text input.
			if ( $prop['name'] === 'multi_input' ) {
				$opt_val = $inputs[ $input_key ];
				$sanitized_inputs = [];

				foreach ( $opt_val as $key => $value ) {
					$sanitized_inputs[ $key ] = \sanitize_post( $value );
				}

				$inputs[ $input_key ] = $sanitized_inputs;

				continue;
			}

			// Sanitize taginput - newline separated values.
			if ( $prop['name'] === 'taginput' ) {
				$val = $inputs[ $input_key ];

				if ( ! empty( $val ) ) {
					// Decode pipe separator back to actual newlines before saving.
					$val = str_replace( '|', "\n", $val );
					$lines = preg_split( '/\r\n|\n/', $val );
					$sanitized = array_filter( array_map( 'trim', $lines ) );
					$inputs[ $input_key ] = implode( "\n", $sanitized );
				}

				continue;
			}

			// Sanitize taginput_regex - with regex validation.
			if ( $prop['name'] === 'taginput_regex' ) {
				$val = $inputs[ $input_key ];

				if ( ! empty( $val ) ) {
					// Decode pipe separator back to actual newlines before processing.
					$val = str_replace( '|', "\n", $val );
					$lines = preg_split( '/\r\n|\n/', $val );
					$sanitized = [];
					$errors = [];

					foreach ( $lines as $line ) {
						$line = trim( $line );

						if ( empty( $line ) ) {
							continue;
						}

						// Validate regex.
						if ( @preg_match( '/' . $line . '/', '' ) === false ) {
							$errors[] = $line;

							continue;
						}

						$sanitized[] = $line;
					}

					$inputs[ $input_key ] = implode( "\n", $sanitized );

					if ( ! empty( $errors ) ) {
						/* translators: %s: Comma-separated list of invalid regex patterns. */
						$this->add_error( '<p>' . sprintf( esc_html__( 'Invalid regex patterns removed: %s', 'wasp' ), implode( ', ', $errors ) ) . '</p>' );
					}
				}
				continue;
			}

			// Sanitize file uploads.
			if ( $prop['name'] === 'file' ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_FILES[ $this->settings_name ] ) ) {
					// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$file_handler = $_FILES[ $this->settings_name ];
				}

				if ( ! isset( $file_handler['name'][ $input_key ] ) || empty( $file_handler['name'][ $input_key ] ) ) {
					$inputs[ $input_key ] = null;

					continue;
				}

				// user defined accepted file mime types.
				$mimes = $prop['mimes'];

				if ( empty( $mimes ) ) {
					$mimes = null;
				}

				$new_handler = [
					'name' => $file_handler['name'][ $input_key ],
					'type' => $file_handler['type'][ $input_key ],
					'tmp_name' => $file_handler['tmp_name'][ $input_key ],
					'error' => $file_handler['error'][ $input_key ],
					'size' => $file_handler['size'][ $input_key ],
				];

				if ( ! function_exists( 'wp_handle_upload' ) ) {
					// phpcs:ignore
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}

				$form_file = \wp_handle_upload(
					$new_handler,
					array(
						'test_form' => false,
						'test_type' => false,
					)
				);

				if ( ! $form_file || isset( $form_file['error'] ) ) {
					$this->add_error( '<p>' . $form_file['error'] . '</p>' );
					$form_file = null;
				} elseif ( ! empty( $mimes ) ) {
					$file_type = mime_content_type( $form_file['file'] );

					if ( ! \wp_match_mime_types( $mimes, $file_type ) ) {
						// Remove file.
						wp_delete_file( $form_file['file'] );

						$this->add_error( '<p>' . \esc_html__( 'UPLOAD ERROR : Unsupported mime type', 'wasp' ) . '</p>' );
						$form_file = null;
					}
				}

				$inputs[ $input_key ] = $form_file;

				continue;
			}

			// Other sanitization functions for example sanitize_text_field.
			$func_name = 'sanitize_' . $prop['name'];

			if ( ! function_exists( $func_name ) ) {
				continue;
			}

			if ( isset( $inputs[ $input_key ] ) ) {
				$inputs[ $input_key ] = call_user_func( $func_name, $inputs[ $input_key ] );
			}
		}

		// Mark it as updated ( settiings saved ).
		\update_option( $this->option_names['updated'], '1' );

		$this->save_errors();

		// Merge with prior saved settings.
		$inputs += $this->setting_values;

		return $inputs;
	}



	/**
	 * Adds an error message to the list of errors.
	 *
	 * @param string $error Error message HTML.
	 */
	protected function add_error( $error ) {
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
		if ( $this->errors !== false && empty( $this->errors ) ) {
			$this->errors = \get_option( $this->option_names['errors'], null );

			if ( ! empty( $this->errors ) ) {
				\delete_option( $this->option_names['errors'] );
			}
		}

		return $this->errors;
	}



	/**
	 * Saves occured errors to option table.
	 */
	protected function save_errors() {
		if ( ! empty( $this->errors ) ) {
			\update_option( $this->option_names['errors'], $this->errors );
		}
	}



	/**
	 * This function registers setting options
	 */
	public function register() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_POST;

		$all_settings = $this->get_indexes();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $all_settings ) && isset( $_POST['setting_name'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$requested_option_page = sanitize_text_field( wp_unslash( $_POST['setting_name'] ) );

			if ( array_key_exists( $requested_option_page, $all_settings ) ) {
				\register_setting( $this->page_name, $requested_option_page, array( $this, 'settings_input_middleware' ) );
			}
		} else {
			\register_setting( $this->page_name, $this->settings_name, array( $this, 'settings_input_middleware' ) );
		}

		$this->ajax->load_handler( $this->ajax_loads );
	}



	/**
	 * This function adds the hidden fields to the setting form
	 */
	public function put_hidden_fields() {
		foreach ( $this->hidden_fields as $name => $value ) {
			echo '<input type="hidden" name="' . \esc_attr( $name ) . '" value="' . \esc_attr( $value ) . '"/>';
		}
	}



	/**
	 * This function runs all setting options. Also prints out as html output all of them.
	 *
	 * @param string|null $section     Section ID.
	 * @param string|null $button_text Button text.
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
		if ( $this->has_media ) {
			echo '<form method="post" action="options.php" enctype="multipart/form-data">';
		} else {
			echo '<form method="post" action="options.php">';
		}
	}



	/**
	 * Manual way to running form. This function runs all or particular setting section.
	 *
	 * @param string $section Section name.
	 *
	 * @since 1.3.0
	 */
	public function run_section( $section = null ) {
		if ( empty( $section ) ) {
			if ( ! is_array( $this->sections ) ) {
				return;
			}

			// Display the settings sections for the page.
			foreach ( $this->sections as $sec_name => $sec_value ) {
				\do_settings_sections( $sec_name );
			}
		} else {
			\do_settings_sections( $section );
		}
	}



	/**
	 * Manual way to running form. That prints all html form starter tags and fires all start process.
	 *
	 * @since 1.3.0
	 *
	 * @param string $section Section name.
	 */
	public function form_start( $section = null ) {
		if ( empty( $this->page_name ) || ! isset( $this->sections ) || ! is_array( $this->sections ) ) {
			return false;
		}

		// Enqueue reset scripts if enabled.
		if ( $this->reset_enabled ) {
			$this->enqueue_reset_scripts();
		}

		// Print HTML form tag.
		$this->form();

		// Display necessary hidden fields for settings.
		\settings_fields( $this->page_name );

		$this->add_hidden_field( 'setting_name', $this->settings_name );
		$this->put_hidden_fields();
	}



	/**
	 * Enqueue JavaScript for reset functionality
	 *
	 * @since 2.3.3
	 */
	protected function enqueue_reset_scripts() {
		static $enqueued = false;

		if ( $enqueued ) {
			return;
		}

		$enqueued = true;

		// Calculate URL from wasp directory (vendor/uuur86/wasp/src/WaspCreators/Wasp.php).
		$wasp_base = dirname( __DIR__, 2 ); // vendor/uuur86/wasp.

		// Convert to web accessible URL using WordPress functions.
		$wasp_base = str_replace( DIRECTORY_SEPARATOR, '/', $wasp_base );

		// Remove ABSPATH from the path to get relative path from content directory.
		// phpcs:ignore
		$abspath = str_replace( DIRECTORY_SEPARATOR, '/', ABSPATH );
		$relative_path = str_replace( $abspath, '', $wasp_base );
		$base_url = '/' . ltrim( $relative_path, '/' ) . '/';

		$script_url = $base_url . 'assets/js/wasp-reset.js';
		$style_url = $base_url . 'assets/css/wasp-reset.css';

		wp_enqueue_script( 'wasp-reset', $script_url, [ 'jquery' ], '1.0.3', true );
		wp_enqueue_style( 'wasp-reset', $style_url, [], '1.0.0' );

		$nonce = wp_create_nonce( $this->reset_nonce_action );

		wp_localize_script(
			'wasp-reset',
			'waspReset',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => $nonce,
				'settingsName' => $this->settings_name,
				'messages' => [
					'confirm' => $this->reset_params['confirm_message'],
					'resetting' => esc_html__( 'Resetting...', 'wasp' ),
					'success' => esc_html__( 'Field reset successfully.', 'wasp' ),
					'error' => esc_html__( 'Error resetting field.', 'wasp' ),
				],
			]
		);
	}



	/**
	 * Manual way to running form. That prints all html form ender tags and fires all end process.
	 *
	 * @since 1.3.0
	 *
	 * @param string $section Section name.
	 */
	public function form_end( $section = null ) {
		$button_text = null;

		if ( empty( $section ) ) {
			if ( is_array( $this->submit_name ) ) {
				$button_text = end( $this->submit_name );
			} else {
				$button_text = $this->submit_name;
			}
		} elseif ( isset( $this->submit_name[ $section ] ) ) {
			$button_text = $this->submit_name[ $section ];
		}

		$this->submit( $button_text );

		echo '</form>';
	}



	/**
	 * Gets submit button html code
	 *
	 * @since 1.2.0
	 *
	 * @param string $name Name of the submit button.
	 * @param bool   $echo Whether to echo the submit button.
	 *
	 * @return string|void Returns the submit button html code if $echo is false.
	 */
	public function submit( $name = null, bool $echo = true ) {
		if ( ! $echo ) {
			return \get_submit_button( $name );
		}

		\submit_button( $name );
	}



	/**
	 * Defines submit button name
	 *
	 * @param string $name Name of the submit button.
	 */
	public function submit_name( $name ) {
		$this->submit_name[ $this->section ] = $name;
	}



	/**
	 * Sets return url for redirecting
	 */
	public function set_return_route() {
		$row_index = $this->last_index();

		if ( $row_index !== false ) {
			$this->route( [ 'edit' => $row_index ] );
		}
	}


	/**
	 * Gets form rows.
	 *
	 * @return array|null Array of form rows.
	 */
	public function get_rows() {
		if ( is_array( $this->rows ) ) {
			return $this->rows;
		}

		$indexes = $this->get_indexes();

		if ( $indexes && is_array( $indexes ) ) {
			foreach ( $indexes as $name => $id ) {
				$this->rows[ $id ] = [
					'name' => $name,
					'fields' => \get_option( $name ),
				];
			}
		}

		return $this->rows;
	}



	/**
	 * Gets the index_name if exists therefore return as false
	 *
	 * @return array|string|bool
	 */
	protected function get_indexes() {
		if ( empty( $this->index_name ) ) {
			return false;
		}

		return \get_option( $this->index_name );
	}



	/**
	 * Removes all setting fields from the database
	 *
	 * @since 2.1.2
	 */
	public function remove_settings() {
		$indexes = $this->get_indexes();

		// If setting has multiple rows then delete from settings index record.
		if ( $indexes !== false ) {

			foreach ( $indexes as $key => $value ) {
				\delete_option( $value );
			}

			\delete_option( $this->index_name );
		}

		foreach ( $this->option_names as $key => $value ) {
			\delete_option( $value );
		}

		if ( \get_option( $this->settings_name ) !== false ) {
			\delete_option( $this->settings_name );
		}
	}



	/**
	 * Gets the latest setting row name if exists therefore return as false
	 *
	 * @return string|bool
	 */
	public function last_index() {
		if ( $this->settings_row > -1 ) {
			return $this->settings_row;
		}

		return false;
	}



	/**
	 * The Ajax data loader function
	 *
	 * @since 2.2.0
	 *
	 * @param array  $args [ string static method hook, mixed parameters... ].
	 * @param string $loading_text text message to be displayed while page is loading.
	 *
	 * @return object $this
	 */
	protected function ajax_loader( array $args, $loading_text = null ) {
		$field = $this->current_field;

		if ( empty( $loading_text ) ) {
			$loading_text = 'Please wait...';
		}

		$hook = array_shift( $args );

		$conf['hook'] = $hook;
		$conf['params'] = $args;
		$conf['field'] = $field;

		$conf['field']['loading_text'] = $loading_text;
		$conf['field']['value'] = $this->get_value( $field['key'] );

		$this->ajax_loads[ $field['key'] ] = $conf;

		return $this;
	}



	/**
	 * Set template file url
	 *
	 * @since 2.2.0
	 *
	 * @param string $path Path to the template files.
	 */
	public function set_theme( $path ) {
		Templates::set( $path );
	}



	/**
	 * Enable reset feature for fields
	 *
	 * @since 2.3.3
	 *
	 * @param array $params Configuration parameters.
	 * @param array $factory_defaults Optional factory default values to use for reset.
	 *
	 * @return $this
	 */
	public function enable_reset( $params = [], $factory_defaults = [] ) {
		$accepted = [
			'confirm_message' => esc_html__( 'Are you sure you want to reset this field to its default value?', 'wasp' ),
			'button_text' => esc_html__( 'Reset', 'wasp' ),
			'button_class' => 'button button-secondary button-small',
			'wrapper_class' => 'wasp-reset-wrapper',
			'show_on_fields' => [], // Empty = all fields, or specify field names.
		];

		$params = array_intersect_key( $params, $accepted );
		$this->reset_params = array_merge( $accepted, $params );
		$this->reset_enabled = true;

		// If factory_defaults provided, merge with default_values (factory takes priority)
		// Otherwise, use default_values as-is.
		if ( ! empty( $factory_defaults ) ) {
			$this->default_values = array_merge( $this->default_values, $factory_defaults );
		}

		// Store initial values (current saved values) for reset.
		$this->store_initial_values();

		// Register AJAX handlers.
		$this->register_reset_ajax_handlers();

		return $this;
	}



	/**
	 * Store initial values for reset functionality
	 *
	 * @since 2.3.3
	 */
	protected function store_initial_values() {
		if ( $this->initial_values === null ) {
			$this->initial_values = $this->setting_values !== null ? $this->setting_values : [];
		}
	}



	/**
	 * Register AJAX handlers for reset functionality
	 *
	 * @since 2.3.3
	 */
	protected function register_reset_ajax_handlers() {
		add_action( 'wp_ajax_wasp_reset_field', [ $this, 'ajax_reset_field' ] );
		add_action( 'wp_ajax_wasp_reset_all_fields', [ $this, 'ajax_reset_all_fields' ] );
	}



	/**
	 * AJAX handler for resetting a single field
	 *
	 * @since 2.3.3
	 */
	public function ajax_reset_field() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), $this->reset_nonce_action ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Security check failed.', 'wasp' ) ] );
			return;
		}

		// Verify user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'wasp' ) ] );
			return;
		}

		$field_name    = isset( $_POST['field_name'] ) ? sanitize_text_field( wp_unslash( $_POST['field_name'] ) ) : '';
		$field_type    = isset( $_POST['field_type'] ) ? sanitize_text_field( wp_unslash( $_POST['field_type'] ) ) : 'text';
		$settings_name = isset( $_POST['settings_name'] ) ? sanitize_text_field( wp_unslash( $_POST['settings_name'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$default_value = isset( $_POST['default_value'] ) ? wp_unslash( $_POST['default_value'] ) : null;

		if ( empty( $field_name ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Field name is required.', 'wasp' ) ] );
			return;
		}

		// Get current settings from database.
		$current_settings = get_option( $settings_name, [] );
		if ( ! is_array( $current_settings ) ) {
			$current_settings = [];
		}

		// Update the setting with default value.
		if ( $default_value !== null ) {
			$current_settings[ $field_name ] = $default_value;
		} else {
			unset( $current_settings[ $field_name ] );
		}

		$result = update_option( $settings_name, $current_settings );

		// Return new value and type for proper UI update.
		wp_send_json_success(
			[
				'message' => esc_html__( 'Field reset successfully.', 'wasp' ),
				'value' => $default_value,
				'field_name' => $field_name,
				'field_type' => $field_type,
				'debug' => [
					'result' => $result,
					'settings' => $current_settings,
				],
			]
		);
	}



	/**
	 * AJAX handler for resetting all fields
	 *
	 * @since 2.3.3
	 */
	public function ajax_reset_all_fields() {
		// Verify nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), $this->reset_nonce_action ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Security check failed.', 'wasp' ) ] );
			return;
		}

		// Verify user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'wasp' ) ] );
			return;
		}

		$settings_name = isset( $_POST['settings_name'] ) ? sanitize_text_field( wp_unslash( $_POST['settings_name'] ) ) : $this->settings_name;

		// Get current settings from database (AJAX is stateless).
		$current_settings = get_option( $settings_name, [] );
		if ( ! is_array( $current_settings ) ) {
			$current_settings = [];
		}

		// Reset all fields to default values.
		foreach ( $this->default_values as $key => $value ) {
			$current_settings[ $key ] = $value;
		}

		// Remove any saved values that don't have defaults.
		foreach ( array_keys( $current_settings ) as $key ) {
			if ( ! isset( $this->default_values[ $key ] ) ) {
				unset( $current_settings[ $key ] );
			}
		}

		update_option( $settings_name, $current_settings );

		wp_send_json_success(
			[
				'message' => esc_html__( 'All fields reset successfully.', 'wasp' ),
				'values' => $this->default_values,
			]
		);
	}



	/**
	 * Check if reset is enabled
	 *
	 * @since 2.3.3
	 *
	 * @return boolean
	 */
	public function is_reset_enabled() {
		return $this->reset_enabled;
	}



	/**
	 * Get reset parameters
	 *
	 * @since 2.3.3
	 *
	 * @return array
	 */
	public function get_reset_params() {
		return $this->reset_params;
	}



	/**
	 * Get initial value for a field
	 *
	 * @since 2.3.3
	 *
	 * @param string $field_name Field name.
	 * @return mixed Initial value of the field.
	 */
	public function get_initial_value( $field_name ) {
		return isset( $this->initial_values[ $field_name ] ) ? $this->initial_values[ $field_name ] : null;
	}



	/**
	 * Check if a field should show reset button
	 *
	 * @since 2.3.3
	 *
	 * @param string $field_name Field name.
	 *
	 * @return bool
	 */
	public function should_show_reset_button( $field_name ) {
		if ( ! $this->reset_enabled ) {
			return false;
		}

		$show_on_fields = $this->reset_params['show_on_fields'];

		// If empty, show on all fields.
		if ( empty( $show_on_fields ) ) {
			return true;
		}

		// Check if field is in the list.
		return in_array( $field_name, $show_on_fields );
	}



	/**
	 * Render reset button HTML
	 *
	 * @since 2.3.3
	 *
	 * @param string $field_name Field name.
	 * @param string $field_key  Field key.
	 * @param string $field_type Field type.
	 * @return string
	 */
	public function render_reset_button( $field_name, $field_key, $field_type = 'text' ) {
		if ( ! $this->should_show_reset_button( $field_key ) ) {
			return '';
		}

		$params = $this->reset_params;
		$nonce = wp_create_nonce( $this->reset_nonce_action );

		$wrapper_class = isset( $params['wrapper_class'] ) ? esc_attr( $params['wrapper_class'] ) : 'wasp-reset-wrapper';
		$button_class = isset( $params['button_class'] ) ? esc_attr( $params['button_class'] ) : 'button button-secondary button-small';
		$button_text = isset( $params['button_text'] ) ? esc_html( $params['button_text'] ) : esc_html__( 'Reset', 'wasp' );
		$confirm_message = isset( $params['confirm_message'] ) ? esc_attr( $params['confirm_message'] ) : esc_html__( 'Are you sure you want to reset this field?', 'wasp' );

		// Get DEFAULT value for this field from default_values (not saved values!).
		$default_value = isset( $this->default_values[ $field_key ] ) ? $this->default_values[ $field_key ] : '';

		// JSON encode to handle special characters, newlines, quotes properly.
		$default_value_json = wp_json_encode( $default_value );

		$html = sprintf(
			'<span class="%s" data-field="%s" data-nonce="%s" data-confirm="%s" data-settings-name="%s" data-field-type="%s">',
			$wrapper_class,
			esc_attr( $field_name ),
			esc_attr( $nonce ),
			esc_attr( $confirm_message ),
			esc_attr( $this->settings_name ),
			esc_attr( $field_type )
		);

		// Hidden input for default value (more reliable than data attribute for complex values).
		$html .= sprintf(
			'<input type="hidden" class="wasp-reset-default-value" name="_wasp_reset_default[%s]" value="%s">',
			esc_attr( $field_key ),
			esc_attr( $default_value_json )
		);

		$html .= sprintf(
			'<button type="button" class="%s wasp-reset-btn" data-action="single">%s</button>',
			$button_class,
			$button_text
		);

		$html .= '</span>';

		return $html;
	}
}
