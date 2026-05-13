<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Onecheckbox extends FieldCreator {

	public $has_options	= false;

	public $has_groups	= false;

	public $params		= [
		'option'	=> 'option',
		'checked'	=> 'checked'
	];
}
