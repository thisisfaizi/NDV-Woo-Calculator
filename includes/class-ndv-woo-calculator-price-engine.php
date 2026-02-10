<?php
/**
 * Pricing calculation engine.
 *
 * Applies admin-defined pricing rules to form data to compute a dynamic price.
 *
 * @package    NDV_Woo_Calculator
 * @subpackage NDV_Woo_Calculator/includes
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class NDV_Woo_Calculator_Price_Engine
 *
 * Evaluates pricing rules against submitted form data.
 *
 * Rules are processed in order. Each rule:
 *   - field_id:    which form field to check
 *   - field_value: the value to match (* = any non-empty value)
 *   - operator:    add | multiply | set | add_percent
 *   - amount:      the numeric amount to apply
 *
 * Supports checkbox fields (arrays/comma-separated values):
 *   A rule matches if the match_value is one of the selected checkbox values.
 *   For example, field "stone" with value ["stone 1", "stone 2"] will match
 *   a rule with field_value "stone 1".
 *
 * When operator is "multiply" and field_value is "*", the actual field value
 * is used as the multiplier instead of the rule amount (useful for quantity-style
 * fields like "number of stones").
 *
 * @since 1.0.0
 */
class NDV_Woo_Calculator_Price_Engine
{

    /**
     * Debug log for last calculation.
     *
     * @var array
     */
    private static $debug_log = array();

    /**
     * Calculate the final price by applying rules to form data.
     *
     * @since  1.0.0
     * @param  array $mapping   The mapping configuration (with base_price and pricing_rules).
     * @param  array $form_data Key-value pairs of form field_id => submitted value.
     * @return float The calculated price.
     */
    public static function calculate($mapping, $form_data)
    {
        self::$debug_log = array();
        $price = floatval($mapping['base_price'] ?? 0);
        $rules = $mapping['pricing_rules'] ?? array();

        self::log('Base price: ' . $price);
        self::log('Form data received: ' . wp_json_encode($form_data));
        self::log('Rules count: ' . count($rules));

        if (empty($rules)) {
            self::log('No rules defined, returning base price.');
            return $price;
        }

        foreach ($rules as $index => $rule) {
            $field_id = $rule['field_id'] ?? '';
            $match_value = $rule['field_value'] ?? '';
            $operator = $rule['operator'] ?? 'add';
            $amount = floatval($rule['amount'] ?? 0);

            // Skip if no field ID configured.
            if (empty($field_id)) {
                self::log("Rule #{$index}: skipped (no field_id)");
                continue;
            }

            // Get the submitted value for this field (handles arrays and strings).
            $submitted_value = self::get_field_value($form_data, $field_id);

            self::log("Rule #{$index}: field={$field_id}, match={$match_value}, op={$operator}, amt={$amount}, submitted=" . wp_json_encode($submitted_value));

            // Check if the rule matches.
            $matches = self::rule_matches($match_value, $submitted_value);

            if (!$matches) {
                self::log("Rule #{$index}: NO MATCH");
                continue;
            }

            $old_price = $price;

            // Determine the effective submitted value for operators.
            $effective_value = is_array($submitted_value)
                ? implode(', ', $submitted_value)
                : $submitted_value;

            // Apply the operator.
            $price = self::apply_operator($price, $operator, $amount, $effective_value, $match_value);

            self::log("Rule #{$index}: MATCHED — price {$old_price} → {$price}");
        }

        /**
         * Filter the final calculated price.
         *
         * @since 1.0.0
         * @param float $price     The calculated price.
         * @param array $mapping   The mapping configuration.
         * @param array $form_data The submitted form data.
         */
        $price = apply_filters('ndvwc_calculated_price', $price, $mapping, $form_data);

        // Ensure price is never negative.
        $final_price = max(0, round($price, 2));

        self::log('Final price: ' . $final_price);

        // Write debug to WP debug log if debug mode is on.
        if (NDV_Woo_Calculator_Config_Manager::is_debug() && defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log('[NDVWC Price Engine] ' . implode(' | ', self::$debug_log));
        }

        return $final_price;
    }

    /**
     * Get the debug log from the last calculation.
     *
     * @since  1.0.0
     * @return array
     */
    public static function get_debug_log()
    {
        return self::$debug_log;
    }

    /**
     * Get a field's submitted value, handling arrays (checkboxes) and strings.
     *
     * @since  1.0.0
     * @param  array  $form_data All submitted data.
     * @param  string $field_id  The field ID to look up.
     * @return string|array The submitted value (string for simple fields, array for checkboxes).
     */
    private static function get_field_value($form_data, $field_id)
    {
        if (!isset($form_data[$field_id])) {
            return '';
        }

        $value = $form_data[$field_id];

        // If it's an array (checkboxes send arrays), sanitize each element.
        if (is_array($value)) {
            return array_map('sanitize_text_field', array_filter($value));
        }

        // If it's a comma-separated string (some checkbox implementations), split it.
        $sanitized = trim(sanitize_text_field($value));

        return $sanitized;
    }

    /**
     * Check if a rule's match value matches the submitted value.
     *
     * Handles both string and array (checkbox) values:
     *   - String: exact case-insensitive match.
     *   - Array:  match if the rule value is one of the array items.
     *
     * @since  1.0.0
     * @param  string       $match_value     Rule's expected value (* = any non-empty).
     * @param  string|array $submitted_value The actual submitted value.
     * @return bool
     */
    private static function rule_matches($match_value, $submitted_value)
    {
        // Normalize submitted value for emptiness check.
        $is_empty = self::is_value_empty($submitted_value);

        // Wildcard: match any non-empty value.
        if ('*' === $match_value) {
            return !$is_empty;
        }

        // If submitted value is an array (checkboxes), check if match_value is in the array.
        if (is_array($submitted_value)) {
            foreach ($submitted_value as $item) {
                if (strtolower(trim($item)) === strtolower(trim($match_value))) {
                    return true;
                }
            }
            return false;
        }

        // Some checkbox plugins send comma-separated values.
        if (strpos($submitted_value, ',') !== false) {
            $parts = array_map('trim', explode(',', $submitted_value));
            foreach ($parts as $part) {
                if (strtolower($part) === strtolower($match_value)) {
                    return true;
                }
            }
            return false;
        }

        // Exact match (case-insensitive).
        return strtolower(trim($match_value)) === strtolower(trim($submitted_value));
    }

    /**
     * Check if a submitted value is empty (works for strings and arrays).
     *
     * @since  1.0.0
     * @param  string|array $value The value to check.
     * @return bool
     */
    private static function is_value_empty($value)
    {
        if (is_array($value)) {
            return empty(array_filter($value, function ($v) {
                return '' !== trim($v);
            }));
        }
        return '' === trim((string) $value);
    }

    /**
     * Apply a pricing operator.
     *
     * Operators that modify the RUNNING PRICE:
     *   - add:         price += amount
     *   - multiply:    price *= amount
     *   - set:         price = amount
     *   - add_percent: price += (price × amount / 100)
     *
     * Operators that compute from the ENTERED FIELD VALUE:
     *   - field_multiply: price += (entered_value × amount)
     *   - field_add:      price += (entered_value + amount)
     *
     * @since  1.0.0
     * @param  float  $price           Current price.
     * @param  string $operator        Operator name.
     * @param  float  $amount          The rule's configured amount.
     * @param  string $submitted_value The submitted field value.
     * @param  string $match_value     The rule's match value.
     * @return float  Modified price.
     */
    private static function apply_operator($price, $operator, $amount, $submitted_value, $match_value)
    {
        $field_numeric = is_numeric($submitted_value) ? floatval($submitted_value) : 0;

        switch ($operator) {
            case 'add':
                $price += $amount;
                break;

            case 'multiply':
                $price *= $amount;
                break;

            case 'set':
                $price = $amount;
                break;

            case 'add_percent':
                $price += ($price * $amount / 100);
                break;

            case 'field_multiply':
                // entered_value × amount → result added to price
                $price += ($field_numeric * $amount);
                break;

            case 'field_add':
                // entered_value + amount → result added to price
                $price += ($field_numeric + $amount);
                break;
        }

        return $price;
    }

    /**
     * Add a message to the debug log.
     *
     * @since  1.0.0
     * @param  string $message Debug message.
     */
    private static function log($message)
    {
        self::$debug_log[] = $message;
    }
}
