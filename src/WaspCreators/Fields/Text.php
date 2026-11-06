<?php
/**
 * Text field type.
 *
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

/**
 * Text field.
 */
class Text extends FieldCreator {

	/**
	 * Whether the field renders a list of options.
	 *
	 * @var bool
	 */
	public $has_options = false;

	/**
	 * Whether options can be grouped.
	 *
	 * @var bool
	 */
	public $has_groups = false;

	/**
	 * Maps template placeholders to field arguments.
	 *
	 * @var array
	 */
	public $params = [
		'value'  => 'value',
		'holder' => 'label',
	];
}
