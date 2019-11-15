<?php

namespace WaspCreators;

use WaspCreators\Templates;

abstract class FieldCreator {
	protected static $template;

	protected static $id;

	protected static $name;



	/**
	 * Gets HTML code of form items
	 *
	 * @param array $args form item parameters
	 *
	 * @return string HTML code of the requested form item
	 */
	public static function get( $args ) {
		$obj		= new static();

		$type		= new \ReflectionClass( get_class( $obj ) );
		$type		= $type->getShortName();

		$template	= Templates::get( $type );

		if ( empty( $template ) ) return false;

		self::$template = $template;
		self::$id		= $args[ 'id' ];
		self::$name		= $args[ 'name' ];

		$main_args	= [
			'id'	=> $args[ 'id' ],
			'name'	=> $args[ 'name' ],
		];

		if ( isset( $args[ 'options' ] ) && $obj->hasOptions && is_array( $args[ 'options' ] ) ) {
			$options = $obj->options( $args );

			if ( ! empty( $options ) ) {
				$main_args[ 'options' ] = $options;
			}
		} else {
			// WaspCreators\Fields\{type}->params
			$attr = $obj->params;

			$opt_params = [
				'value' => $args[ 'value' ],
			];

			if ( isset( $attr[ 'checked' ] ) && isset( $attr[ 'option' ] ) ) {
				$main_args[ 'value' ]	= $args[ 'option' ];
				$main_args[ 'checked' ]	= \checked( $args[ 'option' ], $args[ 'value' ], false );
			}

			if ( isset( $attr[ 'text' ] ) && ! empty( $attr[ 'text' ] ) ) {
				$main_args[ 'text' ] = $opt_params[ $attr[ 'text' ] ];
			}

			if ( isset( $attr[ 'value' ] ) && ! empty( $attr[ 'value' ] ) ) {
				$main_args[ 'value' ] = $opt_params[ $attr[ 'value' ] ];
			}
		}

		$html = Templates::append( $template->main, $main_args );

		return $html;
	}



	/**
	 * Prepares the form item's options
	 *
	 * @param object $template item template object
	 * @param array $args option parameters
	 *
	 * @return string HTML code of the requested form item options
	 */
	public function options( $args ) {
		$options = $this->_walker( $args[ 'options' ], $args[ 'value' ] );

		return $options;
	}



	/**
	 * Walker function for fields which has options
	 *
	 * @param array $options
	 * @param string $value
	 * @return string
	 */
	protected function _walker( $options, $value )
	{
		// Check options and template class
		if ( ! is_object( self::$template ) || ! is_array( $options ) ) {
			return false;
		}

		// The variable container which will return
		$return		= '';

		// Template object
		$template	= self::$template;

		// Field capabilities
		$params		= $this->params;

		// Field options capabilities
		$attr			= $params[ 'options' ];

		foreach ( $options as $opt_key => $opt_attr ) {
			if ( isset( $opt_attr[ 'value' ] ) ) {
				$key		= $opt_attr[ 'value' ];
				$label		= $opt_attr[ 'label' ];

				// Default disabled
				$disabled	= false;

				if ( isset( $opt_attr[ 'disabled' ] ) ) {
					$disabled	= $opt_attr[ 'disabled' ];
				}

				if ( is_array( $value ) ) {
					$fieldval = isset( $value[ $key ] ) ? $value[ $key ] : null;
				} else {
					$fieldval = $value;
				}
				// For standardizing
				$opt_params = [
					'id'	=> self::$id . '_' . $key, // field id
					'name'	=> self::$name . '[' . $key . ']', // field name
					'key'	=> $key, // option key
					'val'	=> $label, // option label
					'fval'	=> $fieldval, // option selected value
				];

				$opt_args = [];

				if ( isset( $attr[ 'id' ] ) && ! empty( $attr[ 'id' ] ) ) {
					$opt_args[ 'id' ] = $opt_params[ $attr[ 'id' ] ];
				}

				if ( isset( $attr[ 'name' ] ) && ! empty( $attr[ 'name' ] ) ) {
					$opt_args[ 'name' ] = $opt_params[ $attr[ 'name' ] ];
				}

				if ( isset( $attr[ 'value' ] ) && ! empty( $attr[ 'value' ] ) ) {
					$opt_args[ 'value' ] = $opt_params[ $attr[ 'value' ] ];
				}

				if ( isset( $attr[ 'label' ] ) && ! empty( $attr[ 'label' ] ) ) {
					$opt_args[ 'label' ] = $opt_params[ $attr[ 'label' ] ];
				}

				if ( isset( $attr[ 'checked' ] ) && $attr[ 'checked' ] ) {
					$opt_args[ 'checked' ] = \checked( $key, $fieldval, false );
				}

				if ( isset( $attr[ 'selected' ] ) && $attr[ 'selected' ] ) {
					$opt_args[ 'selected' ] = \selected( $key, $fieldval, false );
				}

				if ( $disabled ) {
					$opt_args[ 'disabled' ] = ' disabled';
				}

				$return .= Templates::append( $template->option, $opt_args );
			} elseif ( $this->hasGroups ) {
				$grp_args	= [
					'label'	=> $opt_key,
					'options' => $this->_walker( $opt_attr, $value )
				];

				$return .= Templates::append( $template->group, $grp_args );
			}
		}

		return $return;
	}
}
