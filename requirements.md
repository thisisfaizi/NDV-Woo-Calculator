# NDV Woo Calculator

- **Author:** Nowdigiverse
- **Website:** <https://nowdigiverse.com>

---

## Project Overview

NDV Woo Calculator is a reusable WordPress plugin that integrates **Elementor Pro Forms** with **WooCommerce** to enable dynamic product pricing based on form inputs.

The plugin must be **generic**, **production-ready**, **scalable**, and usable by other developers or store owners without requiring custom PHP code per project.

### Branding

| Key        | Value                        |
|------------|------------------------------|
| Plugin Name | NDV Woo Calculator          |
| Author      | Nowdigiverse                |
| Author URL  | https://nowdigiverse.com    |

No additional branding.

---

## Core Purpose

Allow users to:

1. Calculate dynamic product prices using Elementor forms.
2. Add WooCommerce products to cart with the calculated price.
3. Save all selected form data into WooCommerce order meta.
4. Override product base price with the calculated value.
5. Configure everything via admin panel (no hardcoding).

---

## Functional Requirements

### 1. Admin Panel

Create a **top-level WP Admin menu** named `NDV Woo Calculator`.

#### A. Global Settings

- Enable / Disable plugin
- Enable Debug Mode
- Choose submission type:
  - AJAX
  - Standard
- Option to clear hidden fields (Yes / No)

#### B. Form-to-Product Mapping System

- Admin must be able to create **multiple configurations**.
- Each configuration includes:
  - **Elementor Form ID**
  - **WooCommerce Product ID** (dropdown)
  - **Price Field ID** (calculated field)
  - **Fields to Save** (Mapping System)

#### Field Mapping Structure

For each form, define mappings with:

| Form Field ID  | Label in Order      |
|----------------|---------------------|
| `metal_type`   | Metal               |
| `stone_count`  | Number of Stones    |
| `chain_option` | Chain               |

Must support multiple configurations stored in the database.

---

## Frontend Requirements

### 2. Smart Add to Cart Button

Two options supported:

#### Option A (Preferred)

Automatically inject button after the selected Elementor form.

#### Option B

Shortcode:

```
[ndv_woo_add_to_cart form_id="123"]
```

#### Button Behavior

- Monitors form field changes.
- Detects calculated price field.
- Dynamically updates button label (e.g., `Add to Cart – £120`).
- Collects all mapped form data.
- Resets hidden conditional fields (if enabled).
- Sends AJAX request to server.

---

## AJAX Add-to-Cart System

On click, send:

- `product_id`
- `calculated_price`
- `form_data` (JSON)

### Server-Side Validation

1. Verify nonce.
2. Validate product ID.
3. Validate price is numeric.
4. Validate form configuration exists.

### Cart Addition

Add product using:

```php
WC()->cart->add_to_cart()
```

Attach:

- Custom price
- Form selections

---

## WooCommerce Integration

### 1. Override Cart Price

- **Hook:** `woocommerce_before_calculate_totals`
- Replace original product price with calculated price.

### 2. Preserve Unique Cart Items

- **Hook:** `woocommerce_add_cart_item_data`
- Generate unique hash based on form data.

### 3. Save Form Data to Order

- **Hook:** `woocommerce_checkout_create_order_line_item`
- Store:
  - Calculated price
  - All mapped fields
- Display using clean labels.

### 4. Admin Order Display

- Show `label → value` format.
- No raw JSON.
- Clean formatted output.

**Example:**

```
Metal: Gold
Birthstones: 3
Chain: Yes
```

---

## Smart Form Handling

Plugin must support:

- Conditional fields
- Hidden field clearing
- Field types:
  - Text
  - Select
  - Radio
  - Checkbox
  - Number
  - Hidden
  - Calculated fields

---

## Security Requirements

- Nonce verification
- Sanitization & escaping
- Capability checks
- Server-side validation
- Prevent price manipulation
- Optional price re-validation hook support

---

## Technical Architecture

Must be **OOP-based** and **namespaced**.

### File Structure

```
ndv-woo-calculator/
├── ndv-woo-calculator.php
├── includes/
│   ├── class-admin.php
│   ├── class-ajax.php
│   ├── class-frontend.php
│   ├── class-woocommerce-hooks.php
│   └── class-config-manager.php
├── assets/
│   ├── js/
│   └── css/
```

### Development Standards

- WordPress Coding Standards
- Use Settings API
- Proper enqueue system
- No unnecessary inline JS
- Fully translatable
- PHP 8+ compatible

---

## Compatibility

Must support:

- Elementor Pro Forms
- WooCommerce (latest stable)
- WordPress (latest stable)
- PHP 8+

---

## Future Expansion Ready (Phase 2)

Architecture must allow:

- Forminator support
- Gravity Forms support
- Multi-step forms
- Price breakdown display
- REST API support
- Stripe integration
- Webhook triggers

---

## Documentation Requirements

Include:

- Inline code comments
- `README.md` containing:
  - Installation steps
  - Setup instructions
  - Configuration guide
  - Example workflow
  - Troubleshooting section
  - Available hooks & filters

---

## Final Expectations

The plugin must:

1. Be installable as ZIP.
2. Work without manual coding.
3. Be reusable across projects.
4. Support multiple form configurations.
5. Contain no hardcoded IDs.
6. Be scalable.
7. Be clean, optimized, and production-ready.