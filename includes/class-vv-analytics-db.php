<?php
/**
 * Analytics Database Setup for VapeVida Quiz.
 * Handles creation of the custom table for tracking quiz submissions.
 */

if (!defined('ABSPATH'))
    exit;

/**
 * Creates the custom analytics table on plugin activation.
 * This table will store every quiz submission.
 */
function vv_quiz_analytics_db_install()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'vv_quiz_analytics';
    $charset_collate = $wpdb->get_charset_collate();

    // SQL to create the new table
    $sql = "CREATE TABLE $table_name (
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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql); // dbDelta handles table creation and updates
}