<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Textarea extends FieldCreator {

	public $has_options	= false;

	public $has_groups	= false;

	public $params		= [
		'text'		=> 'value',
		'holder'	=> 'label'
	];
}
