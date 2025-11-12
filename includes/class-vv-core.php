<?php
/**
 * Core Logic for VapeVida Quiz. Handles Attribute creation and AJAX handler for filters.
 */

if (!defined('ABSPATH'))
    exit;

if (!defined('VV_QUIZ_VERSION')) {
    // IMPORTANT: Make sure this is updated every time you change the JS file!
    define('VV_QUIZ_VERSION', '0.9.4'); // Bumping version for new JS/PHP logic
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
 * * Returns: count|||primaryOptions|||secondaryOptions
 */
function vv_ajax_filter_ingredients()
{
    // Security check
    if (!isset($_POST['security']) || !check_ajax_referer('vv-quiz-nonce', 'security', false)) {
        echo '0|||Nonce verification failed|||';
        wp_die();
    }

    // Get all filter values
    $type_term_slug = isset($_POST['type_term_slug']) ? sanitize_key($_POST['type_term_slug']) : '';
    $type_slug = isset($_POST['type_slug']) ? sanitize_key($_POST['type_slug']) : '';
    $primary_ingredient = isset($_POST['primary_ingredient']) ? sanitize_key($_POST['primary_ingredient']) : '';
    $secondary_ingredient = isset($_POST['secondary_ingredient']) ? sanitize_key($_POST['secondary_ingredient']) : '';

    $ingredient_taxonomy = 'pa_quiz-ingredient';
    $primary_options_html = '';
    $secondary_options_html = '';
    $product_count = 0;

    if (!$type_slug || !$type_term_slug) {
        echo '0|||Type missing|||';
        wp_die();
        return;
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
            'taxonomy' => $ingredient_taxonomy,
            'field' => 'slug',
            'terms' => $primary_ingredient,
            'operator' => 'IN',
        );
    }

    // Add secondary ingredient to query if selected
    if (!empty($secondary_ingredient)) {
        $tax_query[] = array(
            'taxonomy' => $ingredient_taxonomy,
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
    // Primary options should only filter by the selected Type, and then the JS will preserve the current selection.
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
            'taxonomy' => $ingredient_taxonomy,
            'hide_empty' => true,
            'object_ids' => $primary_product_ids,
            'orderby' => 'name',
        ));

        if (!is_wp_error($primary_terms) && is_array($primary_terms)) {
            foreach ($primary_terms as $term) {
                if ($term instanceof WP_Term && !empty($term->slug) && !empty($term->name)) {
                    $primary_options_html .= '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
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
            'taxonomy' => $ingredient_taxonomy,
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
            'taxonomy' => $ingredient_taxonomy,
            'hide_empty' => true,
            'object_ids' => $secondary_product_ids,
            'orderby' => 'name',
        ));

        if (!is_wp_error($secondary_terms) && is_array($secondary_terms)) {
            foreach ($secondary_terms as $term) {
                // Exclude the primary ingredient from secondary options
                if ($term instanceof WP_Term && !empty($term->slug) && !empty($term->name) && $term->slug !== $primary_ingredient) {
                    $secondary_options_html .= '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                }
            }
        }
    }

    // --- OUTPUT: count|||primaryOptions|||secondaryOptions ---
    echo intval($product_count) . '|||' . $primary_options_html . '|||' . $secondary_options_html;
    wp_die();
}

add_action('wp_ajax_vv_filter_ingredients', 'vv_ajax_filter_ingredients');
add_action('wp_ajax_nopriv_vv_filter_ingredients', 'vv_ajax_filter_ingredients');

/**
 * Enqueue Frontend Scripts with Localization
 */
function vv_enqueue_frontend_scripts()
{
    if (is_front_page() && !is_admin()) {

        wp_enqueue_script(
            'vv-quiz-frontend-script',
            VV_QUIZ_URL . 'assets/vv-quiz-dynamic.js',
            array('jquery'),
            VV_QUIZ_VERSION, // Using constant for strong versioning/cache busting
            true
        );

        $settings = get_option('vv_quiz_settings');
        $cta_button_text = isset($settings['button_cta']) ? $settings['button_cta'] : __('FIND YOUR LIQUID', VV_QUIZ_TEXT_DOMAIN);
        $placeholder_primary = isset($settings['placeholder_primary']) ? $settings['placeholder_primary'] : __('-- Select Primary Ingredient --', VV_QUIZ_TEXT_DOMAIN);
        $placeholder_secondary = isset($settings['placeholder_secondary']) ? $settings['placeholder_secondary'] : __('-- Select Secondary Ingredient --', VV_QUIZ_TEXT_DOMAIN);

        wp_localize_script(
            'vv-quiz-frontend-script',
            'vv_quiz_ajax',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'placeholder_primary' => $placeholder_primary,
                'placeholder_secondary' => $placeholder_secondary,
                'cta_text_default' => $cta_button_text,
                'nonce' => wp_create_nonce('vv-quiz-nonce'),
                'i18n' => array(
                    'loading' => __('Searching...', VV_QUIZ_TEXT_DOMAIN),
                    'loading_options' => __('Loading...', VV_QUIZ_TEXT_DOMAIN),
                    'cta_default' => $cta_button_text,
                    'no_results' => __('🛑 0 RESULTS', VV_QUIZ_TEXT_DOMAIN),
                    'one_result' => __('FIND 1 PRODUCT', VV_QUIZ_TEXT_DOMAIN),
                    'multiple_results' => __('FOUND {count} PRODUCTS', VV_QUIZ_TEXT_DOMAIN),
                    'error_loading' => __('⚠️ DATA ERROR', VV_QUIZ_TEXT_DOMAIN),
                    'error_loading_options' => __('Loading Error', VV_QUIZ_TEXT_DOMAIN),
                )
            )
        );
    }
}
add_action('wp_enqueue_scripts', 'vv_enqueue_frontend_scripts');