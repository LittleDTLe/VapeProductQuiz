<?php
/**
 * Core Logic for VapeVida Quiz. Handles Attribute creation and AJAX handler for filters.
 */

if (!defined('ABSPATH'))
    exit;

// NOTE: VV_QUIZ_VERSION, VV_QUIZ_URL, and VV_QUIZ_TEXT_DOMAIN must be defined in vapevida-quiz.php

/**
 * Create Default WooCommerce Global Attributes
 * Creates pa_geuseis and pa_quiz-ingredient if they don't exist
 */
function vv_create_recommender_attributes()
{
    if (!function_exists('wc_create_attribute')) {
        return;
    }

    // Create pa_geuseis (Flavor Type) if it doesn't exist
    if (!taxonomy_exists('pa_geuseis')) {
        $result = wc_create_attribute(array(
            'name' => 'Τύπος Γεύσης',
            'slug' => 'geuseis',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ));
        if (!is_wp_error($result)) {
            register_taxonomy('pa_geuseis', array('product'));
        }
    }

    // Create pa_quiz-ingredient (Ingredient) if it doesn't exist
    if (!taxonomy_exists('pa_quiz-ingredient')) {
        $result = wc_create_attribute(array(
            'name' => 'Συστατικό (Quiz)',
            'slug' => 'quiz-ingredient',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ));
        if (!is_wp_error($result)) {
            register_taxonomy('pa_quiz-ingredient', array('product'));
        }
    }

    delete_transient('wc_attribute_taxonomies');
    if (function_exists('wc_get_attribute_taxonomies')) {
        wc_get_attribute_taxonomies();
    }
}
add_action('init', 'vv_create_recommender_attributes');


/**
 * AJAX Handler for Cascading Filters AND Result Preview. (P1 & P2 Logic)
 * Returns a delimited string containing the product count ||| and the filtered options HTML.
 */
function vv_ajax_filter_ingredients()
{
    // 1. Get and sanitize the selected Type attribute slug and term
    $type_term_slug = isset($_POST['type_term_slug']) ? sanitize_key($_POST['type_term_slug']) : '';
    $type_slug = isset($_POST['type_slug']) ? sanitize_key($_POST['type_slug']) : '';

    $ingredient_taxonomy = 'pa_quiz-ingredient'; // Fixed taxonomy slug for ingredient
    $options_html = '';
    $product_count = 0;

    if ($type_slug && $type_term_slug) {

        // --- A. BUILD THE COMMON TAX QUERY ---
        $common_tax_query = array(
            array(
                'taxonomy' => $type_slug,
                'field' => 'slug',
                'terms' => $type_term_slug,
                'operator' => 'IN',
            ),
        );

        // --- B. FIND PRODUCTS AND COUNT (Necessary for filtering terms) ---
        $product_args_base = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'tax_query' => $common_tax_query,
            'posts_per_page' => -1,
            'fields' => 'ids',
        );

        $products_query = new WP_Query($product_args_base);
        $product_ids = $products_query->posts;
        $product_count = $products_query->found_posts; // Get the total count

        // --- C. FIND INGREDIENT TERMS ASSOCIATED WITH THOSE PRODUCTS ---
        if ($product_count > 0 && !empty($product_ids)) {
            $terms = get_terms(array(
                'taxonomy' => $ingredient_taxonomy,
                'hide_empty' => true,
                'object_ids' => $product_ids,
                'orderby' => 'name',
            ));

            // --- D. RENDER HTML OPTIONS (Strict Check for Malformed Data) ---
            if (!is_wp_error($terms) && is_array($terms)) {
                foreach ($terms as $term) {
                    if ($term instanceof WP_Term && !empty($term->slug) && !empty($term->name)) {
                        $options_html .= '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                    }
                }
            }
        }
    }

    // --- E. OUTPUT DELIMITED RESPONSE ---
    // Returns: [COUNT] ||| [OPTIONS HTML STRING]
    echo intval($product_count) . '|||' . $options_html;

    wp_die();
}

// Register the AJAX hooks for logged-in and logged-out users
add_action('wp_ajax_vv_filter_ingredients', 'vv_ajax_filter_ingredients');
add_action('wp_ajax_nopriv_vv_filter_ingredients', 'vv_ajax_filter_ingredients');


/**
 * Enqueue Frontend Scripts
 */
function vv_enqueue_frontend_scripts()
{
    // Load only on the frontend and only on the home page (where the shortcode is used)
    if (is_front_page() && !is_admin()) {

        wp_enqueue_script(
            'vv-quiz-frontend-script',
            VV_QUIZ_URL . 'assets/vv-quiz-dynamic.js',
            array('jquery'),
            VV_QUIZ_VERSION,
            true // Load in footer
        );
        // NOTE: wp_localize_script call is in class-vv-frontend.php
    }
}
add_action('wp_enqueue_scripts', 'vv_enqueue_frontend_scripts');