<?php
/**
 * Admin Logic for VapeVida Quiz (LOADER).
 * This file loads all administration modules.
 */

if (!defined('ABSPATH'))
    exit;

// Load Rendering functions for submenu use
require_once VV_QUIZ_DIR . 'includes/admin/admin-rendering.php';
require_once VV_QUIZ_DIR . 'includes/admin/admin-analytics-page.php';

// Load the UI, Callbacks, and Settings API
require_once VV_QUIZ_DIR . 'includes/admin/admin-menu-ui.php';
require_once VV_QUIZ_DIR . 'includes/admin/admin-callbacks.php';
require_once VV_QUIZ_DIR . 'includes/admin/admin-settings-api.php';

