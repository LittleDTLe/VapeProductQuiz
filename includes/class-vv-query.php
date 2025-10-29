<?php
/**
 * Custom Query Logic for VapeVida Quiz. Forces AND logic on the product loop.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'woocommerce_product_query', 'vv_custom_three_filter_query', 20 );

/**
 * Intercepts the WooCommerce product query to apply AND logic for all three quiz fields.
 *
 * @param WP_Query $query The WooCommerce product query object.
 */
function vv_custom_three_filter_query( $query ) {
    // 1. Check if the query is the main product loop and if the form was submitted
    if ( is_admin() || ! $query->is_main_query() || ! is_post_type_archive('product') ) {
        return;
    }
    
    // Check for the hidden input field to ensure the request came from the quiz form
    if ( ! isset( $_GET['filter_applied'] ) ) {
        return;
    }

    // 2. Get and sanitize all three attribute values from the URL
    $type_term = isset( $_GET['filter_geuseis'] ) ? sanitize_text_field( $_GET['filter_geuseis'] ) : '';
    $primary_ingredient = isset( $_GET['filter_quiz-ingredient'] ) ? sanitize_text_field( $_GET['filter_quiz-ingredient'] ) : '';
    $secondary_ingredient = isset( $_GET['filter_quiz-ingredient-optional'] ) ? sanitize_text_field( $_GET['filter_quiz-ingredient-optional'] ) : '';

    
    $filters_applied = false;
    
    // --- Collect ALL terms that need to be queried ---
    $new_tax_query = array();
    
    // --- Filter 1: Type (pa_geuseis) ---
    if ( ! empty( $type_term ) ) {
        $new_tax_query[] = array(
            'taxonomy' => 'pa_geuseis', // The Type Attribute Taxonomy
            'field'    => 'slug',
            'terms'    => array( $type_term ),
            'operator' => 'IN',
        );
        $filters_applied = true;
    }

    // --- Filters 2 & 3: Combined Ingredient Logic ---
    $all_ingredients = array_filter( array( $primary_ingredient, $secondary_ingredient ) );
    
    if ( ! empty( $all_ingredients ) ) {
        
        // Add the combined, strict AND query for the ingredients
        $new_tax_query[] = array(
            'taxonomy' => 'pa_quiz-ingredient', 
            'field'    => 'slug',
            'terms'    => $all_ingredients,
            'operator' => 'AND', // FORCE the product to have ALL selected ingredients
        );
        $filters_applied = true;
    }

    // 4. Apply the final tax_query
    if ( $filters_applied ) {
        // Set the relation explicitly for the new queries we built
        $new_tax_query['relation'] = 'AND';
        
        // Get the current tax query (from the query object)
        $current_tax_query = $query->get('tax_query');

        // Check if WooCommerce already has any tax queries set
        if ( ! empty( $current_tax_query ) && is_array($current_tax_query) ) {
            
            // Check if the first element is a relation string or an array (to prevent the fatal error)
            if ( isset($current_tax_query['relation']) ) {
                 // If a relation is already set, we combine our new query with the existing query.
                 $merged_tax_query = array_merge( $current_tax_query, $new_tax_query );
            } else {
                // If it's just a set of arrays, we wrap it in a new AND array and merge.
                $merged_tax_query = array_merge( $current_tax_query, $new_tax_query );
                $merged_tax_query['relation'] = 'AND';
            }

        } else {
            // If no existing filters, just use ours
            $merged_tax_query = $new_tax_query;
        }

        // Final application of the merged query
        $query->set( 'tax_query', $merged_tax_query );
    }
}