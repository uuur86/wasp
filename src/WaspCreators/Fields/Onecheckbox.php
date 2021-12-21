<?php

/**
 * @package wasp
 */

namespace WaspCreators\Fields;

use WaspCreators\FieldCreator;

class Onecheckbox extends FieldCreator
{
  public $hasOptions	= false;

  public $hasGroups	= false;

  public $params		= [
    'option'	=> 'option',
    'checked'	=> 'checked'
  ];
}
