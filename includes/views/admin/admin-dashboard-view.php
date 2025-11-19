<?php
/**
 * View: Dashboard Widget Content
 * Renders the weekly stats and the top flavor type.
 * Assumes $current, $previous, $top_type, $searches_change, etc., are available.
 */

if (!defined('ABSPATH'))
    exit;

// Helper to determine the dashicon and color class based on the change value
$get_change_class = function ($change) {
    if ($change > 0)
        return 'positive';
    if ($change < 0)
        return 'negative';
    return 'neutral';
};

$get_dashicon = function ($change) {
    if ($change > 0)
        return 'dashicons-arrow-up-alt';
    if ($change < 0)
        return 'dashicons-arrow-down-alt';
    return 'dashicons-minus';
};
?>
<div class="vv-dashboard-widget">
    <div class="vv-widget-grid">

        <div class="vv-widget-card">
            <div class="vv-widget-label"><?php _e('Quiz Searches', 'vapevida-quiz'); ?></div>
            <div class="vv-widget-value"><?php echo number_format($current['searches']); ?></div>
            <div class="vv-widget-change <?php echo $get_change_class($searches_change); ?>">
                <span class="dashicons <?php echo $get_dashicon($searches_change); ?>"></span>
                <?php echo abs($searches_change); ?>% <?php _e('vs last week', 'vapevida-quiz'); ?>
            </div>
        </div>

        <div class="vv-widget-card revenue">
            <div class="vv-widget-label"><?php _e('Revenue', 'vapevida-quiz'); ?></div>
            <div class="vv-widget-value"><?php echo wc_price($current['revenue']); ?></div>
            <div class="vv-widget-change <?php echo $get_change_class($revenue_change); ?>">
                <span class="dashicons <?php echo $get_dashicon($revenue_change); ?>"></span>
                <?php echo abs($revenue_change); ?>% <?php _e('vs last week', 'vapevida-quiz'); ?>
            </div>
        </div>

        <div class="vv-widget-card sales">
            <div class="vv-widget-label"><?php _e('Orders', 'vapevida-quiz'); ?></div>
            <div class="vv-widget-value"><?php echo number_format($current['sales']); ?></div>
            <div class="vv-widget-change <?php echo $get_change_class($sales_change); ?>">
                <span class="dashicons <?php echo $get_dashicon($sales_change); ?>"></span>
                <?php echo abs($sales_change); ?>% <?php _e('vs last week', 'vapevida-quiz'); ?>
            </div>
        </div>

        <div class="vv-widget-card cvr">
            <div class="vv-widget-label"><?php _e('Conversion Rate', 'vapevida-quiz'); ?></div>
            <div class="vv-widget-value"><?php echo $current['cvr']; ?>%</div>
            <div class="vv-widget-change <?php echo $get_change_class($cvr_change); ?>">
                <span class="dashicons <?php echo $get_dashicon($cvr_change); ?>"></span>
                <?php echo abs($cvr_change); ?>% <?php _e('vs last week', 'vapevida-quiz'); ?>
            </div>
        </div>
    </div>

    <?php if ($top_type): ?>
        <div class="vv-widget-top-type">
            <span style="opacity: 0.9;"><?php _e('🔥 Top Flavor This Week', 'vapevida-quiz'); ?></span>
            <strong><?php echo esc_html(VV_Analytics_Data::get_term_name($top_type->type_term, $top_type->type_slug)); ?></strong>
            <span style="opacity: 0.9;"><?php echo number_format($top_type->count); ?>
                <?php _e('searches', 'vapevida-quiz'); ?></span>
        </div>
    <?php endif; ?>

    <div class="vv-widget-footer">
        <div class="vv-widget-link">
            <a href="<?php echo admin_url('admin.php?page=vv-quiz-analytics&range=7_days'); ?>">
                <?php _e('View Full Analytics', 'vapevida-quiz'); ?>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
            </a>
        </div>
    </div>
</div>