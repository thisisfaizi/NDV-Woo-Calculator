<?php
/**
 * Admin functionality — settings page and form-to-product mappings.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/admin
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Admin
 *
 * Registers the top-level admin menu, global settings (Settings API),
 * and form-to-product mapping management with pricing rules.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Admin
{

    /**
     * Settings option key.
     *
     * @since 1.0.0
     * @var string
     */
    private $option_name = 'ndvwc_settings';

    /**
     * Mappings option key.
     *
     * @since 1.0.0
     * @var string
     */
    private $mappings_option = 'ndvwc_form_mappings';

    /**
     * Initialize admin hooks.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX handler for saving mappings.
        add_action('wp_ajax_ndvwc_save_mappings', array($this, 'ajax_save_mappings'));

        // AJAX handler for saving global rates.
        add_action('wp_ajax_ndvwc_save_global_rates', array($this, 'ajax_save_global_rates'));
    }

    /**
     * Add top-level admin menu page.
     *
     * @since 1.0.0
     */
    public function add_menu_page()
    {
        add_menu_page(
            __('NDV Woo Calculator', 'ndv-woo-calculator'),
            __('NDV Calculator', 'ndv-woo-calculator'),
            'manage_options',
            'ndv-woo-calculator',
            array($this, 'render_settings_page'),
            'dashicons-calculator',
            56
        );
    }

    /**
     * Register settings using the Settings API.
     *
     * @since 1.0.0
     */
    public function register_settings()
    {
        register_setting(
            'ndvwc_settings_group',
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_defaults(),
            )
        );

        add_settings_section(
            'ndvwc_general',
            __('General Settings', 'ndv-woo-calculator'),
            array($this, 'render_general_section'),
            'ndv-woo-calculator'
        );

        add_settings_field(
            'ndvwc_enabled',
            __('Enable Plugin', 'ndv-woo-calculator'),
            array($this, 'render_checkbox_field'),
            'ndv-woo-calculator',
            'ndvwc_general',
            array(
                'field' => 'enabled',
                'description' => __('Enable or disable the calculator functionality.', 'ndv-woo-calculator'),
            )
        );

        add_settings_field(
            'ndvwc_debug_mode',
            __('Debug Mode', 'ndv-woo-calculator'),
            array($this, 'render_checkbox_field'),
            'ndv-woo-calculator',
            'ndvwc_general',
            array(
                'field' => 'debug_mode',
                'description' => __('Enable debug logging for troubleshooting.', 'ndv-woo-calculator'),
            )
        );

        add_settings_field(
            'ndvwc_submission_type',
            __('Submission Type', 'ndv-woo-calculator'),
            array($this, 'render_select_field'),
            'ndv-woo-calculator',
            'ndvwc_general',
            array(
                'field' => 'submission_type',
                'options' => array(
                    'ajax' => __('AJAX', 'ndv-woo-calculator'),
                    'standard' => __('Standard', 'ndv-woo-calculator'),
                ),
            )
        );

        add_settings_field(
            'ndvwc_clear_hidden',
            __('Clear Hidden Fields', 'ndv-woo-calculator'),
            array($this, 'render_checkbox_field'),
            'ndv-woo-calculator',
            'ndvwc_general',
            array(
                'field' => 'clear_hidden_fields',
                'description' => __('Reset hidden conditional field values before submission.', 'ndv-woo-calculator'),
            )
        );
    }

    /**
     * Sanitize settings input.
     *
     * @since  1.0.0
     * @param  array $input Raw input.
     * @return array Sanitized settings.
     */
    public function sanitize_settings($input)
    {
        $sanitized = array();

        $sanitized['enabled'] = isset($input['enabled']) ? 'yes' : 'no';
        $sanitized['debug_mode'] = isset($input['debug_mode']) ? 'yes' : 'no';
        $sanitized['submission_type'] = in_array($input['submission_type'] ?? '', array('ajax', 'standard'), true)
            ? $input['submission_type']
            : 'ajax';
        $sanitized['clear_hidden_fields'] = isset($input['clear_hidden_fields']) ? 'yes' : 'no';

        return $sanitized;
    }

    /**
     * Get default settings.
     *
     * @since  1.0.0
     * @return array
     */
    private function get_defaults()
    {
        return array(
            'enabled' => 'yes',
            'debug_mode' => 'no',
            'submission_type' => 'ajax',
            'clear_hidden_fields' => 'no',
        );
    }

    /**
     * Enqueue admin assets only on plugin pages.
     *
     * @since 1.0.0
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets($hook)
    {
        if ('toplevel_page_ndv-woo-calculator' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'ndvwc-admin',
            NDVWC_PLUGIN_URL . 'admin/css/ndv-woo-calculator-admin.css',
            array(),
            NDVWC_VERSION
        );

        wp_enqueue_script(
            'ndvwc-admin',
            NDVWC_PLUGIN_URL . 'admin/js/ndv-woo-calculator-admin.js',
            array('jquery'),
            NDVWC_VERSION,
            true
        );

        wp_localize_script(
            'ndvwc-admin',
            'ndvwcAdmin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ndvwc_admin_nonce'),
                'i18n' => array(
                    'saved' => __('Mappings saved successfully.', 'ndv-woo-calculator'),
                    'error' => __('Error saving mappings.', 'ndv-woo-calculator'),
                    'confirm_delete' => __('Are you sure you want to remove this mapping?', 'ndv-woo-calculator'),
                    'saving' => __('Saving...', 'ndv-woo-calculator'),
                    'save_btn' => __('Save All Mappings', 'ndv-woo-calculator'),
                ),
            )
        );
    }

    /**
     * Render the settings page.
     *
     * @since 1.0.0
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        include NDVWC_PLUGIN_DIR . 'admin/partials/ndv-woo-calculator-admin-display.php';
    }

    /**
     * Render general section description.
     *
     * @since 1.0.0
     */
    public function render_general_section()
    {
        echo '<p>' . esc_html__('Configure the general behavior of NDV Woo Calculator.', 'ndv-woo-calculator') . '</p>';
    }

    /**
     * Render a checkbox field.
     *
     * @since 1.0.0
     * @param array $args Field arguments.
     */
    public function render_checkbox_field($args)
    {
        $options = get_option($this->option_name, $this->get_defaults());
        $field = $args['field'];
        $checked = isset($options[$field]) && 'yes' === $options[$field];
        ?>
        <label>
            <input type="checkbox" name="<?php echo esc_attr($this->option_name . '[' . $field . ']'); ?>" value="yes" <?php checked($checked); ?> />
            <?php
            if (!empty($args['description'])) {
                echo esc_html($args['description']);
            }
            ?>
        </label>
        <?php
    }

    /**
     * Render a select dropdown field.
     *
     * @since 1.0.0
     * @param array $args Field arguments.
     */
    public function render_select_field($args)
    {
        $options = get_option($this->option_name, $this->get_defaults());
        $field = $args['field'];
        $current = $options[$field] ?? '';
        $field_options = $args['options'] ?? array();
        ?>
        <select name="<?php echo esc_attr($this->option_name . '[' . $field . ']'); ?>"
            id="<?php echo esc_attr($field); ?>">
            <?php foreach ($field_options as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($current, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Render a single mapping configuration card.
     *
     * Used for both existing mappings and the JS template for new ones.
     *
     * @since 1.0.0
     * @param int|string $index    Card index (or '{{INDEX}}' for templates).
     * @param array      $mapping  Mapping data (empty array for templates).
     * @param array      $products Product ID => title pairs.
     */
    public function render_mapping_card($index, $mapping, $products)
    {
        $form_id = $mapping['form_id'] ?? '';
        $product_id = absint($mapping['product_id'] ?? 0);
        $price_field_id = $mapping['price_field_id'] ?? '';
        $calc_mode = $mapping['calculation_mode'] ?? 'elementor';
        $base_price = floatval($mapping['base_price'] ?? 0);
        $field_mappings = $mapping['field_mappings'] ?? array();
        $pricing_rules = $mapping['pricing_rules'] ?? array();
        $is_template = empty($mapping);
        $card_title = $is_template
            ? __('New Configuration', 'ndv-woo-calculator')
            : sprintf(
                /* translators: %d: mapping number */
                __('Configuration #%d', 'ndv-woo-calculator'),
                $index + 1
            );
        ?>
        <div class="ndvwc-mapping-row" data-index="<?php echo esc_attr($index); ?>">
            <div class="ndvwc-mapping-header">
                <h3><?php echo esc_html($card_title); ?></h3>
                <button type="button" class="button ndvwc-remove-mapping"
                    title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>

            <table class="form-table ndvwc-mapping-fields">
                <!-- Elementor Form ID -->
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Elementor Form ID', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text ndvwc-field-form-id" value="<?php echo esc_attr($form_id); ?>"
                            placeholder="<?php esc_attr_e('e.g. my_calculator_form', 'ndv-woo-calculator'); ?>" />
                        <p class="description">
                            <?php esc_html_e('The Form Name set in Elementor Pro form settings.', 'ndv-woo-calculator'); ?>
                        </p>
                    </td>
                </tr>

                <!-- WooCommerce Product -->
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('WooCommerce Product', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <select class="regular-text ndvwc-field-product-id">
                            <option value=""><?php esc_html_e('— Select Product —', 'ndv-woo-calculator'); ?></option>
                            <?php foreach ($products as $pid => $title): ?>
                                <option value="<?php echo esc_attr($pid); ?>" <?php selected($product_id, $pid); ?>>
                                    <?php echo esc_html($title); ?> (#<?php echo esc_html($pid); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>

                <!-- Calculation Mode -->
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Calculation Mode', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <select class="regular-text ndvwc-field-calc-mode">
                            <option value="elementor" <?php selected($calc_mode, 'elementor'); ?>>
                                <?php esc_html_e('Elementor Calculated Field', 'ndv-woo-calculator'); ?>
                            </option>
                            <option value="rules" <?php selected($calc_mode, 'rules'); ?>>
                                <?php esc_html_e('Pricing Rules (Plugin Calculates)', 'ndv-woo-calculator'); ?>
                            </option>
                            <option value="pendant" <?php selected($calc_mode, 'pendant'); ?>>
                                <?php esc_html_e('Pendant Configurator', 'ndv-woo-calculator'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('"Elementor" reads price from a form field. "Pricing Rules" calculates via rules. "Pendant Configurator" uses the pendant formula with global rates.', 'ndv-woo-calculator'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Price Field ID (Elementor mode) -->
                <tr class="ndvwc-elementor-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Price Field ID', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text ndvwc-field-price-id"
                            value="<?php echo esc_attr($price_field_id); ?>"
                            placeholder="<?php esc_attr_e('e.g. calculated_price', 'ndv-woo-calculator'); ?>" />
                        <p class="description">
                            <?php esc_html_e('The Elementor calculated field ID that holds the final price.', 'ndv-woo-calculator'); ?>
                        </p>
                    </td>
                </tr>

                <!-- Base Price (Rules mode) -->
                <tr class="ndvwc-rules-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Base Price', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="number" step="0.01" min="0" class="regular-text ndvwc-field-base-price"
                            value="<?php echo esc_attr($base_price); ?>" placeholder="0.00" />
                        <p class="description">
                            <?php esc_html_e('Starting price before pricing rules are applied.', 'ndv-woo-calculator'); ?></p>
                    </td>
                </tr>

                <!-- Pricing Rules (Rules mode) -->
                <tr class="ndvwc-rules-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Pricing Rules', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <div class="ndvwc-pricing-rules-container">
                            <p class="description" style="margin-bottom:8px;">
                                <?php esc_html_e('Define pricing rules. Use * as Value to match any non-empty input. For number fields, use "Entered × Amount" to calculate from the entered number. Rules apply top to bottom.', 'ndv-woo-calculator'); ?>
                            </p>
                            <table class="widefat ndvwc-pricing-rules-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Field ID', 'ndv-woo-calculator'); ?></th>
                                        <th><?php esc_html_e('Value', 'ndv-woo-calculator'); ?></th>
                                        <th><?php esc_html_e('Operator', 'ndv-woo-calculator'); ?></th>
                                        <th><?php esc_html_e('Amount', 'ndv-woo-calculator'); ?></th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($pricing_rules)): ?>
                                        <?php foreach ($pricing_rules as $rule): ?>
                                            <tr class="ndvwc-pricing-rule-row">
                                                <td><input type="text" class="widefat ndvwc-pr-field-id"
                                                        value="<?php echo esc_attr($rule['field_id'] ?? ''); ?>"
                                                        placeholder="<?php esc_attr_e('e.g. metal_type', 'ndv-woo-calculator'); ?>" />
                                                </td>
                                                <td><input type="text" class="widefat ndvwc-pr-field-value"
                                                        value="<?php echo esc_attr($rule['field_value'] ?? ''); ?>"
                                                        placeholder="<?php esc_attr_e('e.g. gold', 'ndv-woo-calculator'); ?>" /></td>
                                                <td>
                                                    <select class="ndvwc-pr-operator">
                                                        <option value="add" <?php selected($rule['operator'] ?? '', 'add'); ?>>
                                                            <?php esc_html_e('Add Fixed (+)', 'ndv-woo-calculator'); ?></option>
                                                        <option value="field_multiply" <?php selected($rule['operator'] ?? '', 'field_multiply'); ?>>
                                                            <?php esc_html_e('Entered × Amount', 'ndv-woo-calculator'); ?></option>
                                                        <option value="multiply" <?php selected($rule['operator'] ?? '', 'multiply'); ?>>
                                                            <?php esc_html_e('Multiply Price (×)', 'ndv-woo-calculator'); ?></option>
                                                        <option value="set" <?php selected($rule['operator'] ?? '', 'set'); ?>>
                                                            <?php esc_html_e('Set Price (=)', 'ndv-woo-calculator'); ?></option>
                                                        <option value="add_percent" <?php selected($rule['operator'] ?? '', 'add_percent'); ?>>
                                                            <?php esc_html_e('Add %', 'ndv-woo-calculator'); ?></option>
                                                        <option value="field_add" <?php selected($rule['operator'] ?? '', 'field_add'); ?>>
                                                            <?php esc_html_e('Entered + Amount', 'ndv-woo-calculator'); ?></option>
                                                    </select>
                                                </td>
                                                <td><input type="number" step="0.01" class="widefat ndvwc-pr-amount"
                                                        value="<?php echo esc_attr($rule['amount'] ?? ''); ?>" placeholder="0.00" />
                                                </td>
                                                <td>
                                                    <button type="button" class="button ndvwc-remove-pricing-rule"
                                                        title="<?php esc_attr_e('Remove', 'ndv-woo-calculator'); ?>">
                                                        <span class="dashicons dashicons-no-alt"></span>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <button type="button" class="button ndvwc-add-pricing-rule">
                                <span class="dashicons dashicons-plus-alt2"></span>
                                <?php esc_html_e('Add Rule', 'ndv-woo-calculator'); ?>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Pendant Configurator Fields (Pendant mode) -->
                <?php
                $pendant_metal_weight = floatval($mapping['pendant_metal_weight'] ?? 0);
                $pendant_available_metals = $mapping['pendant_available_metals'] ?? array();
                $pendant_available_stones = $mapping['pendant_available_stones'] ?? array();
                $pendant_max_stones = absint($mapping['pendant_max_stones'] ?? 5);
                $pendant_available_chains = $mapping['pendant_available_chains'] ?? array();
                $pendant_chain_lengths = $mapping['pendant_chain_lengths'] ?? array();
                $pendant_labor = floatval($mapping['pendant_labor'] ?? 0);
                $pendant_markup = floatval($mapping['pendant_markup'] ?? 0);
                $pendant_markup_type = $mapping['pendant_markup_type'] ?? 'fixed';

                $global_rates = NDV_Woo_Calculator_Config_Manager::get_global_rates();
                $all_metals = $global_rates['metals'] ?? array();
                $all_stones = $global_rates['stones'] ?? array();
                $all_chains = $global_rates['chains'] ?? array();
                ?>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Pendant Metal Weight (g)', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="number" step="0.01" min="0" class="regular-text ndvwc-field-pendant-metal-weight"
                            value="<?php echo esc_attr($pendant_metal_weight); ?>" placeholder="0.00" />
                        <p class="description">
                            <?php esc_html_e('Weight of the pendant setting in grams.', 'ndv-woo-calculator'); ?>
                        </p>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Allow User Weight Input', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" class="ndvwc-field-pendant-user-weight" value="yes" 
                             <?php checked(!empty($mapping['pendant_user_weight_enabled'])); ?> />
                            <?php esc_html_e('Enable customer to enter custom metal weight.', 'ndv-woo-calculator'); ?>
                        </label>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Available Metals', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <div class="ndvwc-pendant-checkboxes">
                            <?php if (!empty($all_metals)): ?>
                                <?php foreach ($all_metals as $metal): ?>
                                    <label class="ndvwc-pendant-checkbox-label">
                                        <input type="checkbox" class="ndvwc-pendant-available-metal"
                                            value="<?php echo esc_attr($metal['key']); ?>"
                                            <?php checked(in_array($metal['key'], $pendant_available_metals, true)); ?> />
                                        <?php echo esc_html($metal['name']); ?>
                                        (<?php echo esc_html(get_woocommerce_currency_symbol() . $metal['price_per_gram']); ?>/g)
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="description">
                                    <?php esc_html_e('No metals defined yet. Add metals in the Global Rates tab first.', 'ndv-woo-calculator'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Available Stones', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <div class="ndvwc-pendant-checkboxes">
                            <?php if (!empty($all_stones)): ?>
                                <?php foreach ($all_stones as $stone): ?>
                                    <label class="ndvwc-pendant-checkbox-label">
                                        <input type="checkbox" class="ndvwc-pendant-available-stone"
                                            value="<?php echo esc_attr($stone['key']); ?>"
                                            <?php checked(in_array($stone['key'], $pendant_available_stones, true)); ?> />
                                        <?php echo esc_html($stone['name']); ?>
                                        (<?php echo esc_html(get_woocommerce_currency_symbol() . $stone['price_per_unit']); ?>/ea)
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="description">
                                    <?php esc_html_e('No stones defined yet. Add stones in the Global Rates tab first.', 'ndv-woo-calculator'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Max Stones Allowed', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="number" step="1" min="1" class="small-text ndvwc-field-pendant-max-stones"
                            value="<?php echo esc_attr($pendant_max_stones); ?>" />
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Available Chains', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <div class="ndvwc-pendant-checkboxes">
                            <?php if (!empty($all_chains)): ?>
                                <?php foreach ($all_chains as $chain): ?>
                                    <label class="ndvwc-pendant-checkbox-label">
                                        <input type="checkbox" class="ndvwc-pendant-available-chain"
                                            value="<?php echo esc_attr($chain['key']); ?>"
                                            <?php checked(in_array($chain['key'], $pendant_available_chains, true)); ?> />
                                        <?php echo esc_html($chain['name']); ?>
                                        (<?php echo esc_html(get_woocommerce_currency_symbol() . $chain['rate_per_cm']); ?>/cm)
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="description">
                                    <?php esc_html_e('No chains defined yet. Add chains in the Global Rates tab first.', 'ndv-woo-calculator'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Chain Length Options (cm)', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text ndvwc-field-pendant-chain-lengths"
                            value="<?php echo esc_attr(implode(', ', $pendant_chain_lengths)); ?>"
                            placeholder="<?php esc_attr_e('e.g. 40, 45, 50, 55, 60', 'ndv-woo-calculator'); ?>" />
                        <p class="description">
                            <?php esc_html_e('Comma-separated list of chain lengths in centimeters.', 'ndv-woo-calculator'); ?>
                        </p>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Labor Cost', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <input type="number" step="0.01" min="0" class="regular-text ndvwc-field-pendant-labor"
                            value="<?php echo esc_attr($pendant_labor); ?>" placeholder="0.00" />
                        <p class="description">
                            <?php esc_html_e('Fixed labor / making charge.', 'ndv-woo-calculator'); ?>
                        </p>
                    </td>
                </tr>

                <tr class="ndvwc-pendant-mode-row">
                    <th scope="row">
                        <label><?php esc_html_e('Profit Markup', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <div style="display:flex;gap:8px;align-items:center;">
                            <input type="number" step="0.01" min="0" class="regular-text ndvwc-field-pendant-markup"
                                value="<?php echo esc_attr($pendant_markup); ?>" placeholder="0.00" />
                            <select class="ndvwc-field-pendant-markup-type">
                                <option value="fixed" <?php selected($pendant_markup_type, 'fixed'); ?>>
                                    <?php esc_html_e('Fixed Amount', 'ndv-woo-calculator'); ?>
                                </option>
                                <option value="percent" <?php selected($pendant_markup_type, 'percent'); ?>>
                                    <?php esc_html_e('Percentage (%)', 'ndv-woo-calculator'); ?>
                                </option>
                            </select>
                        </div>
                    </td>
                </tr>

                <!-- Field Mappings (for order meta) -->
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Fields to Save in Order', 'ndv-woo-calculator'); ?></label>
                    </th>
                    <td>
                        <div class="ndvwc-field-mappings-container">
                            <p class="description" style="margin-bottom:8px;">
                                <?php esc_html_e('Map form field IDs to clean labels for display in WooCommerce orders.', 'ndv-woo-calculator'); ?>
                            </p>
                            <table class="widefat ndvwc-field-mappings-table">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Form Field ID', 'ndv-woo-calculator'); ?></th>
                                        <th><?php esc_html_e('Label in Order', 'ndv-woo-calculator'); ?></th>
                                        <th style="width:50px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($field_mappings)): ?>
                                        <?php foreach ($field_mappings as $fm): ?>
                                            <tr class="ndvwc-field-mapping-row">
                                                <td><input type="text" class="widefat ndvwc-fm-field-id"
                                                        value="<?php echo esc_attr($fm['field_id'] ?? ''); ?>"
                                                        placeholder="<?php esc_attr_e('e.g. metal_type', 'ndv-woo-calculator'); ?>" />
                                                </td>
                                                <td><input type="text" class="widefat ndvwc-fm-label"
                                                        value="<?php echo esc_attr($fm['label'] ?? ''); ?>"
                                                        placeholder="<?php esc_attr_e('e.g. Metal', 'ndv-woo-calculator'); ?>" /></td>
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
        <?php
    }

    /**
     * Handle AJAX request to save form mappings.
     *
     * The JS sends mappings as a JSON string to avoid jQuery nested-array
     * serialization issues. We json_decode it here.
     *
     * @since 1.0.0
     */
    public function ajax_save_mappings()
    {
        check_ajax_referer('ndvwc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ndv-woo-calculator')), 403);
        }

        // Decode the JSON string sent from JS.
        $raw_json = isset($_POST['mappings']) ? wp_unslash($_POST['mappings']) : '';
        $raw_mappings = json_decode($raw_json, true);

        if (!is_array($raw_mappings)) {
            wp_send_json_error(array('message' => __('Invalid data format.', 'ndv-woo-calculator')), 400);
        }

        $valid_operators = array('add', 'multiply', 'set', 'add_percent', 'field_multiply', 'field_add');
        $valid_calc_modes = array('elementor', 'rules', 'pendant');
        $sanitized_mappings = array();

        foreach ($raw_mappings as $mapping) {
            $sanitized = array(
                'form_id' => sanitize_text_field($mapping['form_id'] ?? ''),
                'product_id' => absint($mapping['product_id'] ?? 0),
                'price_field_id' => sanitize_text_field($mapping['price_field_id'] ?? ''),
                'calculation_mode' => in_array($mapping['calculation_mode'] ?? '', $valid_calc_modes, true)
                    ? $mapping['calculation_mode']
                    : 'elementor',
                'base_price' => floatval($mapping['base_price'] ?? 0),
                'field_mappings' => array(),
                'pricing_rules' => array(),
            );

            // Skip if essential fields are empty.
            if (empty($sanitized['form_id']) || 0 === $sanitized['product_id']) {
                continue;
            }

            // Sanitize field mappings.
            if (isset($mapping['field_mappings']) && is_array($mapping['field_mappings'])) {
                foreach ($mapping['field_mappings'] as $fm) {
                    $fid = sanitize_text_field($fm['field_id'] ?? '');
                    $label = sanitize_text_field($fm['label'] ?? '');

                    if (!empty($fid) && !empty($label)) {
                        $sanitized['field_mappings'][] = array(
                            'field_id' => $fid,
                            'label' => $label,
                        );
                    }
                }
            }

            // Sanitize pricing rules.
            if (isset($mapping['pricing_rules']) && is_array($mapping['pricing_rules'])) {
                foreach ($mapping['pricing_rules'] as $rule) {
                    $rfid = sanitize_text_field($rule['field_id'] ?? '');
                    $rval = sanitize_text_field($rule['field_value'] ?? '');
                    $rop = in_array($rule['operator'] ?? '', $valid_operators, true)
                        ? $rule['operator'] : 'add';
                    $ramount = floatval($rule['amount'] ?? 0);

                    if (!empty($rfid)) {
                        $sanitized['pricing_rules'][] = array(
                            'field_id' => $rfid,
                            'field_value' => $rval,
                            'operator' => $rop,
                            'amount' => $ramount,
                        );
                    }
                }
            }

            // Sanitize pendant-specific fields.
            if ('pendant' === $sanitized['calculation_mode']) {
                $sanitized['pendant_metal_weight'] = floatval($mapping['pendant_metal_weight'] ?? 0);
                $sanitized['pendant_user_weight_enabled'] = !empty($mapping['pendant_user_weight_enabled']);

                $sanitized['pendant_available_metals'] = array();
                if (isset($mapping['pendant_available_metals']) && is_array($mapping['pendant_available_metals'])) {
                    $sanitized['pendant_available_metals'] = array_map('sanitize_text_field', $mapping['pendant_available_metals']);
                }

                $sanitized['pendant_available_stones'] = array();
                if (isset($mapping['pendant_available_stones']) && is_array($mapping['pendant_available_stones'])) {
                    $sanitized['pendant_available_stones'] = array_map('sanitize_text_field', $mapping['pendant_available_stones']);
                }

                $sanitized['pendant_max_stones'] = absint($mapping['pendant_max_stones'] ?? 5);

                $sanitized['pendant_available_chains'] = array();
                if (isset($mapping['pendant_available_chains']) && is_array($mapping['pendant_available_chains'])) {
                    $sanitized['pendant_available_chains'] = array_map('sanitize_text_field', $mapping['pendant_available_chains']);
                }

                $sanitized['pendant_chain_lengths'] = array();
                if (isset($mapping['pendant_chain_lengths']) && is_array($mapping['pendant_chain_lengths'])) {
                    $sanitized['pendant_chain_lengths'] = array_map('floatval', $mapping['pendant_chain_lengths']);
                }

                $sanitized['pendant_labor'] = floatval($mapping['pendant_labor'] ?? 0);
                $sanitized['pendant_markup'] = floatval($mapping['pendant_markup'] ?? 0);
                $sanitized['pendant_markup_type'] = in_array($mapping['pendant_markup_type'] ?? '', array('fixed', 'percent'), true)
                    ? $mapping['pendant_markup_type']
                    : 'fixed';
            }

            $sanitized_mappings[] = $sanitized;
        }

        update_option($this->mappings_option, $sanitized_mappings);

        // Clear product cache when mappings change.
        delete_transient('ndvwc_products_cache');

        wp_send_json_success(array('message' => __('Mappings saved.', 'ndv-woo-calculator')));
    }

    /**
     * Handle AJAX request to save global rates (metals, stones, chains).
     *
     * @since 1.1.0
     */
    public function ajax_save_global_rates()
    {
        check_ajax_referer('ndvwc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ndv-woo-calculator')), 403);
        }

        $raw_json = isset($_POST['rates']) ? wp_unslash($_POST['rates']) : '';
        $raw_rates = json_decode($raw_json, true);

        if (!is_array($raw_rates)) {
            wp_send_json_error(array('message' => __('Invalid data format.', 'ndv-woo-calculator')), 400);
        }

        $sanitized = array(
            'metals' => array(),
            'stones' => array(),
            'chains' => array(),
        );

        // Sanitize metals.
        if (isset($raw_rates['metals']) && is_array($raw_rates['metals'])) {
            foreach ($raw_rates['metals'] as $metal) {
                $key  = sanitize_key($metal['key'] ?? '');
                $name = sanitize_text_field($metal['name'] ?? '');
                $ppg  = floatval($metal['price_per_gram'] ?? 0);

                if (!empty($key) && !empty($name)) {
                    $sanitized['metals'][] = array(
                        'key'            => $key,
                        'name'           => $name,
                        'price_per_gram' => $ppg,
                    );
                }
            }
        }

        // Sanitize stones.
        if (isset($raw_rates['stones']) && is_array($raw_rates['stones'])) {
            foreach ($raw_rates['stones'] as $stone) {
                $key  = sanitize_key($stone['key'] ?? '');
                $name = sanitize_text_field($stone['name'] ?? '');
                $ppu  = floatval($stone['price_per_unit'] ?? 0);

                if (!empty($key) && !empty($name)) {
                    $sanitized['stones'][] = array(
                        'key'            => $key,
                        'name'           => $name,
                        'price_per_unit' => $ppu,
                    );
                }
            }
        }

        // Sanitize chains.
        if (isset($raw_rates['chains']) && is_array($raw_rates['chains'])) {
            foreach ($raw_rates['chains'] as $chain) {
                $key  = sanitize_key($chain['key'] ?? '');
                $name = sanitize_text_field($chain['name'] ?? '');
                $rpc  = floatval($chain['rate_per_cm'] ?? 0);
                $mat  = sanitize_text_field($chain['material'] ?? '');

                if (!empty($key) && !empty($name)) {
                    $sanitized['chains'][] = array(
                        'key'         => $key,
                        'name'        => $name,
                        'rate_per_cm' => $rpc,
                        'material'    => $mat,
                    );
                }
            }
        }

        update_option('ndvwc_global_rates', $sanitized);

        wp_send_json_success(array('message' => __('Global rates saved.', 'ndv-woo-calculator')));
    }
}
