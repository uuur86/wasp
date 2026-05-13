<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Text extends FieldCreator {

	public $has_options	= false;

	public $has_groups	= false;

	public $params		= [
		'value'		=> 'value',
		'holder'	=> 'label'
	];
}
