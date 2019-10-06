<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Text extends FieldCreator {

	public $hasOptions	= false;

	public $hasGroups	= false;

	public $params		= [
		'value'		=> 'value',
		'holder'	=> 'label'
	];
}
