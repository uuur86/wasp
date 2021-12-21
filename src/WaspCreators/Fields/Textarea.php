<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Textarea extends FieldCreator
{
  public $hasOptions	= false;

  public $hasGroups	= false;

  public $params		= [
    'text'		=> 'value',
    'holder'	=> 'label'
  ];
}
