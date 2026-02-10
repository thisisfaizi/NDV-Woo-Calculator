<?php
/**
 * Fired during plugin activation.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Activator
 *
 * Sets default options on plugin activation.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Activator
{

    /**
     * Run activation tasks.
     *
     * @since 1.0.0
     */
    public static function activate()
    {

        // Set default settings if they don't exist.
        if (false === get_option('ndvwc_settings')) {
            $defaults = array(
                'enabled' => 'yes',
                'debug_mode' => 'no',
                'submission_type' => 'ajax',
                'clear_hidden_fields' => 'no',
            );
            add_option('ndvwc_settings', $defaults);
        }

        // Initialize empty mappings if they don't exist.
        if (false === get_option('ndvwc_form_mappings')) {
            add_option('ndvwc_form_mappings', array());
        }

        // Initialize empty global rates if they don't exist.
        if (false === get_option('ndvwc_global_rates')) {
            $default_rates = array(
                'metals' => array(),
                'stones' => array(),
                'chains' => array(),
            );
            add_option('ndvwc_global_rates', $default_rates);
        }

        // Store db version.
        add_option('ndvwc_db_version', '1.0.0');
    }
}
