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
     * and saves that value to the analytics table AND the new items table.
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
            // This order did not come from the quiz
            $order->add_meta_data('_vv_analytics_processed', true, true);
            $order->save_meta_data();
            return;
        }

        // 3. Find the *most recent* search from this user
        $table_name_analytics = $wpdb->prefix . 'vv_quiz_analytics';
        $latest_search = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name_analytics 
            WHERE user_id_hash = %s 
            ORDER BY search_timestamp DESC 
            LIMIT 1",
            $user_hash
        ));

        if (!$latest_search) {
            // No search found for this user
            $order->add_meta_data('_vv_analytics_processed', true, true);
            $order->save_meta_data();
            return;
        }

        // 4. --- LOGIC: Calculate Attributed Subtotal & Collect Items ---

        $search_type_slug = $latest_search->type_slug;
        $search_type_term = $latest_search->type_term;
        $search_ingredient_slug = $latest_search->ingredient_slug;
        $required_ingredients = array_filter([$latest_search->primary_ingredient_term, $latest_search->secondary_ingredient_term]);

        $quiz_attributed_total = 0.00;
        $matched_items_for_db = array(); // NEW: Array to hold items for the new table

        // 5. Loop through every item in the order
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            // Get the ID to check (parent or self)
            $parent_id = $product->get_parent_id();
            $product_id_to_check = $parent_id ? $parent_id : $product->get_id();

            // --- Check 1: Type match ---
            $product_has_type = false;
            if (empty($search_type_term) || has_term($search_type_term, $search_type_slug, $product_id_to_check)) {
                $product_has_type = true;
            }

            // --- Check 2: Ingredient match (AND logic) ---
            $product_has_all_ingredients = true;
            if (!empty($required_ingredients)) {
                $product_terms = wp_get_post_terms($product_id_to_check, $search_ingredient_slug, array('fields' => 'slugs'));
                if (is_wp_error($product_terms)) {
                    $product_has_all_ingredients = false;
                } else {
                    foreach ($required_ingredients as $required_term) {
                        if (!in_array($required_term, $product_terms)) {
                            $product_has_all_ingredients = false;
                            break;
                        }
                    }
                }
            }

            // 6. If it matches ALL criteria, add it
            $has_search_criteria = !empty($search_type_term) || !empty($required_ingredients);
            if ($has_search_criteria && $product_has_type && $product_has_all_ingredients) {
                // It's a match!
                $item_subtotal = $item->get_subtotal();
                $quiz_attributed_total += $item_subtotal;

                // NEW: Add to our item array for the new table
                $matched_items_for_db[] = array(
                    'search_id' => $latest_search->id,
                    'order_id' => $order_id,
                    'product_id' => $item->get_product_id(),
                    'variation_id' => $item->get_variation_id(),
                    'quantity' => $item->get_quantity(),
                    'subtotal' => $item_subtotal
                );
            }
        }
        // --- END LOGIC ---

        // 7. Only update the DB if at least one item matched
        if (!empty($matched_items_for_db)) {

            // --- Update Table 1 (Analytics) ---
            $wpdb->update(
                $table_name_analytics,
                array(
                    'converted' => 1,
                    'order_id' => $order_id,
                    'order_total' => $quiz_attributed_total // Save calculated subtotal
                ),
                array('id' => $latest_search->id), // Where
                array('%d', '%d', '%f'), // Data formats
                array('%d') // Where format
            );

            // --- NEW: Insert into Table 2 (Items) ---
            $table_name_items = $wpdb->prefix . 'vv_quiz_conversion_items';
            foreach ($matched_items_for_db as $item_to_insert) {
                $wpdb->insert(
                    $table_name_items,
                    $item_to_insert,
                    array('%d', '%d', '%d', '%d', '%d', '%f') // Data formats
                );
            }
        }

        // 8. Mark the order as processed
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