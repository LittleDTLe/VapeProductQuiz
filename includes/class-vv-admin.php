<?php
/**
 * Admin Logic for VapeVida Quiz (LOADER).
 * This file loads all administration modules.
 */

if (!defined('ABSPATH'))
    exit;

// Define directory constant (if not already done in main loader)
if (!defined('VV_QUIZ_DIR')) {
    define('VV_QUIZ_DIR', plugin_dir_path(__FILE__) . '../');
}

// Ensure all admin components are loaded
require_once VV_QUIZ_DIR . 'includes/admin/admin-menu-ui.php';
require_once VV_QUIZ_DIR . 'includes/admin/admin-callbacks.php';
require_once VV_QUIZ_DIR . 'includes/admin/admin-settings-api.php';
require_once VV_QUIZ_DIR . 'includes/admin/admin-rendering.php';