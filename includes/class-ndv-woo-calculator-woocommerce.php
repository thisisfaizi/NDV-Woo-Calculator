<?php
/**
 * WooCommerce integration hooks.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_WooCommerce
 *
 * Hooks into WooCommerce to override prices, preserve unique cart items,
 * display form data in cart/checkout, and save data to order meta.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_WooCommerce
{

    /**
     * Initialize WooCommerce hooks.
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Generate unique hash so each form submission is a separate cart item.
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_unique_hash'), 10, 3);

        // Override cart item price with calculated price.
        add_action('woocommerce_before_calculate_totals', array($this, 'override_cart_price'), 20, 1);

        // Display form data in cart and checkout.
        add_filter('woocommerce_get_item_data', array($this, 'display_cart_item_data'), 10, 2);

        // Save form data to order line item meta.
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_order_item_meta'), 10, 4);
    }

    /**
     * Add a unique hash to cart item data to ensure each form submission
     * creates a separate cart line item.
     *
     * @since  1.0.0
     * @param  array $cart_item_data Cart item data.
     * @param  int   $product_id     Product ID.
     * @param  int   $variation_id   Variation ID.
     * @return array
     */
    public function add_unique_hash($cart_item_data, $product_id, $variation_id)
    {
        if (isset($cart_item_data['ndvwc_calculated_price'])) {
            $cart_item_data['ndvwc_unique_key'] = md5(
                wp_json_encode($cart_item_data['ndvwc_form_data'] ?? array())
                . $cart_item_data['ndvwc_calculated_price']
                . microtime()
            );
        }
        return $cart_item_data;
    }

    /**
     * Override the product price in the cart with the calculated price.
     *
     * @since 1.0.0
     * @param \WC_Cart $cart WooCommerce cart object.
     */
    public function override_cart_price($cart)
    {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        // Prevent running multiple times.
        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        foreach ($cart->get_cart() as $cart_item) {
            if (isset($cart_item['ndvwc_calculated_price'])) {
                $price = floatval($cart_item['ndvwc_calculated_price']);
                if ($price > 0) {
                    $cart_item['data']->set_price($price);
                }
            }
        }
    }

    /**
     * Display form selections in the cart and checkout pages.
     *
     * @since  1.0.0
     * @param  array $item_data Existing item data.
     * @param  array $cart_item Cart item.
     * @return array
     */
    public function display_cart_item_data($item_data, $cart_item)
    {
        if (!isset($cart_item['ndvwc_form_data']) || !isset($cart_item['ndvwc_form_id'])) {
            return $item_data;
        }

        $form_id = sanitize_text_field($cart_item['ndvwc_form_id']);
        $form_data = $cart_item['ndvwc_form_data'];
        $mapping = NDV_Woo_Calculator_Config_Manager::get_mapping_by_form_id($form_id);

        if (!$mapping || empty($mapping['field_mappings'])) {
            return $item_data;
        }

        // Build label lookup from mapping config.
        $label_map = array();
        foreach ($mapping['field_mappings'] as $field_mapping) {
            if (!empty($field_mapping['field_id']) && !empty($field_mapping['label'])) {
                $label_map[$field_mapping['field_id']] = $field_mapping['label'];
            }
        }

        // Add each mapped field to cart display.
        foreach ($form_data as $field_id => $value) {
            if (isset($label_map[$field_id]) && '' !== $value) {
                $display_value = is_array($value) ? implode(', ', $value) : $value;
                $item_data[] = array(
                    'key' => esc_html($label_map[$field_id]),
                    'value' => esc_html($display_value),
                );
            }
        }

        return $item_data;
    }

    /**
     * Save form data to order line item meta during checkout.
     *
     * @since 1.0.0
     * @param \WC_Order_Item_Product $item          Order item.
     * @param string                 $cart_item_key  Cart item key.
     * @param array                  $values         Cart item values.
     * @param \WC_Order              $order          Order object.
     */
    public function save_order_item_meta($item, $cart_item_key, $values, $order)
    {
        if (!isset($values['ndvwc_form_data']) || !isset($values['ndvwc_form_id'])) {
            return;
        }

        $form_id = sanitize_text_field($values['ndvwc_form_id']);
        $form_data = $values['ndvwc_form_data'];
        $mapping = NDV_Woo_Calculator_Config_Manager::get_mapping_by_form_id($form_id);

        // Save calculated price.
        if (isset($values['ndvwc_calculated_price'])) {
            $item->add_meta_data(
                __('Calculated Price', 'ndv-woo-calculator'),
                wc_price(floatval($values['ndvwc_calculated_price'])),
                true
            );
        }

        if (!$mapping || empty($mapping['field_mappings'])) {
            return;
        }

        // Build label lookup.
        $label_map = array();
        foreach ($mapping['field_mappings'] as $field_mapping) {
            if (!empty($field_mapping['field_id']) && !empty($field_mapping['label'])) {
                $label_map[$field_mapping['field_id']] = $field_mapping['label'];
            }
        }

        // Save each mapped field with its clean label.
        foreach ($form_data as $field_id => $value) {
            if (isset($label_map[$field_id]) && '' !== $value) {
                $display_value = is_array($value) ? implode(', ', $value) : sanitize_text_field($value);
                $item->add_meta_data(
                    sanitize_text_field($label_map[$field_id]),
                    $display_value,
                    true
                );
            }
        }

        /**
         * Fires after calculator form data is saved to the order item.
         *
         * @since 1.0.0
         * @param \WC_Order_Item_Product $item      Order item.
         * @param array                  $form_data Form selections.
         * @param array                  $mapping   Mapping configuration.
         * @param \WC_Order              $order     Order object.
         */
        do_action('ndvwc_after_save_order_item_meta', $item, $form_data, $mapping, $order);
    }
}
