<?php
/**
 * Admin Analytics Page VIEW for VapeVida Quiz.
 * Renders the HTML layout.
 *
 * This file is included by admin-analytics-page.php
 * It expects a $data (VV_Analytics_Data object) and $date_range_label (string) to be available.
 */

if (!defined('ABSPATH'))
    exit;

?>
<div class="wrap vv-analytics-wrap">
    <div class="vv-analytics-header">
        <div class="vv-analytics-header-flex">
            <div>
                <h1><?php esc_html_e('VapeVida Quiz Analytics', 'vapevida-quiz'); ?></h1>
                <p><?php esc_html_e('Comprehensive insights into customer preferences and quiz interactions', 'vapevida-quiz'); ?>
                </p>
            </div>
            <form method="get" style="display: inline-block; margin-right: 10px; vertical-align: middle;">
                <input type="hidden" name="page" value="vv-quiz-analytics" />

                <label for="vv-analytics-range"><?php _e('Date Range:', 'vapevida-quiz'); ?></label>
                <select name="range" id="vv-analytics-range">
                    <option value="all_time" <?php selected($selected_range, 'all_time'); ?>>
                        <?php _e('All Time', 'vapevida-quiz'); ?>
                    </option>
                    <option value="30_days" <?php selected($selected_range, '30_days'); ?>>
                        <?php _e('Last 30 Days', 'vapevida-quiz'); ?>
                    </option>
                    <option value="7_days" <?php selected($selected_range, '7_days'); ?>>
                        <?php _e('Last 7 Days', 'vapevida-quiz'); ?>
                    </option>
                    <option value="this_month" <?php selected($selected_range, 'this_month'); ?>>
                        <?php _e('This Month', 'vapevida-quiz'); ?>
                    </option>
                </select>

                <button type="submit" class="button button-primary">
                    <?php _e('Filter', 'vapevida-quiz'); ?>
                </button>
            </form>

            <form method="get" style="display: inline-block; vertical-align: middle;">
                <input type="hidden" name="page" value="vv-quiz-analytics" />

                <input type="hidden" name="range" value="<?php echo esc_attr($selected_range); ?>" />

                <input type="hidden" name="vv_export_analytics" value="true" />

                <button type="submit" class="button">
                    <?php _e('Export to CSV', 'vapevida-quiz'); ?>
                </button>
            </form>
        </div>
    </div>

    <div class="vv-stats-grid">
        <div class="vv-stat-card">
            <div class="vv-stat-icon">🔍</div>
            <div class="vv-stat-label"><?php esc_html_e('Total Searches', 'vapevida-quiz'); ?></div>
            <div class="vv-stat-value"><?php echo esc_html(number_format($data->total_searches)); ?></div>
        </div>

        <div class="vv-stat-card">
            <div class="vv-stat-icon">✅</div>
            <div class="vv-stat-label"><?php esc_html_e('Multi-Step Searches', 'vapevida-quiz'); ?></div>
            <div class="vv-stat-value"><?php echo esc_html(number_format($data->complete_searches)); ?></div>
            <?php if ($data->total_searches > 0): ?>
                <div class="vv-stat-percentage">
                    <?php echo esc_html(round(($data->complete_searches / $data->total_searches) * 100, 1)); ?>%
                    <?php esc_html_e('completion rate', 'vapevida-quiz'); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="vv-stat-card" style="border-left-color: #28a745;">
            <div class="vv-stat-icon">💰</div>
            <div class="vv-stat-label"><?php esc_html_e('Total Revenue from Quiz', 'vapevida-quiz'); ?></div>
            <div class="vv-stat-value vv-stat-price" style="color: #28a745;">
                <?php echo wc_price($data->total_sales_value); ?>
            </div>
            <div class="vv-stat-percentage" style="color: #555;">
                <?php printf(esc_html__('%s total sales', 'vapevida-quiz'), esc_html(number_format($data->total_sales_count))); ?>
            </div>
        </div>

        <div class="vv-stat-card" style="border-left-color: #17a2b8;">
            <div class="vv-stat-icon">📈</div>
            <div class="vv-stat-label"><?php esc_html_e('Conversion Rate', 'vapevida-quiz'); ?></div>
            <div class="vv-stat-value" style="color: #17a2b8;"><?php echo esc_html($data->conversion_rate); ?>%</div>
            <div class="vv-stat-percentage" style="color: #555;">
                <?php printf(esc_html__('%s sales from %s searches', 'vapevida-quiz'), esc_html(number_format($data->total_sales_count)), esc_html(number_format($data->total_searches))); ?>
            </div>
        </div>


        <!-- <div class="vv-stat-card">
            <div class="vv-stat-icon">🥇</div>
            <div class="vv-stat-label"><?php esc_html_e('With Primary Ingredient', 'vapevida-quiz'); ?></div>
            <div class="vv-stat-value"><?php echo esc_html(number_format($data->searches_with_primary)); ?></div>
            <?php if ($data->total_searches > 0): ?>
                <div class="vv-stat-percentage">
                    <?php echo esc_html(round(($data->searches_with_primary / $data->total_searches) * 100, 1)); ?>%
                </div>
            <?php endif; ?>
        </div>

        <div class="vv-stat-card">
            <div class="vv-stat-icon">🥈</div>
            <div class="vv-stat-label"><?php esc_html_e('With Secondary Ingredient', 'vapevida-quiz'); ?></div>
            <div class="vv-stat-value"><?php echo esc_html(number_format($data->searches_with_secondary)); ?></div>
            <?php if ($data->total_searches > 0): ?>
                <div class="vv-stat-percentage">
                    <?php echo esc_html(round(($data->searches_with_secondary / $data->total_searches) * 100, 1)); ?>%
                </div>
            <?php endif; ?>
        </div> -->
    </div>

    <div class="vv-charts-grid">
        <div class="vv-chart-card">
            <h2><?php esc_html_e('Top Flavor Types (by Popularity)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span></h2>
            <div class="vv-chart-container">
                <canvas id="vvTopTypesChart"></canvas>
            </div>
        </div>

        <div class="vv-chart-card">
            <h2><?php esc_html_e('Top Primary Ingredients (by Popularity)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span></h2>
            <div class="vv-chart-container">
                <canvas id="vvTopPrimaryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="vv-tables-grid">
        <div class="vv-table-card">
            <h2><?php esc_html_e('Top 10 Flavor Types (by Revenue)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span></h2>
            <table class="vv-analytics-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Flavor Type', 'vapevida-quiz'); ?></th>
                        <th class="vv-count-col"><?php esc_html_e('Sales', 'vapevida-quiz'); ?></th>
                        <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data->top_types_by_sales)): ?>
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
                        foreach ($data->top_types_by_sales as $item):
                            $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other";
                            $cvr = ($item->count > 0) ? round(($item->sales_count / $item->count) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                    <?php echo VV_Analytics_Data::get_term_name($item->type_term, $item->type_slug); ?>
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
            <h2><?php esc_html_e('Top 10 Primary Ingredients (by Revenue)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span></h2>
            <table class="vv-analytics-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Ingredient', 'vapevida-quiz'); ?></th>
                        <th class="vv-count-col"><?php esc_html_e('Sales', 'vapevida-quiz'); ?></th>
                        <th class="vv-revenue-col"><?php esc_html_e('Revenue', 'vapevida-quiz'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data->top_primary_by_sales)): ?>
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
                        foreach ($data->top_primary_by_sales as $item):
                            $rank_class = $rank <= 3 ? "vv-rank-$rank" : "vv-rank-other";
                            $cvr = ($item->count > 0) ? round(($item->sales_count / $item->count) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <span class="vv-rank-badge <?php echo $rank_class; ?>"><?php echo $rank; ?></span>
                                    <?php echo VV_Analytics_Data::get_term_name($item->primary_ingredient_term, $item->ingredient_slug); ?>
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
                <?php esc_html_e('Top 10 Products Sold by Quiz (by Revenue)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span>
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
                    <?php if (empty($data->top_sold_products)): ?>
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
                        foreach ($data->top_sold_products as $product): ?>
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
                <?php esc_html_e('Top Converting Combinations (by Revenue)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span>
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
                    <?php if (empty($data->top_converting_combos)): ?>
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
                        <?php foreach ($data->top_converting_combos as $combo): ?>
                            <?php $cvr = ($combo->count > 0) ? round(($combo->sales_count / $combo->count) * 100, 1) : 0; ?>
                            <tr>
                                <td>
                                    <?php
                                    $parts = [];
                                    if (!empty($combo->type_term)) {
                                        $parts[] = VV_Analytics_Data::get_term_name($combo->type_term, $combo->type_slug);
                                    }
                                    if (!empty($combo->ing1)) {
                                        $parts[] = VV_Analytics_Data::get_term_name($combo->ing1, $combo->ingredient_slug);
                                    }
                                    if (!empty($combo->ing2)) {
                                        $parts[] = VV_Analytics_Data::get_term_name($combo->ing2, $combo->ingredient_slug);
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
                <?php esc_html_e('Top Popular Combinations (by Searches)', 'vapevida-quiz'); ?> <span
                    class="vv-table-date-range">(<?php echo esc_html($date_range_label); ?>)</span>
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
                    <?php if (empty($data->top_popular_combos)): ?>
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
                        <?php foreach ($data->top_popular_combos as $combo): ?>
                            <?php $cvr = ($combo->count > 0) ? round(($combo->sales_count / $combo->count) * 100, 1) : 0; ?>
                            <tr>
                                <td>
                                    <?php
                                    $parts = [];
                                    if (!empty($combo->type_term)) {
                                        $parts[] = VV_Analytics_Data::get_term_name($combo->type_term, $combo->type_slug);
                                    }
                                    if (!empty($combo->ing1)) {
                                        $parts[] = VV_Analytics_Data::get_term_name($combo->ing1, $combo->ingredient_slug);
                                    }
                                    if (!empty($combo->ing2)) {
                                        $parts[] = VV_Analytics_Data::get_term_name($combo->ing2, $combo->ingredient_slug);
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