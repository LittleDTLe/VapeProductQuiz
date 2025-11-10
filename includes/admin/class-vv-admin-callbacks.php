<?php
/**
 * Admin Settings Callbacks
 * 
 * Handles all callback functions for settings fields rendering.
 * Each method is responsible for outputting the HTML for a specific field type.
 *
 * @package VapeVida_Quiz
 * @since 0.9.3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Callbacks Class
 */
class VV_Admin_Callbacks
{

    /**
     * Get current settings
     * 
     * @return array
     */
    private function get_settings()
    {
        return get_option(VV_Admin_Settings::get_option_name(), array());
    }

    /**
     * Required Fields Section Description
     */
    public function required_section_callback()
    {
        echo '<p>' . esc_html__('Select which quiz fields should be required.', 'vapevida-quiz') . '</p>';
    }

    /**
     * Main Section Description
     */
    public function main_section_callback()
    {
        echo '<p>' . esc_html__('Here you define the labels and structure of the Quiz displayed on the Home Page.', 'vapevida-quiz') . '</p>';
    }

    /**
     * Button Styling Section Description
     */
    public function button_styling_section_callback()
    {
        echo '<p>' . esc_html__('Customize the colors of the Quiz form buttons (Normal state and Hover).', 'vapevida-quiz') . '</p>';
    }

    /**
     * Checkbox Field Callback
     * 
     * @param array $args Field arguments
     */
    public function checkbox_callback($args)
    {
        $options = $this->get_settings();
        $field_id = $args['field_id'];
        $checked = isset($options[$field_id]) ? checked(1, $options[$field_id], false) : '';

        printf(
            '<input type="checkbox" name="vv_quiz_settings[%s]" value="1" %s>',
            esc_attr($field_id),
            $checked
        );
    }

    /**
     * Text Field Callback
     * 
     * @param array $args Field arguments including 'field_id' and 'default'
     */
    public function text_field_callback($args)
    {
        $options = $this->get_settings();
        $field_id = $args['field_id'];
        $default_value = $args['default'];
        $current_value = isset($options[$field_id]) ? $options[$field_id] : $default_value;

        printf(
            '<input type="text" id="%s" name="vv_quiz_settings[%s]" value="%s" class="regular-text" placeholder="%s" />',
            esc_attr($field_id),
            esc_attr($field_id),
            esc_attr($current_value),
            esc_attr($default_value)
        );
    }

    /**
     * Color Field Callback
     * 
     * @param array $args Field arguments including 'field_id' and 'default'
     */
    public function color_field_callback($args)
    {
        $options = $this->get_settings();
        $field_id = $args['field_id'];
        $default_value = $args['default'];
        $current_value = isset($options[$field_id]) ? $options[$field_id] : $default_value;

        printf(
            '<input type="text" id="%s" name="vv_quiz_settings[%s]" value="%s" class="vv-color-picker" data-default-color="%s" />',
            esc_attr($field_id),
            esc_attr($field_id),
            esc_attr($current_value),
            esc_attr($default_value)
        );
    }

    /**
     * Custom Attributes Toggle Callback
     */
    public function custom_attributes_toggle_callback()
    {
        $options = $this->get_settings();
        $use_custom = isset($options['use_custom_attributes']) ? $options['use_custom_attributes'] : false;
        $checked = checked(1, $use_custom, false);

        echo '<div class="vv-custom-attributes-toggle">';
        echo '<label>';
        printf(
            '<input type="checkbox" id="use_custom_attributes" name="vv_quiz_settings[use_custom_attributes]" value="1" %s onchange="vvToggleCustomAttributes(this)" />',
            $checked
        );
        echo ' <strong>' . esc_html__('Use custom Global Attributes', 'vapevida-quiz') . '</strong>';
        echo '</label>';
        echo '<p class="description">' . esc_html__('Enable this option to select different attributes from the default ones (pa_geuseis and pa_quiz-ingredient).', 'vapevida-quiz') . '</p>';
        echo '</div>';
    }

    /**
     * Field Status (3rd Field Enable) Callback
     */
    public function status_callback()
    {
        $options = $this->get_settings();
        $checked = isset($options['field_status']) ? $options['field_status'] : false;

        printf(
            '<input type="checkbox" name="vv_quiz_settings[field_status]" value="1" %s />',
            checked(1, $checked, false)
        );
        echo '<label for="vv_quiz_settings[field_status]">' . esc_html__('Enable 3rd Field (Secondary Ingredient).', 'vapevida-quiz') . '</label>';
    }

    /**
     * Attribute Select Callback
     * Renders dropdown for selecting WooCommerce Global Attributes
     * 
     * @param array $args Field arguments
     */
    public function attribute_select_callback($args)
    {
        $options = $this->get_settings();
        $field_id = $args['field_id'];
        $current_slug = isset($options[$field_id]) ? $options[$field_id] : '';
        $use_custom = isset($options['use_custom_attributes']) ? $options['use_custom_attributes'] : false;

        // Set defaults based on field
        $default_slug = '';
        $field_label = '';
        if ($field_id === 'attribute_type_slug') {
            $default_slug = 'pa_geuseis';
            $field_label = __('Type', 'vapevida-quiz');
        } elseif ($field_id === 'attribute_ingredient_slug') {
            $default_slug = 'pa_quiz-ingredient';
            $field_label = __('Ingredient', 'vapevida-quiz');
        }

        // If custom attributes not enabled, use default
        if (!$use_custom && empty($current_slug)) {
            $current_slug = $default_slug;
        }

        // Open conditional wrapper ONLY for the first field
        if ($field_id === 'attribute_type_slug') {
            echo '<div id="vv-custom-attributes-fields" class="vv-custom-attributes-fields">';
        }

        echo '<div class="vv-attribute-field-row" data-field-id="' . esc_attr($field_id) . '">';

        // Default value info box (shown when custom is disabled)
        echo '<p class="vv-default-info" style="background: #f0f0f0; padding: 10px; border-left: 3px solid #2271b1; margin: 0;' . ($use_custom ? ' display:none;' : '') . '">';
        /* translators: %s: Attribute field label (Type or Ingredient) */
        echo '<strong>' . sprintf(esc_html__('Default %s:', 'vapevida-quiz'), esc_html($field_label)) . '</strong> <code>' . esc_html($default_slug) . '</code>';
        echo '</p>';

        // Always render the select dropdown
        $attributes = wc_get_attribute_taxonomies();

        echo '<div class="vv-attribute-select-wrapper" style="' . (!$use_custom ? 'display:none;' : '') . '">';
        printf(
            '<select name="vv_quiz_settings[%s]" id="%s" class="regular-text vv-attribute-dropdown">',
            esc_attr($field_id),
            esc_attr($field_id)
        );
        echo '<option value="">' . esc_html__('-- Select Attribute --', 'vapevida-quiz') . '</option>';

        if (!empty($attributes)) {
            foreach ($attributes as $attribute) {
                $taxonomy_slug = 'pa_' . $attribute->attribute_name;
                $selected = selected($current_slug, $taxonomy_slug, false);

                printf(
                    '<option value="%s" %s>%s (%s)</option>',
                    esc_attr($taxonomy_slug),
                    $selected,
                    esc_html($attribute->attribute_label),
                    esc_html($taxonomy_slug)
                );
            }
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Select the Global Attribute that will populate this field.', 'vapevida-quiz') . '</p>';
        echo '</div>'; // Close vv-attribute-select-wrapper

        echo '</div>'; // Close vv-attribute-field-row

        // Close conditional wrapper ONLY after the second field
        if ($field_id === 'attribute_ingredient_slug') {
            echo '</div>'; // Close vv-custom-attributes-fields
        }
    }
}