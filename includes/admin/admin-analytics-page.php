<?php
/**
 * Admin Analytics Page for VapeVida Quiz - MODERN REDESIGN
 * Beautiful, responsive analytics dashboard with enhanced visuals
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Enqueues Chart.js and custom script for the analytics page.
 */
function vv_quiz_analytics_enqueue_scripts($hook_suffix)
{
    if ($hook_suffix !== 'vapevida-quiz_page_vv-quiz-analytics') {
        return;
    }

    // Enqueue Chart.js
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
        array(),
        '3.7.1',
        true
    );

    // Enqueue custom script
    wp_register_script(
        'vv-quiz-analytics-charts',
        false,
        array('chart-js', 'jquery'),
        defined('VV_QUIZ_VERSION') ? VV_QUIZ_VERSION : '1.0',
        true
    );
    wp_enqueue_script('vv-quiz-analytics-charts');
}
add_action('admin_enqueue_scripts', 'vv_quiz_analytics_enqueue_scripts');

/**
 * Helper function to get a term's display name from its slug.
 */
function vv_quiz_get_term_name($term_slug, $taxonomy)
{
    if (empty($term_slug) || empty($taxonomy)) {
        return '<em>' . __('None', 'vapevida-quiz') . '</em>';
    }

    static $term_cache = array();
    $cache_key = $taxonomy . '_' . $term_slug;

    if (isset($term_cache[$cache_key])) {
        return $term_cache[$cache_key];
    }

    $term = get_term_by('slug', $term_slug, $taxonomy);

    if ($term && !is_wp_error($term)) {
        $term_cache[$cache_key] = esc_html($term->name);
        return $term_cache[$cache_key];
    }

    $fallback_name = esc_html(ucwords(str_replace(array('q-', 'pa_', '-'), ' ', $term_slug)));
    $term_cache[$cache_key] = $fallback_name . ' <em>(' . __('Deleted', 'vapevida-quiz') . ')</em>';
    return $term_cache[$cache_key];
}

/**
 * Renders the Analytics Page HTML - MODERN DESIGN
 */
function vv_quiz_render_analytics_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'vv_quiz_analytics';

    // --- Data Fetching ---
    $total_searches = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    $top_types = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT type_term, type_slug, COUNT(*) as count 
            FROM $table_name 
            WHERE type_term != '' 
            GROUP BY type_term, type_slug 
            ORDER BY count DESC 
            LIMIT %d",
            10
        )
    );

    $top_primary = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count 
            FROM $table_name 
            WHERE primary_ingredient_term != '' 
            GROUP BY primary_ingredient_term, ingredient_slug 
            ORDER BY count DESC 
            LIMIT %d",
            10
        )
    );

    $top_combos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT type_term, type_slug, primary_ingredient_term, ingredient_slug, secondary_ingredient_term, COUNT(*) as count 
            FROM $table_name 
            WHERE type_term != '' OR primary_ingredient_term != ''
            GROUP BY type_term, type_slug, primary_ingredient_term, ingredient_slug, secondary_ingredient_term 
            ORDER BY count DESC 
            LIMIT %d",
            15
        )
    );

    // Calculate additional metrics
    $searches_with_primary = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE primary_ingredient_term != ''");
    $searches_with_secondary = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE secondary_ingredient_term != ''");
    $complete_searches = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE type_term != '' AND primary_ingredient_term != ''");

    // Prepare Chart Data
    $type_chart_labels = array();
    $type_chart_data = array();
    if (!empty($top_types)) {
        foreach ($top_types as $item) {
            $type_chart_labels[] = vv_quiz_get_term_name($item->type_term, $item->type_slug);
            $type_chart_data[] = $item->count;
        }
    }

    $primary_chart_labels = array();
    $primary_chart_data = array();
    if (!empty($top_primary)) {
        foreach ($top_primary as $item) {
            $primary_chart_labels[] = vv_quiz_get_term_name($item->primary_ingredient_term, $item->ingredient_slug);
            $primary_chart_data[] = $item->count;
        }
    }

    ?>
    <style>
        /* Modern Analytics Dashboard Styles */
        .vv-analytics-wrap {
            background: #f0f2f5;
            margin: -20px -20px 0 -22px;
            padding: 30px;
            min-height: 100vh;
        }

        .vv-analytics-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .vv-analytics-header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .vv-analytics-header p {
            margin: 10px 0 0 0;
            font-size: 1.1em;
            opacity: 0.95;
        }

        /* Stats Cards Row */
        .vv-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .vv-stat-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border-left: 4px solid #667eea;
            position: relative;
            overflow: hidden;
        }

        .vv-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        .vv-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
            border-radius: 0 0 0 100%;
        }

        .vv-stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            display: inline-block;
        }

        .vv-stat-value {
            font-size: 2.8em;
            font-weight: 700;
            color: #667eea;
            margin: 10px 0;
            line-height: 1;
        }

        .vv-stat-label {
            font-size: 0.95em;
            color: #666;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vv-stat-percentage {
            font-size: 0.85em;
            color: #28a745;
            margin-top: 8px;
            font-weight: 600;
        }

        /* Charts Section */
        .vv-charts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .vv-chart-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .vv-chart-card h2 {
            margin: 0 0 20px 0;
            font-size: 1.4em;
            color: #333;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .vv-chart-card h2::before {
            content: '📊';
            font-size: 1.2em;
        }

        .vv-chart-container {
            position: relative;
            height: 320px;
        }

        /* Tables Section */
        .vv-tables-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .vv-table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .vv-table-card h2 {
            margin: 0;
            padding: 20px 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            font-size: 1.3em;
            color: #333;
            font-weight: 600;
            border-bottom: 2px solid #667eea;
        }

        .vv-analytics-table {
            width: 100%;
            border-collapse: collapse;
        }

        .vv-analytics-table thead {
            background: #f8f9fa;
        }

        .vv-analytics-table th {
            text-align: left;
            padding: 15px 20px;
            font-weight: 600;
            color: #555;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e9ecef;
        }

        .vv-analytics-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f5;
            color: #333;
        }

        .vv-analytics-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .vv-analytics-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .vv-analytics-table tbody tr:last-child td {
            border-bottom: none;
        }

        .vv-analytics-table td.vv-count-col {
            width: 100px;
            text-align: center;
            font-weight: 700;
            color: #667eea;
            font-size: 1.1em;
        }

        .vv-analytics-table th.vv-count-col {
            width: 100px;
            text-align: center;
        }

        /* Combinations Table (Full Width) */
        .vv-combinations-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .vv-combinations-card h2 {
            margin: 0;
            padding: 20px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 1.4em;
            font-weight: 600;
        }

        /* Rank Badges */
        .vv-rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-weight: 700;
            font-size: 0.9em;
            color: white;
            margin-right: 10px;
        }

        .vv-rank-1 {
            background: linear-gradient(135deg, #FFD700, #FFA500);
        }

        .vv-rank-2 {
            background: linear-gradient(135deg, #C0C0C0, #A8A8A8);
        }

        .vv-rank-3 {
            background: linear-gradient(135deg, #CD7F32, #8B4513);
        }

        .vv-rank-other {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        /* Empty State */
        .vv-empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .vv-empty-state-icon {
            font-size: 4em;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .vv-empty-state-text {
            font-size: 1.2em;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .vv-charts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .vv-analytics-wrap {
                padding: 15px;
            }

            .vv-analytics-header {
                padding: 25px;
            }

            .vv-analytics-header h1 {
                font-size: 1.8em;
            }

            .vv-stats-grid {
                grid-template-columns: 1fr;
            }

            .vv-tables-grid {
                grid-template-columns: 1fr;
            }

            .vv-stat-value {
                font-size: 2.2em;
            }

            .vv-chart-container {
                height: 250px;
            }
        }
    </style>

    <div class="wrap vv-analytics-wrap">
        <!-- Header Section -->
        <div class="vv-analytics-header">
            <h1><?php esc_html_e('VapeVida Quiz Analytics', 'vapevida-quiz'); ?></h1>
            <p><?php esc_html_e('Comprehensive insights into customer preferences and quiz interactions', 'vapevida-quiz'); ?>
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="vv-stats-grid">
            <div class="vv-stat-card">
                <div class="vv-stat-icon">🔍</div>
                <div class="vv-stat-label"><?php esc_html_e('Total Searches', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value"><?php echo esc_html(number_format($total_searches)); ?></div>
            </div>

            <div class="vv-stat-card">
                <div class="vv-stat-icon">✅</div>
                <div class="vv-stat-label"><?php esc_html_e('Complete Searches', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value"><?php echo esc_html(number_format($complete_searches)); ?></div>
                <?php if ($total_searches > 0): ?>
                    <div class="vv-stat-percentage">
                        <?php echo esc_html(round(($complete_searches / $total_searches) * 100, 1)); ?>%
                        <?php esc_html_e('completion rate', 'vapevida-quiz'); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="vv-stat-card">
                <div class="vv-stat-icon">🥇</div>
                <div class="vv-stat-label"><?php esc_html_e('With Primary Ingredient', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value"><?php echo esc_html(number_format($searches_with_primary)); ?></div>
                <?php if ($total_searches > 0): ?>
                    <div class="vv-stat-percentage">
                        <?php echo esc_html(round(($searches_with_primary / $total_searches) * 100, 1)); ?>%
                    </div>
                <?php endif; ?>
            </div>

            <div class="vv-stat-card">
                <div class="vv-stat-icon">🥈</div>
                <div class="vv-stat-label"><?php esc_html_e('With Secondary Ingredient', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value"><?php echo esc_html(number_format($searches_with_secondary)); ?></div>
                <?php if ($total_searches > 0): ?>
                    <div class="vv-stat-percentage">
                        <?php echo esc_html(round(($searches_with_secondary / $total_searches) * 100, 1)); ?>%
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="vv-charts-grid">
            <div class="vv-chart-card">
                <h2><?php esc_html_e('Top Flavor Types', 'vapevida-quiz'); ?></h2>
                <div class="vv-chart-container">
                    <canvas id="vvTopTypesChart"></canvas>
                </div>
            </div>

            <div class="vv-chart-card">
                <h2><?php esc_html_e('Top Primary Ingredients', 'vapevida-quiz'); ?></h2>
                <div class="vv-chart-container">
                    <canvas id="vvTopPrimaryChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Tables Section -->
        <div class="vv-tables-grid">
            <!-- Top Flavor Types Table -->
            <div class="vv-table-card">
                <h2><?php esc_html_e('Top 10 Flavor Types', 'vapevida-quiz'); ?></h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Flavor Type', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Searches', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_types)): ?>
                            <tr>
                                <td colspan="2">
                                    <div class="vv-empty-state">
                                        <div class="vv-empty-state-icon">📊</div>
                                        <div class="vv-empty-state-text">
                                            <?php esc_html_e('No search data yet.', 'vapevida-quiz'); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            foreach ($top_types as $item):
                                $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other";
                                ?>
                                <tr>
                                    <td>
                                        <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                        <?php echo vv_quiz_get_term_name($item->type_term, $item->type_slug); ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($item->count)); ?></td>
                                </tr>
                                <?php
                                $rank++;
                            endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Primary Ingredients Table -->
            <div class="vv-table-card">
                <h2><?php esc_html_e('Top 10 Primary Ingredients', 'vapevida-quiz'); ?></h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Ingredient', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Searches', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_primary)): ?>
                            <tr>
                                <td colspan="2">
                                    <div class="vv-empty-state">
                                        <div class="vv-empty-state-icon">🥇</div>
                                        <div class="vv-empty-state-text">
                                            <?php esc_html_e('No search data yet.', 'vapevida-quiz'); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            foreach ($top_primary as $item):
                                $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other";
                                ?>
                                <tr>
                                    <td>
                                        <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                        <?php echo vv_quiz_get_term_name($item->primary_ingredient_term, $item->ingredient_slug); ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($item->count)); ?></td>
                                </tr>
                                <?php
                                $rank++;
                            endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Combinations Table (Full Width) -->
        <div class="vv-combinations-card">
            <h2><?php esc_html_e('Top Search Combinations', 'vapevida-quiz'); ?></h2>
            <table class="vv-analytics-table">
                <thead>
                    <tr>
                        <th class="vv-count-col"><?php esc_html_e('Searches', 'vapevida-quiz'); ?></th>
                        <th><?php esc_html_e('Flavor Type', 'vapevida-quiz'); ?></th>
                        <th><?php esc_html_e('Primary Ingredient', 'vapevida-quiz'); ?></th>
                        <th><?php esc_html_e('Secondary Ingredient', 'vapevida-quiz'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($top_combos)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="vv-empty-state">
                                    <div class="vv-empty-state-icon">🔗</div>
                                    <div class="vv-empty-state-text">
                                        <?php esc_html_e('No search data yet.', 'vapevida-quiz'); ?>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($top_combos as $combo): ?>
                            <tr>
                                <td class="vv-count-col"><?php echo esc_html(number_format($combo->count)); ?></td>
                                <td><?php echo vv_quiz_get_term_name($combo->type_term, $combo->type_slug); ?></td>
                                <td><?php echo vv_quiz_get_term_name($combo->primary_ingredient_term, $combo->ingredient_slug); ?>
                                </td>
                                <td><?php echo vv_quiz_get_term_name($combo->secondary_ingredient_term, $combo->ingredient_slug); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <?php
    // Enhanced Chart Script with Modern Styling
    $chart_script = "
    jQuery(document).ready(function($) {
        
        function createModernChart(ctx, labels, data, chartLabel, gradientColors) {
            var gradient = ctx.createLinearGradient(0, 0, 0, 300);
            gradient.addColorStop(0, gradientColors[0]);
            gradient.addColorStop(1, gradientColors[1]);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: chartLabel,
                        data: data,
                        backgroundColor: gradient,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            titleFont: {
                                size: 14,
                                weight: 'bold'
                            },
                            bodyFont: {
                                size: 13
                            },
                            cornerRadius: 8,
                            displayColors: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                color: '#666'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                                drawBorder: false
                            },
                            ticks: {
                                precision: 0,
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                color: '#666',
                                padding: 10
                            }
                        }
                    },
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        // Top Types Chart
        var ctxTypes = document.getElementById('vvTopTypesChart');
        if (ctxTypes) {
            createModernChart(
                ctxTypes.getContext('2d'),
                " . json_encode($type_chart_labels) . ",
                " . json_encode($type_chart_data) . ",
                '" . esc_js(__('Searches', 'vapevida-quiz')) . "',
                ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)']
            );
        }

        // Top Primary Ingredients Chart
        var ctxPrimary = document.getElementById('vvTopPrimaryChart');
        if (ctxPrimary) {
            createModernChart(
                ctxPrimary.getContext('2d'),
                " . json_encode($primary_chart_labels) . ",
                " . json_encode($primary_chart_data) . ",
                '" . esc_js(__('Searches', 'vapevida-quiz')) . "',
                ['rgba(102, 126, 234, 0.8)', 'rgba(118, 75, 162, 0.8)']
            );
        }
    });
    ";
    wp_add_inline_script('vv-quiz-analytics-charts', $chart_script);
}