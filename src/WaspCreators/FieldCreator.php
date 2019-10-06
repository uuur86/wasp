<?php

namespace WaspCreators;

use WaspCreators\Templates;

abstract class FieldCreator {



	/**
	 *
	 */
	public static function get( $args ) {
		$obj		= new static();

		$type		= new \ReflectionClass( get_class( $obj ) );
		$type		= $type->getShortName();

		$template	= Templates::get( $type );

		if( empty( $template ) ) return false;

		$main_args	= [
			'id'	=> $args[ 'id' ],
			'name'	=> $args[ 'name' ],
		];

		if( $obj->hasOptions ) {
			$options = $obj->options( $template, $args );

			if( !empty( $options ) ) {
				$main_args[ 'options' ] = $options;
			}
		}
		else {
			$attr = $obj->params;
			$opt_params = [
				'value' => $args[ 'value' ],

			];

			if( isset( $attr[ 'text' ] ) && !empty( $attr[ 'text' ] ) ) {
				$main_args[ 'text' ] = $opt_params[ $attr[ 'text' ] ];
			}

			if( isset( $attr[ 'value' ] ) && !empty( $attr[ 'value' ] ) ) {
				$main_args[ 'value' ] = $opt_params[ $attr[ 'value' ] ];
			}
		}

		// Remove unnecessary closure function
		unset( $loop_options );

		$html = Templates::append( $template->main, $main_args );

		return $html;
	}



	public function options( $template, $args ) {
		$options	= '';
		$loop_args	= [
			'template'	=> $template,
			'attr'		=> $this->params,
			'args'		=> $args
		];

		$loop_options = function( $options, $value ) use ( &$loop_options, $loop_args ) {
			$return		= '';
			$template	= $loop_args[ 'template' ];
			$attr		= $loop_args[ 'attr' ][ 'options' ];
			$args		= $loop_args[ 'args' ];

			foreach( $options as $opt_key => $opt_val ) {

				if( is_array( $value ) ) {
					$fieldval = isset( $value[ $opt_key ] ) ? $value[ $opt_key ] : null;
				}
				else {
					$fieldval = $value;
				}

				$opt_params = [
					'id'	=> $args[ 'id' ] . '_' . $opt_key,
					'name'	=> $args[ 'name' ] . '[' . $opt_key . ']',
					'key'	=> $opt_key,
					'val'	=> $opt_val,
					'fval'	=> $fieldval,
				];

				$opt_args = [];

				if( isset( $attr[ 'id' ] ) && !empty( $attr[ 'id' ] ) ) {
					$opt_args[ 'id' ] = $opt_params[ $attr[ 'id' ] ];
				}

				if( isset( $attr[ 'name' ] ) && !empty( $attr[ 'name' ] ) ) {
					$opt_args[ 'name' ] = $opt_params[ $attr[ 'name' ] ];
				}

				if( isset( $attr[ 'value' ] ) && !empty( $attr[ 'value' ] ) ) {
					$opt_args[ 'value' ] = $opt_params[ $attr[ 'value' ] ];
				}

				if( isset( $attr[ 'label' ] ) && !empty( $attr[ 'label' ] ) ) {
					$opt_args[ 'label' ] = $opt_params[ $attr[ 'label' ] ];
				}

				if( isset( $attr[ 'checked' ] ) && $attr[ 'checked' ] ) {
					$opt_args[ 'checked' ] = \checked( $opt_key, $fieldval, false );
				}

				if( isset( $attr[ 'selected' ] ) && $attr[ 'selected' ] ) {
					$opt_args[ 'selected' ] = \selected( $opt_key, $fieldval, false );
				}

				$return .= Templates::append( $template->option, $opt_args );
			}

			return $return;
		};

		foreach( $args[ 'options' ] as $opt_key => $opt_val ) {

			if( $this->hasGroups && is_array( $opt_val ) ) {
				$grp_args	= [
					'label'		=> $opt_key,
					'options' 	=> $loop_options( $opt_val, $args[ 'value' ] )
				];

				$options .= Templates::append( $template->group, $grp_args );
			}
			else {
				$options = $loop_options( $args[ 'options' ], $args[ 'value' ] );

				break;
			}
		}

		return $options;
	}
}
