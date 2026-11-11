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

		$default_attr = [
			'id'                 => [],
			'name'               => [],
			'href'               => [],
			'src'                => [],
			'type'               => [],
			'class'              => [],
			'style'              => [],
			'data-field'         => [],
			'data-nonce'         => [],
			'data-confirm'       => [],
			'data-initial-value' => [],
			'data-settings-name' => [],
			'data-field-type'    => [],
			'data-action'        => [],
			'data-preset'        => [],
			'title'              => [],
		];

		$allowed_tags = [
			'input'    => $default_attr,
			'select'   => $default_attr,
			'option'   => $default_attr,
			'radio'    => $default_attr,
			'a'        => $default_attr,
			'img'      => $default_attr,
			'br'       => $default_attr,
			'div'      => $default_attr,
			'hr'       => $default_attr,
			'span'     => $default_attr,
			'button'   => $default_attr,
			'fieldset' => $default_attr,
			'label'    => $default_attr,
			'textarea' => $default_attr,
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
			'taginput_regex'   => 'TaginputRegex',
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

		$inherit = isset( $args['inherit'] ) && is_array( $args['inherit'] ) ? $args['inherit'] : null;
		$theme   = isset( $args['theme'] ) ? $args['theme'] : null;
		$options = isset( $args['options'] ) && is_array( $args['options'] ) ? $args['options'] : null;

		// Flushes unnecessary array data from ram.
		unset( $args['html_before'], $args['html_after'], $args['type'], $args['inherit'] );

		$html = call_user_func( $obj_name . '::get', $args );

		if ( ! $html ) {

			return;

		}

		$output = self::filter_output( $html, $before, $after );

		if ( null !== $inherit ) {

			$output = self::wrap_inherit( $output, $inherit, $theme, $options );

		}

		return $output;

	}



	/**
	 * Wraps a rendered field with the inherit control.
	 *
	 * The control markup comes from the "Inherit" template, so themes can restyle it.
	 * All dynamic values are escaped by Templates::append().
	 *
	 * With "auto_override" (see Wasp::set_fallback_values()) the wrapper also carries
	 * the inherited value as JSON, so the script can switch the override on when the
	 * user edits the field and restore the inherited value on request.
	 *
	 * @since 3.1.0
	 *
	 * @param string      $output  Rendered field HTML.
	 * @param array       $inherit Inherit data prepared by Wasp::add().
	 * @param string|null $theme   Theme path.
	 * @param array|null  $options Field options, used to show option labels instead of raw values.
	 * @return string
	 */
	protected static function wrap_inherit( $output, array $inherit, $theme = null, $options = null ) {

		$template = Templates::get( 'Inherit', $theme );

		if ( empty( $template ) || empty( $template->main ) ) {

			return $output;

		}

		$locked     = ! empty( $inherit['locked'] );
		$overridden = ! $locked && ! empty( $inherit['overridden'] );
		$state      = $locked ? 'locked' : ( $overridden ? 'overridden' : 'inherited' );
		$value      = isset( $inherit['value'] ) ? $inherit['value'] : null;
		$auto       = ! $locked && ! empty( $inherit['auto'] );

		$control = Templates::append(
			$template->main,
			[
				'id'           => isset( $inherit['id'] ) ? $inherit['id'] : '',
				'name'         => isset( $inherit['name'] ) ? $inherit['name'] : '',
				'checked'      => $overridden ? ' checked="checked"' : '',
				'disabled'     => $locked ? ' disabled="disabled"' : '',
				'label'        => $locked && isset( $inherit['locked_label'] ) ? $inherit['locked_label'] : ( isset( $inherit['label'] ) ? $inherit['label'] : '' ),
				'source_label' => isset( $inherit['source_label'] ) ? $inherit['source_label'] : '',
				'revert_label' => isset( $inherit['revert_label'] ) ? $inherit['revert_label'] : '',
				'inherited'    => self::display_value( self::option_labels( $value, $options ), isset( $inherit['empty_label'] ) ? $inherit['empty_label'] : '' ),
				'state'        => $state,
			]
		);

		$attributes = ' data-wasp-state="' . \esc_attr( $state ) . '"';

		if ( $auto ) {

			$attributes .= ' data-wasp-auto="1" data-wasp-inherited="' . \esc_attr( (string) \wp_json_encode( $value ) ) . '"';

		}

		return '<div class="wasp-inheritable"' . $attributes . '>'
			. $control
			. '<div class="wasp-inheritable-field">' . $output . '</div>'
			. '</div>';

	}



	/**
	 * Replaces option values with their labels, for fields that have options.
	 *
	 * Options are the field's "options" config: a list of [ 'value' => ..., 'label' => ... ]
	 * items, optionally grouped (a group item holds its own "options" list). Values without
	 * a matching option are returned unchanged.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed      $value   Stored value: a scalar, or an array for multiple choices.
	 * @param array|null $options Field options.
	 * @return mixed Value with labels in place of option values.
	 */
	public static function option_labels( $value, $options = null ) {

		if ( ! is_array( $options ) || empty( $options ) ) {

			return $value;

		}

		$labels = self::collect_option_labels( $options );

		if ( empty( $labels ) ) {

			return $value;

		}

		if ( is_array( $value ) ) {

			$mapped = [];

			foreach ( $value as $item ) {

				$mapped[] = ( is_scalar( $item ) && isset( $labels[ (string) $item ] ) ) ? $labels[ (string) $item ] : $item;

			}

			return $mapped;

		}

		if ( is_bool( $value ) ) {

			$value = $value ? '1' : '0';

		}

		if ( is_scalar( $value ) && isset( $labels[ (string) $value ] ) ) {

			return $labels[ (string) $value ];

		}

		return $value;

	}



	/**
	 * Builds a value => label map from a (possibly grouped) options list.
	 *
	 * @since 3.1.0
	 *
	 * @param array $options Field options.
	 * @param int   $depth   Current group depth; groups deeper than one level are ignored.
	 * @return array<string,string>
	 */
	protected static function collect_option_labels( array $options, $depth = 0 ) {

		$labels = [];

		foreach ( $options as $option ) {

			if ( ! is_array( $option ) ) {

				continue;

			}

			if ( isset( $option['value'], $option['label'] ) && is_scalar( $option['value'] ) && is_scalar( $option['label'] ) ) {

				$labels[ (string) $option['value'] ] = (string) $option['label'];

			} elseif ( 0 === $depth && isset( $option['options'] ) && is_array( $option['options'] ) ) {

				$labels += self::collect_option_labels( $option['options'], 1 );

			}

		}

		return $labels;

	}



	/**
	 * Turns a stored value into a short, single-line string for display.
	 *
	 * Arrays (checkbox groups) are joined, and long values are cut to keep the
	 * page small.
	 *
	 * @since 3.1.0
	 *
	 * @param mixed  $value       Value.
	 * @param string $empty_label Text shown for empty values.
	 * @return string
	 */
	public static function display_value( $value, $empty_label = '' ) {

		$max_length = 200;

		if ( is_array( $value ) ) {

			$parts = [];

			foreach ( $value as $item ) {

				if ( is_scalar( $item ) && (string) $item !== '' ) {

					$parts[] = (string) $item;

				}

				if ( count( $parts ) >= 20 ) {

					break;

				}

			}

			$value = implode( ', ', $parts );

		} elseif ( is_bool( $value ) ) {

			$value = $value ? '1' : '0';

		} elseif ( ! is_scalar( $value ) ) {

			$value = '';

		}

		$value = trim( str_replace( [ "\r\n", "\n", "\r", '|' ], ', ', (string) $value ) );

		if ( $value === '' ) {

			return (string) $empty_label;

		}

		if ( function_exists( 'mb_strlen' ) && mb_strlen( $value ) > $max_length ) {

			return mb_substr( $value, 0, $max_length ) . '…';

		}

		if ( strlen( $value ) > $max_length ) {

			return substr( $value, 0, $max_length ) . '…';

		}

		return $value;

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
