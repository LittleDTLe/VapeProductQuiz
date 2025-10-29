<?php
/**
 * Plugin Name: VapeVida Flavorshot Recommender Quiz
 * Description: Product Recommendation Quiz for Flavorshots, which automatically filters populated Global Access Attributes. Requires pa_geuseis and pa_quiz-ingredient, which get automatically created, if missing.
 * Version: 0.8.3
 * Author: Panagiotis Drougas / VapeVida
 * VersionNotes: Modularization of Plugin, Dynamic Text Configuration, Dynamic Required Fields.
 * Features: Button-Color, Admin Menu Responsiveness, Multiple Product Recommendation Quizes.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Plugin Constants (CRUCIAL for file paths)
define( 'VV_QUIZ_DIR', plugin_dir_path( __FILE__ ) );
define( 'VV_QUIZ_URL', plugin_dir_url( __FILE__ ) );


// --- LOAD ADMIN FILE EARLY (Crucial for Dashboard hooks and file.php safety check) ---
if ( is_admin() ) {
    // Load file.php early for get_file_data() stability
    if ( ! function_exists( 'get_file_data' ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
    }
    require_once( VV_QUIZ_DIR . 'includes/class-vv-admin.php' );
}


// --- LOAD CORE FILES ---
require_once( VV_QUIZ_DIR . 'includes/class-vv-core.php' );
require_once( VV_QUIZ_DIR . 'includes/class-vv-frontend.php' );
require_once( VV_QUIZ_DIR . 'includes/class-vv-query.php' );