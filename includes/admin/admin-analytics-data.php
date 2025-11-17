<?php
/**
 * Analytics Data Class for VapeVida Quiz.
 * Handles all database queries for the analytics page.
 */

if (!defined('ABSPATH'))
    exit;

class VV_Analytics_Data
{
    // --- Properties to store our data ---
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

    /**
     * Constructor. Runs all queries.
     * @param string $date_filter_sql       - The SQL "AND" clause for dates.
     * @param string $date_filter_sql_where - The SQL "WHERE" clause for dates.
     */
    public function __construct($date_filter_sql, $date_filter_sql_where)
    {
        global $wpdb;
        $this->analytics_table = $wpdb->prefix . 'vv_quiz_analytics';
        $this->items_table = $wpdb->prefix . 'vv_quiz_conversion_items';

        // Run all our queries
        $this->fetch_kpi_metrics($date_filter_sql, $date_filter_sql_where);
        $this->fetch_top_types($date_filter_sql);
        $this->fetch_top_primary($date_filter_sql);
        $this->fetch_top_combos($date_filter_sql);
        $this->fetch_top_products($date_filter_sql);
    }

    /**
     * Fetches all the main KPI cards.
     */
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

    /**
     * Fetches top types by popularity and sales.
     */
    private function fetch_top_types($date_filter_sql)
    {
        global $wpdb;
        $this->top_types_by_popularity = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT type_term, type_slug, COUNT(*) as count
                FROM $this->analytics_table 
                WHERE type_term != '' $date_filter_sql
                GROUP BY type_term, type_slug 
                ORDER BY count DESC
                LIMIT %d",
                10
            )
        );

        $this->top_types_by_sales = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT type_term, type_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value
                FROM $this->analytics_table 
                WHERE type_term != '' AND converted > 0 $date_filter_sql
                GROUP BY type_term, type_slug 
                ORDER BY sales_value DESC, sales_count DESC
                LIMIT %d",
                10
            )
        );
    }

    /**
     * Fetches top primary ingredients by popularity and sales.
     */
    private function fetch_top_primary($date_filter_sql)
    {
        global $wpdb;
        $this->top_primary_by_popularity = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count
                FROM $this->analytics_table 
                WHERE primary_ingredient_term != '' $date_filter_sql
                GROUP BY primary_ingredient_term, ingredient_slug 
                ORDER BY count DESC
                LIMIT %d",
                10
            )
        );

        $this->top_primary_by_sales = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT primary_ingredient_term, ingredient_slug, COUNT(*) as count, SUM(converted) as sales_count, SUM(order_total) as sales_value
                FROM $this->analytics_table 
                WHERE primary_ingredient_term != '' AND converted > 0 $date_filter_sql
                GROUP BY primary_ingredient_term, ingredient_slug 
                ORDER BY sales_value DESC, sales_count DESC
                LIMIT %d",
                10
            )
        );
    }

    /**
     * Fetches top combinations by popularity and sales.
     */
    private function fetch_top_combos($date_filter_sql)
    {
        global $wpdb;
        $this->top_converting_combos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    type_term, type_slug, 
                    LEAST(primary_ingredient_term, secondary_ingredient_term) as ing1,
                    GREATEST(primary_ingredient_term, secondary_ingredient_term) as ing2,
                    ingredient_slug, 
                    COUNT(*) as count, 
                    SUM(converted) as sales_count, 
                    SUM(order_total) as sales_value
                FROM $this->analytics_table 
                WHERE converted > 0 $date_filter_sql
                GROUP BY type_term, type_slug, ing1, ing2, ingredient_slug
                ORDER BY sales_value DESC, sales_count DESC
                LIMIT %d",
                15
            )
        );

        $this->top_popular_combos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    type_term, type_slug, 
                    LEAST(primary_ingredient_term, secondary_ingredient_term) as ing1,
                    GREATEST(primary_ingredient_term, secondary_ingredient_term) as ing2,
                    ingredient_slug, 
                    COUNT(*) as count, 
                    SUM(converted) as sales_count, 
                    SUM(order_total) as sales_value
                FROM $this->analytics_table 
                WHERE (type_term != '' OR primary_ingredient_term != '') $date_filter_sql
                GROUP BY type_term, type_slug, ing1, ing2, ingredient_slug 
                ORDER BY count DESC, sales_value DESC
                LIMIT %d",
                15
            )
        );
    }

    /**
     * Fetches top sold products from the dedicated items table.
     */
    private function fetch_top_products($date_filter_sql)
    {
        global $wpdb;
        $this->top_sold_products = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    p.post_title as product_name, 
                    i.product_id, 
                    SUM(i.quantity) as total_quantity, 
                    SUM(i.subtotal) as total_revenue
                FROM 
                    $this->items_table as i
                LEFT JOIN 
                    $this->analytics_table as a ON i.search_id = a.id
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
    }

    /**
     * Helper function to get a term's display name from its slug.
     *
     * @param string $term_slug The term slug (e.g., 'q-karamela').
     * @param string $taxonomy  The taxonomy slug (e.g., 'pa_geuseis').
     * @return string The term's display name (e.g., 'Karamela') or a fallback.
     */
    public static function get_term_name($term_slug, $taxonomy)
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
}