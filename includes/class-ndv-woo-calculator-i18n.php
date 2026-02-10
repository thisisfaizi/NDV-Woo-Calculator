<?php
/**
 * Define the internationalization functionality.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_I18n
 *
 * Loads and defines the internationalization files for this plugin.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_I18n
{

    /**
     * Initialize internationalization.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
    }

    /**
     * Load the plugin text domain.
     *
     * @since 1.0.0
     */
    public function load_textdomain()
    {
        load_plugin_textdomain(
            'ndv-woo-calculator',
            false,
            dirname(NDVWC_PLUGIN_BASENAME) . '/languages/'
        );
    }
}
