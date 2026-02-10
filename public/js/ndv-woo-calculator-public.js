/**
 * NDV Woo Calculator — Frontend JavaScript
 *
 * Handles form detection, price monitoring, data collection, and AJAX add-to-cart.
 * Supports three modes:
 *   1. "elementor": reads price from a calculated field in an Elementor form.
 *   2. "rules": lets the server calculate the price based on form data rules.
 *   3. "pendant": separate pendant configurator widget (metals, stones, chains).
 *
 * @package NDV_Woo_Calculator
 * @since   1.0.0
 */

/* global jQuery, ndvwcFrontend, ndvwcPendant */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Initialize standard Elementor mappings.
        if (ndvwcFrontend && ndvwcFrontend.mappings) {
            ndvwcFrontend.mappings.forEach(function (mapping) {
                initMapping(mapping);
            });
        }

        // Initialize Pendant Configurators.
        $('.ndvwc-pendant-configurator').each(function () {
            initPendantConfigurator($(this));
        });
    });

    // ========================================================================
    // STANDARD FORM MAPPING LOGIC
    // ========================================================================

    /**
     * Initialize a single form mapping.
     */
    function initMapping(mapping) {
        var $form = findForm(mapping.form_id);
        if (!$form || !$form.length) {
            if (ndvwcFrontend.debug) {
                console.log('[NDVWC] Form not found:', mapping.form_id);
            }
            return;
        }

        var $wrapper = $('.ndvwc-add-to-cart-wrapper[data-form-id="' + mapping.form_id + '"]');
        if (!$wrapper.length) {
            if (ndvwcFrontend.debug) {
                console.log('[NDVWC] Button wrapper not found for form:', mapping.form_id);
            }
            return;
        }

        var $btn = $wrapper.find('.ndvwc-add-to-cart-btn');
        var $priceSpan = $btn.find('.ndvwc-btn-price');
        var $feedback = $wrapper.find('.ndvwc-btn-feedback');
        var calcMode = mapping.calculation_mode || 'elementor';

        if (ndvwcFrontend.debug) {
            console.log('[NDVWC] Initialized mapping:', mapping.form_id, 'mode:', calcMode);
        }

        // Enable the button immediately for rules mode (price is server-side).
        if ('rules' === calcMode) {
            $btn.prop('disabled', false);
        }

        // Monitor form field changes for price updates (Elementor mode)
        // or just to keep the button enabled (rules mode).
        $form.on('change input', 'input, select, textarea', function () {
            if ('elementor' === calcMode && mapping.price_field_id) {
                var price = getPriceFromField($form, mapping.price_field_id);
                if (price > 0) {
                    $priceSpan.text(' – ' + ndvwcFrontend.currency_symbol + price.toFixed(2));
                    $btn.prop('disabled', false);
                } else {
                    $priceSpan.text('');
                    $btn.prop('disabled', true);
                }
            }
        });

        // Handle add-to-cart click.
        $btn.on('click', function () {
            if ($btn.prop('disabled') || $btn.hasClass('ndvwc-loading')) {
                return;
            }

            var formData = collectFormData($form, mapping.field_ids || []);
            var price = 0;

            if ('elementor' === calcMode) {
                price = getPriceFromField($form, mapping.price_field_id);
                if (price <= 0) {
                    showFeedback($feedback, ndvwcFrontend.i18n.error, 'error');
                    return;
                }
            }
            // For rules mode, price = 0 is fine — the server will calculate it.

            $btn.addClass('ndvwc-loading').prop('disabled', true);
            $btn.find('.ndvwc-btn-text').text(ndvwcFrontend.i18n.adding);
            $feedback.hide();

            $.ajax({
                url: ndvwcFrontend.ajax_url,
                method: 'POST',
                data: {
                    action: 'ndvwc_add_to_cart',
                    nonce: ndvwcFrontend.nonce,
                    product_id: mapping.product_id,
                    form_id: mapping.form_id,
                    calculated_price: price,
                    form_data: formData
                },
                success: function (response) {
                    if (response.success) {
                        $btn.addClass('ndvwc-success');
                        $btn.find('.ndvwc-btn-text').text(ndvwcFrontend.i18n.added);

                        // If the server returned the calculated price, show it.
                        if (response.data && response.data.price) {
                            $priceSpan.text(' – ' + ndvwcFrontend.currency_symbol + parseFloat(response.data.price).toFixed(2));
                        }

                        var cartUrl = response.data && response.data.cart_url
                            ? response.data.cart_url
                            : ndvwcFrontend.cart_url;

                        showFeedback(
                            $feedback,
                            '<a href="' + cartUrl + '" class="ndvwc-view-cart">' + ndvwcFrontend.i18n.view_cart + '</a>',
                            'success'
                        );

                        // Reset after 3 seconds.
                        setTimeout(function () {
                            $btn.removeClass('ndvwc-success');
                            $btn.find('.ndvwc-btn-text').text(ndvwcFrontend.i18n.add_to_cart);
                        }, 3000);

                        // Log debug info on success if available.
                        if (ndvwcFrontend.debug && response.data) {
                            if (response.data.debug) {
                                console.log('[NDVWC] Price engine debug:', response.data.debug);
                            }
                            if (response.data.form_data_received) {
                                console.log('[NDVWC] Form data received by server:', response.data.form_data_received);
                            }
                        }
                    } else {
                        var errorMsg = response.data && response.data.message
                            ? response.data.message
                            : ndvwcFrontend.i18n.error;
                        showFeedback($feedback, errorMsg, 'error');

                        // Log debug info if available.
                        if (ndvwcFrontend.debug && response.data) {
                            if (response.data.debug) {
                                console.log('[NDVWC] Price engine debug:', response.data.debug);
                            }
                            if (response.data.form_data_received) {
                                console.log('[NDVWC] Form data received by server:', response.data.form_data_received);
                            }
                        }
                    }
                },
                error: function () {
                    showFeedback($feedback, ndvwcFrontend.i18n.error, 'error');
                },
                complete: function () {
                    $btn.removeClass('ndvwc-loading').prop('disabled', false);
                }
            });
        });
    }

    /**
     * Find the Elementor form by its form ID.
     * Supports multiple detection methods.
     */
    function findForm(formId) {
        var $form;

        // Method 1: Form with matching id attribute (Elementor's "Form ID" setting).
        $form = $('form#' + formId);
        if ($form.length) return $form;

        // Method 2: Form inside a wrapper with the ID.
        $form = $('#' + formId).find('form');
        if ($form.length) return $form;
        // Or the element itself is the form.
        if ($('#' + formId).is('form')) return $('#' + formId);

        // Method 3: Elementor hidden input named "form_id" with matching value.
        var $input = $('input[name="form_id"][value="' + formId + '"]');
        if ($input.length) return $input.closest('form');

        // Method 4: data-form-name attribute.
        $form = $('form[data-form-name="' + formId + '"]');
        if ($form.length) return $form;

        // Method 5: Elementor form widget wrapper with data-settings.
        var found = null;
        $('.elementor-form, .elementor-widget-form').each(function () {
            var settings = $(this).data('settings');
            if (settings && (settings.form_name === formId || settings.form_id === formId)) {
                found = $(this).is('form') ? $(this) : $(this).find('form');
                return false; // break
            }
        });
        if (found && found.length) return found;

        // Method 6: Form with class containing the form ID.
        $form = $('form[class*="' + formId + '"]');
        if ($form.length) return $form;

        // Method 7: Any form inside an Elementor widget section — broadest fallback.
        $form = $('.elementor-widget-form form');
        if ($form.length === 1) return $form; // Only if there's exactly one form.

        if (ndvwcFrontend.debug) {
            console.log('[NDVWC] All detection methods failed for form ID:', formId);
        }

        return null;
    }

    /**
     * Get the calculated price from a form field.
     */
    function getPriceFromField($form, fieldId) {
        if (!fieldId) return 0;

        var $field = $form.find(
            '[name="form_fields[' + fieldId + ']"],' +
            '[name="' + fieldId + '"],' +
            '#form-field-' + fieldId + ',' +
            '[data-field-id="' + fieldId + '"]'
        ).first();

        if (!$field.length) return 0;

        var val = $field.val();
        if (!val) return 0;

        // Strip currency symbols, commas, etc.
        val = val.replace(/[^0-9.\-]/g, '');
        var price = parseFloat(val);

        return isNaN(price) ? 0 : price;
    }

    /**
     * Collect form data for the mapped field IDs.
     */
    function collectFormData($form, fieldIds) {
        var data = {};

        fieldIds.forEach(function (fieldId) {
            var $field = $form.find(
                '[name="form_fields[' + fieldId + ']"],' +
                '[name="' + fieldId + '"],' +
                '#form-field-' + fieldId
            ).first();

            if (!$field.length) {
                // Try radio buttons.
                $field = $form.find('[name="form_fields[' + fieldId + ']"]:checked');
            }

            if ($field.length) {
                var type = $field.attr('type');

                if ('checkbox' === type) {
                    data[fieldId] = $field.is(':checked') ? $field.val() || 'Yes' : '';
                } else if ('radio' === type) {
                    var $checked = $form.find('[name="' + $field.attr('name') + '"]:checked');
                    data[fieldId] = $checked.length ? $checked.val() : '';
                } else if ($field.is('select')) {
                    data[fieldId] = $field.find('option:selected').text().trim() || $field.val();
                } else {
                    data[fieldId] = $field.val() || '';
                }
            }
        });

        // For rules mode, also collect ALL form fields so the server can evaluate rules.
        $form.find('input, select, textarea').each(function () {
            var name = $(this).attr('name') || '';
            // Extract field ID from Elementor naming convention.
            var match = name.match(/form_fields\[(.+?)\]/);
            if (match && match[1] && !(match[1] in data)) {
                var type = $(this).attr('type');
                if ('checkbox' === type) {
                    data[match[1]] = $(this).is(':checked') ? $(this).val() || 'Yes' : '';
                } else if ('radio' === type) {
                    if ($(this).is(':checked')) {
                        data[match[1]] = $(this).val();
                    }
                } else {
                    data[match[1]] = $(this).val() || '';
                }
            }
        });

        // Clear hidden conditional fields if enabled.
        if (ndvwcFrontend.clear_hidden_fields) {
            $form.find('.elementor-field-group').each(function () {
                if ($(this).is(':hidden') || $(this).css('display') === 'none') {
                    $(this).find('input, select, textarea').each(function () {
                        var fieldName = $(this).attr('name') || '';
                        var fieldMatch = fieldName.match(/form_fields\[(.+?)\]/);
                        if (fieldMatch && fieldMatch[1]) {
                            data[fieldMatch[1]] = '';
                        }
                    });
                }
            });
        }

        return data;
    }

    /**
     * Show feedback message below the button.
     */
    function showFeedback($el, message, type) {
        $el.html(message)
            .removeClass('ndvwc-feedback-success ndvwc-feedback-error')
            .addClass('ndvwc-feedback-' + type)
            .slideDown(200);

        if ('error' === type) {
            setTimeout(function () {
                $el.slideUp(200);
            }, 5000);
        }
    }

    // ========================================================================
    // PENDANT CONFIGURATOR LOGIC
    // ========================================================================

    function initPendantConfigurator($el) {
        var productId = $el.data('product-id');
        var configVar = 'ndvwcPendant_' + productId;
        var config = window[configVar];

        if (!config) {
            if (ndvwcFrontend.debug) console.log('[NDVWC] No config data found for pendant product:', productId);
            return;
        }

        var $metalSelect = $el.find('.ndvwc-pendant-metal-select');
        var $chainSelect = $el.find('.ndvwc-pendant-chain-select');
        var $chainLengthSelect = $el.find('.ndvwc-pendant-chain-length-select');
        var $chainLengthLabel = $el.find('.ndvwc-pendant-chain-length-label');
        var $stoneContainer = $el.find('.ndvwc-pendant-stone-rows');
        var $addStoneBtn = $el.find('.ndvwc-pendant-add-stone-btn');
        var $priceValue = $el.find('.ndvwc-pendant-price-value');
        var $breakdown = $el.find('.ndvwc-pendant-breakdown');
        var $cartBtn = $el.find('.ndvwc-pendant-add-to-cart-btn');
        var $cartBtn = $el.find('.ndvwc-pendant-add-to-cart-btn');
        var $feedback = $el.find('.ndvwc-pendant-feedback');
        var $weightInput = $el.find('.ndvwc-pendant-weight-input');

        // Toggle chain length visibility.
        $chainSelect.on('change', function () {
            if ($(this).val() !== 'none' && $(this).val()) {
                $chainLengthLabel.show();
                $chainLengthSelect.show();
            } else {
                $chainLengthLabel.hide();
                $chainLengthSelect.hide();
            }
            updatePrice();
        });

        // Add correct number of stone rows based on current stones.
        $addStoneBtn.on('click', function () {
            var currentRows = $stoneContainer.find('.ndvwc-pendant-stone-row').length;
            if (currentRows >= config.max_stones) {
                alert('Maximum ' + config.max_stones + ' stones allowed.');
                return;
            }
            addStoneRow($stoneContainer, config.stones, config.max_stones);
        });

        // Remove row delegation.
        $stoneContainer.on('click', '.ndvwc-pendant-remove-stone', function () {
            $(this).closest('.ndvwc-pendant-stone-row').remove();
            updatePrice();
        });

        // Listen for changes to update price.
        $el.on('change input', 'select, input', function () {
            updatePrice();
        });

        // Update price function.
        function updatePrice() {
            var metalKey = $metalSelect.val();
            var chainKey = $chainSelect.val();
            var metalKey = $metalSelect.val();
            var chainKey = $chainSelect.val();
            var chainLen = $chainLengthSelect.val();
            var metalWeight = $weightInput.length ? parseFloat($weightInput.val()) : null;

            // Collect stones.
            var stones = [];
            $stoneContainer.find('.ndvwc-pendant-stone-row').each(function () {
                var sKey = $(this).find('.ndvwc-pendant-stone-select').val();
                var sQty = $(this).find('.ndvwc-pendant-stone-qty').val();
                if (sKey && sQty > 0) {
                    stones.push({ stone_key: sKey, quantity: sQty });
                }
            });

            // Basic validation for button state.
            if (!metalKey) {
                $cartBtn.prop('disabled', true);
                $priceValue.text('—');
                return;
            }

            $cartBtn.prop('disabled', false);
            $priceValue.addClass('ndvwc-updating').css('opacity', '0.5');

            $.ajax({
                url: ndvwcFrontend.ajax_url,
                method: 'POST',
                data: {
                    action: 'ndvwc_pendant_preview_price',
                    nonce: ndvwcFrontend.nonce,
                    product_id: productId,
                    metal_key: metalKey,
                    chain_key: chainKey,
                    chain_length: chainLen,
                    chain_key: chainKey,
                    chain_length: chainLen,
                    metal_weight: metalWeight,
                    stones: stones
                },
                success: function (res) {
                    if (res.success) {
                        $priceValue.html(res.data.formatted);
                        // Optional: render breakdown if in debug mode or desired.
                    }
                },
                complete: function () {
                    $priceValue.removeClass('ndvwc-updating').css('opacity', '1');
                }
            });
        }

        // Add to Cart.
        $cartBtn.on('click', function () {
            if ($cartBtn.prop('disabled')) return;

            var metalKey = $metalSelect.val();
            var chainKey = $chainSelect.val();
            var chainLen = $chainLengthSelect.val();
            var chainLen = $chainLengthSelect.val();
            var metalWeight = $weightInput.length ? parseFloat($weightInput.val()) : null;
            var stones = [];

            $stoneContainer.find('.ndvwc-pendant-stone-row').each(function () {
                var sKey = $(this).find('.ndvwc-pendant-stone-select').val();
                var sQty = $(this).find('.ndvwc-pendant-stone-qty').val();
                if (sKey && sQty > 0) {
                    stones.push({ stone_key: sKey, quantity: sQty });
                }
            });

            $cartBtn.prop('disabled', true).text(ndvwcFrontend.i18n.adding);
            $feedback.hide();

            var data = {
                pendant_metal: metalKey,
                pendant_chain: chainKey,
                pendant_chain_length: chainLen,
                pendant_chain_length: chainLen,
                pendant_metal_weight: metalWeight,
                pendant_stones: stones
            };

            $.ajax({
                url: ndvwcFrontend.ajax_url,
                method: 'POST',
                data: {
                    action: 'ndvwc_add_to_cart',
                    nonce: ndvwcFrontend.nonce,
                    product_id: productId,
                    form_id: config.form_id, // Use actual mapped form ID
                    form_data: data
                },
                success: function (response) {
                    if (response.success) {
                        $cartBtn.text(ndvwcFrontend.i18n.added).addClass('success');
                        showFeedback($feedback,
                            '<a href="' + response.data.cart_url + '" class="ndvwc-view-cart">' + ndvwcFrontend.i18n.view_cart + '</a>',
                            'success'
                        );
                        setTimeout(function () {
                            $cartBtn.text(ndvwcFrontend.i18n.add_to_cart).removeClass('success').prop('disabled', false);
                        }, 3000);
                    } else {
                        showFeedback($feedback, response.data.message || ndvwcFrontend.i18n.error, 'error');
                        $cartBtn.text(ndvwcFrontend.i18n.add_to_cart).prop('disabled', false);
                    }
                },
                error: function () {
                    showFeedback($feedback, ndvwcFrontend.i18n.error, 'error');
                    $cartBtn.text(ndvwcFrontend.i18n.add_to_cart).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Add a stone row dynamically.
     */
    function addStoneRow($container, stones, maxStones) {
        var html = '<div class="ndvwc-pendant-stone-row">';

        // Stone select.
        html += '<select class="ndvwc-pendant-select ndvwc-pendant-stone-select">';
        html += '<option value="">— Select Stone —</option>';
        stones.forEach(function (s) {
            html += '<option value="' + s.key + '">' + s.name + '</option>';
        });
        html += '</select>';

        // Qty input.
        html += '<input type="number" class="ndvwc-pendant-stone-qty" min="0" max="' + maxStones + '" value="1" />';

        // Remove button.
        html += '<button type="button" class="ndvwc-pendant-remove-stone" title="Remove">×</button>';
        html += '</div>';

        $container.append(html);
    }

})(jQuery);
