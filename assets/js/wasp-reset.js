/**
 * Wasp Reset Functionality
 *
 * @package wasp
 * @since 2.3.3
 */

(function ($) {
	'use strict';

	var WaspReset = {
		init: function () {
			this.bindEvents();
		},

		bindEvents: function () {
			$(document).on('click', '.wasp-reset-btn', this.handleResetClick.bind(this));
		},

		handleResetClick: function (e) {
			e.preventDefault();

			var $btn = $(e.currentTarget);
			var $wrapper = $btn.closest('.wasp-reset-wrapper');
			var confirmMsg = $wrapper.data('confirm') || waspReset.messages.confirm;
			var nonce = $wrapper.data('nonce');
			var fieldName = $wrapper.data('field');
			var settingsName = $wrapper.data('settings-name') || waspReset.settingsName;
			var fieldType = $wrapper.data('field-type') || 'text';

			// Read default value from hidden input (more reliable than data attribute)
			var $defaultInput = $wrapper.find('.wasp-reset-default-value');
			var defaultValueJson = $defaultInput.val() || 'null';

			// Parse JSON value
			var defaultValue;
			try {
				defaultValue = JSON.parse(defaultValueJson);
			} catch (e) {
				defaultValue = defaultValueJson;
			}

			// Confirm with user
			if (!confirm(confirmMsg)) {
				return;
			}

			var data = {
				action: 'wasp_reset_field',
				nonce: nonce,
				field_name: fieldName.replace(settingsName + '[', '').replace(']', ''),
				settings_name: settingsName,
				field_type: fieldType,
				default_value: defaultValue
			};

			$btn.prop('disabled', true).text(waspReset.messages.resetting);

			$.ajax({
				url: waspReset.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					if (response.success) {
						$btn.prop('disabled', false).text(waspReset.messages.success);

						// Reload page after short delay to show updated values
						setTimeout(function () {
							window.location.reload();
						}, 500);
					} else {
						alert(response.data.message || waspReset.messages.error);
						$btn.prop('disabled', false).text('Reset');
					}
				},
				error: function () {
					alert(waspReset.messages.error);
					$btn.prop('disabled', false).text('Reset');
				}
			});
		},

		updateFieldValue: function (settingsName, fieldName, value, fieldType) {
			var $field = $('[name="' + settingsName + '[' + fieldName + ']"]');

			if ($field.length === 0) {
				return;
			}

			// Handle different field types
			switch (fieldType) {
				case 'checkbox':
				case 'radio':
					this.updateCheckboxRadio($field, value);
					break;
				case 'taginput':
					this.updateTaginput($field, value);
					break;
				default:
					this.updateTextField($field, value);
			}
		},

		updateCheckboxRadio: function ($field, value) {
			// value should be '1' for checked, '' or null for unchecked
			if (value == '1' || value === 1 || value === true) {
				$field.prop('checked', true);
			} else {
				$field.prop('checked', false);
			}
			// Trigger change event for any listeners
			$field.trigger('change');
		},

		updateTaginput: function ($field, value) {
			var $wrapper = $field.closest('.taginput-wrapper');
			var $tagsContainer = $wrapper.find('.taginput-tags');

			// Clear existing tags
			$tagsContainer.find('.taginput-tag').remove();

			// Get the hidden field and update its value
			$field.val(value || '');

			// Rebuild tags from value
			if (value && typeof value === 'string' && value.trim() !== '') {
				var tags = value.split('\n');
				var $hiddenField = $field;

				tags.forEach(function (tag) {
					tag = tag.trim();
					if (tag) {
						var $tag = $('<span class="taginput-tag">' + WaspReset.escapeHtml(tag) + ' <span class="taginput-remove" title="Remove">×</span></span>');
						$tagsContainer.append($tag);
					}
				});
			}
		},

		updateTextField: function ($field, value) {
			$field.val(value || '').trigger('input');
		},

		escapeHtml: function (text) {
			var div = document.createElement('div');
			div.textContent = text;
			return div.innerHTML;
		}
	};

	$(document).ready(function () {
		WaspReset.init();
	});

})(jQuery);
