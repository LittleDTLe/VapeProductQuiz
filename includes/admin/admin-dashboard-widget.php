<?php
/**
 * Dashboard Widget Controller for VapeVida Quiz
 * Contains registration and data logic.
 *
 * File: includes/admin/class-vv-admin-dashboard.php
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Register the Dashboard Widget
 */
function vv_quiz_register_dashboard_widget()
{
    // Check if the current screen is the dashboard before adding the widget.
    if (function_exists('wp_add_dashboard_widget')) {
        wp_add_dashboard_widget(
            'vv_quiz_dashboard_widget',
            __('VapeVida Quiz - Weekly Stats', 'vapevida-quiz'),
            'vv_quiz_render_dashboard_widget'
        );
    }
}
add_action('wp_dashboard_setup', 'vv_quiz_register_dashboard_widget');

/**
 * Get weekly stats data with comparison (Model Logic)
 */
function vv_quiz_get_weekly_stats()
{
    global $wpdb;
    $analytics_table = $wpdb->prefix . 'vv_quiz_analytics';
    $items_table = $wpdb->prefix . 'vv_quiz_conversion_items';

    // Current week (last 7 days)
    $current_week_start = date('Y-m-d H:i:s', strtotime('-7 days'));
    $current_week_end = current_time('mysql');

    // Previous week (8-14 days ago)
    $previous_week_start = date('Y-m-d H:i:s', strtotime('-14 days'));
    $previous_week_end = date('Y-m-d H:i:s', strtotime('-7 days'));

    // --- CURRENT WEEK STATS ---
    $current_searches = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $analytics_table WHERE search_timestamp >= %s AND search_timestamp <= %s",
        $current_week_start,
        $current_week_end
    ));

    $current_sales = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $analytics_table WHERE converted = 1 AND search_timestamp >= %s AND search_timestamp <= %s",
        $current_week_start,
        $current_week_end
    ));

    $current_revenue = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(order_total) FROM $analytics_table WHERE converted = 1 AND search_timestamp >= %s AND search_timestamp <= %s",
        $current_week_start,
        $current_week_end
    ));

    $current_complete = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $analytics_table 
        WHERE type_term != '' AND primary_ingredient_term != '' 
        AND search_timestamp >= %s AND search_timestamp <= %s",
        $current_week_start,
        $current_week_end
    ));

    // --- PREVIOUS WEEK STATS ---
    $previous_searches = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $analytics_table WHERE search_timestamp >= %s AND search_timestamp < %s",
        $previous_week_start,
        $previous_week_end
    ));

    $previous_sales = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $analytics_table WHERE converted = 1 AND search_timestamp >= %s AND search_timestamp < %s",
        $previous_week_start,
        $previous_week_end
    ));

    $previous_revenue = (float) $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(order_total) FROM $analytics_table WHERE converted = 1 AND search_timestamp >= %s AND search_timestamp < %s",
        $previous_week_start,
        $previous_week_end
    ));

    $previous_complete = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $analytics_table 
        WHERE type_term != '' AND primary_ingredient_term != '' 
        AND search_timestamp >= %s AND search_timestamp < %s",
        $previous_week_start,
        $previous_week_end
    ));

    // Calculate conversion rates
    $current_cvr = $current_searches > 0 ? round(($current_sales / $current_searches) * 100, 1) : 0;
    $previous_cvr = $previous_searches > 0 ? round(($previous_sales / $previous_searches) * 100, 1) : 0;

    // Top performing type this week
    // We assume VV_Analytics_Data::get_term_name is available for term display
    $top_type = $wpdb->get_row($wpdb->prepare(
        "SELECT type_term, type_slug, COUNT(*) as count 
        FROM $analytics_table 
        WHERE type_term != '' AND search_timestamp >= %s AND search_timestamp <= %s 
        GROUP BY type_term, type_slug 
        ORDER BY count DESC 
        LIMIT 1",
        $current_week_start,
        $current_week_end
    ));

    return array(
        'current' => array(
            'searches' => $current_searches,
            'sales' => $current_sales,
            'revenue' => $current_revenue,
            'complete' => $current_complete,
            'cvr' => $current_cvr,
        ),
        'previous' => array(
            'searches' => $previous_searches,
            'sales' => $previous_sales,
            'revenue' => $previous_revenue,
            'complete' => $previous_complete,
            'cvr' => $previous_cvr,
        ),
        'top_type' => $top_type,
    );
}

/**
 * Calculate percentage change
 */
function vv_quiz_calculate_change($current, $previous)
{
    if ($previous == 0) {
        return $current > 0 ? 100 : 0;
    }
    return round((($current - $previous) / $previous) * 100, 1);
}

/**
 * Render the Dashboard Widget (Controller)
 */
function vv_quiz_render_dashboard_widget()
{
    // Load data
    $stats = vv_quiz_get_weekly_stats();
    $current = $stats['current'];
    $previous = $stats['previous'];
    $top_type = $stats['top_type'];

    // Calculate changes
    $searches_change = vv_quiz_calculate_change($current['searches'], $previous['searches']);
    $sales_change = vv_quiz_calculate_change($current['sales'], $previous['sales']);
    $revenue_change = vv_quiz_calculate_change($current['revenue'], $previous['revenue']);
    $cvr_change = vv_quiz_calculate_change($current['cvr'], $previous['cvr']);

    // Load the view file (requires VV_QUIZ_DIR to be defined)
    include(VV_QUIZ_DIR . 'includes/views/admin/admin-dashboard-view.php');
}