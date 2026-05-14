<?php
/**
 * Helper class for Wasp form fields.
 *
 * @package wasp
 */

namespace WaspCreators;

/**
 * Class for handling form fields.
 */
class Fields {

	/**
	 * Filters the output of a field, adding before and after HTML.
	 *
	 * @param string      $html   The HTML to filter.
	 * @param string|null $before HTML to add before the field.
	 * @param string|null $after  HTML to add after the field.
	 * @return string The filtered HTML.
	 */
	public static function filter_output( $html, $before = null, $after = null ) {
		$output = '';

		$default_attr   = [
			'id'    => [],
			'name'  => [],
			'href'  => [],
			'src'   => [],
			'type'  => [],
			'class' => [],
			'style' => [],
			'data-field' => [],
			'data-nonce' => [],
			'data-confirm' => [],
			'data-initial-value' => [],
			'data-settings-name' => [],
			'data-field-type' => [],
			'data-action' => [],
			'data-preset' => [],
			'title' => [],
		];

		$allowed_tags   = [
			'input'     => $default_attr,
			'select'    => $default_attr,
			'option'    => $default_attr,
			'radio'     => $default_attr,
			'a'         => $default_attr,
			'img'       => $default_attr,
			'br'        => $default_attr,
			'div'       => $default_attr,
			'hr'        => $default_attr,
			'span'      => $default_attr,
			'button'    => $default_attr,
			'fieldset'  => $default_attr,
			'label'     => $default_attr,
			'textarea'  => $default_attr,
		];

		if ( ! empty( $before ) ) {
			$output .= \wp_kses( $before, $allowed_tags );
		}

		$output .= $html;

		if ( ! empty( $after ) ) {
			$output .= \wp_kses( $after, $allowed_tags );
		}

		return $output;
	}



	/**
	 * Prepares a field for display by setting the correct class and calling its get method.
	 *
	 * @param array $args An array containing the field arguments, including type, html_before,
	 * html_after, and not_found.
	 * @return string|void The prepared field HTML, or void if the field type is not found.
	 */
	public static function prepare( $args ) {

		if ( ! isset( $args['type'] ) ) {
			return;
		}

		if ( ! empty( $args['not_found'] ) ) {
			return $args['not_found'];
		}

		$type   = $args['type'];
		$before = null;
		$after  = null;

		if ( isset( $args['html_before'] ) ) {
			$before = $args['html_before'];
		}

		if ( isset( $args['html_after'] ) ) {
			$after = $args['html_after'];
		}

		if ( empty( $type ) ) {
			return;
		}

		$type_arr = [
			'text_input'       => 'Text',
			'multi_text_input' => 'Multitext',
			'file_input'       => 'File',
			'taginput'         => 'Taginput',
		];

		if ( isset( $type_arr[ $type ] ) ) {
			$type = $type_arr[ $type ];
		} else {
			$type = ucfirst( $type );
		}

		$obj_name = '\\WaspCreators\\Fields\\' . $type;

		if ( ! method_exists( $obj_name, 'get' ) ) {
			return;
		}

		// Flushes unnecessary array data from ram.
		unset( $args['html_before'], $args['html_after'], $args['type'] );

		$html = call_user_func( $obj_name . '::get', $args );

		if ( ! $html ) {
			return;
		}

		return self::filter_output( $html, $before, $after );
	}



	/**
	 * Callback function for rendering a field.
	 * This method is intended to be used as a WordPress action or filter callback.
	 * It prepares and echoes the field HTML using the prepare method.
	 *
	 * @param array $args An array containing the field arguments. Expected keys include:
	 * 'type' (string), 'name' (string), 'value' (mixed), 'options' (array),
	 * 'html_after' (string), 'html_before' (string), 'settings_name' (string).
	 */
	public static function callback( $args ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML is safely prepared and contains form fields that cannot be escaped via esc_html/wp_kses_post.
		echo self::prepare( $args );
	}
}
