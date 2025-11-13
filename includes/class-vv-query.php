<?php
/**
 * Custom Query Logic for VapeVida Quiz. Forces AND logic on the product loop.
 * - Dynamically reads admin settings for custom attributes.
 */

if (!defined('ABSPATH'))
    exit;

// Define the text domain (Used for translations)
if (!defined('VV_QUIZ_TEXT_DOMAIN')) {
    define('VV_QUIZ_TEXT_DOMAIN', 'vapevida-quiz');
}

add_action('woocommerce_product_query', 'vv_custom_three_filter_query', 20);

/**
 * Intercepts the WooCommerce product query to apply AND logic for all three quiz fields.
 *
 * @param WP_Query $query The WooCommerce product query object.
 */
function vv_custom_three_filter_query($query)
{
    // 1. Check if the query is the main product loop and if the form was submitted
    if (is_admin() || !$query->is_main_query() || !is_post_type_archive('product')) {
        return;
    }

    // Check for the hidden input field to ensure the request came from the quiz form
    if (!isset($_GET['filter_applied'])) {
        return;
    }

    // --- DYNAMIC SETTINGS LOGIC (THE BUG FIX) ---
    // 1. Get the dynamic settings FIRST
    $settings = get_option('vv_quiz_settings');
    $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;

    // 2. Determine the correct taxonomy slugs to use
    $type_slug = $use_custom && !empty($settings['attribute_type_slug'])
        ? $settings['attribute_type_slug']
        : 'pa_geuseis';
    $ingredient_slug = $use_custom && !empty($settings['attribute_ingredient_slug'])
        ? $settings['attribute_ingredient_slug']
        : 'pa_quiz-ingredient';

    // 3. Build the DYNAMIC $_GET keys to look for
    $type_key = str_replace('pa_', 'filter_', $type_slug);
    $ingredient_key = str_replace('pa_', 'filter_', $ingredient_slug);
    $secondary_ingredient_key = $ingredient_key . '-optional';

    // 4. NOW check the $_GET array using the dynamic keys
    $type_term = isset($_GET[$type_key]) ? sanitize_text_field($_GET[$type_key]) : '';
    $primary_ingredient = isset($_GET[$ingredient_key]) ? sanitize_text_field($_GET[$ingredient_key]) : '';
    $secondary_ingredient = isset($_GET[$secondary_ingredient_key]) ? sanitize_text_field($_GET[$secondary_ingredient_key]) : '';
    // --- END DYNAMIC SETTINGS LOGIC ---


    $filters_applied = false;

    // --- Collect ALL terms that need to be queried ---
    $new_tax_query = array();

    // --- Filter 1: Type (Using dynamic slug) ---
    if (!empty($type_term)) {
        $new_tax_query[] = array(
            'taxonomy' => $type_slug, // The Type Attribute Taxonomy (DYNAMIC)
            'field' => 'slug',
            'terms' => array($type_term),
            'operator' => 'IN',
        );
        $filters_applied = true;
    }

    // --- Filters 2 & 3: Combined Ingredient Logic (Using dynamic slug) ---
    $all_ingredients = array_filter(array($primary_ingredient, $secondary_ingredient));

    if (!empty($all_ingredients)) {

        // Add the combined, strict AND query for the ingredients
        $new_tax_query[] = array(
            'taxonomy' => $ingredient_slug, // The Ingredient Attribute Taxonomy (DYNAMIC)
            'field' => 'slug',
            'terms' => $all_ingredients,
            'operator' => 'AND', // FORCE the product to have ALL selected ingredients
        );
        $filters_applied = true;
    }

    // 4. Apply the final tax_query
    if ($filters_applied) {
        // Set the relation explicitly for the new queries we built
        $new_tax_query['relation'] = 'AND';

        // Get the current tax query (from the query object)
        $current_tax_query = $query->get('tax_query');

        // Check if WooCommerce already has any tax queries set
        if (!empty($current_tax_query) && is_array($current_tax_query)) {

            // Check if the first element is a relation string or an array (to prevent the fatal error)
            if (isset($current_tax_query['relation'])) {
                // If a relation is already set, we combine our new query with the existing query.
                $merged_tax_query = array_merge($current_tax_query, $new_tax_query);
            } else {
                // If it's just a set of arrays, we wrap it in a new AND array and merge.
                $merged_tax_query = array_merge($current_tax_query, $new_tax_query);
                $merged_tax_query['relation'] = 'AND';
            }

        } else {
            // If no existing filters, just use ours
            $merged_tax_query = $new_tax_query;
        }

        // Final application of the merged query
        $query->set('tax_query', $merged_tax_query);
    }
}