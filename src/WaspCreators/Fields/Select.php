<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Select extends FieldCreator {

	public $has_options	= true;

	public $has_groups	= true;

	public $params		= [
		'options' => [
			'label'		=> 'val',
			'value'		=> 'key',
			'selected'	=> 'selected',
		]
	];
}
