# NDV Woo Calculator

> Integrates Elementor Pro Forms with WooCommerce to enable dynamic product pricing based on form inputs, including a specialized Jewelry Pendant Configurator.

**Version:** 1.2.0  
**Requires:** WordPress 6.0+, WooCommerce 7.0+, PHP 8.0+  
**License:** GPLv2 or later  

## Description

NDV Woo Calculator connects Elementor Pro Forms to WooCommerce, allowing you to calculate dynamic product prices based on user form inputs, map form fields to order meta, and sell custom products.

### Key Features

*   **Pendant Configurator:** Create custom jewelry products (pendants) with dynamic pricing based on:
    *   **Metal Weight:** Configure base weight per product, or optionally allow **User Input Weight**.
    *   **Global Rates:** Manage current market rates for Metals (Gold 18k, Silver, etc.), Stones, and Chains in a centralized admin tab.
    *   **Components:** Customers select Metal, Stones (qty), and Chains (type & length).
    *   **Labor & Markup:** Set fixed labor costs and percentage/fixed markups per product.
    *   **Live Preview:** Real-time price updates on the frontend as customers configure their pendant.
*   **Dynamic Elementor Forms:** Calculate any product price from Elementor form fields using a "Calculated" field.
*   **WooCommerce Integration:** Adds the custom product to the cart with the calculated price and saves all configuration details (Weight, Stone details, Chain info) as order metadata.
*   **Admin Interface:** Easy-to-use mapping interface to link Forms/Products and configure settings.

## Installation

1.  Upload the plugin files to the `/wp-content/plugins/ndv-woo-calculator` directory, or install the plugin through the WordPress plugins screen.
2.  Activate the plugin through the 'Plugins' screen in WordPress.
3.  Ensure **WooCommerce** is active. (Elementor Pro is optional; demanded only if you use the generic form mapping mode).
4.  Go to **NDV Woo Calculator** in the WordPress admin menu.

## Usage

### 1. Global Rates (For Jewelry Mode)
Go to **NDV Woo Calculator -> Global Rates**.
*   **Metals:** Add rates for metals (e.g., Gold 18k: $50/gram).
*   **Stones:** Add rates for stones (e.g., Diamond: $100/unit).
*   **Chains:** Add rates for chains (e.g., Gold Chain: $2/cm).

### 2. Configure a Product (Pendant Mode)
1.  Create a standard WooCommerce Product (ensure it has a price, though it will be overridden).
2.  Go to **NDV Woo Calculator -> Mappings**.
3.  Click "Add Mapping".
4.  **Product:** Select your WooCommerce product.
5.  **Calculation Mode:** Select "Pendant Configurator".
6.  **Pendant Settings:**
    *   **Metal Weight:** Enter the base weight (e.g., 5g).
    *   **Allow User Weight Input:** Check this to let customers enter a custom weight.
    *   **Labor Cost:** Enter fixed labor cost.
    *   **Markup:** Set markup value and type (Fixed or Percent).
    *   **Available Components:** Check which Metals, Stones, and Chains are available for this pendant.
7.  Save Mappings.

### 3. Display the Configurator
Use the shortcode on any page (or in the Product description):
```
[ndv_pendant_configurator product_id="123"]
```
Replace `123` with your actual Product ID.

### 4. Generic Elementor Form Mode
1.  Create an Elementor Form with a "Calculated" field for the price.
2.  Go to **NDV Woo Calculator -> Mappings**.
3.  **Calculation Mode:** "Elementor Form".
4.  **Form ID:** Enter the Form ID from Elementor.
5.  **Price Field ID:** Enter the ID of the calculated field.
6.  **Field Mapping:** Map other form fields to label names for the Order metadata.
7.  Use the add-to-cart button shortcode: `[ndv_woo_add_to_cart form_id="your_form_id"]`.

## Changelog

### 1.2.0
*   **New:** User Input Weight feature for Pendant Configurator.
*   **Enhancement:** Updated pricing engine logic to prioritize user weight if enabled.
*   **UI:** Added weight input field to frontend configurator.

### 1.1.0
*   **New:** Pendant Configurator module.
*   **New:** Global Rates management.
*   **New:** `[ndv_pendant_configurator]` shortcode.

### 1.0.0
*   Initial release with Elementor Form support.
