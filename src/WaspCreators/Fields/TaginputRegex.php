<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class TaginputRegex extends Taginput {

	public $has_options	= false;

	public $has_groups	= false;

	public $params		= [
		'value'		=> 'value',
		'holder'	=> 'label',
		'presets'	=> 'presets',
		'field_type' => 'field_type',
	];
}
