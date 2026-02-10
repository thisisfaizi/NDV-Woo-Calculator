<?php
/**
 * Plugin Name:       NDV Woo Calculator
 * Plugin URI:        https://nowdigiverse.com/ndv-woo-calculator
 * Description:       Integrates Elementor Pro Forms with WooCommerce to enable dynamic product pricing based on form inputs.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Nowdigiverse
 * Author URI:        https://nowdigiverse.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ndv-woo-calculator
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:      9.0
 *
 * @package NDV_Woo_Calculator
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'NDVWC_VERSION', '1.0.0' );
define( 'NDVWC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'NDVWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NDVWC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Include required files.
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator-activator.php';
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator-deactivator.php';
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator-i18n.php';
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator-config-manager.php';
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator-ajax.php';
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator-woocommerce.php';
require_once NDVWC_PLUGIN_DIR . 'admin/class-ndv-woo-calculator-admin.php';
require_once NDVWC_PLUGIN_DIR . 'public/class-ndv-woo-calculator-public.php';
require_once NDVWC_PLUGIN_DIR . 'includes/class-ndv-woo-calculator.php';

// Activation and deactivation hooks.
register_activation_hook( __FILE__, array( 'NDV_Woo_Calculator_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'NDV_Woo_Calculator_Deactivator', 'deactivate' ) );

/**
 * Begin plugin execution.
 *
 * @since 1.0.0
 */
function ndvwc_run() {

	// Check for WooCommerce dependency.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ndvwc_woocommerce_missing_notice' );
		return;
	}

	$plugin = new NDV_Woo_Calculator();
	$plugin->run();
}
add_action( 'plugins_loaded', 'ndvwc_run' );

/**
 * Admin notice when WooCommerce is not active.
 *
 * @since 1.0.0
 */
function ndvwc_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error">
		<p><?php esc_html_e( 'NDV Woo Calculator requires WooCommerce to be installed and activated.', 'ndv-woo-calculator' ); ?></p>
	</div>
	<?php
}
