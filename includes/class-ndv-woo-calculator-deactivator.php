<?php
/**
 * Fired during plugin deactivation.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Deactivator
 *
 * Handles cleanup on plugin deactivation.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Deactivator
{

    /**
     * Run deactivation tasks.
     *
     * @since 1.0.0
     */
    public static function deactivate()
    {
        // Clean up transients.
        delete_transient('ndvwc_products_cache');
    }
}
