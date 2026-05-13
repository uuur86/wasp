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
	 * Constructor - sets up AJAX hooks.
	 *
	 * @param string $domain Plugin domain.
	 */
	public function __construct( string $domain ) {
		$this->domain   = $domain;
		$hook_name      = 'wp_ajax_wasp_' . self::ACTION_NAME;

		\add_action( $hook_name, [ $this, 'ajax_loader_action' ] );
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
		\wp_enqueue_script( $handle_name, \plugin_dir_url( __FILE__ ) . '../../assets/js/ajax-loader.js', [ 'jquery' ], '1.0.4', true );

		// Localize the script with new data.
		$values = array(
			'ajax_url'      => \admin_url( 'admin-ajax.php' ),
			'action_name'   => 'wasp_ajax_loader_action',
			'nonce'         => \wp_create_nonce( 'wasp_ajax_loader_action' ),
			'fields'        => $this->loads,
		);

		\wp_localize_script( $handle_name, 'ajax_loader_object', $values );
	}



	/**
	 * Admin Ajax response output function
	 */
	public function ajax_loader_action() {
		$has_option_list = [ 'radio', 'select', 'multi_text_input', 'checkbox' ];

		$nonce  = isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Complex array, processed in loop.
		$fields = isset( $_REQUEST['fields'] ) ? wp_unslash( $_REQUEST['fields'] ) : [];

		if ( \wp_verify_nonce( $nonce, 'wasp_ajax_loader_action' ) ) {
			$result_json = [];

			foreach ( $fields as $key => $field ) {
				$hook           = stripslashes( $field['hook'] );
				$hook_exp       = explode( '::', $hook );
				$class_name     = $hook_exp[0];
				$method_name    = $hook_exp[1];
				$params         = isset( $field['params'] ) ? $field['params'] : [];
				$field_type     = $field['field']['type'];

				if ( empty( $params ) || ! is_array( $params ) ) {
					$params = [];
				}

				if ( ! method_exists( $class_name, $method_name ) ) {
					echo esc_html( $hook ) . ' not found!';
				}

				$func_result = call_user_func_array( $hook, $params );

				$not_found_text = 'not found!!!!!';

				if ( $func_result === false ) {
					$field['field']['not_found'] = $not_found_text;
				} elseif ( $field_type === 'text_input' ) {
					$field['field']['value'] = $func_result;
				} elseif ( $field_type === 'textarea' ) {
					$field['field']['text'] = $func_result;
				} elseif ( in_array( $field_type, $has_option_list, true ) ) {
					if ( is_array( $func_result ) ) {
						$field['field']['options'] = $func_result;
					}
				}

				$result = Fields::prepare( $field['field'] );

				$result_json[ $key ]['value'] = $result;
			}

			echo wp_json_encode( $result_json );
		}

		\wp_die();
	}
}
