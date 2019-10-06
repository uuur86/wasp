<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Select extends FieldCreator {

	public $hasOptions	= true;

	public $hasGroups	= true;

	public $params		= [
		'options'	=> [
			'label'		=> 'val',
			'value'		=> 'key',
			'selected'	=> 'selected',
		]
	];
}
