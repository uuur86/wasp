<?php
/**
 * Radio field type.
 *
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

/**
 * Radio field.
 */
class Radio extends FieldCreator {

	/**
	 * Whether each option posts its own value (name[key]).
	 *
	 * @var bool
	 */
	public $has_multiple_choices = false;

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
			'id'      => 'id',
			'name'    => 'name',
			'label'   => 'val',
			'value'   => 'key',
			'checked' => 'checked',
		],
	];

}
