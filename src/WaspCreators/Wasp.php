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
 * @version 3.1.0
 */

namespace WaspCreators;

if ( class_exists( '\\WaspCreators\\Wasp' ) || ! defined( 'ABSPATH' ) ) {
	return;
}

use WaspCreators\Ajax;
use WaspCreators\FormReset;
use WaspCreators\Templates;
use WaspCreators\Storage;

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
	const VERSION = '3.1.0';

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
	 * @var boolean
	 */
	protected $reset_enabled = false;

	/**
	 * Form reset object container
	 *
	 * @var FormReset
	 */
	protected $form_reset;



	/**
	 * Option storage backend.
	 *
	 * @since 3.1.0
	 * @var Storage
	 */
	protected $storage;

	/**
	 * Capability required to save the form.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	protected $capability = 'manage_options';

	/**
	 * Whether the form is saved through the network admin (multisite + network storage).
	 *
	 * @since 3.1.0
	 * @var bool
	 */
	protected $network_form = false;

	/**
	 * Theme path used by this form's fields.
	 *
	 * @since 3.1.0
	 * @var string|null
	 */
	protected $theme = null;

	/**
	 * Values of the fallback layer (for example network defaults), or null when disabled.
	 *
	 * @since 3.1.0
	 * @var array|null
	 */
	protected $fallback_values = null;

	/**
	 * Fallback options: labels and locked keys.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	protected $fallback_args = [];

	/**
	 * Keys of fields that can inherit from the fallback layer.
	 *
	 * @since 3.1.0
	 * @var array
	 */
	protected $inherit_keys = [];

	/**
	 * Name of the hidden input group that carries the "override" toggles.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	const OVERRIDE_KEY = '__wasp_override';

	/**
	 * True while write_values() stores values that must not go through the form middleware.
	 *
	 * @since 3.1.0
	 * @var bool
	 */
	protected $raw_write = false;



	/**
	 * Constructor - Sets up the Wasp settings page with title, settings name, domain, and optional row handling.
	 *
	 * @param string   $page Unique page name.
	 * @param string   $setting_name Setting name will be saved in options.
	 * @param string   $domain Domain for localize.
	 * @param int|null $row Get variable name for multiple form row.
	 * @param array    $options {
	 *     Optional. Since 3.1.0.
	 *
	 *     @type string $storage    'site' (default) stores values with get_option(); 'network' stores them
	 *                              network wide with get_site_option() and saves the form from the network admin.
	 *     @type string $capability Capability required to save. Default 'manage_options', or
	 *                              'manage_network_options' for network storage.
	 * }
	 */
	public function __construct( string $page, string $setting_name, string $domain, $row = null, array $options = [] ) {
		global $pagenow;

		$storage_mode     = isset( $options['storage'] ) ? (string) $options['storage'] : Storage::SITE;
		$this->storage    = new Storage( $storage_mode );
		$this->capability = $this->storage->is_network() ? 'manage_network_options' : 'manage_options';

		if ( ! empty( $options['capability'] ) && is_string( $options['capability'] ) ) {
			$this->capability = $options['capability'];
		}

		// The network admin cannot save through options.php, so network forms use their own handler.
		$this->network_form = $this->storage->is_network() && function_exists( 'is_multisite' ) && \is_multisite();

		if ( ! isset( $page ) || empty( $page ) ) {
			return;
		}

		$this->ajax = new Ajax( $domain );
		$this->ajax->set_capability( $this->capability );
		$this->page_name     = $page;
		$this->domain        = $domain;
		$this->settings_name = (string) $setting_name . '_settings';
		$this->index_name    = (string) $setting_name . '_index';
		$this->option_names  = [
			'updated' => (string) $this->settings_name . '_form_updated',
			'errors'  => (string) $this->settings_name . '_form_errors',
		];

		// for multiple settings field.
		if ( ! empty( $row ) ) {
			$row           = $this->get_form_data( $row );
			$this->indexes = $this->get_indexes();

			// Find the latest row value.
			if ( ! empty( $this->indexes ) && is_array( $this->indexes ) ) {
				$indexes       = $this->indexes;
				$last_row      = end( $indexes );
				$indexes       = array_flip( $indexes );
				$last_settings = $this->storage->get( $indexes[ $last_row ] );

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
			if ( false === $this->storage->get( $this->index_name ) ) {
				// Add index option value for first time.
				$this->storage->add( $this->index_name, [ $this->settings_name => $this->settings_row ] );
			} else {
				// Update indexes.
				$this->indexes[ $this->settings_name ] = $this->settings_row;

				// Update index option value.
				$this->storage->update( $this->index_name, $this->indexes );
			}
		}

		// Append setting_values.
		$settings = $this->storage->get( $this->settings_name );

		if ( false === $settings ) {
			$this->storage->add( $this->settings_name, [] );
			$this->setting_values = [];
		} else {
			$this->setting_values = is_array( $settings ) ? $settings : [];
		}

		$this->is_ready = true;

		// Network form submission (network/edit.php?action=<page>) is handled by handle_network_save().
		if ( $this->network_form && $pagenow === 'edit.php' ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['action'] ) || $_GET['action'] !== $page ) {
				$this->is_ready = false;
			}

			return;
		}

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
		$updated_name   = $this->option_names['updated'];
		$option_updated = $this->storage->get( $updated_name );

		if ( ! $this->check_errors() && $option_updated === '1' ) {
			$this->form_updated = true;
			$this->storage->delete( $updated_name );
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
			'cond'   => true,
			'name'   => null,
			'title'  => null,
			'desc'   => null,
			'after'  => null,
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
			'desc'  => $desc,
		];

		$this->current_section       = $this->sections[ $this->section ];
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
			$this->storage->update( $this->index_name, $indexes );
		}

		if ( ! empty( $this->storage->get( $name, null ) ) ) {
			$this->storage->delete( $name );
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
		// With a fallback layer: locked keys always show the fallback, an override
		// (even an empty one) wins, otherwise the fallback value is shown.
		if ( $this->has_fallback() ) {
			if ( ! $this->is_locked( $name ) && is_array( $this->setting_values ) && array_key_exists( $name, $this->setting_values ) ) {
				return $this->setting_values[ $name ];
			}

			if ( array_key_exists( $name, $this->fallback_values ) ) {
				return $this->fallback_values[ $name ];
			}

			return isset( $this->default_values[ $name ] ) ? $this->default_values[ $name ] : null;
		}

		$value = $this->get_setting( $name );

		// If we have a saved value (including empty string), use it.
		// Only fall back to default when the key doesn't exist at all (null).
		if ( $value !== null ) {
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
	 * Get default values
	 *
	 * @return array
	 */
	public function get_default_values() {
		return $this->default_values;
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
		} elseif ( $sanitize === 'taginput' ) {
			// Store regex pattern for per-value validation (optional).
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
			'cond'              => true,
			'type'              => null,
			'name'              => null,
			'label'             => null,
			'value'             => null,
			'default'           => null,
			'option'            => null,
			'options'           => null,
			'after'             => null,
			'before'            => null,
			'ajax'              => [],
			'sanitize'          => 'text_field',
			'sanitize_callback' => null,
			'save'              => false,
			'field_type'        => 'url',
			'placeholder'       => null,
			'presets'           => null,
			'inherit'           => true,
		];

		$args = array_intersect_key( $args, $accepted );
		$args = array_merge( $accepted, $args );

		$cond              = $args['cond'];
		$type              = $args['type'];
		$name              = $args['name'];
		$label             = $args['label'];
		$value             = $args['value'];
		$default           = $args['default'];
		$option            = $args['option'];
		$options           = $args['options'];
		$after             = $args['after'];
		$before            = $args['before'];
		$ajax              = $args['ajax'];
		$sanitize          = $args['sanitize'];
		$sanitize_callback = $args['sanitize_callback'];
		$save              = $args['save'];
		$field_type        = $args['field_type'];
		$placeholder       = $args['placeholder'];
		$presets           = $args['presets'];

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
		} elseif ( $type === 'taginput' || $type === 'taginput_regex' ) {
			// Normalize legacy taginput_regex → taginput with field_type 'regex'.
			if ( $type === 'taginput_regex' ) {
				$type       = 'taginput';
				$field_type = 'regex';
			}

			$sanitize = 'taginput';
		}

		$item_id = $this->settings_name . '_' . $name . '_' . $type;
		$item_name = $this->settings_name . '[' . $name . ']';

		$this->current_field = [
			'key'               => $name,
			'type'              => $type,
			'id'                => $item_id,
			'name'              => $item_name,
			'label'             => \esc_attr( $label ),
			'option'            => $option,
			'options'           => $options,
			'sanitize'          => $sanitize,
			'sanitize_callback' => $sanitize_callback,
			'placeholder'       => isset( $placeholder ) ? $placeholder : '',
			'presets'           => isset( $presets ) ? $presets : '',
			'field_type'        => isset( $field_type ) ? $field_type : 'url',
			'inherit'           => ( false !== $args['inherit'] ) && ( 'file_input' !== $type ),
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
	 * It adds a setting field.
	 * 
	 * @param string       	 $id       Field ID.
	 * @param string       	 $label    Field label.
	 * @param callable|false $callback Callback function.
	 * @param string       	 $page     Page slug.
	 * @param string       	 $section  Section ID.
	 * @param array        	 $args     Field arguments.
	 */
	public function add_field($id, $label, $callback, $page, $section, $args)
	{
		\add_settings_field(
			$id,
			$label,
			$callback,
			$page,
			$section,
			$args
		);
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

		// For taginput, pass options as regex pattern for per-value validation.
		$sanitize_options = $args['options'];

		if ( $args['sanitize'] === 'taginput' ) {
			$sanitize_options = is_string( $args['options'] ) ? $args['options'] : null;
		}

		$this->register_sanitize(
			$args['type'],
			$args['key'],
			$sanitize_options,
			$args['sanitize']
		);

		// Attach sanitize_callback for taginput if provided by the consuming project.
		if ( $args['sanitize'] === 'taginput' && ! empty( $args['sanitize_callback'] ) && is_callable( $args['sanitize_callback'] ) ) {
			$this->sanitize[ $args['key'] ]['callback'] = $args['sanitize_callback'];
		}

		$field_args = [
			'type' => $args['type'],
			'id' => $args['id'],
			'name' => $args['name'],
			'value' => $this->get_value( $args['key'] ),
			'placeholder' => isset( $args['placeholder'] ) ? $args['placeholder'] : '',
			'presets' => isset( $args['presets'] ) ? $args['presets'] : '',
			'field_type' => isset( $args['field_type'] ) ? $args['field_type'] : 'url',
		];

		if ( ! empty( $this->theme ) ) {
			$field_args['theme'] = $this->theme;
		}

		if ( $this->has_fallback() && ! empty( $args['inherit'] ) ) {
			$key                  = $args['key'];
			$this->inherit_keys[] = $key;
			$locked               = $this->is_locked( $key );
			$inherited_value      = array_key_exists( $key, $this->fallback_values )
				? $this->fallback_values[ $key ]
				: ( isset( $this->default_values[ $key ] ) ? $this->default_values[ $key ] : null );

			$field_args['inherit'] = [
				'id'           => $args['id'] . '_inherit',
				'name'         => $this->settings_name . '[' . self::OVERRIDE_KEY . '][' . $key . ']',
				'value'        => $inherited_value,
				'overridden'   => ! $locked && is_array( $this->setting_values ) && array_key_exists( $key, $this->setting_values ),
				'locked'       => $locked,
				'label'        => $this->fallback_args['label'],
				'source_label' => $this->fallback_args['source_label'],
				'locked_label' => $this->fallback_args['locked_label'],
				'empty_label'  => $this->fallback_args['empty_label'],
				'revert_label' => $this->fallback_args['revert_label'],
				'auto'         => $this->fallback_args['auto_override'],
			];
		}

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
		if ( $this->reset_enabled && $this->form_reset ) {
			// init reset class.
			$field_args = $this->form_reset->process_field_args( $args, $field_args );
		}

		// For taginput types, wrap content in taginput div with field_type.
		if ( $args['type'] === 'taginput' ) {
			$taginput_div = '<div class="taginput-wrapper" data-field-type="' . esc_attr( $args['field_type'] ) . '">';
			if ( isset( $field_args['html_before'] ) ) {
				$field_args['html_before'] = $taginput_div . $field_args['html_before'];
			} else {
				$field_args['html_before'] = $taginput_div;
			}
		}

		$this->add_field(
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

		// Values written by write_values() are already final.
		if ( $this->raw_write ) {
			return $inputs;
		}

		if ( ! is_array( $inputs ) ) {
			$inputs = [];
		}

		// Read and remove the "override" toggles sent by inheritable fields. The group
		// is only present on real form submissions (form_start() adds a marker), so
		// programmatic update_option() calls never drop inherited keys.
		$overrides      = [];
		$is_form_submit = isset( $inputs[ self::OVERRIDE_KEY ] );

		if ( $is_form_submit ) {
			if ( is_array( $inputs[ self::OVERRIDE_KEY ] ) ) {
				$overrides = array_map( 'strval', array_keys( $inputs[ self::OVERRIDE_KEY ] ) );
			}

			unset( $inputs[ self::OVERRIDE_KEY ] );
		}

		if ( ! is_array( $this->sanitize ) ) {
			$this->sanitize = [];
		}

		// Inheritable fields are saved only when their override toggle is on; locked
		// fields are never saved. Dropped keys are not sanitized, and they also leave
		// the stored values so the field goes back to the fallback value.
		$dropped = [];

		if ( $this->has_fallback() && $is_form_submit ) {
			foreach ( $this->inherit_keys as $key ) {
				if ( $this->is_locked( $key ) || ! in_array( (string) $key, $overrides, true ) ) {
					unset( $inputs[ $key ] );
					$dropped[ $key ] = true;
				}
			}
		}

		foreach ( $this->sanitize as $input_key => $prop ) {
			if ( isset( $dropped[ $input_key ] ) ) {
				continue;
			}

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

			// Sanitize taginput - pipe separated values with extensible validation.
			// Validation priority: sanitize_callback > options (regex pattern) > accept as-is.
			if ( $prop['name'] === 'taginput' ) {
				$val      = isset( $inputs[ $input_key ] ) ? $inputs[ $input_key ] : '';
				$callback = isset( $prop['callback'] ) ? $prop['callback'] : null;
				$pattern  = isset( $prop['pattern'] ) ? $prop['pattern'] : null;

				if ( ! empty( $val ) ) {
					$lines     = preg_split( '/\|/', $val );
					$sanitized = [];
					$errors    = [];

					foreach ( $lines as $line ) {
						$line = trim( $line );

						if ( $line === '' ) {
							continue;
						}

						$is_valid = true;

						if ( ! empty( $callback ) && is_callable( $callback ) ) {
							// Custom callback: must return true for valid values.
							$is_valid = (bool) call_user_func( $callback, $line );
						} elseif ( ! empty( $pattern ) ) {
							// Regex pattern: validate each value against the provided pattern.
							$is_valid = (bool) preg_match( '#' . $pattern . '#ui', $line );
						}

						if ( ! $is_valid ) {
							$errors[] = $line;
							continue;
						}

						$sanitized[] = $line;
					}

					$inputs[ $input_key ] = implode( '|', $sanitized );

					if ( ! empty( $errors ) ) {
						/* translators: %s: Comma-separated list of invalid values. */
						$this->add_error(
							'<p>' . sprintf(
								esc_html__( 'Invalid values removed: %s', 'wasp' ),
								esc_html( implode( ', ', $errors ) )
							) . '</p>'
						);
					}
				} else {
					$inputs[ $input_key ] = '';
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
		$this->storage->update( $this->option_names['updated'], '1' );

		$this->save_errors();

		// Merge with prior saved settings.
		$previous = is_array( $this->setting_values ) ? array_diff_key( $this->setting_values, $dropped ) : [];
		$inputs  += $previous;

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
			$this->errors = $this->storage->get( $this->option_names['errors'], null );

			if ( ! empty( $this->errors ) ) {
				$this->storage->delete( $this->option_names['errors'] );
			}
		}

		return $this->errors;
	}



	/**
	 * Saves occured errors to option table.
	 */
	protected function save_errors() {
		if ( ! empty( $this->errors ) ) {
			$this->storage->update( $this->option_names['errors'], $this->errors );
		}
	}



	/**
	 * This function registers setting options
	 */
	public function register() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		global $_POST;

		$this->ajax->load_handler( $this->ajax_loads );

		// Network forms are saved by handle_network_save() on network/edit.php.
		if ( $this->network_form ) {
			\add_action( 'network_admin_edit_' . $this->page_name, [ $this, 'handle_network_save' ] );

			return;
		}

		// Let a custom capability save through options.php.
		if ( 'manage_options' !== $this->capability ) {
			$capability = $this->capability;

			\add_filter(
				'option_page_capability_' . $this->page_name,
				function () use ( $capability ) {
					return $capability;
				}
			);
		}

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
	}



	/**
	 * Saves a network form. Hooked to "network_admin_edit_{page}" by register().
	 *
	 * Mirrors what options.php does for site forms: capability and nonce check,
	 * sanitize, store, then redirect back to the form.
	 *
	 * @since 3.1.0
	 */
	public function handle_network_save() {
		if ( ! \current_user_can( $this->capability ) ) {
			\wp_die( \esc_html__( 'Sorry, you are not allowed to manage these options.', 'wasp' ), '', [ 'response' => 403 ] );
		}

		\check_admin_referer( $this->network_nonce_action(), '_wasp_nonce' );

		$option_name = $this->settings_name;
		$indexes     = $this->get_indexes();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
		if ( is_array( $indexes ) && ! empty( $indexes ) && isset( $_POST['setting_name'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified above.
			$requested = \sanitize_text_field( \wp_unslash( $_POST['setting_name'] ) );

			if ( array_key_exists( $requested, $indexes ) ) {
				$option_name = $requested;
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Verified above; sanitized field by field in settings_input_middleware().
		$raw   = ( isset( $_POST[ $option_name ] ) && is_array( $_POST[ $option_name ] ) ) ? \wp_unslash( $_POST[ $option_name ] ) : [];
		$value = $this->settings_input_middleware( $raw );

		$this->storage->update( $option_name, $value );

		$redirect = \wp_get_referer();

		if ( empty( $redirect ) ) {
			$redirect = \network_admin_url( 'admin.php?page=' . rawurlencode( $this->page_name ) );
		}

		\wp_safe_redirect( \add_query_arg( 'updated', 'true', $redirect ) );
		exit;
	}



	/**
	 * Nonce action used by network forms.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	protected function network_nonce_action() {
		return 'wasp_network_save_' . $this->page_name;
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
		$action = $this->network_form
			? \network_admin_url( 'edit.php?action=' . rawurlencode( $this->page_name ) )
			: 'options.php';

		if ( $this->has_media ) {
			echo '<form method="post" action="' . \esc_url( $action ) . '" enctype="multipart/form-data">';
		} else {
			echo '<form method="post" action="' . \esc_url( $action ) . '">';
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
		if ( $this->reset_enabled && $this->form_reset ) {
			$this->form_reset->enqueue_scripts();
		}

		if ( $this->has_fallback() ) {
			$this->enqueue_inherit_assets();

			// Marks the submission as a form post (see settings_input_middleware()).
			$this->add_hidden_field( $this->settings_name . '[' . self::OVERRIDE_KEY . '][' . self::OVERRIDE_KEY . ']', '1' );
		}

		// Print HTML form tag.
		$this->form();

		// Display necessary hidden fields for settings.
		if ( $this->network_form ) {
			\wp_nonce_field( $this->network_nonce_action(), '_wasp_nonce', ! isset( $this->hidden_fields['_wp_http_referer'] ) );
		} else {
			\settings_fields( $this->page_name );
		}

		$this->add_hidden_field( 'setting_name', $this->settings_name );
		$this->put_hidden_fields();
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
					'fields' => $this->storage->get( $name ),
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

		return $this->storage->get( $this->index_name );
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

			// Index maps option name => row number, so delete by name.
			foreach ( array_keys( (array) $indexes ) as $option_name ) {
				$this->storage->delete( $option_name );
			}

			$this->storage->delete( $this->index_name );
		}

		foreach ( $this->option_names as $key => $value ) {
			$this->storage->delete( $value );
		}

		if ( $this->storage->get( $this->settings_name ) !== false ) {
			$this->storage->delete( $this->settings_name );
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
		$this->theme = $path;

		// Also set the global theme for fields rendered outside this form (AJAX loaders).
		Templates::set( $path );
	}



	/**
	 * Enables a fallback layer: fields show the fallback value until the user
	 * turns on "override" for that field, and only overridden values are saved.
	 *
	 * Typical use is "network default + per-site override" on multisite, but any
	 * two-level setting works (global default + per-item value, parent + child).
	 * Call it before load_form().
	 *
	 * @since 3.1.0
	 *
	 * @param array $values Fallback values keyed by field name.
	 * @param array $args {
	 *     Optional.
	 *
	 *     @type string[] $locked        Field names that always use the fallback and cannot be overridden.
	 *     @type string   $label         Toggle label. Default "Override".
	 *     @type string   $source_label  Label in front of the fallback value. Default "Inherited".
	 *     @type string   $locked_label  Toggle label for locked fields. Default "Locked".
	 *     @type string   $empty_label   Text shown for an empty fallback value. Default "(empty)".
	 *     @type string   $revert_label  Label of a "go back to the inherited value" control, for themes
	 *                                   that show one ([revert_label] in the Inherit template).
	 *                                   Default "Use inherited value".
	 *     @type bool     $auto_override Keep inheritable fields editable: editing a field turns its
	 *                                   override on, getting back to the inherited value turns it off.
	 *                                   Default false (fields stay disabled until the override is on).
	 * }
	 * @return $this
	 */
	public function set_fallback_values( array $values, array $args = [] ) {
		$accepted = [
			'locked'        => [],
			'label'         => \esc_html__( 'Override', 'wasp' ),
			'source_label'  => \esc_html__( 'Inherited', 'wasp' ),
			'locked_label'  => \esc_html__( 'Locked', 'wasp' ),
			'empty_label'   => \esc_html__( '(empty)', 'wasp' ),
			'revert_label'  => \esc_html__( 'Use inherited value', 'wasp' ),
			'auto_override' => false,
		];

		$args                  = array_merge( $accepted, array_intersect_key( $args, $accepted ) );
		$args['locked']        = array_values( array_map( 'strval', (array) $args['locked'] ) );
		$args['auto_override'] = (bool) $args['auto_override'];

		$this->fallback_values = $values;
		$this->fallback_args   = $args;

		return $this;
	}



	/**
	 * Whether a fallback layer is enabled.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	public function has_fallback() {
		return is_array( $this->fallback_values );
	}



	/**
	 * Returns the fallback values, or null when no fallback layer is set.
	 *
	 * @since 3.1.0
	 * @return array|null
	 */
	public function get_fallback_values() {
		return $this->fallback_values;
	}



	/**
	 * Whether a field is locked to its fallback value.
	 *
	 * @since 3.1.0
	 *
	 * @param string $name Field name.
	 * @return bool
	 */
	public function is_locked( $name ) {
		return $this->has_fallback() && in_array( (string) $name, $this->fallback_args['locked'], true );
	}



	/**
	 * Whether the field can inherit from the fallback layer.
	 *
	 * Only fields registered with load_form() / field() in the current request are known.
	 *
	 * @since 3.1.0
	 *
	 * @param string $name Field name.
	 * @return bool
	 */
	public function is_inheritable( $name ) {
		return $this->has_fallback() && in_array( $name, $this->inherit_keys, true );
	}



	/**
	 * Returns the values the form currently resolves to: defaults, then the
	 * fallback layer, then this form's own saved values (locked keys excepted).
	 *
	 * @since 3.1.0
	 * @return array
	 */
	public function get_resolved_values() {
		$own      = is_array( $this->setting_values ) ? $this->setting_values : [];
		$fallback = $this->has_fallback() ? $this->fallback_values : [];

		if ( $this->has_fallback() && ! empty( $this->fallback_args['locked'] ) ) {
			$own = array_diff_key( $own, array_flip( $this->fallback_args['locked'] ) );
		}

		return array_merge( $this->default_values, $fallback, $own );
	}



	/**
	 * Stores the form's values directly, bypassing the form middleware.
	 *
	 * WordPress runs the registered sanitize callback on every update_option() of
	 * this option. The middleware merges previously saved values back in, which is
	 * right for a form post but would undo a programmatic reset. Use this method
	 * for writes that are not form submissions.
	 *
	 * @since 3.1.0
	 *
	 * @param array $values Values to store.
	 * @return bool
	 */
	public function write_values( array $values ) {
		$this->raw_write = true;

		try {
			$result = $this->storage->update( $this->settings_name, $values );
		} finally {
			$this->raw_write = false;
		}

		$this->setting_values = $values;

		return $result;
	}



	/**
	 * Returns the storage backend.
	 *
	 * @since 3.1.0
	 * @return Storage
	 */
	public function get_storage() {
		return $this->storage;
	}



	/**
	 * Returns the capability required to save the form.
	 *
	 * @since 3.1.0
	 * @return string
	 */
	public function get_capability() {
		return $this->capability;
	}



	/**
	 * Enqueues the small script and style used by inheritable fields.
	 *
	 * @since 3.1.0
	 */
	protected function enqueue_inherit_assets() {
		$base_file = dirname( __DIR__, 2 ) . '/wasp.php';

		\wp_enqueue_script( 'wasp-inherit', \plugins_url( 'assets/js/wasp-inherit.js', $base_file ), [], self::VERSION, true );
		\wp_enqueue_style( 'wasp-inherit', \plugins_url( 'assets/css/wasp-inherit.css', $base_file ), [], self::VERSION );
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
		$this->reset_enabled = true;

		$accepted = [
			'confirm_message' => esc_html__( 'Are you sure you want to reset this field to its default value?', 'wasp' ),
			'button_text'     => esc_html__( 'Reset', 'wasp' ),
			'button_class'    => 'button button-secondary button-small',
			'wrapper_class'   => 'wasp-reset-wrapper',
			'show_on_fields'  => [], // Empty = all fields, or specify field names.
		];

		$params = array_intersect_key( $params, $accepted );
		$params = array_merge( $accepted, $params );

		// If factory_defaults provided, merge with default_values (factory takes priority)
		// Otherwise, use default_values as-is.
		if ( ! empty( $factory_defaults ) ) {
			$this->default_values = array_merge( $this->default_values, $factory_defaults );
		}

		if ( ! $this->form_reset ) {
			$this->form_reset = new FormReset( $this, $params );
		}

		return $this;
	}
}
