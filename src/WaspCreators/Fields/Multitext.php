<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Multitext extends FieldCreator {

	public $hasOptions	= true;

	public $hasGroups	= false;

	public $params		= [
		'options'	=> [
			'id'		=> 'id',
			'name'		=> 'name',
			'label'		=> 'val',
			'value'		=> 'fval',
		]
	];
}
