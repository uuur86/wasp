<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Checkbox extends FieldCreator
{
  public $hasMultipleChoices = true;

  public $hasOptions	= true;

  public $hasGroups	= false;

  public $params		= [
    'options'	=> [
      'id'		=> 'id',
      'name'		=> 'name',
      'label'		=> 'val',
      'value'		=> 'key',
      'checked'	=> 'checked',
    ]
  ];
}
