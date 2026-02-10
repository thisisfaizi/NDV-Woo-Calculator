/**
 * NDV Woo Calculator — Admin JavaScript
 *
 * Handles tab switching, dynamic mapping row management, and AJAX save.
 *
 * @package NDV_Woo_Calculator
 * @since   1.0.0
 */

/* global jQuery, ndvwcAdmin */
(function ($) {
	'use strict';

	var mappingIndex = 0;

	/**
	 * Initialize on document ready.
	 */
	$(document).ready(function () {
		// Set initial mapping index based on existing rows.
		mappingIndex = $('.ndvwc-mapping-row').length;

		// Add new mapping configuration.
		$('#ndvwc-add-mapping').on('click', addMapping);

		// Remove mapping configuration (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-remove-mapping', removeMapping);

		// Add field mapping sub-row (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-add-field-mapping', addFieldMapping);

		// Remove field mapping sub-row (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-remove-field-mapping', removeFieldMapping);

		// Save all mappings.
		$('#ndvwc-save-mappings').on('click', saveMappings);
	});

	/**
	 * Add a new mapping row from template.
	 */
	function addMapping() {
		var template = $('#ndvwc-mapping-template').html();
		template = template.replace(/\{\{INDEX\}\}/g, mappingIndex);
		$('#ndvwc-mappings-container').append(template);
		mappingIndex++;
	}

	/**
	 * Remove a mapping row with confirmation.
	 */
	function removeMapping() {
		if (window.confirm(ndvwcAdmin.i18n.confirm_delete)) {
			$(this).closest('.ndvwc-mapping-row').slideUp(200, function () {
				$(this).remove();
			});
		}
	}

	/**
	 * Add a field mapping sub-row from template.
	 */
	function addFieldMapping() {
		var template = $('#ndvwc-field-mapping-template').html();
		$(this).closest('.ndvwc-field-mappings-container')
			.find('.ndvwc-field-mappings-table tbody')
			.append(template);
	}

	/**
	 * Remove a field mapping sub-row.
	 */
	function removeFieldMapping() {
		$(this).closest('.ndvwc-field-mapping-row').remove();
	}

	/**
	 * Collect all mapping data and save via AJAX.
	 */
	function saveMappings() {
		var $btn = $(this);
		var $status = $('#ndvwc-save-status');
		var mappings = [];

		$btn.prop('disabled', true);
		$status.text('').removeClass('error');

		// Collect data from each mapping row.
		$('.ndvwc-mapping-row').each(function () {
			var $row = $(this);
			var mapping = {
				form_id: $row.find('.ndvwc-field-form-id').val() || '',
				product_id: $row.find('.ndvwc-field-product-id').val() || '',
				price_field_id: $row.find('.ndvwc-field-price-id').val() || '',
				field_mappings: []
			};

			// Collect field mapping sub-rows.
			$row.find('.ndvwc-field-mapping-row').each(function () {
				var fieldId = $(this).find('.ndvwc-fm-field-id').val() || '';
				var label = $(this).find('.ndvwc-fm-label').val() || '';

				if (fieldId || label) {
					mapping.field_mappings.push({
						field_id: fieldId,
						label: label
					});
				}
			});

			mappings.push(mapping);
		});

		// Send AJAX request.
		$.ajax({
			url: ndvwcAdmin.ajax_url,
			method: 'POST',
			data: {
				action: 'ndvwc_save_mappings',
				nonce: ndvwcAdmin.nonce,
				mappings: mappings
			},
			success: function (response) {
				if (response.success) {
					$status.text(ndvwcAdmin.i18n.saved).removeClass('error');
				} else {
					$status.text(response.data.message || ndvwcAdmin.i18n.error).addClass('error');
				}
			},
			error: function () {
				$status.text(ndvwcAdmin.i18n.error).addClass('error');
			},
			complete: function () {
				$btn.prop('disabled', false);

				// Auto-clear status after 3 seconds.
				setTimeout(function () {
					$status.text('');
				}, 3000);
			}
		});
	}

})(jQuery);
