<?php
/**
 * Helper class for Wasp form reset feature.
 *
 * @package wasp
 */

namespace WaspCreators;

/**
 * Reset form fields to their factory default values.
 *
 * Provides single-field and bulk reset functionality via AJAX.
 * Default values are resolved server-side to prevent client-side
 * tampering and encoding issues.
 *
 * @since 2.3.3
 */
class FormReset {

	/**
	 * Wasp instance (injected dependency).
	 *
	 * @var Wasp
	 */
	protected $wasp;

	/**
	 * Reset feature parameters (button text, classes, confirm message).
	 *
	 * @var array
	 */
	protected $reset_params = [];

	/**
	 * Reset nonce action name.
	 *
	 * @var string
	 */
	protected $reset_nonce_action = 'wasp_reset_field_nonce';

	/**
	 * Constructor.
	 *
	 * @param Wasp  $wasp   Wasp instance.
	 * @param array $params Reset parameters.
	 */
	public function __construct( Wasp $wasp, $params = [] ) {
		$this->wasp         = $wasp;
		$this->reset_params = $params;

		$this->register_reset_ajax_handlers();
	}

	/**
	 * Process field arguments to add reset button.
	 *
	 * @param array $args       Field generic arguments.
	 * @param array $field_args Field specific arguments.
	 * @return array Modified field arguments.
	 */
	public function process_field_args( $args, $field_args ) {
		if ( $this->should_show_reset_button( $args['key'] ) ) {
			$reset_button = $this->render_reset_button( $args['name'], $args['key'], $args['type'] );

			// For taginput types, reset button goes before closing </div>.
			if ( $args['type'] === 'taginput' ) {
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
		} elseif ( $args['type'] === 'taginput' ) {
			// For taginput without reset, still need closing div.
			if ( ! isset( $field_args['html_after'] ) ) {
				$field_args['html_after'] = '</div>';
			}
		}

		return $field_args;
	}

	/**
	 * Enqueue JavaScript and CSS for reset functionality.
	 *
	 * @since 2.3.3
	 */
	public function enqueue_scripts() {
		static $enqueued = false;

		if ( $enqueued ) {
			return;
		}

		$enqueued = true;

		// The URL used to be derived from ABSPATH, which broke when wasp lives outside the
		// WordPress folder (symlinked vendor directories). See Wasp::asset_url().
		$script_url = Wasp::asset_url( 'assets/js/wasp-reset.js' );
		$style_url  = Wasp::asset_url( 'assets/css/wasp-reset.css' );

		$nonce = wp_create_nonce( $this->reset_nonce_action );

		wp_enqueue_script( 'wasp-reset', $script_url, [ 'jquery' ], '1.1.0', true );
		wp_enqueue_style( 'wasp-reset', $style_url, [], '1.0.0' );

		wp_localize_script(
			'wasp-reset',
			'waspReset',
			[
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => $nonce,
				'settingsName' => $this->wasp->get_settings_name(),
				'messages'     => [
					'confirm'   => esc_attr( $this->reset_params['confirm_message'] ),
					'resetting' => esc_html__( 'Resetting...', 'wasp' ),
					'success'   => esc_html__( 'Field reset successfully.', 'wasp' ),
					'error'     => esc_html__( 'Error resetting field.', 'wasp' ),
				],
			]
		);
	}

	/**
	 * Check if a field should show reset button.
	 *
	 * @since 2.3.3
	 *
	 * @param string $field_name Field name.
	 * @return bool
	 */
	public function should_show_reset_button( $field_name ) {
		$show_on_fields = isset( $this->reset_params['show_on_fields'] ) ? $this->reset_params['show_on_fields'] : [];

		// If empty, show on all fields.
		if ( empty( $show_on_fields ) ) {
			return true;
		}

		// Check if field is in the list.
		return in_array( $field_name, $show_on_fields, true );
	}

	/**
	 * Render reset button HTML.
	 *
	 * Default values are resolved server-side via AJAX — only the field key
	 * is stored in the DOM. This avoids JSON/esc_attr double-encoding issues
	 * and prevents client-side value manipulation.
	 *
	 * @since 2.3.3
	 *
	 * @param string $field_name Field name (settings_name[key]).
	 * @param string $field_key  Field key.
	 * @param string $field_type Field type.
	 * @return string Button HTML.
	 */
	public function render_reset_button( $field_name, $field_key, $field_type = 'text' ) {
		if ( ! $this->should_show_reset_button( $field_key ) ) {
			return '';
		}

		$params = $this->reset_params;
		$nonce  = wp_create_nonce( $this->reset_nonce_action );

		$wrapper_class   = isset( $params['wrapper_class'] ) ? esc_attr( $params['wrapper_class'] ) : 'wasp-reset-wrapper';
		$button_class    = isset( $params['button_class'] ) ? esc_attr( $params['button_class'] ) : 'button button-secondary button-small';
		$button_text     = isset( $params['button_text'] ) ? esc_html( $params['button_text'] ) : esc_html__( 'Reset', 'wasp' );
		$confirm_message = isset( $params['confirm_message'] ) ? esc_attr( $params['confirm_message'] ) : esc_html__( 'Are you sure you want to reset this field?', 'wasp' );

		$html = sprintf(
			'<span class="%s" data-field="%s" data-nonce="%s" data-confirm="%s" data-settings-name="%s" data-field-type="%s">',
			$wrapper_class,
			esc_attr( $field_name ),
			esc_attr( $nonce ),
			esc_attr( $confirm_message ),
			esc_attr( $this->wasp->get_settings_name() ),
			esc_attr( $field_type )
		);

		$html .= sprintf(
			'<button type="button" class="%s wasp-reset-btn" data-action="single">%s</button>',
			$button_class,
			$button_text
		);

		$html .= '</span>';

		return $html;
	}

	/**
	 * Register AJAX handlers for reset functionality.
	 *
	 * @since 2.3.3
	 */
	protected function register_reset_ajax_handlers() {
		add_action( 'wp_ajax_wasp_reset_field', [ $this, 'ajax_reset_field' ] );
		add_action( 'wp_ajax_wasp_reset_all_fields', [ $this, 'ajax_reset_all_fields' ] );
	}

	/**
	 * Validate common AJAX prerequisites (nonce + capability).
	 *
	 * @return bool True if validation passes, false otherwise (sends JSON error).
	 */
	protected function validate_ajax_request() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), $this->reset_nonce_action ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Security check failed.', 'wasp' ) ] );
			return false;
		}

		if ( ! current_user_can( $this->wasp->get_capability() ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'wasp' ) ] );
			return false;
		}

		return true;
	}

	/**
	 * Whether this request targets this form.
	 *
	 * Several Wasp forms can be loaded at once and each registers the same AJAX
	 * actions, so a handler only answers for its own settings name. The option
	 * that gets written is always this form's own option, never a name taken
	 * from the request.
	 *
	 * @since 3.1.0
	 * @return bool
	 */
	protected function is_own_request() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only used to pick the handler; nonce is checked next.
		$requested = isset( $_POST['settings_name'] ) ? sanitize_text_field( wp_unslash( $_POST['settings_name'] ) ) : '';

		return ( '' === $requested || $requested === $this->wasp->get_settings_name() );
	}

	/**
	 * Value a field returns to after a reset: the fallback value when the form has
	 * a fallback layer, otherwise the default value.
	 *
	 * @since 3.1.0
	 *
	 * @param string $field_name Field name.
	 * @return mixed|null
	 */
	protected function reset_value( $field_name ) {
		$fallback = $this->wasp->get_fallback_values();

		if ( is_array( $fallback ) && array_key_exists( $field_name, $fallback ) ) {
			return $fallback[ $field_name ];
		}

		$default_values = $this->wasp->get_default_values();

		return array_key_exists( $field_name, $default_values ) ? $default_values[ $field_name ] : null;
	}

	/**
	 * AJAX handler for resetting a single field.
	 *
	 * Default value is resolved server-side from $this->wasp->get_default_values()
	 * instead of trusting a client-supplied value.
	 *
	 * @since 2.3.3
	 */
	public function ajax_reset_field() {
		if ( ! $this->is_own_request() ) {
			return;
		}

		if ( ! $this->validate_ajax_request() ) {
			return;
		}

		// phpcs:ignore -- already nonce and capability checked in validate_ajax_request
		$field_name    = isset( $_POST['field_name'] ) ? sanitize_text_field( wp_unslash( $_POST['field_name'] ) ) : '';
		// phpcs:ignore -- already nonce and capability checked in validate_ajax_request
		$field_type    = isset( $_POST['field_type'] ) ? sanitize_text_field( wp_unslash( $_POST['field_type'] ) ) : 'text';
		$settings_name = $this->wasp->get_settings_name();
		$storage       = $this->wasp->get_storage();

		if ( empty( $field_name ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Field name is required.', 'wasp' ) ] );
			return;
		}

		// Resolve the value server-side (secure, no encoding issues).
		$reset_value = $this->reset_value( $field_name );

		// Get current settings from database.
		$current_settings = $storage->get( $settings_name, [] );
		if ( ! is_array( $current_settings ) ) {
			$current_settings = [];
		}

		// With a fallback layer, a reset removes the override so the field inherits again.
		// Otherwise: set the default if one exists, remove the key if not.
		$default_values = $this->wasp->get_default_values();

		if ( ! $this->wasp->has_fallback() && array_key_exists( $field_name, $default_values ) && $default_values[ $field_name ] !== null ) {
			$current_settings[ $field_name ] = $default_values[ $field_name ];
		} else {
			unset( $current_settings[ $field_name ] );
		}

		$this->wasp->write_values( $current_settings );
		$default_value = $reset_value;

		wp_send_json_success(
			[
				'message'    => esc_html__( 'Field reset successfully.', 'wasp' ),
				'value'      => $default_value,
				'field_name' => $field_name,
				'field_type' => $field_type,
			]
		);
	}

	/**
	 * AJAX handler for resetting all fields.
	 *
	 * @since 2.3.3
	 */
	public function ajax_reset_all_fields() {
		if ( ! $this->is_own_request() ) {
			return;
		}

		if ( ! $this->validate_ajax_request() ) {
			return;
		}

		$settings_name = $this->wasp->get_settings_name();
		$storage       = $this->wasp->get_storage();

		// With a fallback layer, resetting everything means removing every override.
		if ( $this->wasp->has_fallback() ) {
			$this->wasp->write_values( [] );

			wp_send_json_success(
				[
					'message' => esc_html__( 'All fields reset successfully.', 'wasp' ),
					'values'  => $this->wasp->get_resolved_values(),
				]
			);
			return;
		}

		// Get current settings from database (AJAX is stateless).
		$current_settings = $storage->get( $settings_name, [] );
		if ( ! is_array( $current_settings ) ) {
			$current_settings = [];
		}

		$default_values = $this->wasp->get_default_values();

		// Reset all fields to default values.
		foreach ( $default_values as $key => $value ) {
			$current_settings[ $key ] = $value;
		}

		// Remove any saved values that don't have defaults.
		foreach ( array_keys( $current_settings ) as $key ) {
			if ( ! isset( $default_values[ $key ] ) ) {
				unset( $current_settings[ $key ] );
			}
		}

		$this->wasp->write_values( $current_settings );

		wp_send_json_success(
			[
				'message' => esc_html__( 'All fields reset successfully.', 'wasp' ),
				'values'  => $default_values,
			]
		);
	}

	/**
	 * Get reset parameters.
	 *
	 * @since 2.3.3
	 *
	 * @return array
	 */
	public function get_reset_params() {
		return $this->reset_params;
	}
}
