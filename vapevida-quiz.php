<?php
/**
 * Plugin Name: VapeVida Flavorshot Recommender Quiz
 * Plugin URI: https://github.com/LittleDTLe/VapeProductQuiz
 * Description: Simple Product Finder Quiz with settings for quiz customization. Auto-populate the quiz with Global Attribute Terms with connected products.
 * Version: 1.1.0
 * Author: Panagiotis Drougas
 * Author URI: https://github.com/LittleDTLe
 * Text Domain: vapevida-quiz
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * VersionNotes: Modularization: Admin, Modularisation: Frontend, Dynamic Text Configuration, Dynamic Required Fields, Attribute Selection, Button Color Control, Responsive Admin Page, Easy Shortcode Copy, Dynamic Clear Button, Dynamic Custom Attribute Selectors, WooCommerce Active Checker, Uninstall Script, Modularisation of Admin File, Dynamic Cascading Filters, Real-Time Result Preview, Full Localization Support, Advanced Analytics Dashboard, Sales & Revenue Conversion Tracking, Search Combination Normalization, Stepped Form Logic, Search in Selects
 * Features: Multiple Product Recommendation Quizzes
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define('VV_QUIZ_VERSION', '1.1.0');
define('VV_QUIZ_DIR', plugin_dir_path(__FILE__));
define('VV_QUIZ_URL', plugin_dir_url(__FILE__));
define('VV_QUIZ_BASENAME', plugin_basename(__FILE__));
define('VV_QUIZ_TEXT_DOMAIN', 'vapevida-quiz');

/**
 * Load Plugin Text Domain for Translations (Crucial hook)
 */
function vv_quiz_load_textdomain()
{
    load_plugin_textdomain(
        VV_QUIZ_TEXT_DOMAIN,
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'vv_quiz_load_textdomain');


/**
 * Check WooCommerce Dependency
 */
function vv_quiz_check_woocommerce()
{
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'vv_quiz_woocommerce_missing_notice');
        return false;
    }
    return true;
}

function vv_quiz_woocommerce_missing_notice()
{
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php esc_html_e('VapeVida Quiz', VV_QUIZ_TEXT_DOMAIN); ?>:</strong>
            <?php
            /* translators: %s: WooCommerce plugin name */
            echo sprintf(
                esc_html__('This plugin requires %s to be installed and activated.', VV_QUIZ_TEXT_DOMAIN),
                '<a href="https://wordpress.org/plugins/woocommerce/" target="_blank">WooCommerce</a>'
            );
            ?>
        </p>
    </div>
    <?php
}

/**
 * Initialize Plugin
 */
function vv_quiz_init()
{
    // Check WooCommerce dependency
    if (!vv_quiz_check_woocommerce()) {
        return;
    }

    // Load Admin functionality
    if (is_admin()) {
        // Ensure file.php is loaded for get_file_data()
        if (!function_exists('get_file_data')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        require_once VV_QUIZ_DIR . 'includes/class-vv-admin.php';
    }

    // Load Core functionality
    require_once VV_QUIZ_DIR . 'includes/class-vv-core.php';
    require_once VV_QUIZ_DIR . 'includes/class-vv-frontend.php';
    require_once VV_QUIZ_DIR . 'includes/class-vv-query.php';

    require_once VV_QUIZ_DIR . 'includes/class-vv-assets.php';

    require_once VV_QUIZ_DIR . 'includes/class-vv-analytics-db.php';
    require_once VV_QUIZ_DIR . 'includes/class-vv-analytics.php';

    new VV_Assets();
}
add_action('plugins_loaded', 'vv_quiz_init', 20);

/**
 * Plugin Activation Hook
 */
function vv_quiz_activate()
{
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            sprintf(
                /* translators: 1: Plugin name, 2: WooCommerce plugin name */
                esc_html__('%1$s requires %2$s to be installed and activated.', VV_QUIZ_TEXT_DOMAIN),
                '<strong>' . esc_html__('VapeVida Quiz', VV_QUIZ_TEXT_DOMAIN) . '</strong>',
                '<strong>WooCommerce</strong>'
            ),
            esc_html__('Plugin Activation Error', VV_QUIZ_TEXT_DOMAIN),
            array('back_link' => true)
        );
    }

    // Load the DB installer and run it
    if (!function_exists('vv_quiz_analytics_db_install')) {
        require_once VV_QUIZ_DIR . 'includes/class-vv-analytics-db.php';
    }
    vv_quiz_analytics_db_install();

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'vv_quiz_activate');

/**
 * Plugin Deactivation Hook
 */
function vv_quiz_deactivate()
{
    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'vv_quiz_deactivate');

/**
 * Add useful plugin meta links
 */
function vv_quiz_plugin_row_meta($links, $file)
{
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = array(
            'docs' => '<a href="https://github.com/LittleDTLe/VapeProductQuiz" target="_blank">' . esc_html__('Documentation', VV_QUIZ_TEXT_DOMAIN) . '</a>',
            'support' => '<a href="https://github.com/LittleDTLe/VapeProductQuiz/issues" target="_blank">' . esc_html__('Support', VV_QUIZ_TEXT_DOMAIN) . '</a>',
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'vv_quiz_plugin_row_meta', 10, 2);

