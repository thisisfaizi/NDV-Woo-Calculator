=== NDV Woo Calculator ===
Contributors: nowdigiverse
Tags: woocommerce, elementor, calculator, dynamic pricing, form
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Integrate Elementor Pro Forms with WooCommerce for dynamic product pricing based on form inputs.

== Description ==

NDV Woo Calculator connects Elementor Pro Forms to WooCommerce, allowing you to calculate dynamic product prices based on user form inputs and add them to the cart with a single click.

**Features:**

* Dynamic price calculation from Elementor form fields
* AJAX-powered add-to-cart with live price updates
* Multiple form-to-product mapping configurations
* All form selections saved as WooCommerce order metadata
* Clean admin display of order details (label → value format)
* Shortcode support: `[ndv_woo_add_to_cart form_id="123"]`
* Conditional field handling and hidden field clearing
* Fully configurable via admin panel — no code required

**Supported Field Types:**

* Text, Select, Radio, Checkbox, Number, Hidden, Calculated

== Installation ==

1. Upload the `ndv-woo-calculator` folder to `/wp-content/plugins/` or install through the WordPress plugins screen.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce and Elementor Pro are active.
4. Navigate to **NDV Woo Calculator** in the admin menu.
5. Configure Global Settings and create Form-to-Product Mappings.
6. Add the shortcode `[ndv_woo_add_to_cart form_id="YOUR_FORM_ID"]` to the page with your Elementor form, or enable auto-inject.

== Frequently Asked Questions ==

= Does this plugin require Elementor Pro? =

Yes. The plugin is designed to work with Elementor Pro Forms for form input integration.

= Can I use multiple forms for different products? =

Yes. The admin panel supports creating as many form-to-product mapping configurations as needed.

= How does the price override work? =

The calculated price from your Elementor form replaces the base product price in the WooCommerce cart using the `woocommerce_before_calculate_totals` hook.

= Where can I see the form data after an order? =

All mapped form fields are displayed in the WooCommerce order details (WP Admin → WooCommerce → Orders) with clean labels.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
