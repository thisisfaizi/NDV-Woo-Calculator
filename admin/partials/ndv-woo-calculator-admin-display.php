<?php
/**
 * Admin settings page display template.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/admin/partials
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$mappings = NDV_Woo_Calculator_Config_Manager::get_mappings();
$products = NDV_Woo_Calculator_Config_Manager::get_products_list();
$active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'settings';
?>
<div class="wrap ndvwc-admin-wrap">
    <h1>
        <?php esc_html_e('NDV Woo Calculator', 'ndv-woo-calculator'); ?>
    </h1>

    <nav class="nav-tab-wrapper ndvwc-tabs">
        <a href="?page=ndv-woo-calculator&tab=settings"
            class="nav-tab <?php echo 'settings' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Global Settings', 'ndv-woo-calculator'); ?>
        </a>
        <a href="?page=ndv-woo-calculator&tab=mappings"
            class="nav-tab <?php echo 'mappings' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Form Mappings', 'ndv-woo-calculator'); ?>
        </a>
    </nav>

    <?php if ('settings' === $active_tab): ?>

        <!-- Global Settings Tab -->
        <form action="options.php" method="post" class="ndvwc-settings-form">
            <?php
            settings_fields('ndvwc_settings_group');
            do_settings_sections('ndv-woo-calculator');
            submit_button(__('Save Settings', 'ndv-woo-calculator'));
            ?>
        </form>

    <?php elseif ('mappings' === $active_tab): ?>

        <!-- Form Mappings Tab -->
        <div class="ndvwc-mappings-section">
            <p class="description">
                <?php esc_html_e('Create form-to-product mapping configurations. Each mapping connects an Elementor form to a WooCommerce product.', 'ndv-woo-calculator'); ?>
            </p>

            <div id="ndvwc-mappings-container">
                <?php if (!empty($mappings)): ?>
                    <?php foreach ($mappings as $index => $mapping): ?>
                        <div class="ndvwc-mapping-row" data-index="<?php echo esc_attr($index); ?>">
                            <div class="ndvwc-mapping-header">
                                <h3>
                                    <?php
                                    printf(
                                        /* translators: %d: mapping number */
                                        esc_html__('Configuration #%d', 'ndv-woo-calculator'),
                                        $index + 1
                                    );
                                    ?>
                                </h3>
                                <button type="button" class="button ndvwc-remove-mapping"
                                    title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>

                            <table class="form-table ndvwc-mapping-fields">
                                <tr>
                                    <th scope="row">
                                        <label>
                                            <?php esc_html_e('Elementor Form ID', 'ndv-woo-calculator'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input type="text" class="regular-text ndvwc-field-form-id"
                                            value="<?php echo esc_attr($mapping['form_id'] ?? ''); ?>"
                                            placeholder="<?php esc_attr_e('e.g. my_calculator_form', 'ndv-woo-calculator'); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label>
                                            <?php esc_html_e('WooCommerce Product', 'ndv-woo-calculator'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <select class="regular-text ndvwc-field-product-id">
                                            <option value="">
                                                <?php esc_html_e('— Select Product —', 'ndv-woo-calculator'); ?>
                                            </option>
                                            <?php foreach ($products as $pid => $title): ?>
                                                <option value="<?php echo esc_attr($pid); ?>" <?php selected(absint($mapping['product_id'] ?? 0), $pid); ?>>
                                                    <?php echo esc_html($title); ?> (#
                                                    <?php echo esc_html($pid); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label>
                                            <?php esc_html_e('Price Field ID', 'ndv-woo-calculator'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input type="text" class="regular-text ndvwc-field-price-id"
                                            value="<?php echo esc_attr($mapping['price_field_id'] ?? ''); ?>"
                                            placeholder="<?php esc_attr_e('e.g. calculated_price', 'ndv-woo-calculator'); ?>" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label>
                                            <?php esc_html_e('Field Mappings', 'ndv-woo-calculator'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <div class="ndvwc-field-mappings-container">
                                            <table class="widefat ndvwc-field-mappings-table">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <?php esc_html_e('Form Field ID', 'ndv-woo-calculator'); ?>
                                                        </th>
                                                        <th>
                                                            <?php esc_html_e('Label in Order', 'ndv-woo-calculator'); ?>
                                                        </th>
                                                        <th style="width:50px;"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($mapping['field_mappings'])): ?>
                                                        <?php foreach ($mapping['field_mappings'] as $fm): ?>
                                                            <tr class="ndvwc-field-mapping-row">
                                                                <td>
                                                                    <input type="text" class="widefat ndvwc-fm-field-id"
                                                                        value="<?php echo esc_attr($fm['field_id'] ?? ''); ?>"
                                                                        placeholder="<?php esc_attr_e('e.g. metal_type', 'ndv-woo-calculator'); ?>" />
                                                                </td>
                                                                <td>
                                                                    <input type="text" class="widefat ndvwc-fm-label"
                                                                        value="<?php echo esc_attr($fm['label'] ?? ''); ?>"
                                                                        placeholder="<?php esc_attr_e('e.g. Metal', 'ndv-woo-calculator'); ?>" />
                                                                </td>
                                                                <td>
                                                                    <button type="button" class="button ndvwc-remove-field-mapping"
                                                                        title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                                                        <span class="dashicons dashicons-no-alt"></span>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                            <button type="button" class="button ndvwc-add-field-mapping">
                                                <span class="dashicons dashicons-plus-alt2"></span>
                                                <?php esc_html_e('Add Field', 'ndv-woo-calculator'); ?>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="ndvwc-mapping-actions">
                <button type="button" id="ndvwc-add-mapping" class="button button-secondary">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('Add New Configuration', 'ndv-woo-calculator'); ?>
                </button>
                <button type="button" id="ndvwc-save-mappings" class="button button-primary">
                    <?php esc_html_e('Save All Mappings', 'ndv-woo-calculator'); ?>
                </button>
                <span id="ndvwc-save-status" class="ndvwc-save-status"></span>
            </div>
        </div>

        <!-- Hidden template for new mapping row (used by JS) -->
        <script type="text/template" id="ndvwc-mapping-template">
                <div class="ndvwc-mapping-row" data-index="{{INDEX}}">
                    <div class="ndvwc-mapping-header">
                        <h3><?php esc_html_e('New Configuration', 'ndv-woo-calculator'); ?></h3>
                        <button type="button" class="button ndvwc-remove-mapping" title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    </div>
                    <table class="form-table ndvwc-mapping-fields">
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Elementor Form ID', 'ndv-woo-calculator'); ?></label></th>
                            <td><input type="text" class="regular-text ndvwc-field-form-id" value="" placeholder="<?php esc_attr_e('e.g. my_calculator_form', 'ndv-woo-calculator'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php esc_html_e('WooCommerce Product', 'ndv-woo-calculator'); ?></label></th>
                            <td>
                                <select class="regular-text ndvwc-field-product-id">
                                    <option value=""><?php esc_html_e('— Select Product —', 'ndv-woo-calculator'); ?></option>
                                    <?php foreach ($products as $pid => $title): ?>
                                            <option value="<?php echo esc_attr($pid); ?>">
                                                <?php echo esc_html($title); ?> (#<?php echo esc_html($pid); ?>)
                                            </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Price Field ID', 'ndv-woo-calculator'); ?></label></th>
                            <td><input type="text" class="regular-text ndvwc-field-price-id" value="" placeholder="<?php esc_attr_e('e.g. calculated_price', 'ndv-woo-calculator'); ?>" /></td>
                        </tr>
                        <tr>
                            <th scope="row"><label><?php esc_html_e('Field Mappings', 'ndv-woo-calculator'); ?></label></th>
                            <td>
                                <div class="ndvwc-field-mappings-container">
                                    <table class="widefat ndvwc-field-mappings-table">
                                        <thead>
                                            <tr>
                                                <th><?php esc_html_e('Form Field ID', 'ndv-woo-calculator'); ?></th>
                                                <th><?php esc_html_e('Label in Order', 'ndv-woo-calculator'); ?></th>
                                                <th style="width:50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                    <button type="button" class="button ndvwc-add-field-mapping">
                                        <span class="dashicons dashicons-plus-alt2"></span>
                                        <?php esc_html_e('Add Field', 'ndv-woo-calculator'); ?>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
            </script>

        <!-- Hidden template for new field mapping row -->
        <script type="text/template" id="ndvwc-field-mapping-template">
                <tr class="ndvwc-field-mapping-row">
                    <td><input type="text" class="widefat ndvwc-fm-field-id" value="" placeholder="<?php esc_attr_e('e.g. metal_type', 'ndv-woo-calculator'); ?>" /></td>
                    <td><input type="text" class="widefat ndvwc-fm-label" value="" placeholder="<?php esc_attr_e('e.g. Metal', 'ndv-woo-calculator'); ?>" /></td>
                    <td>
                        <button type="button" class="button ndvwc-remove-field-mapping" title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </td>
                </tr>
            </script>

    <?php endif; ?>
</div>