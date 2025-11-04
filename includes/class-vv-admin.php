<?php

/**
 * Admin Logic for VapeVida Quiz.
 * Handles Menu creation, Settings registration (Settings API), and Dashboard rendering.
 */

if (! defined('ABSPATH')) exit;

// --- 5. Add Admin Menu Page (Top-Level Unique Page) ---
function vv_add_plugin_admin_page()
{
    add_menu_page(
        'VapeVida Quiz Details',
        'VapeVida Quiz',
        'manage_options',
        'vapevida-quiz-details',
        'vv_render_details_page',
        'dashicons-clipboard',
        70
    );
}
add_action('admin_menu', 'vv_add_plugin_admin_page');

// --- 7. Add Settings Link to Plugins Page ---
add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__, 2) . '/vapevida-quiz.php'), 'vv_add_plugin_action_links');

function vv_add_plugin_action_links($actions)
{
    $settings_link = '<a href="admin.php?page=vapevida-quiz-details">' . __('Quiz Info & Help', 'vapevida-quiz') . '</a>';
    array_unshift($actions, $settings_link);
    return $actions;
}

// -----------------------------------------------------------
// A. SETTINGS API IMPLEMENTATION & CALLBACKS
// -----------------------------------------------------------

// --- 8. Register Settings Fields ---
function vv_quiz_register_settings()
{
    register_setting('vv_quiz_options_group', 'vv_quiz_settings');

    // 1. REQUIRED FIELDS SECTION (TOP)
    add_settings_section(
        'vv_quiz_required_section',
        'Ρυθμίσεις Απαιτούμενων Πεδίων (Required)',
        'vv_quiz_required_section_callback',
        'vapevida-quiz-details'
    );
    add_settings_field('is_type_required', 'Απαιτείται ο Τύπος Υγρού;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_type_required']);
    add_settings_field('is_primary_required', 'Απαιτείται το Βασικό Συστατικό;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_primary_required']);
    add_settings_field('is_secondary_required', 'Απαιτείται το Δευτερεύον Συστατικό;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_secondary_required']);

    // 2. MAIN FORM / LABELS SECTION
    add_settings_section(
        'vv_quiz_main_section',
        'Ρυθμίσεις Φόρμας και Ετικετών',
        'vv_quiz_main_section_callback',
        'vapevida-quiz-details'
    );

    // Custom Attributes Toggle
    add_settings_field('use_custom_attributes', 'Χρήση Προσαρμοσμένων Attributes', 'vv_quiz_custom_attributes_toggle_callback', 'vapevida-quiz-details', 'vv_quiz_main_section');
    
    // Attribute Selectors (conditional)
    add_settings_field('attribute_type_slug', '1. Attribute για Τύπο', 'vv_quiz_attribute_select_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'attribute_type_slug']);
    add_settings_field('attribute_ingredient_slug', '2. Attribute για Συστατικό', 'vv_quiz_attribute_select_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'attribute_ingredient_slug']);

    // Status / Visibility / CTA / Headers / Labels / Placeholders 
    add_settings_field('field_status', 'Ενεργοποίηση 3ου Πεδίου (Δευτ. Συστατικό)', 'vv_quiz_status_callback', 'vapevida-quiz-details', 'vv_quiz_main_section');
    add_settings_field('quiz_heading', 'Τίτλος Quiz (H2)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'quiz_heading', 'default' => 'Βρες το Ιδανικό Υγρό!']);
    add_settings_field('quiz_subtitle', 'Υπότιτλος Quiz (P)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'quiz_subtitle', 'default' => 'Επίλεξε το προφίλ γεύσης και τα συστατικά που προτιμάς.']);
    add_settings_field('label_type', 'Ετικέτα 1 (Τύπος Υγρού)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'label_type', 'default' => '1. Προφίλ Υγρού:']);
    add_settings_field('label_primary', 'Ετικέτα 2 (Βασικό Συστατικό)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'label_primary', 'default' => '2. Βασικό Συστατικό:']);
    add_settings_field('label_secondary', 'Ετικέτα 3 (Δευτ. Συστατικό)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'label_secondary', 'default' => '3. Δευτερεύον Συστατικό:']);
    add_settings_field('placeholder_type', 'Placeholder 1 (Τύπος Υγρού)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'placeholder_type', 'default' => '-- Επιλέξτε Προφίλ --']);
    add_settings_field('placeholder_primary', 'Placeholder Βασικού Συστατικού', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'placeholder_primary', 'default' => '-- Επιλογή Βασικού Συστατικού --']);
    add_settings_field('placeholder_secondary', 'Placeholder Δευτερεύοντος Συστατικού', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'placeholder_secondary', 'default' => '-- Επιλογή Δευτερεύον Συστατικού --']);
    
    // Button Text Fields
    add_settings_field('button_cta', 'Κείμενο Κουμπιού Αναζήτησης', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'button_cta', 'default' => 'ΒΡΕΣ ΤΟ ΥΓΡΟ ΣΟΥ']);
    add_settings_field('button_clear_cta', 'Κείμενο Κουμπιού Καθαρισμού', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'button_clear_cta', 'default' => 'ΚΑΘΑΡΙΣΜΟΣ']);
    
    // 3. BUTTON STYLING SECTION
    add_settings_section(
        'vv_quiz_button_styling_section',
        'Ρυθμίσεις Χρωμάτων Κουμπιών',
        'vv_quiz_button_styling_section_callback',
        'vapevida-quiz-details'
    );

    // Submit Button Colors
    add_settings_field('btn_bg_color', 'Κουμπί Αναζήτησης - Χρώμα Φόντου', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'btn_bg_color', 'default' => '#e21e51']);
    add_settings_field('btn_txt_color', 'Κουμπί Αναζήτησης - Χρώμα Κειμένου', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'btn_txt_color', 'default' => '#FFFFFF']);
    add_settings_field('btn_bg_hover_color', 'Κουμπί Αναζήτησης - Hover Φόντο', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'btn_bg_hover_color', 'default' => '#4d40ff']);
    add_settings_field('btn_txt_hover_color', 'Κουμπί Αναζήτησης - Hover Κείμενο', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'btn_txt_hover_color', 'default' => '#FFFFFF']);
    
    // Clear Button Colors
    add_settings_field('clear_btn_bg_color', 'Κουμπί Καθαρισμού - Χρώμα Φόντου', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'clear_btn_bg_color', 'default' => '#6c757d']);
    add_settings_field('clear_btn_txt_color', 'Κουμπί Καθαρισμού - Χρώμα Κειμένου', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'clear_btn_txt_color', 'default' => '#FFFFFF']);
    add_settings_field('clear_btn_bg_hover_color', 'Κουμπί Καθαρισμού - Hover Φόντο', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'clear_btn_bg_hover_color', 'default' => '#5a6268']);
    add_settings_field('clear_btn_txt_hover_color', 'Κουμπί Καθαρισμού - Hover Κείμενο', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_button_styling_section', ['field_id' => 'clear_btn_txt_hover_color', 'default' => '#FFFFFF']);
}
add_action('admin_init', 'vv_quiz_register_settings');

// --- 9. Callbacks for Settings Fields ---

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

// UPDATED: Callback to list all Global WooCommerce Attributes (with conditional wrapper)
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
        $field_label = 'Τύπος';
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
    
    // Default value info box (shown when custom is disabled)
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

// -----------------------------------------------------------
// B. DASHBOARD RENDERING (vv_render_details_page)
// -----------------------------------------------------------

function vv_render_details_page()
{
    if (! current_user_can('manage_options')) return;

    $shop_url = get_permalink(wc_get_page_id('shop'));
    $git_url = 'https://github.com/LittleDTLe/VapeProductQuiz';

    $settings = get_option('vv_quiz_settings');
    $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;
    
    // Get actual slugs with defaults
    $chosen_type_slug = $use_custom && isset($settings['attribute_type_slug']) && !empty($settings['attribute_type_slug']) 
        ? esc_html($settings['attribute_type_slug']) 
        : 'pa_geuseis';
    $chosen_ingredient_slug = $use_custom && isset($settings['attribute_ingredient_slug']) && !empty($settings['attribute_ingredient_slug']) 
        ? esc_html($settings['attribute_ingredient_slug']) 
        : 'pa_quiz-ingredient';

    $all_headers = array('Version' => 'Version', 'Author' => 'Author', 'VersionNotes' => 'VersionNotes', 'Features' => 'Features');
    $plugin_data_raw = get_file_data(dirname(__FILE__, 2) . '/vapevida-quiz.php', $all_headers, 'plugin');

    $plugin_version = isset($plugin_data_raw['Version']) ? esc_html($plugin_data_raw['Version']) : 'N/A';
    $plugin_author = isset($plugin_data_raw['Author']) ? esc_html($plugin_data_raw['Author']) : 'Unknown';
    $version_notes_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['VersionNotes']) ? $plugin_data_raw['VersionNotes'] : '')));
    $features_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['Features']) ? $plugin_data_raw['Features'] : '')));

?>
    <style>
        /* Responsive Layout Styles */
        #vv-admin-top-flex {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        #vv-main-content-wrapper {
            flex: 2;
            min-width: 0;
        }

        #vv-sidebar-metadata {
            flex: 1;
            min-width: 300px;
        }

        #vv-guide-section {
            margin-top: 30px;
        }
        
        /* Conditional Attribute Settings */
        .vv-custom-attributes-toggle {
            background: #fff;
            padding: 15px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .vv-custom-attributes-fields {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .vv-custom-attributes-fields.hidden {
            display: none;
        }
        
        .vv-attribute-field-row {
            margin-bottom: 15px;
        }
        
        .vv-attribute-field-row:last-child {
            margin-bottom: 0;
        }

        /* Tablet breakpoint */
        @media screen and (max-width: 1024px) {
            #vv-admin-top-flex {
                gap: 20px;
            }

            #vv-sidebar-metadata {
                min-width: 250px;
            }
        }

        /* Mobile breakpoint - stack vertically */
        @media screen and (max-width: 768px) {
            #vv-admin-top-flex {
                flex-direction: column;
                gap: 20px;
            }

            #vv-main-content-wrapper,
            #vv-sidebar-metadata {
                flex: 1;
                min-width: 100%;
            }

            #vv-guide-section {
                margin-top: 20px;
            }

            #vv-main-instructions .inside>div {
                padding: 12px !important;
            }

            #vv-main-instructions ul {
                padding-left: 15px !important;
            }
        }

        /* Small mobile */
        @media screen and (max-width: 480px) {
            .wrap h1 {
                font-size: 1.5em;
            }

            .about-text {
                font-size: 0.9em;
            }

            #vv-admin-top-flex {
                gap: 15px;
            }

            #vv-settings-form .inside,
            #vv-sidebar-metadata .inside,
            #vv-main-instructions .inside {
                padding: 10px;
            }

            .regular-text {
                max-width: 100%;
            }
        }
    </style>
    
    <script>
        function vvCopyShortcode(button) {
            const shortcode = '[vapevida_quiz]';

            navigator.clipboard.writeText(shortcode).then(function() {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<span class="dashicons dashicons-yes"></span>Αντιγράφηκε!';
                button.classList.add('copied');

                setTimeout(function() {
                    button.innerHTML = originalHTML;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(function(err) {
                console.error('Failed to copy: ', err);
                alert('Αποτυχία αντιγραφής. Παρακαλώ αντιγράψτε χειροκίνητα.');
            });
        }
        
        // Toggle custom attributes visibility (instant, no save required)
        function vvToggleCustomAttributes(checkbox) {
            const customFields = document.getElementById('vv-custom-attributes-fields');
            if (!customFields) return;
            
            // Always show the container
            customFields.classList.remove('hidden');
            
            // Get all default info boxes and select wrappers
            const defaultInfos = customFields.querySelectorAll('.vv-default-info');
            const selectWrappers = customFields.querySelectorAll('.vv-attribute-select-wrapper');
            
            if (checkbox.checked) {
                // Custom mode: hide default info, show dropdowns
                defaultInfos.forEach(function(info) {
                    info.style.display = 'none';
                });
                selectWrappers.forEach(function(wrapper) {
                    wrapper.style.display = 'block';
                });
            } else {
                // Default mode: show default info, hide dropdowns
                defaultInfos.forEach(function(info) {
                    info.style.display = 'block';
                });
                selectWrappers.forEach(function(wrapper) {
                    wrapper.style.display = 'none';
                });
            }
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const useCustomCheckbox = document.getElementById('use_custom_attributes');
            if (useCustomCheckbox) {
                vvToggleCustomAttributes(useCustomCheckbox);
            }
        });
    </script>

    <div class="wrap">
        <h1>VapeVida Flavorshot Recommender Quiz</h1>
        <p class="about-text">Οδηγίες χρήσης και τεχνικές πληροφορίες για τη διαχείριση του custom plugin.</p>

        <hr class="wp-header-end">

        <div id="vv-admin-top-flex">
            <div id="vv-main-content-wrapper">
                <div id="vv-settings-form" class="postbox" style="margin-bottom: 20px;">
                    <h2 class="hndle"><span><span class="dashicons dashicons-admin-generic"></span> Ρυθμίσεις Quiz</span></h2>
                    <div class="inside">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('vv_quiz_options_group');
                            do_settings_sections('vapevida-quiz-details');
                            submit_button();
                            ?>
                        </form>
                    </div>
                </div>
            </div>

            <div id="vv-sidebar-metadata" class="postbox-container">
                <div class="postbox">
                    <h2 class="hndle"><span><span class="dashicons dashicons-info" style="font-size: 1.2em; vertical-align: middle;"></span> Πληροφορίες Plugin</span></h2>
                    <div class="inside">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 8px;"><strong>Συντάκτης:</strong> <span style="font-weight: bold;"><?php echo $plugin_author; ?></span></li>
                            <li style="margin-bottom: 8px;"><strong>Έκδοση Plugin:</strong> <?php echo $plugin_version; ?></li>

                            <li style="margin-top: 15px; margin-bottom: 5px;"><strong>Version Notes:</strong></li>
                            <ul style="padding-left: 20px; margin-top: 0;">
                                <?php
                                foreach ($version_notes_list as $note) {
                                    if (!empty($note)) {
                                        echo '<li><span class="dashicons dashicons-yes-alt" style="color: #4CAF50; font-size: 14px; vertical-align: sub;"></span> ' . esc_html($note) . '</li>';
                                    }
                                }
                                ?>
                            </ul>

                            <li style="margin-top: 15px; margin-bottom: 5px;"><strong>Features:</strong></li>
                            <ul style="padding-left: 20px; margin-top: 0;">
                                <?php
                                foreach ($features_list as $feature) {
                                    if (!empty($feature)) {
                                        echo '<li><span class="dashicons dashicons-star-filled" style="color: #FFC107; font-size: 14px; vertical-align: sub;"></span> ' . esc_html($feature) . '</li>';
                                    }
                                }
                                ?>
                            </ul>

                            <li style="border-top: 1px solid #eee; padding-top: 12px; margin-top: 15px;"><strong>URL Καταστήματος:</strong> <a href="<?php echo esc_url($shop_url); ?>" target="_blank">Άνοιγμα Καταστήματος</a></li>
                            <li style="margin-top: 5px;"><strong>Github Repository:</strong> <a href="<?php echo esc_url($git_url); ?>" target="_blank">Μεταφέρσου στο Github</a></li>
                        </ul>

                        <h3 style="margin-top: 20px;">Slugs Χαρακτηριστικών</h3>
                        <p>Τα απαραίτητα Global Attributes για το Quiz είναι:</p>
                        <ul style="padding-left: 20px;">
                            <li><strong>Τύπος Γεύσης:</strong> <code><?php echo esc_html($chosen_type_slug); ?></code></li>
                            <li><strong>Συστατικό:</strong> <code><?php echo esc_html($chosen_ingredient_slug); ?></code></li>
                        </ul>

                        <h3 style="margin-top: 30px;"><span class="dashicons dashicons-search"></span> Τεχνική Λογική Φίλτρου</h3>
                        <p>Ο κώδικας Query Logic εξασφαλίζει ότι το φιλτράρισμα γίνεται με **απόλυτη ακρίβεια** (AND Logic) ανάμεσα σε όλα τα επιλεγμένα πεδία.</p>
                        <div style="background: #f7f7f7; padding: 15px; border-left: 4px solid #e21e51; margin-top: 15px;">
                            <p><strong>&bull; Τύπος Γεύσης:</strong> `filter_geuseis` (Single Select)</p>
                            <p><strong>&bull; Βασικό & Δευτερεύον Συστατικό:</strong> `filter_quiz-ingredient` (Combined AND Logic)</p>
                            <p style="margin-top: 10px; font-style: italic;">Το σύστημα ελέγχει αν το προϊόν έχει το 'Τύπος Γεύσης' **ΚΑΙ** και τα δύο επιλεγμένα 'Συστατικά' ταυτόχρονα.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- End TOP SECTION -->

        <!-- BOTTOM SECTION: Full Width Guide -->
        <div id="vv-guide-section">
            <div id="vv-main-instructions" class="postbox">
                <h2 class="hndle"><span><span class="dashicons dashicons-book-alt"></span> Οδηγίες Χρήσης & Συντήρηση</span></h2>
                <div class="inside">
                    <p>Αυτό το plugin λειτουργεί ως ένας **Γρήγορος Οδηγός Προϊόντων** (Product Finder). Βασίζεται σε φίλτρα URL για την άμεση εύρεση υγρών από τον πελάτη.</p>

                    <h3><span class="dashicons dashicons-admin-home"></span> Εμφάνιση Quiz & Προσαρμογή Κειμένων</h3>

                    <h4><span class="dashicons dashicons-editor-code"></span> Κωδικός Εμφάνισης (Shortcode)</h4>

                    <div style="background: #f0f0f0; padding: 15px; border-left: 4px solid #4a67b2; margin-bottom: 20px;">
                        <p><strong>Κωδικός Εμφάνισης (Shortcode):</strong> Χρησιμοποιήστε τον παρακάτω κωδικό για να εμφανίσετε τη φόρμα σε οποιαδήποτε σελίδα (π.χ. Αρχική):</p>

                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px;">
                            <code style="flex-grow: 1; padding: 8px 10px; background: #fff; border: 1px dashed #ccc; font-weight: bold; border-radius: 3px;">
                                [vapevida_quiz]
                            </code>

                            <button type="button"
                                class="button button-secondary dashicons-before dashicons-admin-page"
                                style="flex-shrink: 0;"
                                onclick="vvCopyShortcode(this)">
                                Αντιγραφή
                            </button>
                        </div>
                    </div>

                    <p>Όλα τα κείμενα (Τίτλοι, Ετικέτες, CTA) και η δομή του Quiz ρυθμίζονται από την ενότητα <strong>«Ρυθμίσεις Quiz»</strong> που βρίσκεται ακριβώς επάνω:</p>

                    <ul style="padding-left: 20px; color: #555;">
                        <li><span style="font-weight: bold;">Τίτλοι (H2/P):</span> Αλλάξτε το κύριο μήνυμα για εποχιακά ή προωθητικά θέματα.</li>
                        <li><span style="font-weight: bold;">Υποχρεωτικότητα (Required):</span> Ελέγξτε ποια πεδία πρέπει να είναι συμπληρωμένα για την υποβολή της φόρμας.</li>
                        <li><span style="font-weight: bold;">Ενεργοποίηση 3ου Πεδίου:</span> Επιλέξτε εάν το τρίτο πεδίο (Δευτερεύον Συστατικό) εμφανίζεται στη φόρμα.</li>
                    </ul>

                    <h3 style="margin-top: 30px;"><span class="dashicons dashicons-editor-ul"></span> Οδηγός Διαχείρισης Γεύσεων (Βήματα Group B)</h3>
                    <p style="margin-bottom: 15px;">Η προσθήκη νέων επιλογών στο Quiz γίνεται **αυτόματα**, αρκεί να ενημερωθούν οι σωστές ρυθμίσεις στα Global Attributes του WooCommerce.</p>

                    <div style="background: #eef7ff; padding: 15px; border: 1px solid #cceeff; border-radius: 5px;">
                        <p style="font-weight: bold; margin-top: 0;">Βήμα 1: Εύρεση του Σωστού Φακέλου</p>
                        <p style="margin-left: 10px;">Πηγαίνετε: <strong>Προϊόντα &rarr; Χαρακτηριστικά</strong>. Τα δύο Attributes που γεμίζουν το Quiz είναι:</p>
                        <ul style="padding-left: 15px;">
                            <li>**Τύπος Υγρού (π.χ. Γλυκές, Καπνικές):** <code>pa_geuseis</code></li>
                            <li>**Συστατικό (π.χ. Φράουλα, Κρέμα):** <code>pa_quiz-ingredient</code></li>
                        </ul>
                    </div>

                    <div style="background: #fdf5e6; padding: 15px; border: 1px solid #ffaa00; border-radius: 5px; margin-top: 10px;">
                        <p style="font-weight: bold; margin-top: 0;">Βήμα 2: Προσθήκη Νέου Όρου (Term)</p>
                        <p style="margin-left: 10px;">Κάντε κλικ στο **"Ρύθμιση όρων"** (Configure Terms) δίπλα στο Attribute **Συστατικό (Quiz)**.</p>
                        <ul style="padding-left: 15px; list-style-type: square;">
                            <li>**Ονομασία (Name):** Γράψτε την πλήρη ονομασία (π.χ. 'Ακτινίδιο') και ένα καθαρό slug (π.χ. 'actinidio').</li>
                        </ul>
                    </div>

                    <div style="background: #f0fff0; padding: 15px; border: 1px solid #3c763d; border-radius: 5px; margin-top: 10px;">
                        <p style="font-weight: bold; margin-top: 0;">Βήμα 3: Σύνδεση με Προϊόν & Έλεγχος</p>
                        <p style="margin-left: 10px;">Για να εμφανιστεί ο νέος Όρος στο Quiz, πρέπει να είναι αντιστοιχισμένος σε τουλάχουν ένα δημοσιευμένο προϊόν.</p>
                        <p style="margin-left: 10px; color: #d9534f; font-weight: bold;">
                            <span class="dashicons dashicons-warning" style="font-size: 1.2em; vertical-align: middle;"></span> **Πολύ Σημαντικό:** Αν ένας όρος **δεν** έχει προϊόντα, το Quiz τον αγνοεί (για να μην οδηγεί σε κενά αποτελέσματα).
                        </p>
                    </div>
                </div>
            </div>
        </div><!-- End BOTTOM SECTION -->
    </div>
<?php
}