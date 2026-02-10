# NDV Woo Calculator — Testing Guide

> **For testers with no prior knowledge of this plugin.**
> This guide walks you through everything from setup to testing every feature.

---

## What Does This Plugin Do?

NDV Woo Calculator connects **Elementor Pro Forms** (a form builder) to **WooCommerce** (an e-commerce plugin) so that:

1. A user fills out a form on the website (e.g., choosing metal type, number of stones, chain option).
2. The form calculates a **dynamic price** based on their selections.
3. The user clicks an **"Add to Cart"** button that sends the product + calculated price to the WooCommerce cart.
4. All the form selections (e.g., "Metal: Gold", "Stones: 3") are saved in the WooCommerce order for the shop owner to see.

---

## Prerequisites (What You Need Before Testing)

Make sure you have the following installed and active on your WordPress site:

| Requirement | How to Check |
|---|---|
| **WordPress** (6.0+) | WP Admin → Dashboard → "At a Glance" shows WP version |
| **WooCommerce** (7.0+) | WP Admin → Plugins → WooCommerce should be "Active" |
| **Elementor Pro** | WP Admin → Plugins → Elementor & Elementor Pro should be "Active" |
| **At least 1 WooCommerce Product** | WP Admin → Products → Should have at least one published product |

> ⚠️ **If WooCommerce is NOT active**, the plugin will show an error notice and won't work. That's expected behavior.

---

## Step 1: Install & Activate the Plugin

1. Go to **WP Admin → Plugins → Add New → Upload Plugin**.
2. Upload the `NDV Woo Calculator` zip file (or it may already be in your plugins folder).
3. Click **Activate**.

### ✅ What to Verify
- [ ] No PHP errors or white screen after activation.
- [ ] A new menu item **"NDV Calculator"** (with a calculator icon) appears in the left sidebar of WP Admin.
- [ ] If WooCommerce is NOT active → a red error notice appears saying *"NDV Woo Calculator requires WooCommerce to be installed and activated."*

---

## Step 2: Test the Global Settings Page

1. Go to **WP Admin → NDV Calculator**.
2. You should see a page with **two tabs**: "Global Settings" and "Form Mappings".
3. Click the **"Global Settings"** tab (it should be active by default).

### ✅ What to Verify

- [ ] Page loads without errors.
- [ ] You see 4 settings:

| Setting | Type | Default |
|---|---|---|
| Enable Plugin | Checkbox | ✅ Checked |
| Debug Mode | Checkbox | ☐ Unchecked |
| Submission Type | Dropdown | "AJAX" |
| Clear Hidden Fields | Checkbox | ☐ Unchecked |

### Test: Save Settings
1. **Uncheck** "Enable Plugin".
2. Change "Submission Type" to **"Standard"**.
3. **Check** "Debug Mode".
4. Click **"Save Settings"**.
5. Refresh the page.

- [ ] All your changes are still saved after refresh.
- [ ] Settings persist correctly (Enable Plugin = unchecked, Submission Type = Standard, Debug Mode = checked).

> **Reset for further testing**: Set Enable Plugin back to ✅ checked, Submission Type back to AJAX, and save again.

---

## Step 3: Test Form Mappings

1. Go to **WP Admin → NDV Calculator**.
2. Click the **"Form Mappings"** tab.

### Test: Add a New Configuration
1. Click **"Add New Configuration"**.
2. A new card/box should appear with fields.

- [ ] New configuration card appears (no errors).
- [ ] You see the following fields:

| Field | Type | What to Enter |
|---|---|---|
| Elementor Form ID | Text input | Enter any text, e.g. `jewelry_calculator` |
| WooCommerce Product | Dropdown | Should list all your published products — select one |
| Price Field ID | Text input | Enter any text, e.g. `total_price` |

### Test: Add Field Mappings (Sub-rows)
1. Inside the configuration card, click **"Add Field"**.
2. A new row should appear inside the "Field Mappings" table.

- [ ] New field mapping row appears.
- [ ] You see two text inputs: "Form Field ID" and "Label in Order".

3. Fill in some test mappings:

| Form Field ID | Label in Order |
|---|---|
| `metal_type` | Metal |
| `stone_count` | Number of Stones |
| `chain_option` | Chain |

### Test: Add Multiple Configurations
1. Click **"Add New Configuration"** again.
2. Fill in a second configuration with different values.

- [ ] Two separate configuration cards are now visible.

### Test: Remove a Field Mapping
1. Click the **✕ button** next to one of the field mapping rows.

- [ ] The row is removed.

### Test: Remove a Configuration
1. Click the **🗑️ trash button** on a configuration card.
2. A confirmation dialog should appear.

- [ ] Confirmation dialog says "Are you sure you want to remove this mapping?"
- [ ] Clicking OK removes the entire card.
- [ ] Clicking Cancel keeps it.

### Test: Save All Mappings
1. Make sure you have at least **one valid configuration** with:
   - A Form ID filled in
   - A Product selected
   - A Price Field ID filled in
   - At least one field mapping row
2. Click **"Save All Mappings"**.

- [ ] A green success message appears: "Mappings saved successfully."
- [ ] Refresh the page — all mappings are still there with correct values.

### Test: Save with Empty/Invalid Data
1. Add a configuration but leave the Form ID empty.
2. Click "Save All Mappings".

- [ ] The incomplete configuration should be skipped/ignored (not saved).

---

## Step 4: Create an Elementor Form for Testing

> **This step requires Elementor Pro.** If you don't have it, skip to "Step 6: Test Without Elementor".

1. Go to **Pages → Add New**. Name it "Calculator Test".
2. Click **Edit with Elementor**.
3. Drag a **"Form"** widget onto the page.
4. Set the **Form Name / ID** to match what you entered in Step 3 (e.g., `jewelry_calculator`).

### Configure Form Fields

Add these fields in the Elementor form:

| Field Label | Field ID | Field Type |
|---|---|---|
| Metal Type | `metal_type` | Select (options: Gold, Silver, Platinum) |
| Number of Stones | `stone_count` | Number |
| Chain | `chain_option` | Radio (options: Yes, No) |
| Total Price | `total_price` | Hidden or Number (this is the calculated price field) |

> **Tip**: For the "Total Price" field, you can use Elementor's **Calculated Fields** feature if available, or just use a hidden field and manually enter a test value.

5. **Publish** the page.

---

## Step 5: Test the Frontend Button & Add to Cart

1. Open the page you created in Step 4 on the **frontend** (not Elementor editor).
2. Below the form, add the shortcode block or text widget with:

```
[ndv_woo_add_to_cart form_id="jewelry_calculator"]
```

> Replace `jewelry_calculator` with the actual Form ID you used.

### ✅ What to Verify

- [ ] An **"Add to Cart"** button appears on the page.
- [ ] The button is **initially disabled** (greyed out) because no price is set yet.

### Test: Price Label Updates
1. If you have a calculated price field, change form values.
2. The button text should update to something like: **"Add to Cart – £120.00"** (with your currency symbol).

- [ ] Button text shows the calculated price.
- [ ] Changing form values updates the price on the button.
- [ ] When price is valid (> 0), the button becomes **enabled** (clickable).

### Test: Add to Cart
1. Fill in all form fields.
2. Make sure the price is showing on the button.
3. Click **"Add to Cart"**.

- [ ] Button shows **"Adding..."** with a **spinning loader**.
- [ ] After a moment: button turns **green** briefly.
- [ ] A **"View Cart"** link appears below the button.
- [ ] No errors in the browser console (press F12 → Console tab to check).

### Test: Verify Cart Contents
1. Click **"View Cart"** or go to the WooCommerce Cart page.

- [ ] The product is in the cart.
- [ ] The **price matches** the calculated price from the form (NOT the original product price).
- [ ] Your form selections are shown below the product name:
  - Metal: Gold
  - Number of Stones: 3
  - Chain: Yes

### Test: Multiple Additions
1. Go back to the calculator page.
2. Change some form values (e.g., different metal, different stone count).
3. Click "Add to Cart" again.

- [ ] A **second** cart item appears (not merged with the first one — each form submission is unique).
- [ ] Each cart item shows its own form selections and price.

---

## Step 6: Test Order Meta (Checkout)

1. Go to the Cart page and proceed to **Checkout**.
2. Fill in billing details and place the order.
3. Go to **WP Admin → WooCommerce → Orders**.
4. Click on the order you just placed.

### ✅ What to Verify

- [ ] Under the order items, you see the product with:
  - **Calculated Price**: the amount from the form
  - **Metal**: Gold (or whatever was selected)
  - **Number of Stones**: 3
  - **Chain**: Yes
- [ ] The data is shown as **clean labels**, NOT raw JSON or field IDs.

---

## Step 7: Test Edge Cases

### A. Plugin Disabled
1. Go to **NDV Calculator → Global Settings**.
2. **Uncheck** "Enable Plugin" and save.
3. Go to the frontend calculator page.

- [ ] The add-to-cart button does **NOT** appear.
- [ ] No errors on the page.

> Re-enable the plugin after testing.

### B. Missing WooCommerce
1. **Deactivate** WooCommerce from Plugins page.
2. Check the admin dashboard.

- [ ] An error notice appears: *"NDV Woo Calculator requires WooCommerce..."*
- [ ] The NDV Calculator menu may still appear, but settings won't function for cart/ordering.

> Re-activate WooCommerce after testing.

### C. Invalid Shortcode
1. On a page, add the shortcode with a **non-existent** form ID:
```
[ndv_woo_add_to_cart form_id="does_not_exist"]
```

- [ ] Nothing appears on the page (no button, no error).

### D. Empty Shortcode
1. Add: `[ndv_woo_add_to_cart]` (without form_id).

- [ ] Nothing appears on the page (no button, no error).

---

## Step 8: Test Debug Mode

1. Go to **NDV Calculator → Global Settings**.
2. **Check** "Debug Mode" and save.
3. Go to the frontend calculator page.
4. Open browser **Developer Tools** (F12) → **Console** tab.

- [ ] You see log messages prefixed with `[NDVWC]` showing initialization, price updates, etc.

> Disable Debug Mode after testing.

---

## Test Summary Checklist

| # | Test | Status |
|---|---|---|
| 1 | Plugin activates without errors | ☐ |
| 2 | Admin menu appears | ☐ |
| 3 | Global Settings save & persist | ☐ |
| 4 | Form Mappings — add/remove configurations | ☐ |
| 5 | Form Mappings — add/remove field mappings | ☐ |
| 6 | Form Mappings — save & persist | ☐ |
| 7 | Product dropdown shows WooCommerce products | ☐ |
| 8 | Shortcode renders button | ☐ |
| 9 | Button price updates from form | ☐ |
| 10 | AJAX add to cart works | ☐ |
| 11 | Cart shows correct calculated price | ☐ |
| 12 | Cart shows form selections | ☐ |
| 13 | Multiple items stay separate in cart | ☐ |
| 14 | Order meta has clean labels | ☐ |
| 15 | Plugin disabled = button hidden | ☐ |
| 16 | Missing WooCommerce = error notice | ☐ |
| 17 | Invalid/empty shortcode = no output | ☐ |
| 18 | Debug mode shows console logs | ☐ |

---

## Reporting Bugs

When reporting a bug, please include:
1. **Which test step** failed (e.g., "Step 5 — Add to Cart").
2. **What you expected** vs. **what actually happened**.
3. **Browser console errors** (F12 → Console — copy any red error messages).
4. **Screenshot** if it's a visual issue.
5. **WordPress version**, **WooCommerce version**, and **PHP version** (found at WP Admin → Tools → Site Health → Info).
