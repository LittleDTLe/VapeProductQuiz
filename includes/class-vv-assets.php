<?php
/**
 * Asset Manager for VapeVida Quiz.
 * Enqueues all admin and frontend CSS & JS.
 */

if (!defined('ABSPATH'))
    exit;

class VV_Assets
{

    /**
     * Constructor. Adds the hooks.
     */
    public function __construct()
    {
        // Hook for frontend assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Hook for admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Enqueues all Admin assets
     */
    public function enqueue_admin_assets($hook_suffix)
    {

        // --- 1. Assets for the MAIN SETTINGS page ---
        if ($hook_suffix === 'toplevel_page_vv-quiz-main') {

            // Enqueue Color Picker
            wp_enqueue_style('wp-color-picker');

            // Enqueue custom settings CSS
            wp_enqueue_style(
                'vv-quiz-admin-settings',
                VV_QUIZ_URL . 'assets/css/admin-settings.css',
                array(),
                VV_QUIZ_VERSION
            );

            // Enqueue custom settings JS
            wp_enqueue_script(
                'vv-quiz-admin-settings',
                VV_QUIZ_URL . 'assets/js/admin-settings.js',
                array('jquery', 'wp-color-picker'), // Add 'wp-color-picker' dependency
                VV_QUIZ_VERSION,
                true
            );

            // Pass translatable strings to our admin JS
            wp_localize_script(
                'vv-quiz-admin-settings',
                'vvAdminSettings',
                array(
                    'i18n' => array(
                        'copied' => __('Copied!', 'vapevida-quiz'),
                        'copyFailed' => __('Copy failed. Please copy manually.', 'vapevida-quiz')
                    )
                )
            );
        }

        // --- 2. Assets for the ANALYTICS page ---
        if ($hook_suffix === 'vapevida-quiz_page_vv-quiz-analytics') {

            $this->enqueue_analytics_assets();
        }
    }

    /**
     * Enqueues assets for the frontend quiz (shortcode)
     */
    public function enqueue_frontend_assets()
    {
        // Only load if the shortcode is present on the page
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'vapevida_quiz')) {

            // Enqueue Frontend Quiz CSS
            wp_enqueue_style(
                'vv-quiz-frontend',
                VV_QUIZ_URL . 'assets/css/frontend-quiz.css',
                array(),
                VV_QUIZ_VERSION
            );

            // Enqueue Frontend Quiz JS
            wp_enqueue_script(
                'vv-quiz-frontend-script',
                VV_QUIZ_URL . 'assets/js/vv-quiz-dynamic.js',
                array('jquery'),
                VV_QUIZ_VERSION,
                true
            );

            // Localize script with data (MOVED FROM class-vv-core.php)
            $settings = get_option('vv_quiz_settings');
            $cta_button_text = isset($settings['button_cta']) ? $settings['button_cta'] : __('FIND YOUR LIQUID', VV_QUIZ_TEXT_DOMAIN);
            $placeholder_primary = isset($settings['placeholder_primary']) ? $settings['placeholder_primary'] : __('-- Select Primary Ingredient --', VV_QUIZ_TEXT_DOMAIN);
            $placeholder_secondary = isset($settings['placeholder_secondary']) ? $settings['placeholder_secondary'] : __('-- Select Secondary Ingredient --', VV_QUIZ_TEXT_DOMAIN);

            $error_type = isset($settings['error_msg_type']) && !empty($settings['error_msg_type'])
                ? $settings['error_msg_type']
                : __('Please select a Flavor Type.', VV_QUIZ_TEXT_DOMAIN);
            $error_primary = isset($settings['error_msg_primary']) && !empty($settings['error_msg_primary'])
                ? $settings['error_msg_primary']
                : __('Please select a Primary Ingredient.', VV_QUIZ_TEXT_DOMAIN);
            $error_secondary = isset($settings['error_msg_secondary']) && !empty($settings['error_msg_secondary'])
                ? $settings['error_msg_secondary']
                : __('Please select a Secondary Ingredient.', VV_QUIZ_TEXT_DOMAIN);

            $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;
            $type_taxonomy_slug = $use_custom && !empty($settings['attribute_type_slug'])
                ? $settings['attribute_type_slug']
                : 'pa_geuseis';
            $ingredient_taxonomy_slug = $use_custom && !empty($settings['attribute_ingredient_slug'])
                ? $settings['attribute_ingredient_slug']
                : 'pa_quiz-ingredient';

            $is_type_required = !empty($settings['is_type_required']);
            $is_primary_required = !empty($settings['is_primary_required']);
            $is_secondary_required = !empty($settings['is_secondary_required']) && !empty($settings['field_status']);

            wp_localize_script(
                'vv-quiz-frontend-script',
                'vv_quiz_ajax',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'placeholder_primary' => $placeholder_primary,
                    'placeholder_secondary' => $placeholder_secondary,
                    'cta_text_default' => $cta_button_text,
                    'nonce' => wp_create_nonce('vv-quiz-nonce'),
                    'type_slug' => $type_taxonomy_slug,
                    'ingredient_slug' => $ingredient_taxonomy_slug,
                    'is_type_required' => $is_type_required,
                    'is_primary_required' => $is_primary_required,
                    'is_secondary_required' => $is_secondary_required,
                    'i18n' => array(
                        'loading' => __('Searching...', VV_QUIZ_TEXT_DOMAIN),
                        'loading_options' => __('Loading...', VV_QUIZ_TEXT_DOMAIN),
                        'cta_default' => $cta_button_text,
                        'no_results' => __('🛑 0 RESULTS', VV_QUIZ_TEXT_DOMAIN),
                        'one_result' => __('FIND 1 PRODUCT', VV_QUIZ_TEXT_DOMAIN),
                        'multiple_results' => __('FOUND {count} PRODUCTS', VV_QUIZ_TEXT_DOMAIN),
                        'error_loading' => __('⚠️ DATA ERROR', VV_QUIZ_TEXT_DOMAIN),
                        'error_loading_options' => __('Loading Error', VV_QUIZ_TEXT_DOMAIN),
                        'error_required_type' => $error_type,
                        'error_required_primary' => $error_primary,
                        'error_required_secondary' => $error_secondary,
                    )
                )
            );
        }
    }

    /**
     * Helper to load analytics assets (already modular)
     */
    private function enqueue_analytics_assets()
    {

        // 1. --- Get Date Filter Logic ---
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
        wp_enqueue_style(
            'vv-quiz-admin-analytics',
            VV_QUIZ_URL . 'assets/css/admin-analytics.css',
            array(),
            VV_QUIZ_VERSION
        );

        wp_enqueue_script(
            'chart-js',
            'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js',
            array(),
            '3.7.1',
            true
        );

        wp_enqueue_script(
            'vv-quiz-admin-charts',
            VV_QUIZ_URL . 'assets/js/admin-analytics-charts.js',
            array('jquery', 'chart-js'),
            VV_QUIZ_VERSION,
            true
        );

        // 4. --- Pass data to our new JS file ---
        wp_localize_script(
            'vv-quiz-admin-charts',
            'vvChartData',
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
}