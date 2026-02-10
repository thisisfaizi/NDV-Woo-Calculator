<?php
/**
 * Public-facing functionality — shortcode and frontend assets.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/public
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Public
 *
 * Registers the shortcode, enqueues frontend assets, and passes
 * configuration data to the frontend JavaScript.
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Public
{

    /**
     * Initialize public hooks.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_shortcode('ndv_woo_add_to_cart', array($this, 'render_shortcode'));
        add_shortcode('ndv_pendant_configurator', array($this, 'render_pendant_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Enqueue frontend assets on pages that have Elementor content.
     *
     * @since 1.0.0
     */
    public function enqueue_assets()
    {

        // Only load if plugin is enabled.
        if (!NDV_Woo_Calculator_Config_Manager::is_enabled()) {
            return;
        }

        $mappings = NDV_Woo_Calculator_Config_Manager::get_mappings();

        // Don't load if no mappings configured.
        if (empty($mappings)) {
            return;
        }

        wp_enqueue_style(
            'ndvwc-public',
            NDVWC_PLUGIN_URL . 'public/css/ndv-woo-calculator-public.css',
            array(),
            NDVWC_VERSION
        );

        wp_enqueue_script(
            'ndvwc-public',
            NDVWC_PLUGIN_URL . 'public/js/ndv-woo-calculator-public.js',
            array('jquery'),
            NDVWC_VERSION,
            true
        );

        // Prepare mappings for frontend (only form_id, product_id, price_field_id, field IDs).
        $frontend_mappings = array();
        foreach ($mappings as $mapping) {
            $field_ids = array();
            if (!empty($mapping['field_mappings'])) {
                foreach ($mapping['field_mappings'] as $fm) {
                    $field_ids[] = $fm['field_id'];
                }
            }
            $frontend_mappings[] = array(
                'form_id' => $mapping['form_id'],
                'product_id' => absint($mapping['product_id']),
                'price_field_id' => $mapping['price_field_id'] ?? '',
                'calculation_mode' => $mapping['calculation_mode'] ?? 'elementor',
                'field_ids' => $field_ids,
            );
        }

        $settings = NDV_Woo_Calculator_Config_Manager::get_settings();

        wp_localize_script(
            'ndvwc-public',
            'ndvwcFrontend',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ndvwc_nonce'),
                'mappings' => $frontend_mappings,
                'clear_hidden_fields' => 'yes' === $settings['clear_hidden_fields'],
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'cart_url' => wc_get_cart_url(),
                'debug' => NDV_Woo_Calculator_Config_Manager::is_debug(),
                'i18n' => array(
                    'add_to_cart' => __('Add to Cart', 'ndv-woo-calculator'),
                    'adding' => __('Adding...', 'ndv-woo-calculator'),
                    'added' => __('Added to Cart!', 'ndv-woo-calculator'),
                    'error' => __('Error adding to cart.', 'ndv-woo-calculator'),
                    'view_cart' => __('View Cart', 'ndv-woo-calculator'),
                ),
            )
        );
    }

    /**
     * Render the add-to-cart shortcode.
     *
     * @since  1.0.0
     * @param  array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_shortcode($atts)
    {

        if (!NDV_Woo_Calculator_Config_Manager::is_enabled()) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'form_id' => '',
                'btn_text' => __('Add to Cart', 'ndv-woo-calculator'),
                'btn_class' => '',
            ),
            $atts,
            'ndv_woo_add_to_cart'
        );

        $form_id = sanitize_text_field($atts['form_id']);

        if (empty($form_id)) {
            return '';
        }

        // Verify mapping exists for this form.
        $mapping = NDV_Woo_Calculator_Config_Manager::get_mapping_by_form_id($form_id);
        if (!$mapping) {
            return '';
        }

        $btn_class = 'ndvwc-add-to-cart-btn';
        if (!empty($atts['btn_class'])) {
            $btn_class .= ' ' . sanitize_html_class($atts['btn_class']);
        }

        ob_start();
        ?>
        <div class="ndvwc-add-to-cart-wrapper" data-form-id="<?php echo esc_attr($form_id); ?>">
            <button type="button" class="<?php echo esc_attr($btn_class); ?>" data-form-id="<?php echo esc_attr($form_id); ?>"
                data-product-id="<?php echo esc_attr($mapping['product_id']); ?>"
                data-price-field="<?php echo esc_attr($mapping['price_field_id'] ?? ''); ?>"
                data-calc-mode="<?php echo esc_attr($mapping['calculation_mode'] ?? 'elementor'); ?>" disabled>
                <span class="ndvwc-btn-text">
                    <?php echo esc_html($atts['btn_text']); ?>
                </span>
                <span class="ndvwc-btn-price"></span>
            </button>
            <div class="ndvwc-btn-feedback" style="display:none;"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render the pendant configurator shortcode.
     *
     * Usage: [ndv_pendant_configurator product_id="123"]
     *
     * @since  1.1.0
     * @param  array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render_pendant_shortcode($atts)
    {
        if (!NDV_Woo_Calculator_Config_Manager::is_enabled()) {
            return '';
        }

        $atts = shortcode_atts(
            array(
                'product_id' => '',
                'btn_text' => __('Add to Cart', 'ndv-woo-calculator'),
            ),
            $atts,
            'ndv_pendant_configurator'
        );

        $product_id = absint($atts['product_id']);
        if (0 === $product_id) {
            return '';
        }

        // Find pendant mapping for this product.
        $mappings = NDV_Woo_Calculator_Config_Manager::get_mappings();
        $mapping = null;
        foreach ($mappings as $m) {
            if (absint($m['product_id'] ?? 0) === $product_id && 'pendant' === ($m['calculation_mode'] ?? '')) {
                $mapping = $m;
                break;
            }
        }

        if (!$mapping) {
            return '<!-- NDVWC: No pendant configuration for product ' . esc_html($product_id) . ' -->';
        }

        // Retrieve global rates.
        $global_rates = NDV_Woo_Calculator_Config_Manager::get_global_rates();
        $all_metals = $global_rates['metals'] ?? array();
        $all_stones = $global_rates['stones'] ?? array();
        $all_chains = $global_rates['chains'] ?? array();

        // Filter to only available items.
        $avail_metal_keys = $mapping['pendant_available_metals'] ?? array();
        $avail_stone_keys = $mapping['pendant_available_stones'] ?? array();
        $avail_chain_keys = $mapping['pendant_available_chains'] ?? array();
        $chain_lengths = $mapping['pendant_chain_lengths'] ?? array();
        $max_stones = absint($mapping['pendant_max_stones'] ?? 5);
        $user_weight_enabled = !empty($mapping['pendant_user_weight_enabled']);
        $initial_weight = floatval($mapping['pendant_metal_weight'] ?? 0);

        $metals = array_filter($all_metals, function ($m) use ($avail_metal_keys) {
            return in_array($m['key'], $avail_metal_keys, true);
        });
        $stones = array_filter($all_stones, function ($s) use ($avail_stone_keys) {
            return in_array($s['key'], $avail_stone_keys, true);
        });
        $chains = array_filter($all_chains, function ($c) use ($avail_chain_keys) {
            return in_array($c['key'], $avail_chain_keys, true);
        });

        // Enqueue pendant-specific JS data.
        wp_enqueue_script('ndvwc-public');
        wp_localize_script('ndvwc-public', 'ndvwcPendant_' . $product_id, array(
            'product_id' => $product_id,
            'form_id' => $mapping['form_id'] ?? '',
            'metals' => array_values($metals),
            'stones' => array_values($stones),
            'chains' => array_values($chains),
            'chain_lengths' => $chain_lengths,
            'max_stones' => $max_stones,
            'user_weight_enabled' => $user_weight_enabled,
            'initial_weight' => $initial_weight,
        ));

        ob_start();
        ?>
        <div class="ndvwc-pendant-configurator" data-product-id="<?php echo esc_attr($product_id); ?>">

            <!-- User Weight Input -->
            <?php if ($user_weight_enabled): ?>
                <div class="ndvwc-pendant-section ndvwc-pendant-weight">
                    <label class="ndvwc-pendant-label"><?php esc_html_e('Metal Weight (g)', 'ndv-woo-calculator'); ?></label>
                    <input type="number" step="0.01" min="0" class="ndvwc-pendant-input ndvwc-pendant-weight-input"
                        value="<?php echo esc_attr($initial_weight); ?>" />
                </div>
            <?php endif; ?>

            <!-- Metal Selection -->
            <?php if (!empty($metals)): ?>
                <div class="ndvwc-pendant-section ndvwc-pendant-metals">
                    <label class="ndvwc-pendant-label"><?php esc_html_e('Metal Type', 'ndv-woo-calculator'); ?></label>
                    <select class="ndvwc-pendant-select ndvwc-pendant-metal-select">
                        <option value=""><?php esc_html_e('— Select Metal —', 'ndv-woo-calculator'); ?></option>
                        <?php foreach ($metals as $metal): ?>
                            <option value="<?php echo esc_attr($metal['key']); ?>">
                                <?php echo esc_html($metal['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Stone Selection -->
            <?php if (!empty($stones)): ?>
                <div class="ndvwc-pendant-section ndvwc-pendant-stones">
                    <label class="ndvwc-pendant-label">
                        <?php esc_html_e('Stones', 'ndv-woo-calculator'); ?>
                        <small>(<?php printf(
                            /* translators: %d: maximum number of stones */
                            esc_html__('max %d', 'ndv-woo-calculator'),
                            $max_stones
                        ); ?>)</small>
                    </label>
                    <div class="ndvwc-pendant-stone-rows">
                        <div class="ndvwc-pendant-stone-row">
                            <select class="ndvwc-pendant-select ndvwc-pendant-stone-select">
                                <option value=""><?php esc_html_e('— Select Stone —', 'ndv-woo-calculator'); ?></option>
                                <?php foreach ($stones as $stone): ?>
                                    <option value="<?php echo esc_attr($stone['key']); ?>">
                                        <?php echo esc_html($stone['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="number" class="ndvwc-pendant-stone-qty" min="0" max="<?php echo esc_attr($max_stones); ?>"
                                value="0" />
                        </div>
                    </div>
                    <button type="button" class="ndvwc-pendant-add-stone-btn">
                        <?php esc_html_e('+ Add Another Stone', 'ndv-woo-calculator'); ?>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Chain Selection -->
            <?php if (!empty($chains)): ?>
                <div class="ndvwc-pendant-section ndvwc-pendant-chains">
                    <label class="ndvwc-pendant-label"><?php esc_html_e('Chain', 'ndv-woo-calculator'); ?></label>
                    <select class="ndvwc-pendant-select ndvwc-pendant-chain-select">
                        <option value="none"><?php esc_html_e('No Chain', 'ndv-woo-calculator'); ?></option>
                        <?php foreach ($chains as $chain): ?>
                            <option value="<?php echo esc_attr($chain['key']); ?>">
                                <?php echo esc_html($chain['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <?php if (!empty($chain_lengths)): ?>
                        <label class="ndvwc-pendant-label ndvwc-pendant-chain-length-label" style="display:none;">
                            <?php esc_html_e('Chain Length (cm)', 'ndv-woo-calculator'); ?>
                        </label>
                        <select class="ndvwc-pendant-select ndvwc-pendant-chain-length-select" style="display:none;">
                            <?php foreach ($chain_lengths as $len): ?>
                                <option value="<?php echo esc_attr($len); ?>">
                                    <?php echo esc_html($len . ' cm'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <!-- Price Display -->
            <div class="ndvwc-pendant-section ndvwc-pendant-price-section">
                <div class="ndvwc-pendant-price-display">
                    <span
                        class="ndvwc-pendant-price-label"><?php esc_html_e('Estimated Price:', 'ndv-woo-calculator'); ?></span>
                    <span class="ndvwc-pendant-price-value">—</span>
                </div>
                <div class="ndvwc-pendant-breakdown" style="display:none;"></div>
            </div>

            <!-- Add to Cart -->
            <button type="button" class="ndvwc-pendant-add-to-cart-btn" data-product-id="<?php echo esc_attr($product_id); ?>"
                disabled>
                <?php echo esc_html($atts['btn_text']); ?>
            </button>
            <div class="ndvwc-pendant-feedback" style="display:none;"></div>

        </div>
        <?php
        return ob_get_clean();
    }
}
