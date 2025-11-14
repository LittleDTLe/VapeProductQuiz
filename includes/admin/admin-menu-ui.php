<?php
/**
 * Admin Menu and UI Hooks.
 * Handles top-level menu creation and the link on the Plugins page.
 */

if (!defined('ABSPATH'))
    exit;

// --- 5. Add Admin Menu Page (Top-Level Unique Page) ---
function vv_add_plugin_admin_page()
{
    // 1. Create the Top-Level Menu (as a container)
    add_menu_page(
        'VapeVida Quiz', // Page title (for browser tab)
        'VapeVida Quiz', // Menu title (what you see)
        'manage_options', // Capability
        'vv-quiz-main', // Parent slug (a new, clean slug)
        'vv_render_details_page', // Default function (Settings)
        'dashicons-clipboard',
        70
    );

    // 2. Create the "Settings" Submenu Page
    add_submenu_page(
        'vv-quiz-main', // Parent slug
        __('Quiz Settings', 'vapevida-quiz'), // Page title
        __('Settings', 'vapevida-quiz'), // Menu title
        'manage_options', // Capability
        'vv-quiz-main', // Menu slug (matches parent to be default)
        'vv_render_details_page' // Function from admin-rendering.php
    );

    // 3. Create the "Analytics" Submenu Page
    add_submenu_page(
        'vv-quiz-main', // Parent slug
        __('Quiz Analytics', 'vapevida-quiz'), // Page title
        __('Analytics', 'vapevida-quiz'), // Menu title
        'manage_options', // Capability
        'vv-quiz-analytics', // Menu slug
        'vv_quiz_render_analytics_page' // Function from admin-analytics-page.php
    );
}
add_action('admin_menu', 'vv_add_plugin_admin_page');

// --- 7. Add Settings Link to Plugins Page ---
add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__, 4) . '/vapevida-quiz.php'), 'vv_add_plugin_action_links');

function vv_add_plugin_action_links($actions)
{
    $settings_link = '<a href="admin.php?page=vapevida-quiz-details">' . 'Quiz Info & Help' . '</a>';
    array_unshift($actions, $settings_link);
    return $actions;
}