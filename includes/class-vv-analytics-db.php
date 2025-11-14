<?php
/**
 * Analytics Database Setup for VapeVida Quiz.
 * Handles creation of the custom tables for tracking.
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Creates/Updates the custom analytics tables on plugin activation.
 */
function vv_quiz_analytics_db_install()
{
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // --- Table 1: Search Analytics ---
    $table_name_analytics = $wpdb->prefix . 'vv_quiz_analytics';
    $sql_analytics = "CREATE TABLE $table_name_analytics (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        search_timestamp datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        user_id_hash varchar(64) NOT NULL,
        type_slug varchar(100) NOT NULL,
        type_term varchar(100) NOT NULL,
        ingredient_slug varchar(100) NOT NULL,
        primary_ingredient_term varchar(100) NOT NULL,
        secondary_ingredient_term varchar(100) DEFAULT '' NOT NULL,
        converted tinyint(1) DEFAULT 0 NOT NULL,
        order_id mediumint(9) DEFAULT 0 NOT NULL,
        order_total decimal(10,2) DEFAULT 0.00 NOT NULL,
        PRIMARY KEY  (id),
        KEY idx_type_term (type_term),
        KEY idx_primary_ingredient (primary_ingredient_term),
        KEY idx_order_id (order_id),
        KEY idx_converted (converted)
    ) $charset_collate;";

    dbDelta($sql_analytics); // dbDelta handles table creation and updates

    // --- Table 2: Converted Items ---
    $table_name_items = $wpdb->prefix . 'vv_quiz_conversion_items';
    $sql_items = "CREATE TABLE $table_name_items (
        item_id bigint(20) NOT NULL AUTO_INCREMENT,
        search_id mediumint(9) NOT NULL,
        order_id bigint(20) NOT NULL,
        product_id bigint(20) NOT NULL,
        variation_id bigint(20) DEFAULT 0 NOT NULL,
        quantity int(11) NOT NULL,
        subtotal decimal(10,2) NOT NULL,
        PRIMARY KEY  (item_id),
        KEY idx_search_id (search_id),
        KEY idx_product_id (product_id)
    ) $charset_collate;";

    dbDelta($sql_items); // dbDelta creates/updates the new table
}