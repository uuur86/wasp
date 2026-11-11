<?php
/**
 * WP Admin Settings Page
 *
 * @package wasp
 */

namespace WaspCreators;

use WaspCreators\Fields;

/**
 * Enqueues and handles AJAX loader script and data.
 */
class Ajax {

	/**
	 * AJAX action name.
	 *
	 * @var string
	 */
	const ACTION_NAME = 'ajax_loader_action';

	/**
	 * Plugin domain.
	 *
	 * @var string
	 */
	protected $domain;

	/**
	 * AJAX loader loads.
	 *
	 * @var array
	 */
	protected $loads;

	/**
	 * Capability required to run the AJAX loaders.
	 *
	 * @since 3.1.0
	 * @var string
	 */
	protected $capability = 'manage_options';



	/**
	 * Constructor - sets up AJAX hooks.
	 *
	 * @param string $domain Plugin domain.
	 */
	public function __construct( string $domain ) {

		$this->domain = $domain;
		$hook_name    = 'wp_ajax_wasp_' . self::ACTION_NAME;

		\add_action( $hook_name, [ $this, 'ajax_loader_action' ] );

	}




	/**
	 * Sets the capability required to run the AJAX loaders.
	 *
	 * @since 3.1.0
	 *
	 * @param string $capability Capability.
	 */
	public function set_capability( $capability ) {

		if ( is_string( $capability ) && $capability !== '' ) {

			$this->capability = $capability;

		}

	}




	/**
	 * Loads AJAX handler for fields.
	 *
	 * @param array $loads Array of AJAX loader configurations.
	 */
	public function load_handler( $loads = [] ) {

		if ( empty( $loads ) || ! is_array( $loads ) ) {

			return false;

		}

		$this->loads = $loads;

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_ajax_enqueue' ] );

	}




	/**
	 * Enqueues AJAX loader script and localizes data.
	 */
	public function admin_ajax_enqueue() {

		$handle_name = $this->domain . '_wasp_ajax_loader';

		// Enqueued script with localized data.
		\wp_enqueue_script( $handle_name, Wasp::asset_url( 'assets/js/ajax-loader.js' ), [ 'jquery' ], '1.0.4', true );

		// Localize the script with new data.
		$values = array(
			'ajax_url'    => \admin_url( 'admin-ajax.php' ),
			'action_name' => 'wasp_ajax_loader_action',
			'nonce'       => \wp_create_nonce( 'wasp_ajax_loader_action' ),
			'fields'      => $this->loads,
		);

		\wp_localize_script( $handle_name, 'ajax_loader_object', $values );

	}



	/**
	 * Admin Ajax response output function.
	 *
	 * Since 3.1.0 the hook and parameters always come from the server-side field
	 * configuration registered in this request (load_form() runs on admin_init,
	 * before AJAX actions). The request may only name which fields to load.
	 * Before 3.1.0 the hook name and parameters were taken from the request and
	 * called directly, which let anyone holding the nonce call any PHP function.
	 */
	public function ajax_loader_action() {

		// Several forms register this action; answer only for this form's fields.
		if ( empty( $this->loads ) || ! is_array( $this->loads ) ) {

			return;

		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only the keys are used, and only to look up server-side config.
		$requested = ( isset( $_REQUEST['fields'] ) && is_array( $_REQUEST['fields'] ) ) ? array_keys( wp_unslash( $_REQUEST['fields'] ) ) : [];
		$keys      = array_values( array_intersect( array_map( 'strval', $requested ), array_map( 'strval', array_keys( $this->loads ) ) ) );

		if ( empty( $keys ) ) {

			return;

		}

		$nonce = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';

		if ( ! \wp_verify_nonce( $nonce, 'wasp_ajax_loader_action' ) || ! \current_user_can( $this->capability ) ) {

			\wp_die( '', '', [ 'response' => 403 ] );

		}

		$has_option_list = [ 'radio', 'select', 'multi_text_input', 'checkbox' ];
		$result_json     = [];

		foreach ( $keys as $key ) {

			$conf       = $this->loads[ $key ];
			$hook       = isset( $conf['hook'] ) ? $conf['hook'] : null;
			$params     = ( isset( $conf['params'] ) && is_array( $conf['params'] ) ) ? array_values( $conf['params'] ) : [];
			$field      = isset( $conf['field'] ) && is_array( $conf['field'] ) ? $conf['field'] : [];
			$field_type = isset( $field['type'] ) ? $field['type'] : '';

			$func_result = is_callable( $hook ) ? call_user_func_array( $hook, $params ) : false;

			if ( $func_result === false ) {

				$field['not_found'] = \esc_html__( 'Data could not be loaded.', 'wasp' );

			} elseif ( $field_type === 'text_input' ) {

				$field['value'] = $func_result;

			} elseif ( $field_type === 'textarea' ) {

				$field['text'] = $func_result;

			} elseif ( in_array( $field_type, $has_option_list, true ) && is_array( $func_result ) ) {

				$field['options'] = $func_result;

			}

			$result_json[ $key ]['value'] = Fields::prepare( $field );

		}

		echo \wp_json_encode( $result_json );

		\wp_die();

	}

}
