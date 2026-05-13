<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Radio extends FieldCreator {

	public $has_multiple_choices = false;

	public $has_options	= true;

	public $has_groups	= false;

	public $params		= [
		'options' => [
			'id'		=> 'id',
			'name'		=> 'name',
			'label'		=> 'val',
			'value'		=> 'key',
			'checked'	=> 'checked',
		]
	];
}
