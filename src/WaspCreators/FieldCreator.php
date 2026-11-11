<?php
/**
 * WaspCreators\FieldCreator class.
 *
 * @package wasp
 */

namespace WaspCreators;

use WaspCreators\Templates;

/**
 * Abstract class FieldCreator.
 */
abstract class FieldCreator {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	protected static $type;

	/**
	 * Field template object.
	 *
	 * @var object
	 */
	protected static $template;

	/**
	 * Field ID.
	 *
	 * @var string
	 */
	protected static $id;

	/**
	 * Field name.
	 *
	 * @var string
	 */
	protected static $name;

	/**
	 * Does field have options?
	 *
	 * @var bool
	 */
	public $has_options = false;

	/**
	 * Does field have multiple choices?
	 *
	 * @var bool
	 */
	public $has_multiple_choices = false;

	/**
	 * Does field have groups?
	 *
	 * @var bool
	 */
	public $has_groups = false;

	/**
	 * Field parameters.
	 *
	 * @var array
	 */
	public $params = [];

	/**
	 * Gets HTML code of form items.
	 *
	 * @param array $args Form item parameters.
	 *
	 * @return string|false HTML code of the requested form item, false on failure.
	 */
	public static function get( $args ) {

		$obj = new static();

		$type      = new \ReflectionClass( get_class( $obj ) );
		$type_name = $type->getShortName();

		// Map lowercase type to proper template name.
		$type_map = [
			'taginput'      => 'Taginput',
			// TaginputRegex has no template of its own; it reuses Taginput.
			'taginputregex' => 'Taginput',
		];

		$type = isset( $type_map[ strtolower( $type_name ) ] ) ? $type_map[ strtolower( $type_name ) ] : $type_name;

		$theme    = isset( $args['theme'] ) ? $args['theme'] : null;
		$template = Templates::get( $type, $theme );

		if ( empty( $template ) ) {

			return false;

		}

		self::$type     = $type;
		self::$template = $template;
		self::$id       = $args['id'];
		self::$name     = $args['name'];

		$main_args = [
			'id'   => $args['id'],
			'name' => $args['name'],
		];

		if ( isset( $args['options'] ) && $obj->has_options && is_array( $args['options'] ) ) {

			$options = $obj->options( $args );

			if ( ! empty( $options ) ) {

				$main_args['options'] = $options;

			}

		} else {

			// Retrieve parameters from the field type object.
			$attr = $obj->params;

			$opt_params = [
				'value' => $args['value'],
			];

			if ( isset( $attr['checked'] ) && isset( $attr['option'] ) ) {

				$main_args['value']   = $args['option'];
				$main_args['checked'] = \checked( $args['option'], $args['value'], false );

			}

			if ( isset( $attr['text'] ) && ! empty( $attr['text'] ) ) {

				$main_args['text'] = $opt_params[ $attr['text'] ];

			}

			if ( isset( $attr['value'] ) && ! empty( $attr['value'] ) ) {

				$main_args['value'] = $opt_params[ $attr['value'] ];

			}

			// Pass through extra args for fields like Taginput.
			$extra_args = [ 'placeholder', 'presets', 'field_type' ];

			foreach ( $extra_args as $key ) {

				if ( isset( $args[ $key ] ) ) {

					$main_args[ $key ] = $args[ $key ];

				}

			}

		}

		$html = Templates::append( $template->main, $main_args );

		return $html;

	}

	/**
	 * Prepares the form item's options.
	 *
	 * @param array $args Option parameters.
	 *
	 * @return string HTML code of the requested form item options.
	 */
	public function options( $args ) {

		$options = $this->walker( $args['options'], $args['value'] );

		return $options;

	}

	/**
	 * Walker function for fields which has options.
	 *
	 * @param array             $options Field options.
	 * @param string|array|null $value   Field value.
	 * @return string|false              HTML options string, false on failure.
	 */
	protected function walker( $options, $value ) {

		// Check options and template class.
		if ( ! is_object( self::$template ) || ! is_array( $options ) ) {

			return false;

		}

		// The variable container which will return.
		$return = '';

		// Template object.
		$template = self::$template;

		// Field capabilities.
		$params = $this->params;

		// Field options capabilities.
		$attr = $params['options'];

		foreach ( $options as $opt_key => $opt_attr ) {

			if ( isset( $opt_attr['value'] ) ) {

				$key   = $opt_attr['value'];
				$label = $opt_attr['label'];

				// Default disabled.
				$disabled = false;

				if ( isset( $opt_attr['disabled'] ) ) {

					$disabled = $opt_attr['disabled'];

				}

				if ( is_array( $value ) ) {

					$fieldval = isset( $value[ $key ] ) ? $value[ $key ] : null;

				} else {

					$fieldval = $value;

				}

				$type     = self::$type;
				$opt_name = self::$name;

				if ( $this->has_multiple_choices ) {

					$opt_name .= '[' . $key . ']';

				}

				// For standardizing.
				$opt_params = [
					'id'   => self::$id . '_' . $key, // field id.
					'name' => $opt_name, // field name.
					'key'  => $key, // option key.
					'val'  => $label, // option label.
					'fval' => $fieldval, // option selected value.
				];

				$opt_args = [];

				if ( isset( $attr['id'] ) && ! empty( $attr['id'] ) ) {

					$opt_args['id'] = $opt_params[ $attr['id'] ];

				}

				if ( isset( $attr['name'] ) && ! empty( $attr['name'] ) ) {

					$opt_args['name'] = $opt_params[ $attr['name'] ];

				}

				if ( isset( $attr['value'] ) && ! empty( $attr['value'] ) ) {

					$opt_args['value'] = $opt_params[ $attr['value'] ];

				}

				if ( isset( $attr['label'] ) && ! empty( $attr['label'] ) ) {

					$opt_args['label'] = $opt_params[ $attr['label'] ];

				}

				if ( isset( $attr['checked'] ) && $attr['checked'] ) {

					$opt_args['checked'] = \checked( $key, $fieldval, false );

				}

				if ( isset( $attr['selected'] ) && $attr['selected'] ) {

					$opt_args['selected'] = \selected( $key, $fieldval, false );

				}

				if ( $disabled ) {

					$opt_args['disabled'] = ' disabled';

				}

				$return .= Templates::append( $template->option, $opt_args );

			} elseif ( $this->has_groups ) {

				$grp_args = [
					'label'   => $opt_key,
					'options' => $this->walker( $opt_attr, $value ),
				];

				$return .= Templates::append( $template->group, $grp_args );

			}

		}

		return $return;

	}

}
