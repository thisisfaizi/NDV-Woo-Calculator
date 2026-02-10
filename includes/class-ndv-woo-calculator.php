<?php
/**
 * Main plugin orchestrator class.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator
 *
 * Orchestrates all plugin components — instantiates feature classes and calls init().
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator
{

    /**
     * Admin instance.
     *
     * @since 1.0.0
     * @var NDV_Woo_Calculator_Admin
     */
    private $admin;

    /**
     * Public/frontend instance.
     *
     * @since 1.0.0
     * @var NDV_Woo_Calculator_Public
     */
    private $public;

    /**
     * AJAX handler instance.
     *
     * @since 1.0.0
     * @var NDV_Woo_Calculator_Ajax
     */
    private $ajax;

    /**
     * WooCommerce integration instance.
     *
     * @since 1.0.0
     * @var NDV_Woo_Calculator_WooCommerce
     */
    private $woocommerce;

    /**
     * I18n instance.
     *
     * @since 1.0.0
     * @var NDV_Woo_Calculator_I18n
     */
    private $i18n;

    /**
     * Run the plugin.
     *
     * Instantiates and initializes all feature classes.
     *
     * @since 1.0.0
     */
    public function run()
    {

        // Internationalization.
        $this->i18n = new NDV_Woo_Calculator_I18n();
        $this->i18n->init();

        // Admin panel.
        if (is_admin()) {
            $this->admin = new NDV_Woo_Calculator_Admin();
            $this->admin->init();
        }

        // Frontend.
        $this->public = new NDV_Woo_Calculator_Public();
        $this->public->init();

        // AJAX handler (runs on both admin and frontend contexts).
        $this->ajax = new NDV_Woo_Calculator_Ajax();
        $this->ajax->init();

        // WooCommerce hooks.
        $this->woocommerce = new NDV_Woo_Calculator_WooCommerce();
        $this->woocommerce->init();

        /**
         * Fires after all NDV Woo Calculator components are loaded.
         *
         * @since 1.0.0
         * @param NDV_Woo_Calculator $plugin Main plugin instance.
         */
        do_action('ndvwc_loaded', $this);
    }
}
