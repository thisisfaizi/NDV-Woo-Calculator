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
 * Supports both Elementor calculated-field mode and server-side pricing rules.
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

        // Pendant live price preview.
        add_action('wp_ajax_ndvwc_pendant_preview_price', array($this, 'handle_pendant_preview'));
        add_action('wp_ajax_nopriv_ndvwc_pendant_preview_price', array($this, 'handle_pendant_preview'));
    }

    /**
     * Handle the AJAX add-to-cart request.
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
        $client_price = isset($_POST['calculated_price']) ? floatval($_POST['calculated_price']) : 0;
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

        // 5. Validate mapping configuration.
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

        // 6. Determine the final price based on calculation mode.
        $calc_mode = $mapping['calculation_mode'] ?? 'elementor';

        if ('rules' === $calc_mode) {
            // Server-side calculation using pricing rules engine.
            $calculated_price = NDV_Woo_Calculator_Price_Engine::calculate($mapping, $form_data);
            $debug_log = NDV_Woo_Calculator_Price_Engine::get_debug_log();
        } elseif ('pendant' === $calc_mode) {
            // Pendant configurator mode: calculate from pendant selections.
            $selections = $this->extract_pendant_selections($form_data);
            $result = NDV_Woo_Calculator_Pendant_Engine::calculate($mapping, $selections);
            $calculated_price = $result['price'];
            $debug_log = NDV_Woo_Calculator_Pendant_Engine::get_debug_log();

            // Store breakdown for cart display.
            $form_data['_pendant_breakdown'] = $result['breakdown'];
        } else {
            // Elementor mode: trust the client-provided price from the calculated field.
            $calculated_price = $client_price;
            $debug_log = array();
        }

        // 7. Validate price.
        if ($calculated_price <= 0) {
            $error_data = array('message' => __('Invalid price. Please check your form selections.', 'ndv-woo-calculator'));

            // Include debug info if debug mode is on.
            if (NDV_Woo_Calculator_Config_Manager::is_debug()) {
                $error_data['debug'] = $debug_log;
                $error_data['form_data_received'] = $form_data;
            }

            wp_send_json_error($error_data, 400);
        }

        // 8. Build cart item data.
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

        // 9. Add to cart.
        $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

        if (!$cart_item_key) {
            wp_send_json_error(
                array('message' => __('Could not add product to cart.', 'ndv-woo-calculator')),
                500
            );
        }

        // 10. Return success.
        $success_data = array(
            'message' => __('Product added to cart.', 'ndv-woo-calculator'),
            'cart_url' => wc_get_cart_url(),
            'cart_count' => WC()->cart->get_cart_contents_count(),
            'price' => $calculated_price,
        );

        // Include debug info if debug mode is on.
        if (NDV_Woo_Calculator_Config_Manager::is_debug()) {
            $success_data['debug'] = $debug_log;
            $success_data['form_data_received'] = $form_data;
        }

        wp_send_json_success($success_data);
    }

    /**
     * Handle pendant live price preview AJAX request.
     *
     * Returns the calculated price and breakdown without adding to cart.
     *
     * @since 1.1.0
     */
    public function handle_pendant_preview()
    {
        check_ajax_referer('ndvwc_nonce', 'nonce');

        $product_id = absint($_POST['product_id'] ?? 0);

        if (0 === $product_id) {
            wp_send_json_error(array('message' => __('Invalid product.', 'ndv-woo-calculator')), 400);
        }

        // Find the pendant mapping for this product.
        $mappings = NDV_Woo_Calculator_Config_Manager::get_mappings();
        $mapping = null;

        foreach ($mappings as $m) {
            if (absint($m['product_id'] ?? 0) === $product_id && 'pendant' === ($m['calculation_mode'] ?? '')) {
                $mapping = $m;
                break;
            }
        }

        if (!$mapping) {
            wp_send_json_error(array('message' => __('No pendant configuration found for this product.', 'ndv-woo-calculator')), 404);
        }

        // Build selections from POST data.
        $selections = array(
            'metal_key' => sanitize_text_field(wp_unslash($_POST['metal_key'] ?? '')),
            'stones' => array(),
            'chain_key' => sanitize_text_field(wp_unslash($_POST['chain_key'] ?? 'none')),
            'chain_length' => floatval($_POST['chain_length'] ?? 0),
            'metal_weight' => isset($_POST['metal_weight']) ? floatval($_POST['metal_weight']) : null,
        );

        // Parse stones array.
        $raw_stones = isset($_POST['stones']) ? wp_unslash($_POST['stones']) : array();
        if (is_array($raw_stones)) {
            foreach ($raw_stones as $stone) {
                $selections['stones'][] = array(
                    'stone_key' => sanitize_text_field($stone['stone_key'] ?? ''),
                    'quantity' => absint($stone['quantity'] ?? 0),
                );
            }
        }

        $result = NDV_Woo_Calculator_Pendant_Engine::calculate($mapping, $selections);

        wp_send_json_success(array(
            'price' => $result['price'],
            'formatted' => wc_price($result['price']),
            'breakdown' => $result['breakdown'],
        ));
    }

    /**
     * Extract pendant selections from form_data array.
     *
     * @since  1.1.0
     * @param  array $form_data The submitted form data.
     * @return array Pendant selections.
     */
    private function extract_pendant_selections($form_data)
    {
        $selections = array(
            'metal_key' => sanitize_text_field($form_data['pendant_metal'] ?? ''),
            'stones' => array(),
            'chain_key' => sanitize_text_field($form_data['pendant_chain'] ?? 'none'),
            'chain_length' => floatval($form_data['pendant_chain_length'] ?? 0),
            'metal_weight' => isset($form_data['pendant_metal_weight']) ? floatval($form_data['pendant_metal_weight']) : null,
        );

        // Stones come as pendant_stones array.
        if (isset($form_data['pendant_stones']) && is_array($form_data['pendant_stones'])) {
            foreach ($form_data['pendant_stones'] as $stone) {
                $selections['stones'][] = array(
                    'stone_key' => sanitize_text_field($stone['stone_key'] ?? ''),
                    'quantity' => absint($stone['quantity'] ?? 0),
                );
            }
        }

        return $selections;
    }
}
