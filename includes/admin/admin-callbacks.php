<?php
/**
 * Admin Callbacks.
 * Contains all functions responsible for rendering fields and handling specific logic (like color pickers).
 */

if (!defined('ABSPATH'))
    exit;

function vv_quiz_required_section_callback()
{
    echo '<p>Επιλέξτε ποια από τα πεδία του Quiz πρέπει να είναι υποχρεωτικά.</p>';
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
    echo '<p>Εδώ ορίζετε τις ετικέτες και τη δομή του Quiz που εμφανίζεται στην Αρχική Σελίδα.</p>';
}

function vv_quiz_button_styling_section_callback()
{
    echo '<p>Προσαρμόστε τα χρώματα των κουμπιών της φόρμας Quiz (Κανονική κατάσταση και Hover).</p>';
}

// Custom Attributes Toggle Callback
function vv_quiz_custom_attributes_toggle_callback()
{
    $options = get_option('vv_quiz_settings');
    $use_custom = isset($options['use_custom_attributes']) ? $options['use_custom_attributes'] : false;
    $checked = checked(1, $use_custom, false);

    echo '<div class="vv-custom-attributes-toggle">';
    echo '<label>';
    echo '<input type="checkbox" id="use_custom_attributes" name="vv_quiz_settings[use_custom_attributes]" value="1" ' . $checked . ' onchange="vvToggleCustomAttributes(this)" />';
    echo ' <strong>Χρήση προσαρμοσμένων Global Attributes</strong>';
    echo '</label>';
    echo '<p class="description">Ενεργοποιήστε αυτή την επιλογή για να επιλέξετε διαφορετικά attributes από τα προεπιλεγμένα (pa_geuseis και pa_quiz-ingredient).</p>';
    echo '</div>';
}

function vv_quiz_status_callback()
{
    $options = get_option('vv_quiz_settings');
    $checked = isset($options['field_status']) ? $options['field_status'] : false;
    echo '<input type="checkbox" name="vv_quiz_settings[field_status]" value="1" ' . checked(1, $checked, false) . ' />';
    echo '<label for="vv_quiz_settings[field_status]">Ενεργοποίηση 3ου Πεδίου (Δευτ. Συστατικό).</label>';
}

function vv_quiz_text_field_callback($args)
{
    $options = get_option('vv_quiz_settings');
    $field_id = $args['field_id'];
    $default_value = $args['default'];
    $current_value = isset($options[$field_id]) ? $options[$field_id] : $default_value;

    echo '<input type="text" id="' . esc_attr($field_id) . '" name="vv_quiz_settings[' . esc_attr($field_id) . ']" value="' . esc_attr($current_value) . '" class="regular-text" placeholder="' . esc_attr($default_value) . '" />';
}


// --- Callback to list all Global WooCommerce Attributes ---
function vv_quiz_attribute_select_callback($args)
{
    $options = get_option('vv_quiz_settings');
    $field_id = $args['field_id'];
    $current_slug = isset($options[$field_id]) ? $options[$field_id] : '';
    $use_custom = isset($options['use_custom_attributes']) ? $options['use_custom_attributes'] : false;

    // Set defaults based on field
    $default_slug = '';
    $field_label = '';
    if ($field_id === 'attribute_type_slug') {
        $default_slug = 'pa_geuseis';
        $field_label = 'Τύπου';
    } elseif ($field_id === 'attribute_ingredient_slug') {
        $default_slug = 'pa_quiz-ingredient';
        $field_label = 'Συστατικό';
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

    // 1. Default value info box (shown when custom is disabled)
    echo '<p class="vv-default-info" style="background: #f0f0f0; padding: 10px; border-left: 3px solid #2271b1; margin: 0;' . ($use_custom ? ' display:none;' : '') . '">';
    echo '<strong>Προεπιλογή ' . esc_html($field_label) . ':</strong> <code>' . esc_html($default_slug) . '</code>';
    echo '</p>';

    // Always render the select dropdown
    $attributes = wc_get_attribute_taxonomies();

    echo '<div class="vv-attribute-select-wrapper" style="' . (!$use_custom ? 'display:none;' : '') . '">';
    echo '<select name="vv_quiz_settings[' . esc_attr($field_id) . ']" id="' . esc_attr($field_id) . '" class="regular-text vv-attribute-dropdown">';
    echo '<option value="">-- Επιλέξτε Attribute --</option>';

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
    echo '<p class="description">Επιλέξτε τον Global Attribute που θα γεμίσει αυτό το πεδίο.</p>';
    echo '</div>'; // Close vv-attribute-select-wrapper

    echo '</div>'; // Close vv-attribute-field-row

    // Close conditional wrapper ONLY after the second field
    if ($field_id === 'attribute_ingredient_slug') {
        echo '</div>'; // Close vv-custom-attributes-fields
    }
}

// Callback for Color Picker fields
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

// Enqueue the Color Picker Assets and Initialization Script
function vv_enqueue_color_picker_assets($hook_suffix)
{
    if ('toplevel_page_vapevida-quiz-details' !== $hook_suffix && 'vapevida-quiz-details' !== $hook_suffix) {
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