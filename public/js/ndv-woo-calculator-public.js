/**
 * NDV Woo Calculator — Frontend JavaScript
 *
 * Monitors Elementor form fields, updates the add-to-cart button price label,
 * collects form data, and sends AJAX add-to-cart requests.
 *
 * @package NDV_Woo_Calculator
 * @since   1.0.0
 */

/* global jQuery, ndvwcFrontend */
(function ($) {
    'use strict';

    /**
     * Debug logger.
     */
    function log() {
        if (ndvwcFrontend.debug && window.console) {
            console.log.apply(console, ['[NDVWC]'].concat(Array.prototype.slice.call(arguments)));
        }
    }

    /**
     * Initialize when DOM is ready.
     */
    $(document).ready(function () {
        if (!ndvwcFrontend.mappings || !ndvwcFrontend.mappings.length) {
            log('No mappings configured.');
            return;
        }

        // Initialize each mapping.
        ndvwcFrontend.mappings.forEach(function (mapping) {
            initMapping(mapping);
        });
    });

    /**
     * Initialize a single form-to-product mapping.
     *
     * @param {Object} mapping Mapping configuration.
     */
    function initMapping(mapping) {
        var formId = mapping.form_id;
        var priceFieldId = mapping.price_field_id;

        log('Initializing mapping for form:', formId);

        // Find the button for this form (from shortcode).
        var $btn = $('.ndvwc-add-to-cart-btn[data-form-id="' + formId + '"]');

        if (!$btn.length) {
            log('No button found for form:', formId);
            return;
        }

        // Find the Elementor form by form ID.
        // Elementor forms use a hidden field or data attribute for form ID.
        var $form = findElementorForm(formId);

        if (!$form.length) {
            log('Elementor form not found for ID:', formId);
            // Retry after Elementor frontend loads.
            $(window).on('elementor/frontend/init', function () {
                $form = findElementorForm(formId);
                if ($form.length) {
                    setupFormListeners($form, $btn, mapping);
                }
            });
            return;
        }

        setupFormListeners($form, $btn, mapping);
    }

    /**
     * Find the Elementor form element by form ID.
     *
     * @param  {string} formId Elementor form ID.
     * @return {jQuery}        Form element.
     */
    function findElementorForm(formId) {
        // Elementor Pro stores the form name/ID in a hidden input or as form-fields data.
        var $form = $();

        // Method 1: Look for form with matching form_id hidden field.
        $('form.elementor-form').each(function () {
            var $f = $(this);
            var $hiddenId = $f.find('input[name="form_id"]');

            if ($hiddenId.length && $hiddenId.val() === formId) {
                $form = $f;
                return false; // break.
            }
        });

        // Method 2: Look for form widget with matching ID in data attribute.
        if (!$form.length) {
            $('.elementor-form').each(function () {
                var $f = $(this);
                var widgetSettings = $f.closest('.elementor-widget').data('settings');

                if (widgetSettings && widgetSettings.form_id === formId) {
                    $form = $f;
                    return false;
                }
            });
        }

        // Method 3: Look in form fields wrapper.
        if (!$form.length) {
            $('.elementor-form-fields-wrapper').closest('form').each(function () {
                var $f = $(this);
                var $idInput = $f.find('[name="form_id"]');
                if ($idInput.length && $idInput.val() === formId) {
                    $form = $f;
                    return false;
                }
            });
        }

        return $form;
    }

    /**
     * Set up field change listeners and button click handler.
     *
     * @param {jQuery} $form   The Elementor form element.
     * @param {jQuery} $btn    The add-to-cart button.
     * @param {Object} mapping Mapping configuration.
     */
    function setupFormListeners($form, $btn, mapping) {
        var priceFieldId = mapping.price_field_id;

        log('Setting up listeners for form:', mapping.form_id);

        // Monitor all form fields for changes.
        $form.on('input change', 'input, select, textarea', function () {
            updateButtonPrice($form, $btn, priceFieldId);
        });

        // Also listen for Elementor's calculated field updates.
        // Elementor calculated fields emit custom events.
        $(document).on(
            'elementor-pro/forms/calculation',
            function () {
                updateButtonPrice($form, $btn, priceFieldId);
            }
        );

        // Run initial price check.
        setTimeout(function () {
            updateButtonPrice($form, $btn, priceFieldId);
        }, 500);

        // Button click handler.
        $btn.on('click', function (e) {
            e.preventDefault();
            handleAddToCart($form, $btn, mapping);
        });
    }

    /**
     * Update the button price label from the calculated price field.
     *
     * @param {jQuery} $form        The form element.
     * @param {jQuery} $btn         The button element.
     * @param {string} priceFieldId The price field ID.
     */
    function updateButtonPrice($form, $btn, priceFieldId) {
        var $priceField = $form.find('[name="form_fields[' + priceFieldId + ']"]');

        // Also try alternative selectors.
        if (!$priceField.length) {
            $priceField = $form.find('#form-field-' + priceFieldId);
        }
        if (!$priceField.length) {
            $priceField = $form.find('[id*="' + priceFieldId + '"]');
        }

        if ($priceField.length) {
            var price = parseFloat($priceField.val());

            if (!isNaN(price) && price > 0) {
                $btn.find('.ndvwc-btn-price').text(
                    ' – ' + ndvwcFrontend.currency_symbol + price.toFixed(2)
                );
                $btn.prop('disabled', false);
                log('Price updated:', price);
            } else {
                $btn.find('.ndvwc-btn-price').text('');
                $btn.prop('disabled', true);
            }
        }
    }

    /**
     * Handle add-to-cart button click.
     *
     * @param {jQuery} $form   The form element.
     * @param {jQuery} $btn    The button element.
     * @param {Object} mapping Mapping configuration.
     */
    function handleAddToCart($form, $btn, mapping) {
        var priceFieldId = mapping.price_field_id;
        var productId = mapping.product_id;
        var formId = mapping.form_id;

        // Get calculated price.
        var $priceField = $form.find('[name="form_fields[' + priceFieldId + ']"]');
        if (!$priceField.length) {
            $priceField = $form.find('#form-field-' + priceFieldId);
        }
        if (!$priceField.length) {
            $priceField = $form.find('[id*="' + priceFieldId + '"]');
        }

        var price = parseFloat($priceField.val());

        if (isNaN(price) || price <= 0) {
            showFeedback($btn, ndvwcFrontend.i18n.error, 'error');
            return;
        }

        // Collect all mapped form data.
        var formData = collectFormData($form, mapping);

        // Optionally clear hidden conditional fields.
        if (ndvwcFrontend.clear_hidden_fields) {
            formData = clearHiddenFields($form, formData, mapping);
        }

        log('Submitting:', { productId: productId, price: price, formData: formData });

        // Disable button and show loading state.
        $btn.prop('disabled', true);
        var originalText = $btn.find('.ndvwc-btn-text').text();
        $btn.find('.ndvwc-btn-text').text(ndvwcFrontend.i18n.adding);
        $btn.addClass('ndvwc-loading');

        // Send AJAX request.
        $.ajax({
            url: ndvwcFrontend.ajax_url,
            method: 'POST',
            data: {
                action: 'ndvwc_add_to_cart',
                nonce: ndvwcFrontend.nonce,
                product_id: productId,
                calculated_price: price,
                form_id: formId,
                form_data: formData
            },
            success: function (response) {
                if (response.success) {
                    showFeedback($btn, ndvwcFrontend.i18n.added, 'success');

                    // Update cart fragments if available.
                    $(document.body).trigger('wc_fragment_refresh');

                    // Show view cart link.
                    var $feedback = $btn.closest('.ndvwc-add-to-cart-wrapper').find('.ndvwc-btn-feedback');
                    $feedback.html(
                        '<a href="' + ndvwcFrontend.cart_url + '" class="ndvwc-view-cart-link">' +
                        ndvwcFrontend.i18n.view_cart +
                        '</a>'
                    ).fadeIn();

                    log('Product added to cart successfully.');
                } else {
                    var errorMsg = response.data && response.data.message
                        ? response.data.message
                        : ndvwcFrontend.i18n.error;
                    showFeedback($btn, errorMsg, 'error');
                    log('Error:', errorMsg);
                }
            },
            error: function () {
                showFeedback($btn, ndvwcFrontend.i18n.error, 'error');
                log('AJAX error.');
            },
            complete: function () {
                $btn.removeClass('ndvwc-loading');
                $btn.find('.ndvwc-btn-text').text(originalText);
                $btn.prop('disabled', false);

                // Re-update price label.
                updateButtonPrice($form, $btn, priceFieldId);
            }
        });
    }

    /**
     * Collect mapped form field data.
     *
     * @param  {jQuery} $form   The form element.
     * @param  {Object} mapping Mapping configuration.
     * @return {Object}         Key-value pairs of field_id => value.
     */
    function collectFormData($form, mapping) {
        var data = {};

        if (!mapping.field_ids || !mapping.field_ids.length) {
            return data;
        }

        mapping.field_ids.forEach(function (fieldId) {
            var $field = $form.find('[name="form_fields[' + fieldId + ']"]');

            // Alternative selectors.
            if (!$field.length) {
                $field = $form.find('#form-field-' + fieldId);
            }

            if ($field.length) {
                var type = $field.attr('type');

                if ('checkbox' === type) {
                    // Handle checkbox groups.
                    var checked = [];
                    $form.find('[name="form_fields[' + fieldId + '][]"]:checked, [name="form_fields[' + fieldId + ']"]:checked').each(function () {
                        checked.push($(this).val());
                    });
                    data[fieldId] = checked.length ? checked.join(', ') : '';
                } else if ('radio' === type) {
                    data[fieldId] = $form.find('[name="form_fields[' + fieldId + ']"]:checked').val() || '';
                } else {
                    data[fieldId] = $field.val() || '';
                }
            }
        });

        return data;
    }

    /**
     * Clear values of hidden conditional fields.
     *
     * @param  {jQuery} $form       The form element.
     * @param  {Object} formData    Collected form data.
     * @param  {Object} mapping     Mapping configuration.
     * @return {Object}             Cleaned form data.
     */
    function clearHiddenFields($form, formData, mapping) {
        var cleaned = {};

        for (var fieldId in formData) {
            if (formData.hasOwnProperty(fieldId)) {
                var $wrapper = $form.find('.elementor-field-group').filter(function () {
                    return $(this).find('[name*="' + fieldId + '"]').length > 0;
                });

                // If the field's wrapper is visible, keep the data.
                if ($wrapper.length && $wrapper.is(':visible')) {
                    cleaned[fieldId] = formData[fieldId];
                } else if (!$wrapper.length) {
                    // If we can't find the wrapper, keep the data to be safe.
                    cleaned[fieldId] = formData[fieldId];
                }
                // Otherwise, skip hidden field value.
            }
        }

        return cleaned;
    }

    /**
     * Show feedback message on the button.
     *
     * @param {jQuery} $btn    The button element.
     * @param {string} message Message text.
     * @param {string} type    'success' or 'error'.
     */
    function showFeedback($btn, message, type) {
        $btn.addClass('ndvwc-feedback-' + type);

        setTimeout(function () {
            $btn.removeClass('ndvwc-feedback-success ndvwc-feedback-error');
        }, 3000);
    }

})(jQuery);
