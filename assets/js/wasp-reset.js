/**
 * Wasp Reset Functionality
 *
 * Handles single-field and bulk reset via AJAX.
 * Default values are resolved server-side for security and encoding reliability.
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

			// Confirm with user.
			if (!confirm(confirmMsg)) {
				return;
			}

			// Default value is resolved server-side — no client-side value needed.
			var data = {
				action: 'wasp_reset_field',
				nonce: nonce,
				field_name: fieldName.replace(settingsName + '[', '').replace(']', ''),
				settings_name: settingsName,
				field_type: fieldType
			};

			var originalText = $btn.text();
			$btn.prop('disabled', true).text(waspReset.messages.resetting);

			$.ajax({
				url: waspReset.ajaxUrl,
				type: 'POST',
				data: data,
				success: function (response) {
					if (response.success) {
						// Update the field value in UI before reload.
						WaspReset.updateFieldValue(
							settingsName,
							data.field_name,
							response.data.value,
							fieldType
						);

						$btn.text(waspReset.messages.success);

						// Reload page after short delay to show updated values.
						setTimeout(function () {
							window.location.reload();
						}, 600);
					} else {
						alert(response.data.message || waspReset.messages.error);
						$btn.prop('disabled', false).text(originalText);
					}
				},
				error: function () {
					alert(waspReset.messages.error);
					$btn.prop('disabled', false).text(originalText);
				}
			});
		},

		updateFieldValue: function (settingsName, fieldName, value, fieldType) {
			var $field = $('[name="' + settingsName + '[' + fieldName + ']"]');

			if ($field.length === 0) {
				return;
			}

			// Handle different field types.
			switch (fieldType) {
				case 'radio':
					this.updateRadio($field, value);
					break;
				case 'checkbox':
					this.updateCheckboxRadio($field, value);
					break;
				case 'taginput':
					this.updateTaginput($field, value);
					break;
				default:
					this.updateTextField($field, value);
			}
		},

		// A radio group shares one name: check the option whose value matches.
		updateRadio: function ($field, value) {
			var wanted = (value === null || value === undefined) ? '' : String(value);

			$field.each(function () {
				this.checked = (this.value === wanted);
			});
			$field.filter(':checked').trigger('change');
		},

		updateCheckboxRadio: function ($field, value) {
			if (value == '1' || value === 1 || value === true) {
				$field.prop('checked', true);
			} else {
				$field.prop('checked', false);
			}
			$field.trigger('change');
		},

		updateTaginput: function ($field, value) {
			var $wrapper = $field.closest('.taginput-wrapper');
			var $tagsContainer = $wrapper.find('.taginput-tags');

			// Clear existing tags.
			$tagsContainer.find('.taginput-tag').remove();

			// Update hidden field value (pipe-separated).
			var displayValue = value || '';
			$field.val(displayValue);

			// Rebuild tags from value (pipe-separated format).
			if (displayValue && typeof displayValue === 'string' && displayValue.trim() !== '') {
				var tags = displayValue.split('|');

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
