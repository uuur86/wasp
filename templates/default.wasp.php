<?php

return [
  'Text' => [
    'main' => '<input type="text" id="[id]" name="[name]" value="[value]" placeholder="[holder]"/>',
  ],
  'Textarea' => [
    'main' => '<textarea id="[id]" name="[name]" placeholder="[holder]">[text]</textarea>',
  ],
  'File' => [
    'main' => '<input type="file" id="[id]" name="[name]"/>',
  ],
  'Radio' => [
    'main'   => '<fieldset id="[id]">[options]</fieldset>',
    'option' => '
      <span>
        <input type="radio" name="[name]" id="[id]" value="[value]" [checked]/>
        <label for="[id]">[label]</label>
      </span>
    '
  ],
  'Multitext' => [
    'main'   => '<fieldset id="[id]">[options]</fieldset>',
    'option' => '
      <span>
        <input type="text" name="[name]" id="[id]" value="[value]"/>
        <label for="[id]">[label]</label>
      </span>
    '
  ],
  'Select' => [
    'main'   => '<select id="[id]" name="[name]">[options]</select>',
    'group'  => '<optgroup label="[label]">[options]</optgroup>',
    'option' => '<option value="[value]" [selected][disabled]>[label]</option>',
  ],
  'Checkbox' => [
    'main'   => '<fieldset id="[id]">[options]</fieldset>',
    'option' => '
      <span>
        <input type="checkbox" name="[name]" id="[id]" value="[value]" [checked]/>
        <label for="[id]">[label]</label>
      </span>
    '
  ],
  'Onecheckbox' => [
    'main' => '
    <span>
      <input type="checkbox" name="[name]" id="[id]" value="[value]" [checked]/>
      <label for="[id]">[label]</label>
    </span>',
  ]
];
