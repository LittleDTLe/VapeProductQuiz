<?php
/**
 * Admin Analytics Page CONTROLLER for VapeVida Quiz.
 *
 * This file is now responsible for:
 * 1. Enqueuing CSS and JS.
 * 2. Handling the date filter logic.
 * 3. Instantiating the Data class.
 * 4. Localizing chart data for JavaScript.
 * 5. Loading the View file.
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Enqueues Chart.js, custom CSS, and custom JS for the analytics page.
 * Also localizes (passes) data from PHP to our chart JS.
 */
function vv_quiz_analytics_enqueue_scripts($hook_suffix)
{
    if ($hook_suffix !== 'vapevida-quiz_page_vv-quiz-analytics') {
        return;
    }

    // 1. --- Get Date Filter Logic (must run here to get data) ---
    $selected_range = isset($_GET['range']) ? sanitize_key($_GET['range']) : 'all_time';
    $date_filter_sql = '';
    $date_filter_sql_where = '';

    if ($selected_range !== 'all_time') {
        global $wpdb;
        $start_date = '';
        switch ($selected_range) {
            case '7_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case 'this_month':
                $start_date = date('Y-m-01 00:00:00');
                break;
        }
        if ($start_date) {
            $date_filter_sql = $wpdb->prepare(" AND search_timestamp >= %s ", $start_date);
            $date_filter_sql_where = $wpdb->prepare(" WHERE search_timestamp >= %s ", $start_date);
        }
    }

    // 2. --- Get Chart Data ---
    // (We only need chart data for localization)
    $data = new VV_Analytics_Data($date_filter_sql, $date_filter_sql_where);

    $type_chart_labels = array();
    $type_chart_data = array();
    if (!empty($data->top_types_by_popularity)) {
        foreach ($data->top_types_by_popularity as $item) {
            $type_chart_labels[] = VV_Analytics_Data::get_term_name($item->type_term, $item->type_slug);
            $type_chart_data[] = $item->count;
        }
    }

    $primary_chart_labels = array();
    $primary_chart_data = array();
    if (!empty($data->top_primary_by_popularity)) {
        foreach ($data->top_primary_by_popularity as $item) {
            $primary_chart_labels[] = VV_Analytics_Data::get_term_name($item->primary_ingredient_term, $item->ingredient_slug);
            $primary_chart_data[] = $item->count;
        }
    }

    // 3. --- Enqueue Assets ---

    // Enqueue our new CSS file
    wp_enqueue_style(
        'vv-quiz-admin-analytics',
        VV_QUIZ_URL . 'assets/css/admin-analytics.css',
        array(),
        defined('VV_QUIZ_VERSION') ? VV_QUIZ_VERSION : '1.0'
    );

    // Enqueue Chart.js
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
        array(),
        '3.7.1',
        true
    );

    // Enqueue our new JS file
    wp_enqueue_script(
        'vv-quiz-admin-charts',
        VV_QUIZ_URL . 'assets/js/admin-analytics-charts.js',
        array('jquery', 'chart-js'), // Dependencies
        defined('VV_QUIZ_VERSION') ? VV_QUIZ_VERSION : '1.0',
        true
    );

    // 4. --- Pass data to our new JS file ---
    wp_localize_script(
        'vv-quiz-admin-charts',
        'vvChartData', // This object will contain our data in JS
        array(
            'type_labels' => $type_chart_labels,
            'type_data' => $type_chart_data,
            'primary_labels' => $primary_chart_labels,
            'primary_data' => $primary_chart_data,
            'i18n' => array(
                'searches' => __('Searches', 'vapevida-quiz')
            )
        )
    );
}
add_action('admin_enqueue_scripts', 'vv_quiz_analytics_enqueue_scripts');


/**
 * Renders the Analytics Page HTML.
 * This is the main callback for the admin menu.
 */
function vv_quiz_render_analytics_page()
{
    global $wpdb;

    // --- 1. Date Range Filtering Logic ---
    $selected_range = isset($_GET['range']) ? sanitize_key($_GET['range']) : 'all_time';
    $date_filter_sql = '';
    $date_filter_sql_where = '';

    if ($selected_range !== 'all_time') {
        $start_date = '';
        switch ($selected_range) {
            case '7_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-7 days'));
                break;
            case '30_days':
                $start_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                break;
            case 'this_month':
                $start_date = date('Y-m-01 00:00:00');
                break;
        }

        if ($start_date) {
            $date_filter_sql = $wpdb->prepare(" AND search_timestamp >= %s ", $start_date);
            $date_filter_sql_where = $wpdb->prepare(" WHERE search_timestamp >= %s ", $start_date);
        }
    }

    // Get the display label for the view
    $date_range_label = '';
    switch ($selected_range) {
        case '7_days':
            $date_range_label = __('Last 7 Days', 'vapevida-quiz');
            break;
        case '30_days':
            $date_range_label = __('Last 30 Days', 'vapevida-quiz');
            break;
        case 'this_month':
            $date_range_label = __('This Month', 'vapevida-quiz');
            break;
        case 'all_time':
        default:
            $date_range_label = __('All Time', 'vapevida-quiz');
            break;
    }

    // --- 2. Instantiate Data Class ---
    // This runs all the queries
    $data = new VV_Analytics_Data($date_filter_sql, $date_filter_sql_where);

    // --- 3. Load the View ---
    // The $data and $date_range_label variables will be available in the view.
    require_once VV_QUIZ_DIR . 'includes/views/admin/admin-analytics-view.php';
}