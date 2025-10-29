<?php
/**
 * Core Logic for VapeVida Quiz. Handles Attribute creation.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// --- 2. Automated Attribute Creation ---
function vv_create_recommender_attributes() {
    if ( ! function_exists( 'wc_create_attribute' ) ) return;

    // These slugs are used by the frontend and query files
    $attributes = [
        'geuseis'           => 'Τύπος Γεύσης',      
        'quiz-ingredient'   => 'Συστατικό (Quiz)',         
    ];

    foreach ( $attributes as $name => $label ) {
        if ( ! taxonomy_exists( 'pa_' . $name ) ) {
            wc_create_attribute( [
                'name'         => $label,
                'slug'         => $name,
                'type'         => 'select',
                'orderby'      => 'menu_order',
                'has_archives' => true,
            ] );
        }
    }
}
add_action( 'init', 'vv_create_recommender_attributes' );