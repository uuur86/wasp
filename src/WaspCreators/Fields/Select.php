<?php
/**
 * Select field type.
 *
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

/**
 * Select field.
 */
class Select extends FieldCreator {

	/**
	 * Whether the field renders a list of options.
	 *
	 * @var bool
	 */
	public $has_options = true;

	/**
	 * Whether options can be grouped.
	 *
	 * @var bool
	 */
	public $has_groups = true;

	/**
	 * Maps template placeholders to field arguments.
	 *
	 * @var array
	 */
	public $params = [
		'options' => [
			'label'    => 'val',
			'value'    => 'key',
			'selected' => 'selected',
		],
	];
}
