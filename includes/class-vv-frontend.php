<?php
/**
 * Frontend Logic for VapeVida Quiz.
 * This file is now a CONTROLLER.
 * It handles the shortcode, gets the data, and loads the view.
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Main Shortcode function
 *
 * @return string The HTML for the quiz
 */
function vv_recommender_quiz_shortcode()
{
    // --- 1. GET ALL SETTINGS ---
    $settings = get_option('vv_quiz_settings');

    $show_third_field = isset($settings['field_status']) && $settings['field_status'];

    $quiz_heading = isset($settings['quiz_heading']) ? esc_html($settings['quiz_heading']) : __('Find Your Perfect Liquid!', VV_QUIZ_TEXT_DOMAIN);
    $quiz_subtitle = isset($settings['quiz_subtitle']) ? esc_html($settings['quiz_subtitle']) : __('Select the flavor profile and ingredients you prefer.', VV_QUIZ_TEXT_DOMAIN);
    $label_type = isset($settings['label_type']) ? esc_html($settings['label_type']) : __('1. Liquid Profile:', VV_QUIZ_TEXT_DOMAIN);
    $label_primary = isset($settings['label_primary']) ? esc_html($settings['label_primary']) : __('2. Primary Ingredient:', VV_QUIZ_TEXT_DOMAIN);
    $label_secondary = isset($settings['label_secondary']) ? esc_html($settings['label_secondary']) : __('3. Secondary Ingredient:', VV_QUIZ_TEXT_DOMAIN);
    $cta_button_text = isset($settings['button_cta']) ? esc_html($settings['button_cta']) : __('FIND YOUR LIQUID', VV_QUIZ_TEXT_DOMAIN);
    $clear_button_text = isset($settings['button_clear_cta']) ? esc_html($settings['button_clear_cta']) : __('CLEAR', VV_QUIZ_TEXT_DOMAIN);

    $placeholder_type = isset($settings['placeholder_type']) ? esc_attr($settings['placeholder_type']) : __('-- Select Profile --', VV_QUIZ_TEXT_DOMAIN);
    $placeholder_primary = isset($settings['placeholder_primary']) ? esc_attr($settings['placeholder_primary']) : __('-- Select Primary Ingredient --', VV_QUIZ_TEXT_DOMAIN);
    $placeholder_secondary = isset($settings['placeholder_secondary']) ? esc_attr($settings['placeholder_secondary']) : __('-- Select Secondary Ingredient --', VV_QUIZ_TEXT_DOMAIN);

    // Get required status from settings
    $is_type_required_attr = !empty($settings['is_type_required']) ? 'required' : '';
    $is_primary_required_attr = !empty($settings['is_primary_required']) ? 'required' : '';
    $is_secondary_required_attr = !empty($settings['is_secondary_required']) && $show_third_field ? 'required' : '';

    $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;
    $type_taxonomy_slug = $use_custom && isset($settings['attribute_type_slug']) && !empty($settings['attribute_type_slug'])
        ? $settings['attribute_type_slug']
        : 'pa_geuseis';
    $ingredient_taxonomy_slug = $use_custom && isset($settings['attribute_ingredient_slug']) && !empty($settings['attribute_ingredient_slug'])
        ? $settings['attribute_ingredient_slug']
        : 'pa_quiz-ingredient';

    $form_filter_type_name = str_replace('pa_', 'filter_', $type_taxonomy_slug);
    $form_filter_ingredient_name = str_replace('pa_', 'filter_', $ingredient_taxonomy_slug);

    $shop_url = get_permalink(wc_get_page_id('shop'));

    $flavor_type_terms = get_terms(array(
        'taxonomy' => $type_taxonomy_slug,
        'hide_empty' => true,
    ));

    if (empty($type_taxonomy_slug) || empty($ingredient_taxonomy_slug) || is_wp_error($flavor_type_terms)) {
        return '<p style="color: red;">' . __('[Configuration Error]: Please configure the Global Attributes in the Quiz Info page.', VV_QUIZ_TEXT_DOMAIN) . '</p>';
    }

    // --- 2. GET DYNAMIC COLOR STYLES ---
    $btn_bg_color = isset($settings['btn_bg_color']) ? esc_html($settings['btn_bg_color']) : '#e21e51';
    $btn_bg_hover_color = isset($settings['btn_bg_hover_color']) ? esc_html($settings['btn_bg_hover_color']) : '#c91a48';
    $btn_txt_color = isset($settings['btn_txt_color']) ? esc_html($settings['btn_txt_color']) : '#FFFFFF';
    $btn_txt_hover_color = isset($settings['btn_txt_hover_color']) ? esc_html($settings['btn_txt_hover_color']) : '#FFFFFF';
    $clear_btn_bg_color = isset($settings['clear_btn_bg_color']) ? esc_html($settings['clear_btn_bg_color']) : '#6c757d';
    $clear_btn_bg_hover_color = isset($settings['clear_btn_bg_hover_color']) ? esc_html($settings['clear_btn_bg_hover_color']) : '#5a6268';
    $clear_btn_txt_color = isset($settings['clear_btn_txt_color']) ? esc_html($settings['clear_btn_txt_color']) : '#FFFFFF';
    $clear_btn_txt_hover_color = isset($settings['clear_btn_txt_hover_color']) ? esc_html($settings['clear_btn_txt_hover_color']) : '#FFFFFF';

    // --- 3. LOAD THE VIEW ---
    ob_start();

    // All variables defined above ($quiz_heading, $btn_bg_color, etc.)
    // are now available to the included view file.
    include(VV_QUIZ_DIR . 'includes/views/frontend/quiz-form-view.php');

    return ob_get_clean();
}
add_shortcode('vapevida_quiz', 'vv_recommender_quiz_shortcode');