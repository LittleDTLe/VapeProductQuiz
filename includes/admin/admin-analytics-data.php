<?php
/**
 * Analytics Data Class for VapeVida Quiz.
 * VERSION: 2.0 - EXCEL OPTIMIZED (Updated: 2025-11-18)
 */

if (!defined('ABSPATH'))
    exit;

// Clear any PHP caches
if (function_exists('opcache_reset')) {
    @opcache_reset();
}

class VV_Analytics_Data
{
    public $total_searches = 0;
    public $total_sales_count = 0;
    public $total_sales_value = 0;
    public $conversion_rate = 0;
    public $complete_searches = 0;
    public $searches_with_primary = 0;
    public $searches_with_secondary = 0;

    public $top_types_by_popularity = array();
    public $top_types_by_sales = array();
    public $top_primary_by_popularity = array();
    public $top_primary_by_sales = array();
    public $top_converting_combos = array();
    public $top_popular_combos = array();
    public $top_sold_products = array();

    public function __construct($date_filter_sql, $date_filter_sql_where)
    {
        global $wpdb;
        $this->analytics_table = $wpdb->prefix . 'vv_quiz_analytics';
        $this->items_table = $wpdb->prefix . 'vv_quiz_conversion_items';

        $this->fetch_kpi_metrics($date_filter_sql, $date_filter_sql_where);
        $this->fetch_top_types($date_filter_sql);
        $this->fetch_top_primary($date_filter_sql);
        $this->fetch_top_combos($date_filter_sql);
        $this->fetch_top_products($date_filter_sql);
    }

    private function fetch_kpi_metrics($date_filter_sql, $date_filter_sql_where)
    {
        global $wpdb;
        $this->total_searches = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->analytics_table $date_filter_sql_where");
        $this->total_sales_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->analytics_table WHERE converted = 1 $date_filter_sql");
        $this->total_sales_value = (float) $wpdb->get_var("SELECT SUM(order_total) FROM $this->analytics_table WHERE converted = 1 $date_filter_sql");
        $this->conversion_rate = ($this->total_searches > 0) ? round(($this->total_sales_count / $this->total_searches) * 100, 1) : 0;
        $this->searches_with_primary = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->analytics_table WHERE primary_ingredient_term != '' $date_filter_sql");
        $this->searches_with_secondary = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->analytics_table WHERE secondary_ingredient_term != '' $date_filter_sql");
        $this->complete_searches = (int) $wpdb->get_var("SELECT COUNT(*) FROM $this->analytics_table WHERE type_term != '' AND primary_ingredient_term != '' AND secondary_ingredient_term != '' $date_filter_sql");
    }

    private function fetch_top_types($date_filter_sql)
    {
        global $wpdb;
        $this->top_types_by_popularity = $wpdb->get_results($wpdb->prepare("SELECT type_term, type_slug, COUNT(*) as count FROM $this->analytics_table WHERE type_term != '' $date_filter_sql GROUP BY type_term, type_slug ORDER BY count DESC LIMIT %d", 10));
        $this->top_types_by_sales = $wpdb->get_results($wpdb->prepare("SELECT type_term, type_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value FROM $this->analytics_table WHERE type_term != '' AND converted > 0 $date_filter_sql GROUP BY type_term, type_slug ORDER BY sales_value DESC, sales_count DESC LIMIT %d", 10));
    }

    private function fetch_top_primary($date_filter_sql)
    {
        global $wpdb;
        $this->top_primary_by_popularity = $wpdb->get_results($wpdb->prepare("SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count FROM $this->analytics_table WHERE primary_ingredient_term != '' $date_filter_sql GROUP BY primary_ingredient_term, ingredient_slug ORDER BY count DESC LIMIT %d", 10));
        $this->top_primary_by_sales = $wpdb->get_results($wpdb->prepare("SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value FROM $this->analytics_table WHERE primary_ingredient_term != '' AND converted > 0 $date_filter_sql GROUP BY primary_ingredient_term, ingredient_slug ORDER BY sales_value DESC, sales_count DESC LIMIT %d", 10));
    }

    private function fetch_top_combos($date_filter_sql)
    {
        global $wpdb;
        $this->top_converting_combos = $wpdb->get_results($wpdb->prepare("SELECT type_term, type_slug, LEAST(primary_ingredient_term, secondary_ingredient_term) as ing1, GREATEST(primary_ingredient_term, secondary_ingredient_term) as ing2, ingredient_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value FROM $this->analytics_table WHERE converted > 0 $date_filter_sql GROUP BY type_term, type_slug, ing1, ing2, ingredient_slug ORDER BY sales_value DESC, sales_count DESC LIMIT %d", 15));
        $this->top_popular_combos = $wpdb->get_results($wpdb->prepare("SELECT type_term, type_slug, LEAST(primary_ingredient_term, secondary_ingredient_term) as ing1, GREATEST(primary_ingredient_term, secondary_ingredient_term) as ing2, ingredient_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value FROM $this->analytics_table WHERE (type_term != '' OR primary_ingredient_term != '') $date_filter_sql GROUP BY type_term, type_slug, ing1, ing2, ingredient_slug ORDER BY count DESC, sales_value DESC LIMIT %d", 15));
    }

    private function fetch_top_products($date_filter_sql)
    {
        global $wpdb;
        $this->top_sold_products = $wpdb->get_results($wpdb->prepare("SELECT p.post_title as product_name, i.product_id, SUM(i.quantity) as total_quantity, SUM(i.subtotal) as total_revenue FROM $this->items_table as i LEFT JOIN $this->analytics_table as a ON i.search_id = a.id LEFT JOIN {$wpdb->posts} as p ON i.product_id = p.ID WHERE 1=1 $date_filter_sql GROUP BY i.product_id ORDER BY total_revenue DESC LIMIT %d", 10));
    }

    public static function get_term_name($term_slug, $taxonomy)
    {
        if (empty($term_slug) || empty($taxonomy))
            return __('None', 'vapevida-quiz');
        static $term_cache = array();
        $cache_key = $taxonomy . '_' . $term_slug;
        if (isset($term_cache[$cache_key]))
            return $term_cache[$cache_key];
        $term = get_term_by('slug', $term_slug, $taxonomy);
        if ($term && !is_wp_error($term)) {
            $term_cache[$cache_key] = $term->name;
            return $term_cache[$cache_key];
        }
        return ucwords(str_replace(array('q-', 'pa_', '-'), ' ', $term_slug));
    }

    public static function export_all_to_csv()
    {

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

        $data = new VV_Analytics_Data($date_filter_sql, $date_filter_sql_where);

        while (ob_get_level()) {
            ob_end_clean();
        }

        $date_label = self::get_date_label($selected_range);
        $filename = "VapeVida-Analytics-{$date_label}-" . date('Ymd-His') . ".csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: no-cache, no-store, must-revalidate');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        $currency = get_woocommerce_currency_symbol();

        fputcsv($output, ['VAPEVIDA QUIZ ANALYTICS REPORT']);

        fputcsv($output, ['KEY PERFORMANCE INDICATORS']);
        fputcsv($output, []);
        fputcsv($output, ['Metric', 'Value']);
        fputcsv($output, ['Total Quiz Searches', $data->total_searches]);
        fputcsv($output, ['Searches with All Fields', $data->complete_searches]);
        fputcsv($output, ['Orders Generated', $data->total_sales_count]);
        fputcsv($output, ['Total Revenue', number_format($data->total_sales_value, 2) . ' ' . $currency]);
        fputcsv($output, ['Conversion Rate', $data->conversion_rate . '%']);
        if ($data->total_searches > 0) {
            $avg_order = $data->total_sales_count > 0 ? $data->total_sales_value / $data->total_sales_count : 0;
            fputcsv($output, ['Average Order Value', number_format($avg_order, 2) . ' ' . $currency]);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP FLAVOR TYPES BY POPULARITY']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Flavor Type', 'Total Searches', 'Share %']);
        if (!empty($data->top_types_by_popularity)) {
            $rank = 1;
            foreach ($data->top_types_by_popularity as $item) {
                $share = ($data->total_searches > 0) ? round(($item->count / $data->total_searches) * 100, 1) : 0;
                fputcsv($output, [$rank, self::get_term_name($item->type_term, $item->type_slug), $item->count, $share . '%']);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP FLAVOR TYPES BY REVENUE']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Flavor Type', 'Searches', 'Orders', 'Revenue (' . $currency . ')']);
        if (!empty($data->top_types_by_sales)) {
            $rank = 1;
            foreach ($data->top_types_by_sales as $item) {
                fputcsv($output, [$rank, self::get_term_name($item->type_term, $item->type_slug), $item->count, $item->sales_count, number_format($item->sales_value, 2)]);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP PRIMARY INGREDIENTS BY POPULARITY']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Ingredient', 'Total Searches', 'Share %']);
        if (!empty($data->top_primary_by_popularity)) {
            $rank = 1;
            foreach ($data->top_primary_by_popularity as $item) {
                $share = ($data->total_searches > 0) ? round(($item->count / $data->total_searches) * 100, 1) : 0;
                fputcsv($output, [$rank, self::get_term_name($item->primary_ingredient_term, $item->ingredient_slug), $item->count, $share . '%']);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP PRIMARY INGREDIENTS BY REVENUE']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Ingredient', 'Searches', 'Orders', 'Revenue (' . $currency . ')']);
        if (!empty($data->top_primary_by_sales)) {
            $rank = 1;
            foreach ($data->top_primary_by_sales as $item) {
                fputcsv($output, [$rank, self::get_term_name($item->primary_ingredient_term, $item->ingredient_slug), $item->count, $item->sales_count, number_format($item->sales_value, 2)]);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP COMBINATIONS BY REVENUE (CONVERTING SEARCHES)']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Combination', 'Searches', 'Orders', 'Revenue (' . $currency . ')']);
        if (!empty($data->top_converting_combos)) {
            $rank = 1;
            foreach ($data->top_converting_combos as $combo) {
                $parts = [];
                if (!empty($combo->type_term))
                    $parts[] = self::get_term_name($combo->type_term, $combo->type_slug);
                if (!empty($combo->ing1))
                    $parts[] = self::get_term_name($combo->ing1, $combo->ingredient_slug);
                if (!empty($combo->ing2))
                    $parts[] = self::get_term_name($combo->ing2, $combo->ingredient_slug);
                $combination = implode(' + ', $parts);
                fputcsv($output, [$rank, $combination, $combo->count, $combo->sales_count, number_format($combo->sales_value, 2)]);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP COMBINATIONS BY POPULARITY (ALL SEARCHES)']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Combination', 'Total Searches', 'Orders', 'Revenue (' . $currency . ')', 'Conversion %']);
        if (!empty($data->top_popular_combos)) {
            $rank = 1;
            foreach ($data->top_popular_combos as $combo) {
                $parts = [];
                if (!empty($combo->type_term))
                    $parts[] = self::get_term_name($combo->type_term, $combo->type_slug);
                if (!empty($combo->ing1))
                    $parts[] = self::get_term_name($combo->ing1, $combo->ingredient_slug);
                if (!empty($combo->ing2))
                    $parts[] = self::get_term_name($combo->ing2, $combo->ingredient_slug);
                $combination = implode(' + ', $parts);
                $cvr = ($combo->count > 0) ? round(($combo->sales_count / $combo->count) * 100, 1) : 0;
                fputcsv($output, [$rank, $combination, $combo->count, $combo->sales_count, number_format($combo->sales_value, 2), $cvr . '%']);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, []);

        fputcsv($output, ['TOP PRODUCTS SOLD VIA QUIZ']);
        fputcsv($output, []);
        fputcsv($output, ['Rank', 'Product Name', 'Quantity Sold', 'Total Revenue (' . $currency . ')']);
        if (!empty($data->top_sold_products)) {
            $rank = 1;
            foreach ($data->top_sold_products as $product) {
                $name = $product->product_name ? $product->product_name : 'Product #' . $product->product_id . ' (Deleted)';
                fputcsv($output, [$rank, $name, $product->total_quantity, number_format($product->total_revenue, 2)]);
                $rank++;
            }
        } else {
            fputcsv($output, ['', 'No data available']);
        }
        fputcsv($output, []);
        fputcsv($output, ['--- END OF REPORT ---']);

        fclose($output);
        exit;
    }

    private static function get_date_label($range)
    {
        switch ($range) {
            case '7_days':
                return 'Last 7 Days';
            case '30_days':
                return 'Last 30 Days';
            case 'this_month':
                return 'This Month';
            default:
                return 'All Time';
        }
    }
}