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