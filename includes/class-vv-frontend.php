<?php
/**
 * Frontend Logic for VapeVida Quiz. Contains Shortcode, HTML structure, and inline CSS.
 */

if (!defined('ABSPATH'))
    exit;

// --- 1. Shortcode Function: Loads the Form and Populates Selects ---
function vv_recommender_quiz_shortcode()
{
    ob_start();

    // --- DYNAMICALLY LOAD SAVED SETTINGS ---
    $settings = get_option('vv_quiz_settings');

    // Checkbox status: Enable/Disable Secondary Ingredient field
    $show_third_field = isset($settings['field_status']) && $settings['field_status'];

    // Placeholders and Label texts (Reading from settings)
    $quiz_heading = isset($settings['quiz_heading']) ? esc_html($settings['quiz_heading']) : 'Βρες το Ιδανικό Υγρό!';
    $quiz_subtitle = isset($settings['quiz_subtitle']) ? esc_html($settings['quiz_subtitle']) : 'Επίλεξε το προφίλ γεύσης και τα συστατικά που προτιμάς.';
    $label_type = isset($settings['label_type']) ? esc_html($settings['label_type']) : '1. Προφίλ Υγρού:';
    $label_primary = isset($settings['label_primary']) ? esc_html($settings['label_primary']) : '2. Βασικό Συστατικό:';
    $label_secondary = isset($settings['label_secondary']) ? esc_html($settings['label_secondary']) : '3. Δευτερεύον Συστατικό:';
    $cta_button_text = isset($settings['button_cta']) ? esc_html($settings['button_cta']) : 'ΒΡΕΣ ΤΟ ΥΓΡΟ ΣΟΥ';
    $clear_button_text = isset($settings['button_clear_cta']) ? esc_html($settings['button_clear_cta']) : 'ΚΑΘΑΡΙΣΜΟΣ';

    // --- Dynamic Placeholder for Field 1 ---
    $placeholder_type = isset($settings['placeholder_type']) ? esc_attr($settings['placeholder_type']) : '-- Επιλέξτε Προφίλ --';
    $placeholder_primary = isset($settings['placeholder_primary']) ? esc_attr($settings['placeholder_primary']) : '-- Επιλέξτε Βασικό Συστατικό --';
    $placeholder_secondary = isset($settings['placeholder_secondary']) ? esc_attr($settings['placeholder_secondary']) : '-- Επιλέξτε Δευτερεύον Συστατικό --';

    // --- Get Required Statuses and generate HTML attribute string ---
    $is_type_required_attr = isset($settings['is_type_required']) && $settings['is_type_required'] ? 'required' : '';
    $is_primary_required_attr = isset($settings['is_primary_required']) && $settings['is_primary_required'] ? 'required' : '';
    $is_secondary_required_attr = isset($settings['is_secondary_required']) && $settings['is_secondary_required'] ? 'required' : '';

    // --- DYNAMICALLY READ ATTRIBUTE SLUGS FROM SAVED SETTINGS ---
    $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;
    $type_taxonomy_slug = $use_custom && isset($settings['attribute_type_slug']) && !empty($settings['attribute_type_slug'])
        ? $settings['attribute_type_slug']
        : 'pa_geuseis';
    $ingredient_taxonomy_slug = $use_custom && isset($settings['attribute_ingredient_slug']) && !empty($settings['attribute_ingredient_slug'])
        ? $settings['attribute_ingredient_slug']
        : 'pa_quiz-ingredient';

    // IMPORTANT: The form input name must be the attribute slug without the 'pa_' prefix.
    $form_filter_type_name = str_replace('pa_', 'filter_', $type_taxonomy_slug);
    $form_filter_ingredient_name = str_replace('pa_', 'filter_', $ingredient_taxonomy_slug);

    // Get Shop URL
    $shop_url = get_permalink(wc_get_page_id('shop'));

    // Dynamically fetch all active terms using the retrieved slugs
    $flavor_type_terms = get_terms(array(
        'taxonomy' => $type_taxonomy_slug,
        'hide_empty' => true,
    ));

    $ingredient_terms = get_terms(array(
        'taxonomy' => $ingredient_taxonomy_slug,
        'hide_empty' => true,
    ));

    // Safety Check: 
    if (empty($type_taxonomy_slug) || empty($ingredient_taxonomy_slug) || is_wp_error($flavor_type_terms)) {
        return '<p style="color: red;">[Σφάλμα Ρύθμισης]: Παρακαλούμε ρυθμίστε τους Global Attributes στο Quiz Info page.</p>';
    }

    // --- DYNAMIC BUTTON COLORS ---
    $btn_bg_color = isset($settings['btn_bg_color']) ? esc_html($settings['btn_bg_color']) : '#e21e51';
    $btn_bg_hover_color = isset($settings['btn_bg_hover_color']) ? esc_html($settings['btn_bg_hover_color']) : '#c91a48';
    $btn_txt_color = isset($settings['btn_txt_color']) ? esc_html($settings['btn_txt_color']) : '#FFFFFF';
    $btn_txt_hover_color = isset($settings['btn_txt_hover_color']) ? esc_html($settings['btn_txt_hover_color']) : '#FFFFFF';
    $clear_btn_bg_color = isset($settings['clear_btn_bg_color']) ? esc_html($settings['clear_btn_bg_color']) : '#6c757d';
    $clear_btn_bg_hover_color = isset($settings['clear_btn_bg_hover_color']) ? esc_html($settings['clear_btn_bg_hover_color']) : '#5a6268';
    $clear_btn_txt_color = isset($settings['clear_btn_txt_color']) ? esc_html($settings['clear_btn_txt_color']) : '#FFFFFF';
    $clear_btn_txt_hover_color = isset($settings['clear_btn_txt_hover_color']) ? esc_html($settings['clear_btn_txt_hover_color']) : '#FFFFFF';


    // Pass essential data to the frontend script
    wp_localize_script(
        'vv-quiz-frontend-script', // Handle for the script we'll create below
        'vv_quiz_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            // Other data for future AJAX features could go here
        )
    );


    ?>
    <style>
        /* BASE STYLING (Mobile/Tablet Default) */
        .vv-quiz-container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .vv-quiz-container h2,
        .vv-quiz-container p {
            text-align: center;
        }

        /* Mobile Stacked Layout (The default) */
        .vv-select-row {
            display: block;
        }

        .vv-select-col {
            margin-bottom: 15px;
        }

        /* ------------------------------------------------------------- */
        /* --- BUTTONS AND NATIVE SELECT (Original Styles Remain) --- */
        /* ------------------------------------------------------------- */

        /* Button Row Container */
        .vv-button-row {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        /* Submit Button Style */
        .vv-quiz-container .vv-cta-button {
            flex: 1;
            min-width: 150px;
            padding: 12px 20px;
            background-color:
                <?= $btn_bg_color; ?>
            ;
            color:
                <?= $btn_txt_color ?>
            ;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .vv-quiz-container .vv-cta-button:hover {
            background-color:
                <?= $btn_bg_hover_color ?>
            ;
            color:
                <?= $btn_txt_hover_color ?>
            ;
        }

        /* Clear Button Style */
        .vv-quiz-container .vv-clear-button {
            flex: 1;
            min-width: 150px;
            padding: 12px 20px;
            background-color:
                <?= $clear_btn_bg_color; ?>
            ;
            color:
                <?= $clear_btn_txt_color ?>
            ;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .vv-quiz-container .vv-clear-button:hover {
            background-color:
                <?= $clear_btn_bg_hover_color ?>
            ;
            color:
                <?= $clear_btn_txt_hover_color ?>
            ;
        }

        /* Required Field Asterisk */
        .vv-quiz-container label[required]:after {
            content: " *";
            color: #d9534f;
            font-weight: bold;
        }

        /* Native Select (For Field 1) and General Fallback */
        .vv-quiz-container select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        /* Highlight border of invalid fields (Native Select) */
        .vv-quiz-container select:invalid:required {
            border-color: #d9534f;
            box-shadow: 0 0 5px rgba(217, 83, 79, 0.5);
        }

        /* --- DESKTOP STYLING (Remains the same) --- */
        @media (min-width: 600px) {
            .vv-button-row {
                justify-content: center;
            }

            .vv-quiz-container .vv-cta-button,
            .vv-quiz-container .vv-clear-button {
                flex: 0 1 auto;
                min-width: 200px;
            }
        }

        @media (min-width: 768px) {
            .vv-quiz-container .vv-select-row {
                display: flex !important;
                gap: 20px;
                align-items: flex-start;
                margin-bottom: 20px;
            }

            .vv-quiz-container .vv-select-col {
                flex: 1 1 0;
                min-width: 0;
                margin-bottom: 0;
            }
        }
    </style>

    <div class="vv-quiz-container">
        <h2><?php echo $quiz_heading; ?></h2>
        <p><?php echo $quiz_subtitle; ?></p>

        <form method="get" action="<?php echo esc_url($shop_url); ?>" id="vv-recommender-form">

            <input type="hidden" name="filter_applied" value="1">

            <div class="vv-select-row">

                <div class="vv-select-col">
                    <label for="flavor_type" <?php echo $is_type_required_attr; ?>><?php echo $label_type; ?></label>
                    <select name="<?php echo esc_attr($form_filter_type_name); ?>" id="flavor_type" class="vv-quiz-select"
                        <?php echo $is_type_required_attr; ?>>
                        <option value=""><?php echo $placeholder_type; ?></option>
                        <?php
                        foreach ($flavor_type_terms as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="vv-select-col">
                    <label for="flavor_ingredient" <?php echo $is_primary_required_attr; ?>><?php echo $label_primary; ?></label>
                    <select name="<?php echo esc_attr($form_filter_ingredient_name); ?>" id="flavor_ingredient"
                        class="vv-quiz-select" <?php echo $is_primary_required_attr; ?>>
                        <option value=""><?php echo $placeholder_primary; ?></option>
                        <?php
                        foreach ($ingredient_terms as $term) {
                            echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <?php if ($show_third_field): ?>
                    <div class="vv-select-col">
                        <label for="flavor_ingredient_optional" <?php echo $is_secondary_required_attr; ?>><?php echo $label_secondary; ?></label>
                        <select name="<?php echo esc_attr($form_filter_ingredient_name); ?>-optional"
                            id="flavor_ingredient_optional" class="vv-quiz-select" <?php echo $is_secondary_required_attr; ?>>
                            <option value=""><?php echo $placeholder_secondary; ?></option>
                            <?php
                            foreach ($ingredient_terms as $term) {
                                echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>

            </div>

            <div class="vv-button-row">
                <button type="button" class="button vv-clear-button" onclick="vvClearQuizForm()">
                    <?php echo $clear_button_text; ?>
                </button>
                <button type="submit" class="button vv-cta-button">
                    <?php echo $cta_button_text; ?>
                </button>
            </div>
        </form>
    </div>

    <script>
        // NOTE: This logic should ideally be in a separate, enqueued JS file (class-vv-dynamic.js)
        function vvClearQuizForm() {
            const form = document.getElementById('vv-recommender-form');
            if (form) {
                // Reset all select elements to their first option (empty value)
                const selects = form.querySelectorAll('select');
                selects.forEach(select => {
                    select.selectedIndex = 0;
                });

                // Submit the form to clear filters from the URL
                // This will redirect to the base shop URL because all filter fields will be empty
                form.submit();
            }
        }
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('vapevida_quiz', 'vv_recommender_quiz_shortcode');