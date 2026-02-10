<?php
/**
 * Pendant pricing calculation engine.
 *
 * Calculates dynamic pendant prices using the formula:
 *   Total = (MetalWeight × MetalRate) + Σ(StoneCost × Qty) + (ChainRate × Length) + Labor + Markup
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Pendant_Engine
 *
 * Dedicated engine for pendant pricing. Uses global rates from
 * ndvwc_global_rates and per-product pendant configuration from
 * the mapping entry to compute real-time prices.
 *
 * @since 1.1.0
 */
class NDV_Woo_Calculator_Pendant_Engine
{

    /**
     * Debug log for last calculation.
     *
     * @since 1.1.0
     * @var array
     */
    private static $debug_log = array();

    /**
     * Calculate the total pendant price.
     *
     * @since  1.1.0
     * @param  array $mapping    The mapping configuration (pendant fields).
     * @param  array $selections Customer selections:
     *   - metal_key      (string) Key of the selected metal.
     *   - stones         (array)  Array of { stone_key, quantity }.
     *   - chain_key      (string) Key of the selected chain (or 'none').
     *   - chain_length   (float)  Selected chain length in cm.
     * @return array {
     *   price:     (float) Total price,
     *   breakdown: (array) Itemised breakdown,
     * }
     */
    public static function calculate($mapping, $selections)
    {
        self::$debug_log = array();

        $rates = NDV_Woo_Calculator_Config_Manager::get_global_rates();

        // Index rates by key for fast lookup.
        $metal_rates = self::index_by_key($rates['metals'] ?? array());
        $stone_rates = self::index_by_key($rates['stones'] ?? array());
        $chain_rates = self::index_by_key($rates['chains'] ?? array());

        // --- 1. Metal cost ---------------------------------------------------
        $metal_weight = floatval($mapping['pendant_metal_weight'] ?? 0);

        // Override with user input if enabled.
        if (!empty($mapping['pendant_user_weight_enabled']) && isset($selections['metal_weight']) && is_numeric($selections['metal_weight'])) {
            $user_weight = floatval($selections['metal_weight']);
            if ($user_weight > 0) {
                $metal_weight = $user_weight;
            }
        }

        $metal_key = sanitize_text_field($selections['metal_key'] ?? '');
        $metal_rate = 0;
        $metal_name = '';

        if (isset($metal_rates[$metal_key])) {
            $metal_rate = floatval($metal_rates[$metal_key]['price_per_gram'] ?? 0);
            $metal_name = $metal_rates[$metal_key]['name'] ?? $metal_key;
        }

        $metal_cost = $metal_weight * $metal_rate;
        self::log("Metal: {$metal_name} ({$metal_key}), weight={$metal_weight}g, rate={$metal_rate}/g, cost={$metal_cost}");

        // --- 2. Stone cost ---------------------------------------------------
        $stone_cost = 0;
        $stone_details = array();
        $raw_stones = $selections['stones'] ?? array();

        if (is_array($raw_stones)) {
            foreach ($raw_stones as $stone) {
                $s_key = sanitize_text_field($stone['stone_key'] ?? '');
                $s_qty = max(0, absint($stone['quantity'] ?? 0));

                if (empty($s_key) || 0 === $s_qty) {
                    continue;
                }

                $s_price = 0;
                $s_name = $s_key;
                if (isset($stone_rates[$s_key])) {
                    $s_price = floatval($stone_rates[$s_key]['price_per_unit'] ?? 0);
                    $s_name = $stone_rates[$s_key]['name'] ?? $s_key;
                }

                $line_cost = $s_price * $s_qty;
                $stone_cost += $line_cost;

                $stone_details[] = array(
                    'name' => $s_name,
                    'quantity' => $s_qty,
                    'unit' => $s_price,
                    'total' => $line_cost,
                );

                self::log("Stone: {$s_name} × {$s_qty} @ {$s_price} = {$line_cost}");
            }
        }

        // --- 3. Chain cost ---------------------------------------------------
        $chain_key = sanitize_text_field($selections['chain_key'] ?? 'none');
        $chain_length = floatval($selections['chain_length'] ?? 0);
        $chain_cost = 0;
        $chain_name = '';

        if ('none' !== $chain_key && !empty($chain_key) && $chain_length > 0) {
            if (isset($chain_rates[$chain_key])) {
                $rate_per_cm = floatval($chain_rates[$chain_key]['rate_per_cm'] ?? 0);
                $chain_name = $chain_rates[$chain_key]['name'] ?? $chain_key;
                $chain_cost = $rate_per_cm * $chain_length;
            }
        }

        self::log("Chain: {$chain_name} ({$chain_key}), length={$chain_length}cm, cost={$chain_cost}");

        // --- 4. Labor --------------------------------------------------------
        $labor = floatval($mapping['pendant_labor'] ?? 0);
        self::log("Labor: {$labor}");

        // --- 5. Markup -------------------------------------------------------
        $markup_value = floatval($mapping['pendant_markup'] ?? 0);
        $markup_type = sanitize_text_field($mapping['pendant_markup_type'] ?? 'fixed');

        $subtotal = $metal_cost + $stone_cost + $chain_cost + $labor;

        if ('percent' === $markup_type) {
            $markup = $subtotal * $markup_value / 100;
        } else {
            $markup = $markup_value;
        }

        self::log("Subtotal: {$subtotal}, markup({$markup_type}): {$markup}");

        // --- Total -----------------------------------------------------------
        $total = $subtotal + $markup;
        $total = max(0, round($total, 2));

        self::log("Total price: {$total}");

        // Write to WP debug log if debug mode is on.
        if (NDV_Woo_Calculator_Config_Manager::is_debug() && defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[NDVWC Pendant Engine] ' . implode(' | ', self::$debug_log));
        }

        return array(
            'price' => $total,
            'breakdown' => array(
                'metal' => round($metal_cost, 2),
                'metal_name' => $metal_name,
                'stones' => round($stone_cost, 2),
                'stone_details' => $stone_details,
                'chain' => round($chain_cost, 2),
                'chain_name' => $chain_name,
                'labor' => round($labor, 2),
                'markup' => round($markup, 2),
            ),
        );
    }

    /**
     * Get the debug log from the last calculation.
     *
     * @since  1.1.0
     * @return array
     */
    public static function get_debug_log()
    {
        return self::$debug_log;
    }

    /**
     * Build an associative array indexed by 'key' from a list of rate entries.
     *
     * @since  1.1.0
     * @param  array $items List of items, each with a 'key' property.
     * @return array Keyed array.
     */
    private static function index_by_key($items)
    {
        $indexed = array();
        foreach ($items as $item) {
            if (!empty($item['key'])) {
                $indexed[$item['key']] = $item;
            }
        }
        return $indexed;
    }

    /**
     * Add a message to the debug log.
     *
     * @since  1.1.0
     * @param  string $message Debug message.
     */
    private static function log($message)
    {
        self::$debug_log[] = $message;
    }
}
