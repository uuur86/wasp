<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Multitext extends FieldCreator {

	public $has_options	= true;

	public $has_groups	= false;

	public $params		= [
		'options' => [
			'id'	=> 'id',
			'name'	=> 'name',
			'label'	=> 'val',
			'value'	=> 'fval',
		]
	];
}
