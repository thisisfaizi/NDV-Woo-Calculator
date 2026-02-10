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
        <a href="?page=ndv-woo-calculator&tab=rates"
            class="nav-tab <?php echo 'rates' === $active_tab ? 'nav-tab-active' : ''; ?>">
            <?php esc_html_e('Global Rates', 'ndv-woo-calculator'); ?>
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
                <?php esc_html_e('Create form-to-product mapping configurations. Each mapping connects an Elementor form to a WooCommerce product and defines how the price is calculated.', 'ndv-woo-calculator'); ?>
            </p>

            <div id="ndvwc-mappings-container">
                <?php if (!empty($mappings)): ?>
                    <?php foreach ($mappings as $index => $mapping): ?>
                        <?php $this->render_mapping_card($index, $mapping, $products); ?>
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
                        <?php $this->render_mapping_card('{{INDEX}}', array(), $products); ?>
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

        <!-- Hidden template for new pricing rule row -->
        <script type="text/template" id="ndvwc-pricing-rule-template">
                            <tr class="ndvwc-pricing-rule-row">
                                <td><input type="text" class="widefat ndvwc-pr-field-id" value="" placeholder="<?php esc_attr_e('e.g. metal_type', 'ndv-woo-calculator'); ?>" /></td>
                                <td><input type="text" class="widefat ndvwc-pr-field-value" value="" placeholder="<?php esc_attr_e('e.g. gold (or * for any)', 'ndv-woo-calculator'); ?>" /></td>
                                <td>
                                    <select class="ndvwc-pr-operator">
                                        <option value="add"><?php esc_html_e('Add Fixed (+)', 'ndv-woo-calculator'); ?></option>
                                        <option value="field_multiply"><?php esc_html_e('Entered × Amount', 'ndv-woo-calculator'); ?></option>
                                        <option value="multiply"><?php esc_html_e('Multiply Price (×)', 'ndv-woo-calculator'); ?></option>
                                        <option value="set"><?php esc_html_e('Set Price (=)', 'ndv-woo-calculator'); ?></option>
                                        <option value="add_percent"><?php esc_html_e('Add %', 'ndv-woo-calculator'); ?></option>
                                        <option value="field_add"><?php esc_html_e('Entered + Amount', 'ndv-woo-calculator'); ?></option>
                                    </select>
                                </td>
                                <td><input type="number" step="0.01" class="widefat ndvwc-pr-amount" value="" placeholder="0.00" /></td>
                                <td>
                                    <button type="button" class="button ndvwc-remove-pricing-rule" title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </td>
                            </tr>
                        </script>

    <?php elseif ('rates' === $active_tab): ?>

        <!-- Global Rates Tab -->
        <?php
        $global_rates = NDV_Woo_Calculator_Config_Manager::get_global_rates();
        $metals = $global_rates['metals'] ?? array();
        $stones = $global_rates['stones'] ?? array();
        $chains = $global_rates['chains'] ?? array();
        ?>
        <div class="ndvwc-rates-section">
            <p class="description">
                <?php esc_html_e('Define global rates for metals, stones, and chains. These rates are used by the Pendant Configurator to calculate prices. Updating a rate here will instantly affect all pendant products.', 'ndv-woo-calculator'); ?>
            </p>

            <!-- Metal Rates -->
            <h2><?php esc_html_e('Metal Rates', 'ndv-woo-calculator'); ?></h2>
            <table class="widefat ndvwc-rates-table" id="ndvwc-metal-rates-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Key (slug)', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Display Name', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Price per Gram', 'ndv-woo-calculator'); ?></th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($metals)): ?>
                        <?php foreach ($metals as $metal): ?>
                            <tr class="ndvwc-rate-row">
                                <td><input type="text" class="widefat ndvwc-rate-key" value="<?php echo esc_attr($metal['key']); ?>"
                                        placeholder="e.g. 9ct_yellow" /></td>
                                <td><input type="text" class="widefat ndvwc-rate-name"
                                        value="<?php echo esc_attr($metal['name']); ?>" placeholder="e.g. 9ct Yellow Gold" /></td>
                                <td><input type="number" step="0.01" min="0" class="widefat ndvwc-rate-value"
                                        value="<?php echo esc_attr($metal['price_per_gram']); ?>" placeholder="0.00" /></td>
                                <td>
                                    <button type="button" class="button ndvwc-remove-rate-row"
                                        title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" class="button ndvwc-add-rate-row" data-table="ndvwc-metal-rates-table"
                data-value-label="price_per_gram">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add Metal', 'ndv-woo-calculator'); ?>
            </button>

            <hr />

            <!-- Stone Rates -->
            <h2><?php esc_html_e('Stone Rates', 'ndv-woo-calculator'); ?></h2>
            <table class="widefat ndvwc-rates-table" id="ndvwc-stone-rates-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Key (slug)', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Display Name', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Price per Unit', 'ndv-woo-calculator'); ?></th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($stones)): ?>
                        <?php foreach ($stones as $stone): ?>
                            <tr class="ndvwc-rate-row">
                                <td><input type="text" class="widefat ndvwc-rate-key" value="<?php echo esc_attr($stone['key']); ?>"
                                        placeholder="e.g. birthstone_a" /></td>
                                <td><input type="text" class="widefat ndvwc-rate-name"
                                        value="<?php echo esc_attr($stone['name']); ?>" placeholder="e.g. Birthstone A" /></td>
                                <td><input type="number" step="0.01" min="0" class="widefat ndvwc-rate-value"
                                        value="<?php echo esc_attr($stone['price_per_unit']); ?>" placeholder="0.00" /></td>
                                <td>
                                    <button type="button" class="button ndvwc-remove-rate-row"
                                        title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" class="button ndvwc-add-rate-row" data-table="ndvwc-stone-rates-table"
                data-value-label="price_per_unit">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add Stone', 'ndv-woo-calculator'); ?>
            </button>

            <hr />

            <!-- Chain Rates -->
            <h2><?php esc_html_e('Chain Rates', 'ndv-woo-calculator'); ?></h2>
            <table class="widefat ndvwc-rates-table" id="ndvwc-chain-rates-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Key (slug)', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Display Name', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Rate per cm', 'ndv-woo-calculator'); ?></th>
                        <th><?php esc_html_e('Material', 'ndv-woo-calculator'); ?></th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($chains)): ?>
                        <?php foreach ($chains as $chain): ?>
                            <tr class="ndvwc-rate-row">
                                <td><input type="text" class="widefat ndvwc-rate-key" value="<?php echo esc_attr($chain['key']); ?>"
                                        placeholder="e.g. silver_chain" /></td>
                                <td><input type="text" class="widefat ndvwc-rate-name"
                                        value="<?php echo esc_attr($chain['name']); ?>" placeholder="e.g. Silver Chain" /></td>
                                <td><input type="number" step="0.01" min="0" class="widefat ndvwc-rate-value"
                                        value="<?php echo esc_attr($chain['rate_per_cm']); ?>" placeholder="0.00" /></td>
                                <td><input type="text" class="widefat ndvwc-rate-material"
                                        value="<?php echo esc_attr($chain['material']); ?>" placeholder="e.g. Sterling Silver" />
                                </td>
                                <td>
                                    <button type="button" class="button ndvwc-remove-rate-row"
                                        title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                        <span class="dashicons dashicons-no-alt"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <button type="button" class="button ndvwc-add-rate-row" data-table="ndvwc-chain-rates-table"
                data-value-label="rate_per_cm">
                <span class="dashicons dashicons-plus-alt2"></span>
                <?php esc_html_e('Add Chain', 'ndv-woo-calculator'); ?>
            </button>

            <hr />

            <div class="ndvwc-rates-actions">
                <button type="button" id="ndvwc-save-rates" class="button button-primary">
                    <?php esc_html_e('Save All Rates', 'ndv-woo-calculator'); ?>
                </button>
                <span id="ndvwc-rates-save-status" class="ndvwc-save-status"></span>
            </div>
        </div>

    <?php endif; ?>
</div>