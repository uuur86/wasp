<?php
/**
 * Multitext field type.
 *
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

/**
 * Multitext field.
 */
class Multitext extends FieldCreator {

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
	public $has_groups = false;

	/**
	 * Maps template placeholders to field arguments.
	 *
	 * @var array
	 */
	public $params = [
		'options' => [
			'id'    => 'id',
			'name'  => 'name',
			'label' => 'val',
			'value' => 'fval',
		],
	];
}
