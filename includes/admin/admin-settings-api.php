<?php
/**
 * Settings API Registration Structure - FULLY LOCALIZED
 */

if (!defined('ABSPATH'))
    exit;

function vv_quiz_register_settings()
{
    register_setting('vv_quiz_options_group', 'vv_quiz_settings');

    // 1. REQUIRED FIELDS SECTION
    add_settings_section(
        'vv_quiz_required_section',
        __('Required Fields Settings', 'vapevida-quiz'),
        'vv_quiz_required_section_callback',
        'vapevida-quiz-details'
    );

    add_settings_field(
        'is_type_required',
        __('Is Liquid Type Required?', 'vapevida-quiz'),
        'vv_quiz_checkbox_callback',
        'vapevida-quiz-details',
        'vv_quiz_required_section',
        ['field_id' => 'is_type_required']
    );

    add_settings_field(
        'is_primary_required',
        __('Is Primary Ingredient Required?', 'vapevida-quiz'),
        'vv_quiz_checkbox_callback',
        'vapevida-quiz-details',
        'vv_quiz_required_section',
        ['field_id' => 'is_primary_required']
    );

    add_settings_field(
        'is_secondary_required',
        __('Is Secondary Ingredient Required?', 'vapevida-quiz'),
        'vv_quiz_checkbox_callback',
        'vapevida-quiz-details',
        'vv_quiz_required_section',
        ['field_id' => 'is_secondary_required']
    );

    // 2. MAIN FORM / LABELS SECTION
    add_settings_section(
        'vv_quiz_main_section',
        __('Form and Labels Settings', 'vapevida-quiz'),
        'vv_quiz_main_section_callback',
        'vapevida-quiz-details'
    );

    // Custom Attributes Toggle
    add_settings_field(
        'use_custom_attributes',
        __('Use Custom Attributes', 'vapevida-quiz'),
        'vv_quiz_custom_attributes_toggle_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section'
    );

    // Attribute Selectors
    add_settings_field(
        'attribute_type_slug',
        __('1. Attribute for Type', 'vapevida-quiz'),
        'vv_quiz_attribute_select_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'attribute_type_slug']
    );

    add_settings_field(
        'attribute_ingredient_slug',
        __('2. Attribute for Ingredient', 'vapevida-quiz'),
        'vv_quiz_attribute_select_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'attribute_ingredient_slug']
    );

    // COLOR PICKERS
    add_settings_field(
        'btn_bg_color',
        __('Button Color (Background)', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'btn_bg_color', 'default' => '#e21e51']
    );

    add_settings_field(
        'btn_txt_color',
        __('Button Color (Text)', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'btn_txt_color', 'default' => '#FFFFFF']
    );

    add_settings_field(
        'btn_bg_hover_color',
        __('Hover Color (Background)', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'btn_bg_hover_color', 'default' => '#c91a48']
    );

    add_settings_field(
        'btn_txt_hover_color',
        __('Hover Color (Text)', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'btn_txt_hover_color', 'default' => '#FFFFFF']
    );

    // Clear Button Colors
    add_settings_field(
        'clear_btn_bg_color',
        __('Clear Button - Background Color', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'clear_btn_bg_color', 'default' => '#6c757d']
    );

    add_settings_field(
        'clear_btn_txt_color',
        __('Clear Button - Text Color', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'clear_btn_txt_color', 'default' => '#FFFFFF']
    );

    add_settings_field(
        'clear_btn_bg_hover_color',
        __('Clear Button - Hover Background', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'clear_btn_bg_hover_color', 'default' => '#5a6268']
    );

    add_settings_field(
        'clear_btn_txt_hover_color',
        __('Clear Button - Hover Text', 'vapevida-quiz'),
        'vv_quiz_color_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'clear_btn_txt_hover_color', 'default' => '#FFFFFF']
    );

    // Status / Visibility / CTA / Headers / Labels / Placeholders 
    add_settings_field(
        'field_status',
        __('Enable 3rd Field (Secondary Ingredient)', 'vapevida-quiz'),
        'vv_quiz_status_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section'
    );

    add_settings_field(
        'quiz_heading',
        __('Quiz Title (H2)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'quiz_heading', 'default' => __('Find Your Perfect Liquid!', 'vapevida-quiz')]
    );

    add_settings_field(
        'quiz_subtitle',
        __('Quiz Subtitle (P)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'quiz_subtitle', 'default' => __('Select the flavor profile and ingredients you prefer.', 'vapevida-quiz')]
    );

    add_settings_field(
        'label_type',
        __('Label 1 (Liquid Type)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'label_type', 'default' => __('1. Liquid Profile:', 'vapevida-quiz')]
    );

    add_settings_field(
        'label_primary',
        __('Label 2 (Primary Ingredient)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'label_primary', 'default' => __('2. Primary Ingredient:', 'vapevida-quiz')]
    );

    add_settings_field(
        'label_secondary',
        __('Label 3 (Secondary Ingredient)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'label_secondary', 'default' => __('3. Secondary Ingredient:', 'vapevida-quiz')]
    );

    add_settings_field(
        'placeholder_type',
        __('Placeholder 1 (Liquid Type)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'placeholder_type', 'default' => __('-- Select Profile --', 'vapevida-quiz')]
    );

    add_settings_field(
        'placeholder_primary',
        __('Primary Ingredient Placeholder', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'placeholder_primary', 'default' => __('-- Select Primary Ingredient --', 'vapevida-quiz')]
    );

    add_settings_field(
        'placeholder_secondary',
        __('Secondary Ingredient Placeholder', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'placeholder_secondary', 'default' => __('-- Select Secondary Ingredient --', 'vapevida-quiz')]
    );

    add_settings_field(
        'button_cta',
        __('Button Text (CTA)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'button_cta', 'default' => __('FIND YOUR LIQUID', 'vapevida-quiz')]
    );

    add_settings_field(
        'button_clear_cta',
        __('Button Text (CLEAR)', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_main_section',
        ['field_id' => 'button_clear_cta', 'default' => __('CLEAR', 'vapevida-quiz')]
    );

    // --- NEW: ERROR MESSAGES SECTION ---
    add_settings_section(
        'vv_quiz_errors_section',
        __('Error Messages', 'vapevida-quiz'),
        'vv_quiz_errors_section_callback',
        'vapevida-quiz-details'
    );

    add_settings_field(
        'error_msg_type',
        __('Error: Type Required', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_errors_section',
        ['field_id' => 'error_msg_type', 'default' => __('Please select a Flavor Type.', 'vapevida-quiz')]
    );

    add_settings_field(
        'error_msg_primary',
        __('Error: Primary Required', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_errors_section',
        ['field_id' => 'error_msg_primary', 'default' => __('Please select a Primary Ingredient.', 'vapevida-quiz')]
    );

    add_settings_field(
        'error_msg_secondary',
        __('Error: Secondary Required', 'vapevida-quiz'),
        'vv_quiz_text_field_callback',
        'vapevida-quiz-details',
        'vv_quiz_errors_section',
        ['field_id' => 'error_msg_secondary', 'default' => __('Please select a Secondary Ingredient.', 'vapevida-quiz')]
    );
}
add_action('admin_init', 'vv_quiz_register_settings');