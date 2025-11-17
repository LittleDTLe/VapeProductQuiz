<?php
/**
 * Core Logic for VapeVida Quiz. Handles Attribute creation and AJAX handler for filters.
 */

if (!defined('ABSPATH'))
    exit;

if (!defined('VV_QUIZ_VERSION')) {
    define('VV_QUIZ_VERSION', '0.9.9');
}
if (!defined('VV_QUIZ_TEXT_DOMAIN')) {
    define('VV_QUIZ_TEXT_DOMAIN', 'vapevida-quiz');
}
if (!defined('VV_QUIZ_URL')) {
    define('VV_QUIZ_URL', plugin_dir_url(dirname(__FILE__)));
}

/**
 * Create Default WooCommerce Global Attributes
 */
function vv_create_recommender_attributes()
{
    if (!function_exists('wc_create_attribute')) {
        return;
    }

    if (!taxonomy_exists('pa_geuseis')) {
        $result = wc_create_attribute(array(
            'name' => __('Flavor Type', VV_QUIZ_TEXT_DOMAIN),
            'slug' => 'geuseis',
            'type' => 'select',
            'order_by' => 'menu_order',
            'has_archives' => false,
        ));
        if (!is_wp_error($result)) {
            register_taxonomy('pa_geuseis', array('product'));
        }
    }

    if (!taxonomy_exists('pa_quiz-ingredient')) {
        $result = wc_create_attribute(array(
            'name' => __('Ingredient (Quiz)', VV_QUIZ_TEXT_DOMAIN),
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
 * AJAX Handler - Smart Cascading with Real-Time Product Count
 * * Returns: JSON object: { success: true, data: { count: 0, primaryOptions: [], secondaryOptions: [] } }
 */
function vv_ajax_filter_ingredients()
{
    // Security check
    if (!isset($_POST['security']) || !check_ajax_referer('vv-quiz-nonce', 'security', false)) {
        wp_send_json_error(array('message' => 'Nonce verification failed'), 403);
        wp_die();
    }

    // Get all filter values
    $type_term_slug = isset($_POST['type_term_slug']) ? sanitize_key($_POST['type_term_slug']) : '';
    $type_slug = isset($_POST['type_slug']) ? sanitize_key($_POST['type_slug']) : '';
    $primary_ingredient = isset($_POST['primary_ingredient']) ? sanitize_key($_POST['primary_ingredient']) : '';
    $secondary_ingredient = isset($_POST['secondary_ingredient']) ? sanitize_key($_POST['secondary_ingredient']) : '';

    // ==================================================================
    // FIX: Get ingredient taxonomy from POST data
    // ==================================================================
    $ingredient_taxonomy_slug = isset($_POST['ingredient_slug']) && !empty($_POST['ingredient_slug'])
        ? sanitize_key($_POST['ingredient_slug'])
        : 'pa_quiz-ingredient';
    // ==================================================================


    // --- MODIFICATION: Initialize arrays for JSON response ---
    $primary_options = array();
    $secondary_options = array();
    $product_count = 0;

    if (!$type_slug || !$type_term_slug) {
        wp_send_json_error(array('message' => 'Type missing or invalid'), 400);
        wp_die();
    }

    // --- 1. GET PRODUCT COUNT (BASED ON ALL 3 FILTERS) ---
    $tax_query = array(
        'relation' => 'AND',
        array(
            'taxonomy' => $type_slug,
            'field' => 'slug',
            'terms' => $type_term_slug,
            'operator' => 'IN',
        ),
    );

    // Add primary ingredient to query if selected
    if (!empty($primary_ingredient)) {
        $tax_query[] = array(
            'taxonomy' => $ingredient_taxonomy_slug, // Use dynamic slug
            'field' => 'slug',
            'terms' => $primary_ingredient,
            'operator' => 'IN',
        );
    }

    // Add secondary ingredient to query if selected
    if (!empty($secondary_ingredient)) {
        $tax_query[] = array(
            'taxonomy' => $ingredient_taxonomy_slug, // Use dynamic slug
            'field' => 'slug',
            'terms' => $secondary_ingredient,
            'operator' => 'IN',
        );
    }

    $product_args_count = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'tax_query' => $tax_query,
        'posts_per_page' => 1, // Only need count, not all posts
        'fields' => 'ids',
    );

    $products_query = new WP_Query($product_args_count);
    $product_count = $products_query->found_posts;

    // --- 2. BUILD PRIMARY INGREDIENT OPTIONS (BASED ON TYPE ONLY) ---
    $primary_tax_query = array(
        array(
            'taxonomy' => $type_slug,
            'field' => 'slug',
            'terms' => $type_term_slug,
            'operator' => 'IN',
        ),
    );

    $primary_product_args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'tax_query' => $primary_tax_query,
        'posts_per_page' => -1,
        'fields' => 'ids',
    );

    $primary_products_query = new WP_Query($primary_product_args);
    $primary_product_ids = $primary_products_query->posts;

    if (!empty($primary_product_ids)) {
        $primary_terms = get_terms(array(
            'taxonomy' => $ingredient_taxonomy_slug, // Use dynamic slug
            'hide_empty' => true,
            'object_ids' => $primary_product_ids,
            'orderby' => 'name',
        ));

        if (!is_wp_error($primary_terms) && is_array($primary_terms)) {
            foreach ($primary_terms as $term) {
                if ($term instanceof WP_Term && !empty($term->slug) && !empty($term->name)) {
                    // --- MODIFICATION: Add to array instead of HTML string ---
                    $primary_options[] = array(
                        'value' => $term->slug,
                        'text' => $term->name
                    );
                }
            }
        }
    }


    // --- 3. BUILD SECONDARY INGREDIENT OPTIONS (FILTERED BY TYPE + PRIMARY) ---
    $secondary_tax_query = array(
        'relation' => 'AND',
        array(
            'taxonomy' => $type_slug,
            'field' => 'slug',
            'terms' => $type_term_slug,
            'operator' => 'IN',
        ),
    );

    // If primary is selected, secondary options MUST be compatible with it
    if (!empty($primary_ingredient)) {
        $secondary_tax_query[] = array(
            'taxonomy' => $ingredient_taxonomy_slug, // Use dynamic slug
            'field' => 'slug',
            'terms' => $primary_ingredient,
            'operator' => 'IN',
        );
    }

    $secondary_product_args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'tax_query' => $secondary_tax_query,
        'posts_per_page' => -1,
        'fields' => 'ids',
    );

    $secondary_products_query = new WP_Query($secondary_product_args);
    $secondary_product_ids = $secondary_products_query->posts;

    if (!empty($secondary_product_ids)) {
        $secondary_terms = get_terms(array(
            'taxonomy' => $ingredient_taxonomy_slug, // Use dynamic slug
            'hide_empty' => true,
            'object_ids' => $secondary_product_ids,
            'orderby' => 'name',
        ));

        if (!is_wp_error($secondary_terms) && is_array($secondary_terms)) {
            foreach ($secondary_terms as $term) {
                // Exclude the primary ingredient from secondary options
                if ($term instanceof WP_Term && !empty($term->slug) && !empty($term->name) && $term->slug !== $primary_ingredient) {
                    // --- MODIFICATION: Add to array instead of HTML string ---
                    $secondary_options[] = array(
                        'value' => $term->slug,
                        'text' => $term->name
                    );
                }
            }
        }
    }

    // --- FIX: Send a proper SUCCESS JSON response ---
    // This wraps the array in { "success": true, "data": { ... } }
    wp_send_json_success(array(
        'count' => intval($product_count),
        'primaryOptions' => $primary_options,
        'secondaryOptions' => $secondary_options
    ));

    wp_die(); // This is good practice after wp_send_json_success

}

add_action('wp_ajax_vv_filter_ingredients', 'vv_ajax_filter_ingredients');
add_action('wp_ajax_nopriv_vv_filter_ingredients', 'vv_ajax_filter_ingredients');


/**
 * Clear Caches When Plugin Settings Are Saved
 * Automatically purges LiteSpeed, WP Rocket, and other caches when settings are updated.
 */
function vv_quiz_clear_caches_on_save($old_value, $new_value)
{
    // LiteSpeed Cache
    if (function_exists('litespeed_purge_all')) {
        litespeed_purge_all();
    }
    // WP Rocket
    if (function_exists('rocket_clean_domain')) {
        rocket_clean_domain();
    }
    // W3 Total Cache
    if (function_exists('w3tc_flush_all')) {
        w3tc_flush_all();
    }
    // WP Super Cache
    if (function_exists('wp_cache_clear_cache')) {
        wp_cache_clear_cache();
    }
    // WordPress Object Cache
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
}
add_action('update_option_vv_quiz_settings', 'vv_quiz_clear_caches_on_save', 10, 2);