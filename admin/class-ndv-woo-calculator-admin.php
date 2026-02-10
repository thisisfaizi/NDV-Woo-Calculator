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
 * and form-to-product mapping management.
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

        // General Settings section.
        add_settings_section(
            'ndvwc_general',
            __('General Settings', 'ndv-woo-calculator'),
            array($this, 'render_general_section'),
            'ndv-woo-calculator'
        );

        // Enable/Disable.
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

        // Debug Mode.
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

        // Submission Type.
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

        // Clear Hidden Fields.
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
            <input type="checkbox" name="<?php echo esc_attr($this->option_name . '[' . $field . ']'); ?>" value="yes" <?php checked($checked); ?>
            />
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
     * Handle AJAX request to save form mappings.
     *
     * @since 1.0.0
     */
    public function ajax_save_mappings()
    {
        check_ajax_referer('ndvwc_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized.', 'ndv-woo-calculator')), 403);
        }

        $raw_mappings = isset($_POST['mappings']) ? wp_unslash($_POST['mappings']) : array();

        if (!is_array($raw_mappings)) {
            wp_send_json_error(array('message' => __('Invalid data.', 'ndv-woo-calculator')), 400);
        }

        $sanitized_mappings = array();

        foreach ($raw_mappings as $mapping) {
            $sanitized_mapping = array(
                'form_id' => sanitize_text_field($mapping['form_id'] ?? ''),
                'product_id' => absint($mapping['product_id'] ?? 0),
                'price_field_id' => sanitize_text_field($mapping['price_field_id'] ?? ''),
                'field_mappings' => array(),
            );

            // Skip if essential fields are empty.
            if (empty($sanitized_mapping['form_id']) || 0 === $sanitized_mapping['product_id']) {
                continue;
            }

            // Sanitize field mappings sub-array.
            if (isset($mapping['field_mappings']) && is_array($mapping['field_mappings'])) {
                foreach ($mapping['field_mappings'] as $field_map) {
                    $field_id = sanitize_text_field($field_map['field_id'] ?? '');
                    $label = sanitize_text_field($field_map['label'] ?? '');

                    if (!empty($field_id) && !empty($label)) {
                        $sanitized_mapping['field_mappings'][] = array(
                            'field_id' => $field_id,
                            'label' => $label,
                        );
                    }
                }
            }

            $sanitized_mappings[] = $sanitized_mapping;
        }

        update_option($this->mappings_option, $sanitized_mappings);

        // Clear product cache when mappings change.
        delete_transient('ndvwc_products_cache');

        wp_send_json_success(array('message' => __('Mappings saved.', 'ndv-woo-calculator')));
    }
}
