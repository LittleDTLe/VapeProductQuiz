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
    add_menu_page(
        'VapeVida Quiz Details',
        'VapeVida Quiz',
        'manage_options',
        'vapevida-quiz-details',
        'vv_render_details_page', // Defined in admin-rendering.php
        'dashicons-clipboard',
        70
    );
}
add_action('admin_menu', 'vv_add_plugin_admin_page');

// --- 7. Add Settings Link to Plugins Page ---
add_filter('plugin_action_links_' . plugin_basename(dirname(__FILE__, 3) . '/vapevida-quiz.php'), 'vv_add_plugin_action_links');

function vv_add_plugin_action_links($actions)
{
    $settings_link = '<a href="admin.php?page=vapevida-quiz-details">' . 'Quiz Info & Help' . '</a>';
    array_unshift($actions, $settings_link);
    return $actions;
}