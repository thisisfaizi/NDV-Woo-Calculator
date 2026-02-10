=== NDV Woo Calculator ===
Contributors: nowdigiverse
Tags: woocommerce, elementor, calculator, dynamic pricing, form, pendant, jewelry
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate Elementor Pro Forms with WooCommerce for dynamic product pricing, including a specialized Jewelry Pendant Configurator.

== Description ==

NDV Woo Calculator connects Elementor Pro Forms to WooCommerce, allowing you to calculate dynamic product prices based on user form inputs. 

**New in 1.2.0: User Weight Input!**
You can now optionally allow customers to input the metal weight manually in the Pendant Configurator, with the backend falling back to your configured weight if they don't.

**Core Features:**

*   **Pendant Configurator:** Create custom jewelry products with dynamic metal, stone, and chain pricing.
*   **User Weight Input:** (New) Optional customer input for metal weight.
*   **Global Material Rates:** Set centralized prices for gold, silver, diamonds, and chains.
*   **Dynamic Elementor Forms:** Calculate any product price from form fields.
*   **AJAX-powered:** Live price updates on the frontend.
*   **WooCommerce Integration:** Adds all custom options as order metadata.
*   **Shortcode Support:**
    *   `[ndv_woo_add_to_cart form_id="123"]` for Elementor forms.
    *   `[ndv_pendant_configurator product_id="123"]` for jewelry configurators.

== Installation ==

1.  Upload the `ndv-woo-calculator` folder to `/wp-content/plugins/` or install through the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Ensure WooCommerce is active (Elementor Pro is required only for form-based pricing).
4.  Navigate to **NDV Woo Calculator** in the admin menu.
5.  **For Jewelry:** Go to the "Global Rates" tab to set metal/stone prices, then create a Mapping with "Pendant Configurator" mode.
6.  **For Forms:** Create a Mapping with "Elementor Form" mode and use the form shortcode.

== Frequently Asked Questions ==

= Does this plugin require Elementor Pro? =

Yes, for the standard form integration. The **Pendant Configurator** feature works independently without Elementor Pro.

= How does the Pendant Configurator calculate price? =

It uses the formula: `(Metal Weight * Rate) + (Stone Cost * Qty) + (Chain Rate * Length) + Labor + Markup`. All rates are managed globally.

= Can I allow customers to enter their own weight? =

Yes! As of version 1.2.0, you can enable "Allow User Weight Input" in the Pendant Configurator mapping settings.

= Can I use different markups per product? =

Yes, each product mapping allows you to set a specific markup (fixed or percentage) and labor cost.

== Changelog ==

= 1.2.0 =
*   Added "Allow User Weight Input" setting for Pendant Configurator.
*   Updated calculating engine to support user-defined weight.
*   Updated frontend UI to show weight input when enabled.

= 1.1.0 =
*   Added Pendant Configurator module.
*   Added Global Rates management for Metals, Stones, and Chains.
*   Added new shortcode `[ndv_pendant_configurator]`.
*   Added support for multiple calculation modes in mappings.
*   Improved admin UI with tabbed navigation.

= 1.0.0 =
*   Initial release.
