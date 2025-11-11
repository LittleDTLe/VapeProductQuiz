<?php
/**
 * Core Logic for VapeVida Quiz. Handles Attribute creation.
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Create Default WooCommerce Global Attributes
 * Creates pa_geuseis and pa_quiz-ingredient if they don't exist
 */
function vv_create_recommender_attributes()
{
    // Make sure WooCommerce attribute functions are available
    if (!function_exists('wc_create_attribute')) {
        return;
    }

    // Create pa_geuseis (Flavor Type) if it doesn't exist
    if (!taxonomy_exists('pa_geuseis')) {
        $result = wc_create_attribute(array(
            'name' => __('Flavor Type', 'vapevida-quiz'),  // Translatable name!
            'slug' => 'geuseis',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ));

        // Register the taxonomy immediately after creation
        if (!is_wp_error($result)) {
            register_taxonomy('pa_geuseis', array('product'));
        }
    }

    // Create pa_quiz-ingredient (Ingredient) if it doesn't exist
    if (!taxonomy_exists('pa_quiz-ingredient')) {
        $result = wc_create_attribute(array(
            'name' => __('Ingredient (Quiz)', 'vapevida-quiz'), // Translatable!
            'slug' => 'quiz-ingredient',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ));

        if (!is_wp_error($result)) {
            register_taxonomy('pa_quiz-ingredient', array('product'));
        }
    }

    // Clear attribute cache
    delete_transient('wc_attribute_taxonomies');

    // Force WooCommerce to refresh attribute cache
    if (function_exists('wc_get_attribute_taxonomies')) {
        wc_get_attribute_taxonomies();
    }
}

add_action('init', 'vv_create_recommender_attributes');

/**
 * AJAX Handler for Cascading Filters.
 * Finds available terms in the Ingredient attribute based on the selected Type.
 */
function vv_ajax_filter_ingredients()
{
    // START OUTPUT BUFFERING HERE to catch any extraneous spaces/warnings
    ob_start();

    // 1. Get and sanitize the selected Type attribute slug and term
    $type_slug = isset($_POST['type_slug']) ? sanitize_key($_POST['type_slug']) : '';
    $type_term_slug = isset($_POST['type_term_slug']) ? sanitize_key($_POST['type_term_slug']) : '';

    $ingredient_taxonomy = 'pa_quiz-ingredient';
    $options_html = '';

    if ($type_slug && $type_term_slug) {
        // --- A. FIND PRODUCTS MATCHING THE SELECTED TYPE ---
        $product_args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'fields' => 'ids', // We only need the IDs
            'tax_query' => array(
                array(
                    'taxonomy' => $type_slug,
                    'field' => 'slug',
                    'terms' => $type_term_slug,
                    'operator' => 'IN',
                ),
            ),
        );
        $product_ids = get_posts($product_args);

        // --- B. FIND INGREDIENT TERMS ASSOCIATED WITH THOSE PRODUCTS ---
        if (!empty($product_ids)) {
            $terms = get_terms(array(
                'taxonomy' => $ingredient_taxonomy,
                'hide_empty' => true,
                'object_ids' => $product_ids, // Filter terms by the product IDs found
                'orderby' => 'name',
            ));

            // --- C. RENDER HTML OPTIONS ---
            if (!is_wp_error($terms) && is_array($terms)) {
                foreach ($terms as $term) {

                    // CRITICAL FIX: Ensure the object is a valid WP_Term instance AND that 
                    // the required properties (slug and name) are not empty strings.
                    if ($term instanceof WP_Term && !empty($term->slug) && !empty($term->name)) {
                        $options_html .= '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                    }
                }
            }
        }
    }

    // // Always include the default placeholder option
    // $settings = get_option('vv_quiz_settings');
    // $placeholder_secondary = isset($settings['placeholder_secondary']) ? esc_attr($settings['placeholder_secondary']) : '-- Select Secondary Ingredient --';
    // $placeholder_primary = isset($settings['placeholder_primary']) ? esc_attr($settings['placeholder_primary']) : '-- Select Primary Ingredient --';

    // Output the clean HTML directly
    echo $options_html;

    // Capture the buffer content and discard any output before the intended content
    $final_output = ob_get_clean();

    // Print only the final, clean output and terminate execution
    echo $final_output;

    wp_die();
}

// Register the AJAX hooks for logged-in and logged-out users
add_action('wp_ajax_vv_filter_ingredients', 'vv_ajax_filter_ingredients');
add_action('wp_ajax_nopriv_vv_filter_ingredients', 'vv_ajax_filter_ingredients');

function vv_enqueue_frontend_scripts()
{
    // Load only on the frontend and only on the home page (where the shortcode is used)
    if (is_front_page() && !is_admin()) {

        wp_enqueue_script(
            'vv-quiz-frontend-script',
            VV_QUIZ_URL . 'assets/vv-quiz-dynamic.js', // We will create this file in Step 3
            array('jquery'),
            VV_QUIZ_VERSION,
            true // Load in footer
        );
        // The wp_localize_script call must be in the shortcode function itself.
    }
}
add_action('wp_enqueue_scripts', 'vv_enqueue_frontend_scripts');

