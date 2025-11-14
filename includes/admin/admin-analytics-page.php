<?php
/**
 * Admin Analytics Page for VapeVida Quiz - MODERN REDESIGN
 * Beautiful, responsive analytics dashboard with enhanced visuals
 *
 * NEW: Added Date Range Filtering and CVR % Columns
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
    $analytics_table = $wpdb->prefix . 'vv_quiz_analytics';
    $items_table = $wpdb->prefix . 'vv_quiz_conversion_items';

    // --- NEW: Date Range Filtering Logic ---
    $selected_range = isset($_GET['range']) ? sanitize_key($_GET['range']) : 'all_time';
    $date_filter_sql = '';
    $date_filter_sql_where = ''; // Version that starts with WHERE

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
    // --- END: Date Range Logic ---

    // --- Data Fetching (NOW INCLUDES SALES VALUE & DATE FILTER) ---
    $total_searches = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table $date_filter_sql_where");

    // --- UPDATED: Fetch Sales Count and Sales VALUE (with date filter) ---
    $total_sales_count = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE converted = 1 $date_filter_sql");
    $total_sales_value = $wpdb->get_var("SELECT SUM(order_total) FROM $analytics_table WHERE converted = 1 $date_filter_sql");
    $conversion_rate = ($total_searches > 0) ? round(($total_sales_count / $total_searches) * 100, 1) : 0;

    if (is_null($total_sales_value)) {
        $total_sales_value = 0;
    }

    // 1a. Top Types by POPULARITY (for charts)
    $top_types_by_popularity = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT type_term, type_slug, COUNT(*) as count
            FROM $analytics_table 
            WHERE type_term != '' $date_filter_sql
            GROUP BY type_term, type_slug 
            ORDER BY count DESC
            LIMIT %d",
            10
        )
    );

    // 1b. Top Types by SALES VALUE (for table)
    $top_types_by_sales = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT type_term, type_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value
            FROM $analytics_table 
            WHERE type_term != '' AND converted > 0 $date_filter_sql
            GROUP BY type_term, type_slug 
            ORDER BY sales_value DESC, sales_count DESC
            LIMIT %d",
            10
        )
    );

    // 2a. Top Primary by POPULARITY (for charts)
    $top_primary_by_popularity = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count
            FROM $analytics_table 
            WHERE primary_ingredient_term != '' $date_filter_sql
            GROUP BY primary_ingredient_term, ingredient_slug 
            ORDER BY count DESC
            LIMIT %d",
            10
        )
    );

    // 2b. Top Primary by SALES VALUE (for table)
    $top_primary_by_sales = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value
            FROM $analytics_table 
            WHERE primary_ingredient_term != '' AND converted > 0 $date_filter_sql
            GROUP BY primary_ingredient_term, ingredient_slug 
            ORDER BY sales_value DESC, sales_count DESC
            LIMIT %d",
            10
        )
    );

    // 3a. Top CONVERTING by VALUE (Normalized)
    $top_converting_combos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT 
                type_term, type_slug, 
                LEAST(primary_ingredient_term, secondary_ingredient_term) as ing1,
                GREATEST(primary_ingredient_term, secondary_ingredient_term) as ing2,
                ingredient_slug, 
                COUNT(*) as count, 
                SUM(converted) as sales_count, 
                SUM(order_total) as sales_value
            FROM $analytics_table 
            WHERE converted > 0 $date_filter_sql
            GROUP BY type_term, type_slug, ing1, ing2, ingredient_slug
            ORDER BY sales_value DESC, sales_count DESC
            LIMIT %d",
            15
        )
    );

    // 3b. Top POPULAR (Normalized)
    $top_popular_combos = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT 
                type_term, type_slug, 
                LEAST(primary_ingredient_term, secondary_ingredient_term) as ing1,
                GREATEST(primary_ingredient_term, secondary_ingredient_term) as ing2,
                ingredient_slug, 
                COUNT(*) as count, 
                SUM(converted) as sales_count, 
                SUM(order_total) as sales_value
            FROM $analytics_table 
            WHERE (type_term != '' OR primary_ingredient_term != '') $date_filter_sql
            GROUP BY type_term, type_slug, ing1, ing2, ingredient_slug 
            ORDER BY count DESC, sales_value DESC
            LIMIT %d",
            15
        )
    );

    // --- NEW: 4. Top Sold PRODUCTS (with date filter) ---
    // We must join with the analytics table to get the date
    $top_sold_products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT 
                p.post_title as product_name, 
                i.product_id, 
                SUM(i.quantity) as total_quantity, 
                SUM(i.subtotal) as total_revenue
            FROM 
                $items_table as i
            LEFT JOIN 
                $analytics_table as a ON i.search_id = a.id
            LEFT JOIN 
                {$wpdb->posts} as p ON i.product_id = p.ID
            WHERE 1=1 $date_filter_sql 
            GROUP BY 
                i.product_id
            ORDER BY 
                total_revenue DESC
            LIMIT %d",
            10
        )
    );
    // --- END NEW ---

    // Calculate additional metrics
    $searches_with_primary = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE primary_ingredient_term != '' $date_filter_sql");
    $searches_with_secondary = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE secondary_ingredient_term != '' $date_filter_sql");
    $complete_searches = $wpdb->get_var("SELECT COUNT(*) FROM $analytics_table WHERE type_term != '' AND primary_ingredient_term != '' $date_filter_sql");

    // Prepare Chart Data
    $type_chart_labels = array();
    $type_chart_data = array();
    if (!empty($top_types_by_popularity)) {
        foreach ($top_types_by_popularity as $item) {
            $type_chart_labels[] = vv_quiz_get_term_name($item->type_term, $item->type_slug);
            $type_chart_data[] = $item->count;
        }
    }

    $primary_chart_labels = array();
    $primary_chart_data = array();
    if (!empty($top_primary_by_popularity)) {
        foreach ($top_primary_by_popularity as $item) {
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

        /* --- UPDATED: Date Filter Form --- */
        .vv-analytics-header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .vv-date-filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .vv-date-filter-form label {
            font-weight: 600;
            font-size: 0.9em;
            opacity: 0.9;
        }

        .vv-date-filter-form select {
            min-width: 150px;
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.5);
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            font-weight: 600;
        }

        .vv-date-filter-form select option {
            background: #fff;
            color: #333;
        }

        .vv-date-filter-form .button {
            background: #fff;
            color: #667eea;
            border: none;
            font-weight: 700;
            border-radius: 6px;
            padding: 8px 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .vv-date-filter-form .button:hover {
            background: #f0f0f0;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* --- END UPDATED --- */

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

        .vv-stat-value.vv-stat-price {
            font-size: 2.2em;
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

        .vv-table-card-full {
            grid-column: 1 / -1;
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

        .vv-analytics-table td.vv-revenue-col {
            width: 100px;
            text-align: center;
            font-weight: 700;
            color: #28a745;
            font-size: 1.1em;
        }

        .vv-analytics-table td.vv-cvr-col {
            width: 100px;
            text-align: center;
            font-weight: 700;
            color: #17a2b8;
            font-size: 1.1em;
        }

        .vv-analytics-table th.vv-count-col,
        .vv-analytics-table th.vv-revenue-col,
        .vv-analytics-table th.vv-cvr-col {
            width: 100px;
            text-align: center;
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

            .vv-analytics-header-flex {
                flex-direction: column;
                align-items: flex-start;
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
        <div class="vv-analytics-header">
            <div class="vv-analytics-header-flex">
                <div>
                    <h1><?php esc_html_e('VapeVida Quiz Analytics', 'vapevida-quiz'); ?></h1>
                    <p><?php esc_html_e('Comprehensive insights into customer preferences and quiz interactions', 'vapevida-quiz'); ?>
                    </p>
                </div>
                <form method="get" class="vv-date-filter-form">
                    <input type="hidden" name="page" value="vv-quiz-analytics">
                    <select name="range" id="vv-date-range">
                        <option value="all_time" <?php selected($selected_range, 'all_time'); ?>>
                            <?php esc_html_e('All Time', 'vapevida-quiz'); ?>
                        </option>
                        <option value="30_days" <?php selected($selected_range, '30_days'); ?>>
                            <?php esc_html_e('Last 30 Days', 'vapevida-quiz'); ?>
                        </option>
                        <option value="7_days" <?php selected($selected_range, '7_days'); ?>>
                            <?php esc_html_e('Last 7 Days', 'vapevida-quiz'); ?>
                        </option>
                        <option value="this_month" <?php selected($selected_range, 'this_month'); ?>>
                            <?php esc_html_e('This Month', 'vapevida-quiz'); ?>
                        </option>
                    </select>
                    <button type="submit" class="button"><?php esc_html_e('Filter', 'vapevida-quiz'); ?></button>
                </form>
            </div>
        </div>

        <div class="vv-stats-grid">
            <div class="vv-stat-card">
                <div class="vv-stat-icon">🔍</div>
                <div class="vv-stat-label"><?php esc_html_e('Total Searches', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value"><?php echo esc_html(number_format($total_searches)); ?></div>
            </div>

            <div class="vv-stat-card" style="border-left-color: #28a745;">
                <div class="vv-stat-icon">💰</div>
                <div class="vv-stat-label"><?php esc_html_e('Total Revenue from Quiz', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value vv-stat-price" style="color: #28a745;"><?php echo wc_price($total_sales_value); ?>
                </div>
                <div class="vv-stat-percentage" style="color: #555;">
                    <?php printf(esc_html__('%s total sales', 'vapevida-quiz'), esc_html(number_format($total_sales_count))); ?>
                </div>
            </div>

            <div class="vv-stat-card" style="border-left-color: #17a2b8;">
                <div class="vv-stat-icon">📈</div>
                <div class="vv-stat-label"><?php esc_html_e('Conversion Rate', 'vapevida-quiz'); ?></div>
                <div class="vv-stat-value" style="color: #17a2b8;"><?php echo esc_html($conversion_rate); ?>%</div>
                <div class="vv-stat-percentage" style="color: #555;">
                    <?php printf(esc_html__('%s sales from %s searches', 'vapevida-quiz'), esc_html(number_format($total_sales_count)), esc_html(number_format($total_searches))); ?>
                </div>
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

        <div class="vv-charts-grid">
            <div class="vv-chart-card">
                <h2><?php esc_html_e('Top Flavor Types (by Popularity)', 'vapevida-quiz'); ?></h2>
                <div class="vv-chart-container">
                    <canvas id="vvTopTypesChart"></canvas>
                </div>
            </div>

            <div class="vv-chart-card">
                <h2><?php esc_html_e('Top Primary Ingredients (by Popularity)', 'vapevida-quiz'); ?></h2>
                <div class="vv-chart-container">
                    <canvas id="vvTopPrimaryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="vv-tables-grid">
            <div class="vv-table-card">
                <h2><?php esc_html_e('Top 10 Flavor Types (by Revenue)', 'vapevida-quiz'); ?></h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Flavor Type', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Sales', 'vapevida-quiz'); ?></th>
                            <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_types_by_sales)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="vv-empty-state">
                                        <div class="vv-empty-state-icon">📊</div>
                                        <div class="vv-empty-state-text">
                                            <?php esc_html_e('No search data with sales yet.', 'vapevida-quiz'); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            foreach ($top_types_by_sales as $item):
                                $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other";
                                $cvr = ($item->count > 0) ? round(($item->sales_count / $item->count) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                        <?php echo vv_quiz_get_term_name($item->type_term, $item->type_slug); ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($item->sales_count)); ?></td>
                                    <td class="vv-revenue-col"><?php echo wc_price($item->sales_value); ?></td>
                                </tr>
                                <?php
                                $rank++;
                            endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="vv-table-card">
                <h2><?php esc_html_e('Top 10 Primary Ingredients (by Revenue)', 'vapevida-quiz'); ?></h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Ingredient', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Sales', 'vapevida-quiz'); ?></th>
                            <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_primary_by_sales)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="vv-empty-state">
                                        <div class="vv-empty-state-icon">🥇</div>
                                        <div class="vv-empty-state-text">
                                            <?php esc_html_e('No search data with sales yet.', 'vapevida-quiz'); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php
                            $rank = 1;
                            foreach ($top_primary_by_sales as $item):
                                $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other";
                                $cvr = ($item->count > 0) ? round(($item->sales_count / $item->count) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                        <?php echo vv_quiz_get_term_name($item->primary_ingredient_term, $item->ingredient_slug); ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($item->sales_count)); ?></td>
                                    <td class="vv-revenue-col"><?php echo wc_price($item->sales_value); ?></td>
                                </tr>
                                <?php
                                $rank++;
                            endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="vv-tables-grid">
            <div class="vv-table-card vv-table-card-full">
                <h2 style="border-bottom-color: #ffc107;">
                    <?php esc_html_e('Top 10 Products Sold by Quiz (by Revenue)', 'vapevida-quiz'); ?>
                </h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Qty Sold', 'vapevida-quiz'); ?></th>
                            <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_sold_products)): ?>
                            <tr>
                                <td colspan="3">
                                    <div class="vv-empty-state">
                                        <div class="vv-empty-state-icon">📦</div>
                                        <div class="vv-empty-state-text">
                                            <?php esc_html_e('No product sales have been tracked from the quiz yet.', 'vapevida-quiz'); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $rank = 1;
                            foreach ($top_sold_products as $product): ?>
                                <?php $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other"; ?>
                                <tr>
                                    <td>
                                        <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                        <?php if ($product->product_name): ?>
                                            <a href="<?php echo esc_url(get_edit_post_link($product->product_id)); ?>">
                                                <?php echo esc_html($product->product_name); ?>
                                            </a>
                                        <?php else: ?>
                                            <em><?php esc_html_e('Product Deleted', 'vapevida-quiz'); ?> (ID:
                                                <?php echo esc_html($product->product_id); ?>)</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($product->total_quantity)); ?></td>
                                    <td class="vv-revenue-col"><?php echo wc_price($product->total_revenue); ?></td>
                                </tr>
                                <?php $rank++; endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>


        <div class="vv-tables-grid">

            <div class="vv-table-card">
                <h2 style="border-bottom-color: #28a745;">
                    <?php esc_html_e('Top Converting Combinations (by Revenue)', 'vapevida-quiz'); ?>
                </h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Combination', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Sales', 'vapevida-quiz'); ?></th>
                            <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_converting_combos)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="vv-empty-state">
                                        <div class="vv-empty-state-icon">💰</div>
                                        <div class="vv-empty-state-text">
                                            <?php esc_html_e('No converting searches yet.', 'vapevida-quiz'); ?>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($top_converting_combos as $combo): ?>
                                <?php $cvr = ($combo->count > 0) ? round(($combo->sales_count / $combo->count) * 100, 1) : 0; ?>
                                <tr>
                                    <td>
                                        <?php
                                        $parts = [];
                                        if (!empty($combo->type_term)) {
                                            $parts[] = vv_quiz_get_term_name($combo->type_term, $combo->type_slug);
                                        }
                                        if (!empty($combo->ing1)) {
                                            $parts[] = vv_quiz_get_term_name($combo->ing1, $combo->ingredient_slug);
                                        }
                                        if (!empty($combo->ing2)) {
                                            $parts[] = vv_quiz_get_term_name($combo->ing2, $combo->ingredient_slug);
                                        }
                                        echo implode(' + ', $parts);
                                        ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($combo->sales_count)); ?></td>
                                    <td class="vv-revenue-col"><?php echo wc_price($combo->sales_value); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="vv-table-card">
                <h2 style="border-bottom-color: #667eea;">
                    <?php esc_html_e('Top Popular Combinations (by Searches)', 'vapevida-quiz'); ?>
                </h2>
                <table class="vv-analytics-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Combination', 'vapevida-quiz'); ?></th>
                            <th class="vv-count-col"><?php esc_html_e('Searches', 'vapevida-quiz'); ?></th>
                            <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                            <th class="vv-cvr-col"><?php esc_html_e('CVR', 'vapevida-quiz'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_popular_combos)): ?>
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
                            <?php foreach ($top_popular_combos as $combo): ?>
                                <?php $cvr = ($combo->count > 0) ? round(($combo->sales_count / $combo->count) * 100, 1) : 0; ?>
                                <tr>
                                    <td>
                                        <?php
                                        $parts = [];
                                        if (!empty($combo->type_term)) {
                                            $parts[] = vv_quiz_get_term_name($combo->type_term, $combo->type_slug);
                                        }
                                        if (!empty($combo->ing1)) {
                                            $parts[] = vv_quiz_get_term_name($combo->ing1, $combo->ingredient_slug);
                                        }
                                        if (!empty($combo->ing2)) {
                                            $parts[] = vv_quiz_get_term_name($combo->ing2, $combo->ingredient_slug);
                                        }
                                        echo implode(' + ', $parts);
                                        ?>
                                    </td>
                                    <td class="vv-count-col"><?php echo esc_html(number_format($combo->count)); ?></td>
                                    <td class="vv-revenue-col"><?php echo wc_price($combo->sales_value); ?></td>
                                    <td class="vv-cvr-col"><?php echo esc_html($cvr); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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