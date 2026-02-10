# NDV Woo Calculator - User Guide

## Overview

The **NDV Woo Calculator** plugin now includes a powerful **Pendant Configurator** feature. This allows you to set global pricing for materials (metals, stones, chains) and offer customizable jewelry products where customers can select components and see a live price estimate before adding the item to their cart.

---

## 1. Setting Up Global Rates

Before creating a pendant product, you must define the base rates for your materials. These rates are used across all pendant products to ensure consistent pricing.

1.  Navigate to **Settings > NDV Woo Calculator** in your WordPress dashboard.
2.  Click on the **Global Rates** tab.

### Metal Rates
- **Key**: A unique identifier (e.g., `18k_gold`, `silver_925`). Lowercase, no spaces (use underscores).
- **Name**: The display name shown to customers (e.g., "18k Gold", "Sterling Silver").
- **Price per Gram**: The cost of the metal per gram.

### Stone Rates
- **Key**: Unique identifier (e.g., `diamond_rnd`, `ruby_ovl`).
- **Name**: Display name (e.g., "Round Diamond", "Oval Ruby").
- **Price per Unit**: The cost for a single stone.

### Chain Rates
- **Key**: Unique identifier (e.g., `gold_chain_slim`, `silver_chain_chunky`).
- **Name**: Display name.
- **Rate per cm**: The cost of the chain per centimeter of length.
- **Material**: (Optional) Description of the chain material.

**Click "Save Global Rates"** at the bottom of the page after adding or updating any rates.

---

## 2. Configuring a Pendant Product

Once your global rates are set, you can link a WooCommerce product to the calculator.

1.  Create a standard **Simple Product** in WooCommerce (e.g., "Custom Pendant"). Set a base price (e.g., 0 or a base fee) if desired, but the calculator will largely determine the final price.
2.  Go to **Settings > NDV Woo Calculator > Form Mappings**.
3.  Click **Add New Mapping** or edit an existing one.
4.  **Form ID**: Enter a unique identifier for this configuration (e.g., `pendant_config_01`).
5.  **Product**: Select your WooCommerce product from the dropdown.
6.  **Calculation Mode**: Select **Pendant Configurator**.

### Pendant Specific Settings
Once "Pendant Configurator" is selected, new fields will appear:

- **Pendant Metal Weight**: Enter the weight of the metal component in grams (e.g., `3.5`).
- **Labor Cost**: Enter a fixed labor cost for producing this item (e.g., `50`).
- **Markup**: Enter a markup value.
- **Markup Type**: Choose **Fixed** (adds the value directly) or **Percentage** (adds a % of the material + labor cost).
- **Available Metals**: Check the boxes for metals you want to offer for this specific pendant.
- **Available Stones**: Check the boxes for stones available for this pendant.
- **Max Stones**: Set the maximum number of stones a customer can add (e.g., `5`).
- **Available Chains**: Check the boxes for available chains.
- **Chain Lengths**: Enter the available lengths in cm, separated by commas (e.g., `40, 45, 50, 60`).

**Click "Save Changes"**.

---

## 3. Displaying the Configurator

To show the configurator on a page:

1.  Edit the page where you want the configurator to appear.
2.  Add the following shortcode:

    ```
    [ndv_pendant_configurator product_id="123"]
    ```

    *Replace `123` with the actual ID of your WooCommerce product.*

3.  **Update/Publish** the page.

Your customers will now see a dropdown for Metal, a section to add Stones, and a Chain selector. The price will update automatically as they make choices!

---

## Troubleshooting

- **Price not updating?** Ensure your Global Rates are saved and that you have selected "Available" metals/stones in the mapping.
- **"Form not found"?** For the pendant configurator, you don't need an Elementor form. Just use the `[ndv_pendant_configurator]` shortcode directly.
- **Styles missing?** The plugin automatically enqueues styles. If it looks unstyled, check if your theme is overriding CSS or if the plugin is active.
