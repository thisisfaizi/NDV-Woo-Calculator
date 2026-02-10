<?php
/**
 * AJAX handler for add-to-cart functionality.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Ajax
 *
 * Handles the AJAX add-to-cart request from the frontend form.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Ajax
{

    /**
     * Initialize AJAX hooks.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('wp_ajax_ndvwc_add_to_cart', array($this, 'handle_add_to_cart'));
        add_action('wp_ajax_nopriv_ndvwc_add_to_cart', array($this, 'handle_add_to_cart'));
    }

    /**
     * Handle the AJAX add-to-cart request.
     *
     * Validates nonce, input data, mapping configuration, and adds the product to cart.
     *
     * @since 1.0.0
     */
    public function handle_add_to_cart()
    {

        // 1. Verify nonce.
        check_ajax_referer('ndvwc_nonce', 'nonce');

        // 2. Check plugin is enabled.
        if (!NDV_Woo_Calculator_Config_Manager::is_enabled()) {
            wp_send_json_error(
                array('message' => __('Calculator is currently disabled.', 'ndv-woo-calculator')),
                403
            );
        }

        // 3. Sanitize inputs.
        $product_id = absint($_POST['product_id'] ?? 0);
        $calculated_price = isset($_POST['calculated_price']) ? floatval($_POST['calculated_price']) : 0;
        $form_id = sanitize_text_field(wp_unslash($_POST['form_id'] ?? ''));
        $form_data_raw = isset($_POST['form_data']) ? wp_unslash($_POST['form_data']) : array();

        // Sanitize form data recursively.
        $form_data = is_array($form_data_raw)
            ? map_deep($form_data_raw, 'sanitize_text_field')
            : array();

        // 4. Validate product ID.
        if (0 === $product_id) {
            wp_send_json_error(
                array('message' => __('Invalid product.', 'ndv-woo-calculator')),
                400
            );
        }

        $product = wc_get_product($product_id);
        if (!$product) {
            wp_send_json_error(
                array('message' => __('Product not found.', 'ndv-woo-calculator')),
                404
            );
        }

        // 5. Validate price.
        if ($calculated_price <= 0) {
            wp_send_json_error(
                array('message' => __('Invalid price.', 'ndv-woo-calculator')),
                400
            );
        }

        // 6. Validate mapping configuration.
        $mapping = NDV_Woo_Calculator_Config_Manager::get_mapping_by_form_id($form_id);
        if (!$mapping) {
            wp_send_json_error(
                array('message' => __('Form configuration not found.', 'ndv-woo-calculator')),
                400
            );
        }

        // Verify product ID matches mapping.
        if (absint($mapping['product_id']) !== $product_id) {
            wp_send_json_error(
                array('message' => __('Product does not match form configuration.', 'ndv-woo-calculator')),
                400
            );
        }

        // 7. Build cart item data.
        $cart_item_data = array(
            'ndvwc_calculated_price' => $calculated_price,
            'ndvwc_form_id' => $form_id,
            'ndvwc_form_data' => $form_data,
        );

        /**
         * Filter the cart item data before adding to cart.
         *
         * @since 1.0.0
         * @param array  $cart_item_data Cart item data.
         * @param int    $product_id     Product ID.
         * @param string $form_id        Form ID.
         * @param array  $form_data      Form selections.
         */
        $cart_item_data = apply_filters('ndvwc_cart_item_data', $cart_item_data, $product_id, $form_id, $form_data);

        // 8. Add to cart.
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        if (!$cart_item_key) {
            wp_send_json_error(
                array('message' => __('Could not add product to cart.', 'ndv-woo-calculator')),
                500
            );
        }

        // 9. Return success.
        wp_send_json_success(
            array(
                'message' => __('Product added to cart.', 'ndv-woo-calculator'),
                'cart_url' => wc_get_cart_url(),
                'cart_count' => WC()->cart->get_cart_contents_count(),
            )
        );
    }
}
