<?php
/**
 * Configuration manager for plugin settings and form mappings.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Config_Manager
 *
 * Provides static helpers for reading plugin settings and form-to-product mappings.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Config_Manager
{

    /**
     * Default settings.
     *
     * @since 1.0.0
     * @var array
     */
    private static $defaults = array(
        'enabled' => 'yes',
        'debug_mode' => 'no',
        'submission_type' => 'ajax',
        'clear_hidden_fields' => 'no',
    );

    /**
     * Get all plugin settings merged with defaults.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_settings()
    {
        $settings = get_option('ndvwc_settings', array());
        return wp_parse_args($settings, self::$defaults);
    }

    /**
     * Get a single setting value.
     *
     * @since  1.0.0
     * @param  string $key     Setting key.
     * @param  mixed  $default Optional fallback value.
     * @return mixed
     */
    public static function get_setting($key, $default = null)
    {
        $settings = self::get_settings();
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        return null !== $default ? $default : (self::$defaults[$key] ?? '');
    }

    /**
     * Check if the plugin is enabled.
     *
     * @since  1.0.0
     * @return bool
     */
    public static function is_enabled()
    {
        return 'yes' === self::get_setting('enabled');
    }

    /**
     * Check if debug mode is on.
     *
     * @since  1.0.0
     * @return bool
     */
    public static function is_debug()
    {
        return 'yes' === self::get_setting('debug_mode');
    }

    /**
     * Check if hidden fields should be cleared.
     *
     * @since  1.0.0
     * @return bool
     */
    public static function should_clear_hidden_fields()
    {
        return 'yes' === self::get_setting('clear_hidden_fields');
    }

    /**
     * Get all form-to-product mapping configurations.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_mappings()
    {
        $mappings = get_option('ndvwc_form_mappings', array());
        return is_array($mappings) ? $mappings : array();
    }

    /**
     * Get a mapping configuration by Elementor form ID.
     *
     * @since  1.0.0
     * @param  string $form_id Elementor form ID.
     * @return array|false Mapping config or false if not found.
     */
    public static function get_mapping_by_form_id($form_id)
    {
        $mappings = self::get_mappings();
        foreach ($mappings as $mapping) {
            if (isset($mapping['form_id']) && $mapping['form_id'] === $form_id) {
                return $mapping;
            }
        }
        return false;
    }

    /**
     * Get all global rates (metals, stones, chains).
     *
     * @since  1.1.0
     * @return array { metals: array, stones: array, chains: array }
     */
    public static function get_global_rates()
    {
        $defaults = array(
            'metals' => array(),
            'stones' => array(),
            'chains' => array(),
        );
        $rates = get_option('ndvwc_global_rates', $defaults);
        return wp_parse_args($rates, $defaults);
    }

    /**
     * Get metal rates.
     *
     * @since  1.1.0
     * @return array
     */
    public static function get_metal_rates()
    {
        $rates = self::get_global_rates();
        return $rates['metals'];
    }

    /**
     * Get stone rates.
     *
     * @since  1.1.0
     * @return array
     */
    public static function get_stone_rates()
    {
        $rates = self::get_global_rates();
        return $rates['stones'];
    }

    /**
     * Get chain rates.
     *
     * @since  1.1.0
     * @return array
     */
    public static function get_chain_rates()
    {
        $rates = self::get_global_rates();
        return $rates['chains'];
    }

    /**
     * Get WooCommerce products for the admin dropdown.
     *
     * Uses transient caching to avoid expensive queries on every page load.
     *
     * @since  1.0.0
     * @return array Array of product ID => title pairs.
     */
    public static function get_products_list()
    {
        $products = get_transient('ndvwc_products_cache');

        if (false === $products) {
            $products = array();

            $query = new \WP_Query(
                array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                    'orderby' => 'title',
                    'order' => 'ASC',
                )
            );

            if ($query->have_posts()) {
                foreach ($query->posts as $product_id) {
                    $products[$product_id] = get_the_title($product_id);
                }
            }

            set_transient('ndvwc_products_cache', $products, HOUR_IN_SECONDS);
        }

        return $products;
    }
}
