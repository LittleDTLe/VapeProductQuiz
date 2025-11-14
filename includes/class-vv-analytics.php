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

        // --- Tag the WooCommerce session ---
        if (function_exists('WC') && WC()->session && !WC()->session->has_session()) {
            WC()->session->set_customer_session_cookie(true);
        }
        if (function_exists('WC') && WC()->session) {
            WC()->session->set('vv_quiz_user_hash', $user_hash);
        }

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

    /**
     * Initiates tracking on the shop page load.
     * Hooks into 'template_redirect' to run before headers are sent.
     */
    public static function init_quiz_tracking()
    {
        // Only run on the frontend, on a quiz-filtered page, on a product archive
        if (is_admin() || !isset($_GET['filter_applied']) || !(is_shop() || is_product_category())) {
            return;
        }

        // --- DYNAMIC SETTINGS LOGIC ---
        $settings = get_option('vv_quiz_settings');
        $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;

        $type_slug = $use_custom && !empty($settings['attribute_type_slug'])
            ? $settings['attribute_type_slug']
            : 'pa_geuseis';
        $ingredient_slug = $use_custom && !empty($settings['attribute_ingredient_slug'])
            ? $settings['attribute_ingredient_slug']
            : 'pa_quiz-ingredient';

        // Build the DYNAMIC $_GET keys
        $type_key = str_replace('pa_', 'filter_', $type_slug);
        $ingredient_key = str_replace('pa_', 'filter_', $ingredient_slug);
        $secondary_ingredient_key = $ingredient_key . '-optional';

        // Get the search terms from the URL
        $type_term = isset($_GET[$type_key]) ? sanitize_text_field($_GET[$type_key]) : '';
        $primary_ingredient = isset($_GET[$ingredient_key]) ? sanitize_text_field($_GET[$ingredient_key]) : '';
        $secondary_ingredient = isset($_GET[$secondary_ingredient_key]) ? sanitize_text_field($_GET[$secondary_ingredient_key]) : '';

        // Call the tracking function
        self::track_search(
            $type_slug,
            $type_term,
            $ingredient_slug,
            $primary_ingredient,
            $secondary_ingredient
        );
    }

    /**
     * Saves the user hash from the session to the order meta data.
     * This links the order to the anonymous quiz user.
     *
     * @param WC_Order $order The order object.
     * @param array    $data  The checkout data.
     */
    public static function save_hash_to_order_meta($order, $data)
    {
        if (function_exists('WC') && WC()->session && WC()->session->get('vv_quiz_user_hash')) {
            $user_hash = WC()->session->get('vv_quiz_user_hash');
            $order->add_meta_data('_vv_quiz_user_hash', $user_hash, true);

            // Clear the session variable so it doesn't apply to a future non-quiz order
            WC()->session->set('vv_quiz_user_hash', null);
        }
    }

    /**
     * Updates the analytics table when an order is paid.
     * FINDS THE MATCHING PRODUCTS in the order, calculates their subtotal,
     * and saves that value to the analytics table.
     *
     * **UPDATED:** Now correctly checks variable products by using the parent ID.
     *
     * @param int $order_id The ID of the completed order.
     */
    public static function mark_search_as_converted($order_id)
    {
        global $wpdb;
        $order = wc_get_order($order_id);

        if (!$order) {
            return; // Order not found
        }

        // 1. Check if we have already processed this conversion
        if ($order->get_meta('_vv_analytics_processed')) {
            return;
        }

        // 2. Get the user hash we saved to the order
        $user_hash = $order->get_meta('_vv_quiz_user_hash');
        if (empty($user_hash)) {
            // This order did not come from the quiz, so mark it processed and exit
            $order->add_meta_data('_vv_analytics_processed', true, true);
            $order->save_meta_data();
            return;
        }

        // 3. Find the *most recent* search from this user
        $table_name = $wpdb->prefix . 'vv_quiz_analytics';
        $latest_search = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE user_id_hash = %s 
            ORDER BY search_timestamp DESC 
            LIMIT 1",
            $user_hash
        ));

        if (!$latest_search) {
            // No search found for this user, mark as processed and exit
            $order->add_meta_data('_vv_analytics_processed', true, true);
            $order->save_meta_data();
            return;
        }

        // 4. --- LOGIC: Calculate Attributed Subtotal ---

        // Get the search terms from the analytics row
        $search_type_slug = $latest_search->type_slug;
        $search_type_term = $latest_search->type_term;
        $search_ingredient_slug = $latest_search->ingredient_slug;
        $search_primary_term = $latest_search->primary_ingredient_term;
        $search_secondary_term = $latest_search->secondary_ingredient_term;

        // Create an array of all required ingredient slugs
        $required_ingredients = array_filter([$search_primary_term, $search_secondary_term]);

        $quiz_attributed_total = 0.00;
        $items_matched = false;

        // 5. Loop through every item in the order
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();

            if (!$product) {
                continue;
            }

            // --- FIX: Get the PARENT product ID if this is a variation ---
            // Terms are stored on the parent product, not the variation.
            $parent_id = $product->get_parent_id();
            $product_id_to_check = $parent_id ? $parent_id : $product->get_id();
            // --- END FIX ---

            // --- Check 1: Does the product match the "Type" (e.g., 'pa_geuseis')? ---
            $product_has_type = false;
            if (empty($search_type_term)) {
                $product_has_type = true; // If no type was searched, this check passes
            } else {
                // Use the corrected $product_id_to_check
                if (has_term($search_type_term, $search_type_slug, $product_id_to_check)) {
                    $product_has_type = true;
                }
            }

            // --- Check 2: Does the product match ALL required "Ingredients"? (AND logic) ---
            $product_has_all_ingredients = true; // Assume true until proven false
            if (!empty($required_ingredients)) {
                // Get all ingredient terms for this product, using the corrected $product_id_to_check
                $product_terms = wp_get_post_terms($product_id_to_check, $search_ingredient_slug, array('fields' => 'slugs'));

                if (is_wp_error($product_terms)) {
                    $product_has_all_ingredients = false; // Error, so it's not a match
                } else {
                    // Check if *all* required terms are in the product's list
                    foreach ($required_ingredients as $required_term) {
                        if (!in_array($required_term, $product_terms)) {
                            $product_has_all_ingredients = false; // Missing a required term
                            break; // Stop checking this product
                        }
                    }
                }
            }

            // 6. If it matches ALL criteria, add its subtotal
            // We must also have at least one search criterion to match against
            $has_search_criteria = !empty($search_type_term) || !empty($required_ingredients);

            if ($has_search_criteria && $product_has_type && $product_has_all_ingredients) {
                // It's a match!
                $quiz_attributed_total += $item->get_subtotal(); // Use subtotal (before discounts)
                $items_matched = true;
            }
        }
        // --- END LOGIC ---

        // 7. Only update the DB if at least one item matched
        if ($items_matched) {
            $wpdb->update(
                $table_name,
                array(
                    'converted' => 1,
                    'order_id' => $order_id,
                    'order_total' => $quiz_attributed_total // Save our calculated, attributed subtotal
                ),
                array('id' => $latest_search->id), // Where
                array(
                    '%d', // converted
                    '%d', // order_id
                    '%f'  // order_total
                ),
                array('%d') // Where format
            );
        }

        // 8. Mark the order as processed (even if no items matched)
        // This prevents re-checking this order on every status change.
        $order->add_meta_data('_vv_analytics_processed', true, true);
        $order->save_meta_data();
    }
}
// Hook to initiate tracking early
add_action('template_redirect', array('VV_Analytics', 'init_quiz_tracking'));

// Hook to save meta data when the order is created
add_action('woocommerce_checkout_create_order', array('VV_Analytics', 'save_hash_to_order_meta'), 10, 2);

// Hook to mark conversion when order is paid (processing or completed)
add_action('woocommerce_order_status_processing', array('VV_Analytics', 'mark_search_as_converted'), 10, 1);
add_action('woocommerce_order_status_completed', array('VV_Analytics', 'mark_search_as_converted'), 10, 1);