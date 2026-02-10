/**
 * NDV Woo Calculator — Admin JavaScript
 *
 * Handles dynamic mapping row management, pricing rules, pendant config,
 * global rates management, and AJAX save.
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

		// Apply initial calc-mode state on existing rows.
		$('.ndvwc-mapping-row').each(function () {
			updateCalcModeVisibility($(this));
		});

		// Add new mapping configuration.
		$('#ndvwc-add-mapping').on('click', addMapping);

		// Remove mapping configuration (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-remove-mapping', removeMapping);

		// Add field mapping sub-row (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-add-field-mapping', addFieldMapping);

		// Remove field mapping sub-row (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-remove-field-mapping', removeFieldMapping);

		// Add pricing rule row (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-add-pricing-rule', addPricingRule);

		// Remove pricing rule row (delegated).
		$('#ndvwc-mappings-container').on('click', '.ndvwc-remove-pricing-rule', removePricingRule);

		// Calculation mode toggle (delegated).
		$('#ndvwc-mappings-container').on('change', '.ndvwc-field-calc-mode', function () {
			updateCalcModeVisibility($(this).closest('.ndvwc-mapping-row'));
		});

		// Save all mappings.
		$('#ndvwc-save-mappings').on('click', saveMappings);

		// ---- Global Rates Tab ----
		// Add rate row (delegated).
		$('.ndvwc-rates-section').on('click', '.ndvwc-add-rate-row', addRateRow);

		// Remove rate row (delegated).
		$('.ndvwc-rates-section').on('click', '.ndvwc-remove-rate-row', removeRateRow);

		// Save all rates.
		$('#ndvwc-save-rates').on('click', saveGlobalRates);
	});

	// ========================================================================
	// MAPPING MANAGEMENT
	// ========================================================================

	/**
	 * Update the data-calc-mode attribute on a mapping row so CSS can
	 * show/hide the right fields.
	 */
	function updateCalcModeVisibility($row) {
		var mode = $row.find('.ndvwc-field-calc-mode').val() || 'elementor';
		$row.attr('data-calc-mode', mode);
	}

	/**
	 * Add a new mapping row from template.
	 */
	function addMapping() {
		var template = $('#ndvwc-mapping-template').html();
		template = template.replace(/\{\{INDEX\}\}/g, mappingIndex);
		var $newRow = $(template);
		$('#ndvwc-mappings-container').append($newRow);
		updateCalcModeVisibility($newRow);
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
	 * Add a pricing rule row from template.
	 */
	function addPricingRule() {
		var template = $('#ndvwc-pricing-rule-template').html();
		$(this).closest('.ndvwc-pricing-rules-container')
			.find('.ndvwc-pricing-rules-table tbody')
			.append(template);
	}

	/**
	 * Remove a pricing rule row.
	 */
	function removePricingRule() {
		$(this).closest('.ndvwc-pricing-rule-row').remove();
	}

	/**
	 * Collect all mapping data and save via AJAX.
	 */
	function saveMappings() {
		var $btn = $(this);
		var $status = $('#ndvwc-save-status');
		var mappings = [];

		$btn.prop('disabled', true).text(ndvwcAdmin.i18n.saving || 'Saving...');
		$status.text('').removeClass('error success');

		// Collect data from each mapping row.
		$('.ndvwc-mapping-row').each(function () {
			var $row = $(this);
			var calcMode = $row.find('.ndvwc-field-calc-mode').val() || 'elementor';

			var mapping = {
				form_id: $row.find('.ndvwc-field-form-id').val() || '',
				product_id: $row.find('.ndvwc-field-product-id').val() || '',
				price_field_id: $row.find('.ndvwc-field-price-id').val() || '',
				calculation_mode: calcMode,
				base_price: $row.find('.ndvwc-field-base-price').val() || '0',
				field_mappings: [],
				pricing_rules: []
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

			// Collect pricing rule rows.
			$row.find('.ndvwc-pricing-rule-row').each(function () {
				var $ruleRow = $(this);
				var rule = {
					field_id: $ruleRow.find('.ndvwc-pr-field-id').val() || '',
					field_value: $ruleRow.find('.ndvwc-pr-field-value').val() || '',
					operator: $ruleRow.find('.ndvwc-pr-operator').val() || 'add',
					amount: $ruleRow.find('.ndvwc-pr-amount').val() || '0'
				};

				if (rule.field_id) {
					mapping.pricing_rules.push(rule);
				}
			});

			// Collect pendant-specific fields when in pendant mode.
			if ('pendant' === calcMode) {
				mapping.pendant_metal_weight = $row.find('.ndvwc-field-pendant-metal-weight').val() || '0';
				mapping.pendant_user_weight_enabled = $row.find('.ndvwc-field-pendant-user-weight').is(':checked') ? 'yes' : '';
				mapping.pendant_max_stones = $row.find('.ndvwc-field-pendant-max-stones').val() || '5';
				mapping.pendant_labor = $row.find('.ndvwc-field-pendant-labor').val() || '0';
				mapping.pendant_markup = $row.find('.ndvwc-field-pendant-markup').val() || '0';
				mapping.pendant_markup_type = $row.find('.ndvwc-field-pendant-markup-type').val() || 'fixed';

				// Collect checked metals.
				mapping.pendant_available_metals = [];
				$row.find('.ndvwc-pendant-available-metal:checked').each(function () {
					mapping.pendant_available_metals.push($(this).val());
				});

				// Collect checked stones.
				mapping.pendant_available_stones = [];
				$row.find('.ndvwc-pendant-available-stone:checked').each(function () {
					mapping.pendant_available_stones.push($(this).val());
				});

				// Collect checked chains.
				mapping.pendant_available_chains = [];
				$row.find('.ndvwc-pendant-available-chain:checked').each(function () {
					mapping.pendant_available_chains.push($(this).val());
				});

				// Parse chain lengths from comma-separated string.
				var chainLengthsStr = $row.find('.ndvwc-field-pendant-chain-lengths').val() || '';
				mapping.pendant_chain_lengths = chainLengthsStr
					.split(',')
					.map(function (s) { return parseFloat(s.trim()); })
					.filter(function (n) { return !isNaN(n) && n > 0; });
			}

			mappings.push(mapping);
		});

		// Use JSON.stringify to avoid jQuery nested object serialization issues.
		$.ajax({
			url: ndvwcAdmin.ajax_url,
			method: 'POST',
			data: {
				action: 'ndvwc_save_mappings',
				nonce: ndvwcAdmin.nonce,
				mappings: JSON.stringify(mappings)
			},
			success: function (response) {
				if (response.success) {
					$status.text(ndvwcAdmin.i18n.saved).removeClass('error').addClass('success');
				} else {
					var msg = response.data && response.data.message ? response.data.message : ndvwcAdmin.i18n.error;
					$status.text(msg).addClass('error').removeClass('success');
				}
			},
			error: function () {
				$status.text(ndvwcAdmin.i18n.error).addClass('error').removeClass('success');
			},
			complete: function () {
				$btn.prop('disabled', false).text(ndvwcAdmin.i18n.save_btn || 'Save All Mappings');

				// Auto-clear status after 4 seconds.
				setTimeout(function () {
					$status.text('').removeClass('error success');
				}, 4000);
			}
		});
	}

	// ========================================================================
	// GLOBAL RATES TAB
	// ========================================================================

	/**
	 * Add a new empty row to a rates table.
	 */
	function addRateRow() {
		var tableId = $(this).data('table');
		var isChain = tableId === 'ndvwc-chain-rates-table';

		var html = '<tr class="ndvwc-rate-row">' +
			'<td><input type="text" class="widefat ndvwc-rate-key" value="" placeholder="e.g. slug_key" /></td>' +
			'<td><input type="text" class="widefat ndvwc-rate-name" value="" placeholder="Display Name" /></td>' +
			'<td><input type="number" step="0.01" min="0" class="widefat ndvwc-rate-value" value="" placeholder="0.00" /></td>';

		if (isChain) {
			html += '<td><input type="text" class="widefat ndvwc-rate-material" value="" placeholder="e.g. Sterling Silver" /></td>';
		}

		html += '<td><button type="button" class="button ndvwc-remove-rate-row" title="Remove">' +
			'<span class="dashicons dashicons-no-alt"></span></button></td>' +
			'</tr>';

		$('#' + tableId + ' tbody').append(html);
	}

	/**
	 * Remove a rate row.
	 */
	function removeRateRow() {
		$(this).closest('.ndvwc-rate-row').remove();
	}

	/**
	 * Collect all rate data and save via AJAX.
	 */
	function saveGlobalRates() {
		var $btn = $(this);
		var $status = $('#ndvwc-rates-save-status');

		$btn.prop('disabled', true).text(ndvwcAdmin.i18n.saving || 'Saving...');
		$status.text('').removeClass('error success');

		var rates = {
			metals: [],
			stones: [],
			chains: []
		};

		// Collect metals.
		$('#ndvwc-metal-rates-table tbody .ndvwc-rate-row').each(function () {
			var key = $(this).find('.ndvwc-rate-key').val() || '';
			var name = $(this).find('.ndvwc-rate-name').val() || '';
			var val = $(this).find('.ndvwc-rate-value').val() || '0';
			if (key && name) {
				rates.metals.push({
					key: key,
					name: name,
					price_per_gram: parseFloat(val)
				});
			}
		});

		// Collect stones.
		$('#ndvwc-stone-rates-table tbody .ndvwc-rate-row').each(function () {
			var key = $(this).find('.ndvwc-rate-key').val() || '';
			var name = $(this).find('.ndvwc-rate-name').val() || '';
			var val = $(this).find('.ndvwc-rate-value').val() || '0';
			if (key && name) {
				rates.stones.push({
					key: key,
					name: name,
					price_per_unit: parseFloat(val)
				});
			}
		});

		// Collect chains.
		$('#ndvwc-chain-rates-table tbody .ndvwc-rate-row').each(function () {
			var key = $(this).find('.ndvwc-rate-key').val() || '';
			var name = $(this).find('.ndvwc-rate-name').val() || '';
			var val = $(this).find('.ndvwc-rate-value').val() || '0';
			var mat = $(this).find('.ndvwc-rate-material').val() || '';
			if (key && name) {
				rates.chains.push({
					key: key,
					name: name,
					rate_per_cm: parseFloat(val),
					material: mat
				});
			}
		});

		$.ajax({
			url: ndvwcAdmin.ajax_url,
			method: 'POST',
			data: {
				action: 'ndvwc_save_global_rates',
				nonce: ndvwcAdmin.nonce,
				rates: JSON.stringify(rates)
			},
			success: function (response) {
				if (response.success) {
					$status.text(ndvwcAdmin.i18n.saved || 'Saved!').removeClass('error').addClass('success');
				} else {
					var msg = response.data && response.data.message ? response.data.message : (ndvwcAdmin.i18n.error || 'Error');
					$status.text(msg).addClass('error').removeClass('success');
				}
			},
			error: function () {
				$status.text(ndvwcAdmin.i18n.error || 'Error').addClass('error').removeClass('success');
			},
			complete: function () {
				$btn.prop('disabled', false).text('Save All Rates');

				setTimeout(function () {
					$status.text('').removeClass('error success');
				}, 4000);
			}
		});
	}

})(jQuery);
