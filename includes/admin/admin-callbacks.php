<?php
/**
 * Admin Callbacks - FULLY LOCALIZED
 */

if (!defined('ABSPATH'))
    exit;

function vv_quiz_required_section_callback()
{
    echo '<p>' . esc_html__('Select which quiz fields should be required.', 'vapevida-quiz') . '</p>';
}

function vv_quiz_checkbox_callback($args)
{
    $options = get_option('vv_quiz_settings');
    $field_id = $args['field_id'];
    $checked = isset($options[$field_id]) ? checked(1, $options[$field_id], false) : '';
    echo '<input type="checkbox" name="vv_quiz_settings[' . esc_attr($field_id) . ']" value="1" ' . $checked . '>';
}

function vv_quiz_main_section_callback()
{
    echo '<p>' . esc_html__('Here you define the labels and structure of the Quiz displayed on the Home Page.', 'vapevida-quiz') . '</p>';
}

/**
 * NEW: Callback for the Error Messages section
 */
function vv_quiz_errors_section_callback()
{
    echo '<p>' . esc_html__('Define the custom error messages for form validation.', 'vapevida-quiz') . '</p>';
}

function vv_quiz_button_styling_section_callback()
{
    echo '<p>' . esc_html__('Customize the colors of the Quiz form buttons (Normal state and Hover).', 'vapevida-quiz') . '</p>';
}

function vv_quiz_custom_attributes_toggle_callback()
{
    $options = get_option('vv_quiz_settings');
    $use_custom = isset($options['use_custom_attributes']) ? $options['use_custom_attributes'] : false;
    $checked = checked(1, $use_custom, false);

    echo '<div class="vv-custom-attributes-toggle">';
    echo '<label>';
    echo '<input type="checkbox" id="use_custom_attributes" name="vv_quiz_settings[use_custom_attributes]" value="1" ' . $checked . ' onchange="vvToggleCustomAttributes(this)" />';
    echo ' <strong>' . esc_html__('Use custom Global Attributes', 'vapevida-quiz') . '</strong>';
    echo '</label>';
    echo '<p class="description">' . esc_html__('Enable this option to select different attributes from the default ones (pa_geuseis and pa_quiz-ingredient).', 'vapevida-quiz') . '</p>';
    echo '</div>';
}

function vv_quiz_status_callback()
{
    $options = get_option('vv_quiz_settings');
    $checked = isset($options['field_status']) ? $options['field_status'] : false;
    echo '<input type="checkbox" name="vv_quiz_settings[field_status]" value="1" ' . checked(1, $checked, false) . ' />';
    echo '<label for="vv_quiz_settings[field_status]">' . esc_html__('Enable 3rd Field (Secondary Ingredient).', 'vapevida-quiz') . '</label>';
}

function vv_quiz_text_field_callback($args)
{
    $options = get_option('vv_quiz_settings');
    $field_id = $args['field_id'];
    $default_value = $args['default'];
    $current_value = isset($options[$field_id]) ? $options[$field_id] : $default_value;

    echo '<input type="text" id="' . esc_attr($field_id) . '" name="vv_quiz_settings[' . esc_attr($field_id) . ']" value="' . esc_attr($current_value) . '" class="regular-text" placeholder="' . esc_attr($default_value) . '" />';
}

function vv_quiz_attribute_select_callback($args)
{
    $options = get_option('vv_quiz_settings');
    $field_id = $args['field_id'];
    $current_slug = isset($options[$field_id]) ? $options[$field_id] : '';
    $use_custom = isset($options['use_custom_attributes']) ? $options['use_custom_attributes'] : false;

    $default_slug = '';
    $field_label = '';
    if ($field_id === 'attribute_type_slug') {
        $default_slug = 'pa_geuseis';
        $field_label = __('Type', 'vapevida-quiz');
    } elseif ($field_id === 'attribute_ingredient_slug') {
        $default_slug = 'pa_quiz-ingredient';
        $field_label = __('Ingredient', 'vapevida-quiz');
    }

    if (!$use_custom && empty($current_slug)) {
        $current_slug = $default_slug;
    }

    if ($field_id === 'attribute_type_slug') {
        echo '<div id="vv-custom-attributes-fields" class="vv-custom-attributes-fields">';
    }

    echo '<div class="vv-attribute-field-row" data-field-id="' . esc_attr($field_id) . '">';

    echo '<p class="vv-default-info" style="background: #f0f0f0; padding: 10px; border-left: 3px solid #2271b1; margin: 0;' . ($use_custom ? ' display:none;' : '') . '">';
    echo '<strong>' . sprintf(
        /* translators: %s: Attribute field label (Type or Ingredient) */
        esc_html__('Default %s:', 'vapevida-quiz'),
        esc_html($field_label)
    ) . '</strong> <code>' . esc_html($default_slug) . '</code>';
    echo '</p>';

    $attributes = wc_get_attribute_taxonomies();

    echo '<div class="vv-attribute-select-wrapper" style="' . (!$use_custom ? 'display:none;' : '') . '">';
    echo '<select name="vv_quiz_settings[' . esc_attr($field_id) . ']" id="' . esc_attr($field_id) . '" class="regular-text vv-attribute-dropdown">';
    echo '<option value="">' . esc_html__('-- Select Attribute --', 'vapevida-quiz') . '</option>';

    if (!empty($attributes)) {
        foreach ($attributes as $attribute) {
            $taxonomy_slug = 'pa_' . $attribute->attribute_name;
            $selected = selected($current_slug, $taxonomy_slug, false);

            echo '<option value="' . esc_attr($taxonomy_slug) . '" ' . $selected . '>';
            echo esc_html($attribute->attribute_label) . ' (' . esc_html($taxonomy_slug) . ')';
            echo '</option>';
        }
    }
    echo '</select>';
    echo '<p class="description">' . esc_html__('Select the Global Attribute that will populate this field.', 'vapevida-quiz') . '</p>';
    echo '</div>';

    echo '</div>';

    if ($field_id === 'attribute_ingredient_slug') {
        echo '</div>';
    }
}

function vv_quiz_color_field_callback($args)
{
    $options = get_option('vv_quiz_settings');
    $field_id = $args['field_id'];
    $default_value = $args['default'];
    $current_value = isset($options[$field_id]) ? $options[$field_id] : $default_value;

    echo '<input type="text" 
            id="' . esc_attr($field_id) . '" 
            name="vv_quiz_settings[' . esc_attr($field_id) . ']" 
            value="' . esc_attr($current_value) . '" 
            class="vv-color-picker" 
            data-default-color="' . esc_attr($default_value) . '" />';
}

function vv_enqueue_color_picker_assets($hook_suffix)
{
    // Ensure this hook suffix matches your admin page
    if ('toplevel_page_vapevida-quiz-details' !== $hook_suffix) {
        return;
    }

    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');

    wp_add_inline_script(
        'wp-color-picker',
        "jQuery(document).ready(function($) {
            $('.vv-color-picker').wpColorPicker();
        });"
    );
}
add_action('admin_enqueue_scripts', 'vv_enqueue_color_picker_assets');