<?php
/**
 * Analytics Tracking Logic for VapeVida Quiz.
 * Handles writing search events to the custom database table.
 */

if (!defined('ABSPATH'))
    exit;

class VV_Analytics
{
    /**
     * Gets a unique, anonymous hash for the current user.
     * Uses a long-lived cookie to group sessions.
     *
     * @return string A 64-char SHA256 hash.
     */
    private static function get_user_hash()
    {
        $cookie_name = 'vv_quiz_uid';
        $user_hash = '';

        if (isset($_COOKIE[$cookie_name])) {
            $user_hash = sanitize_text_field($_COOKIE[$cookie_name]);
        }

        // If no hash or invalid, create a new one
        if (empty($user_hash) || strlen($user_hash) !== 64) {
            // Generate a unique ID based on IP and User Agent for anonymity
            $anon_string = (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') .
                (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') .
                microtime();

            $user_hash = hash('sha256', $anon_string);

            // Set the cookie for 1 year
            setcookie($cookie_name, $user_hash, time() + (86400 * 365), COOKIEPATH, COOKIE_DOMAIN);
        }

        return $user_hash;
    }

    /**
     * Tracks and logs a single quiz search event.
     *
     * @param string $type_slug Taxonomy slug for the 'type' (e.g., 'pa_geuseis').
     * @param string $type_term Term slug for the 'type' (e.g., 'fruits').
     * @param string $ingredient_slug Taxonomy slug for the 'ingredient' (e.g., 'pa_quiz-ingredient').
     * @param string $primary_term Term slug for the 'primary ingredient'.
     * @param string $secondary_term Term slug for the 'secondary ingredient'.
     */
    public static function track_search($type_slug, $type_term, $ingredient_slug, $primary_term, $secondary_term)
    {
        // Don't track empty searches (e.g., if user just lands on shop page)
        if (empty($type_term) && empty($primary_term) && empty($secondary_term)) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'vv_quiz_analytics';

        $user_hash = self::get_user_hash();

        $wpdb->insert(
            $table_name,
            array(
                'search_timestamp' => current_time('mysql'),
                'user_id_hash' => $user_hash,
                'type_slug' => $type_slug,
                'type_term' => $type_term,
                'ingredient_slug' => $ingredient_slug,
                'primary_ingredient_term' => $primary_term,
                'secondary_ingredient_term' => $secondary_term,
                'converted' => 0 // 'converted' field is for future use (sales tracking)
            ),
            array(
                '%s', // search_timestamp
                '%s', // user_id_hash
                '%s', // type_slug
                '%s', // type_term
                '%s', // ingredient_slug
                '%s', // primary_ingredient_term
                '%s', // secondary_ingredient_term
                '%d'  // converted
            )
        );

        // Note: Conversion tracking (setting 'converted' to 1) is a complex
        // feature that requires hooking into the WooCommerce 'thank you' page
        // and matching the user_id_hash. This implementation lays the groundwork.
    }
}