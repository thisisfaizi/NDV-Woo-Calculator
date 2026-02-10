<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package NDV_Woo_Calculator
 * @since   1.0.0
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options.
delete_option('ndvwc_settings');
delete_option('ndvwc_form_mappings');
delete_option('ndvwc_global_rates');
delete_option('ndvwc_db_version');

// Clean up any transients.
delete_transient('ndvwc_products_cache');
