<?php
/**
 * Admin Analytics Page for VapeVida Quiz.
 * Renders the submenu page and enqueues scripts.
 *
 * NOTE: The menu itself is now created in admin-menu-ui.php
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Enqueues Chart.js and custom script for the analytics page.
 */
function vv_quiz_analytics_enqueue_scripts($hook_suffix)
{
    // Only load on our specific analytics page
    // Note: The hook suffix changes to match the new parent slug
    if ($hook_suffix !== 'vapevida-quiz_page_vv-quiz-analytics') {
        return;
    }

    // Enqueue Chart.js from a reliable CDN
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
        array(),
        '3.7.1',
        true
    );

    // Enqueue a custom script to initialize charts
    wp_register_script(
        'vv-quiz-analytics-charts',
        false, // No file, will be inline
        array('chart-js', 'jquery'),
        defined('VV_QUIZ_VERSION') ? VV_QUIZ_VERSION : '1.0', // Added safety check
        true
    );
    wp_enqueue_script('vv-quiz-analytics-charts');
}
add_action('admin_enqueue_scripts', 'vv_quiz_analytics_enqueue_scripts');


/**
 * Renders the Analytics Page HTML.
 */
function vv_quiz_render_analytics_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'vv_quiz_analytics';

    // --- Data Fetching ---

    // 1. Total Searches
    $total_searches = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    // 2. Top Flavor Types
    $top_types = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT type_term, COUNT(*) as count 
            FROM $table_name 
            WHERE type_term != '' 
            GROUP BY type_term 
            ORDER BY count DESC 
            LIMIT %d",
            10
        )
    );

    // 3. Top Primary Ingredients
    $top_primary = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT primary_ingredient_term, COUNT(*) as count 
            FROM $table_name 
            WHERE primary_ingredient_term != '' 
            GROUP BY primary_ingredient_term 
            ORDER BY count DESC 
            LIMIT %d",
            10
        )
    );

    // 4. Top Search Combinations
    $top_combos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT type_term, primary_ingredient_term, secondary_ingredient_term, COUNT(*) as count 
            FROM $table_name 
            WHERE type_term != '' OR primary_ingredient_term != ''
            GROUP BY type_term, primary_ingredient_term, secondary_ingredient_term 
            ORDER BY count DESC 
            LIMIT %d",
            15
        )
    );

    // --- Prepare Data for Charts (JSON) ---
    $type_chart_labels = json_encode(wp_list_pluck($top_types, 'type_term'));
    $type_chart_data = json_encode(wp_list_pluck($top_types, 'count'));

    $primary_chart_labels = json_encode(wp_list_pluck($top_primary, 'primary_ingredient_term'));
    $primary_chart_data = json_encode(wp_list_pluck($top_primary, 'count'));

    ?>
    <style>
        .vv-analytics-wrap .postbox {
            margin-bottom: 20px;
        }

        .vv-analytics-wrap h2 {
            font-size: 1.5em;
            margin-bottom: 0.5em;
        }

        .vv-analytics-flex {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .vv-analytics-flex>div {
            flex: 1;
            min-width: 300px;
        }

        .vv-analytics-chart-container {
            padding: 15px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            max-height: 400px;
        }

        .vv-analytics-table {
            width: 100%;
            border-collapse: collapse;
        }

        .vv-analytics-table th,
        .vv-analytics-table td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }

        .vv-analytics-table th {
            background: #f9f9f9;
        }

        .vv-analytics-table tr:last-child td {
            border-bottom: none;
        }

        .vv-analytics-total {
            font-size: 2.5em;
            font-weight: bold;
            color: #2271b1;
            text-align: center;
            padding: 20px;
        }
    </style>

    <div class="wrap vv-analytics-wrap">
        <h1><?php esc_html_e('VapeVida Quiz Analytics', 'vapevida-quiz'); ?></h1>
        <p><?php esc_html_e('This page shows the most popular search terms and combinations submitted via the quiz.', 'vapevida-quiz'); ?>
        </p>

        <div class="postbox">
            <h2 class="hndle" style="padding: 10px 15px;"><?php esc_html_e('Total Quiz Submissions', 'vapevida-quiz'); ?>
            </h2>
            <div class="inside">
                <div class="vv-analytics-total"><?php echo esc_html($total_searches); ?></div>
            </div>
        </div>

        <div class="vv-analytics-flex">
            <div class="vv-chart-box-left">
                <h2><?php esc_html_e('Top Flavor Types', 'vapevida-quiz'); ?></h2>
                <div class="vv-analytics-chart-container">
                    <canvas id="vvTopTypesChart"></canvas>
                </div>
            </div>
            <div class="vv-chart-box-right">
                <h2><?php esc_html_e('Top Primary Ingredients', 'vapevida-quiz'); ?></h2>
                <div class="vv-analytics-chart-container">
                    <canvas id="vvTopPrimaryChart"></canvas>
                    }
                </div>
            </div>

            <div class="postbox" style="margin-top: 20px;">
                <h2 class="hndle" style="padding: 10px 15px;">
                    <?php esc_html_e('Top Search Combinations', 'vapevida-quiz'); ?>
                </h2>
                <div class="inside">
                    <table class="vv-analytics-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Searches', 'vapevida-quiz'); ?></th>
                                <th><?php esc_html_e('Flavor Type', 'vapevida-quiz'); ?></th>
                                <th><?php esc_html_e('Primary Ingredient', 'vapevida-quiz'); ?></th>
                                <th><?php esc_html_e('Secondary Ingredient', 'vapevida-quiz'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($top_combos)): ?>
                                <tr>
                                    <td colspan="4"><?php esc_html_e('No search data yet.', 'vapevida-quiz'); ?></td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($top_combos as $combo): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($combo->count); ?></strong></td>
                                        <td><?php echo esc_html($combo->type_term ? $combo->type_term : '<em>' . __('None', 'vapevida-quiz') . '</em>'); ?>
                                        </td>
                                        <td><?php echo esc_html($combo->primary_ingredient_term ? $combo->primary_ingredient_term : '<em>' . __('None', 'vapevida-quiz') . '</em>'); ?>
                                        </td>
                                        <td><?php echo esc_html($combo->secondary_ingredient_term ? $combo->secondary_ingredient_term : '<em>' . __('None', 'vapevida-quiz') . '</em>'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
        <?php
        // Add the inline script to initialize the charts
        $chart_script = "
    jQuery(document).ready(function($) {
        
        function createBarChart(ctx, labels, data, chartLabel) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: chartLabel,
                        data: data,
                        backgroundColor: 'rgba(34, 113, 177, 0.6)',
                        borderColor: 'rgba(34, 113, 177, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0 
                            }
                        }
                    }
                }
            });
        }

        // Top Types Chart
        var ctxTypes = document.getElementById('vvTopTypesChart');
        if (ctxTypes) {
            createBarChart(
                ctxTypes.getContext('2d'),
                {$type_chart_labels},
                {$type_chart_data},
                '" . esc_js(__('Searches', 'vapevida-quiz')) . "'
            );
        }

        // Top Primary Ingredients Chart
        var ctxPrimary = document.getElementById('vvTopPrimaryChart');
        if (ctxPrimary) {
            createBarChart(
                ctxPrimary.getContext('2d'),
                {$primary_chart_labels},
                {$primary_chart_data},
                '" . esc_js(__('Searches', 'vapevida-quiz')) . "'
            );
        }
    });
    ";
        wp_add_inline_script('vv-quiz-analytics-charts', $chart_script);
}