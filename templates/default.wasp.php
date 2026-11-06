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
		'main' => '<fieldset id="[id]">[options]</fieldset>',
		'option' => '
			<span>
				<input type="radio" name="[name]" id="[id]" value="[value]" [checked]/>
				<label for="[id]">[label]</label>
			</span>
		'
	],
	'Multitext' => [
		'main' => '<fieldset id="[id]">[options]</fieldset>',
		'option' => '
			<span>
				<input type="text" name="[name]" id="[id]" value="[value]"/>
				<label for="[id]">[label]</label>
			</span>
		'
	],
	'Select' => [
		'main' => '<select id="[id]" name="[name]">[options]</select>',
		'group' => '<optgroup label="[label]">[options]</optgroup>',
		'option' => '<option value="[value]" [selected][disabled]>[label]</option>',
	],
	'Checkbox' => [
		'main' => '<fieldset id="[id]">[options]</fieldset>',
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
	],
	// Since 3.1.0: control shown next to a field whose value can be inherited
	// from a fallback layer (see Wasp::set_fallback_values()).
	'Inherit' => [
		'main' => '<div class="wasp-inherit" data-wasp-inherit="[state]">
					<label class="wasp-inherit-toggle" for="[id]">
						<input type="checkbox" class="wasp-inherit-checkbox" name="[name]" id="[id]" value="1" [checked][disabled]/>
						<span>[label]</span>
					</label>
					<span class="wasp-inherit-source">[source_label]: <code>[inherited]</code></span>
				</div>',
	],
	'Taginput' => [
		'main' => '<div class="taginput-input-row">
					<input type="text" class="taginput-input" placeholder="[placeholder]" />
					<button type="button" class="taginput-add-btn">+ Add</button>
				</div>
				<div class="taginput-tags"></div>
				<input type="hidden" name="[name]" class="taginput-hidden" value="[value]" />
				<div class="taginput-error"></div>
				<div class="taginput-presets">[presets]</div>',
	]
];