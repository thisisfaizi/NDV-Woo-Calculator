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
                'price_field_id' => $mapping['price_field_id'],
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
            <button type="button" class="<?php echo esc_attr($btn_class); ?>"
                data-form-id="<?php echo esc_attr($form_id); ?>"
                data-product-id="<?php echo esc_attr($mapping['product_id']); ?>"
                data-price-field="<?php echo esc_attr($mapping['price_field_id']); ?>" disabled>
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
}
