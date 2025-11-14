<?php
/**
 * Frontend Logic for VapeVida Quiz. Contains Shortcode, HTML structure, and inline CSS.
 */

if (!defined('ABSPATH'))
    exit;

function vv_recommender_quiz_shortcode()
{
    ob_start();

    $settings = get_option('vv_quiz_settings');

    $show_third_field = isset($settings['field_status']) && $settings['field_status'];

    $quiz_heading = isset($settings['quiz_heading']) ? esc_html($settings['quiz_heading']) : __('Find Your Perfect Liquid!', VV_QUIZ_TEXT_DOMAIN);
    $quiz_subtitle = isset($settings['quiz_subtitle']) ? esc_html($settings['quiz_subtitle']) : __('Select the flavor profile and ingredients you prefer.', VV_QUIZ_TEXT_DOMAIN);
    $label_type = isset($settings['label_type']) ? esc_html($settings['label_type']) : __('1. Liquid Profile:', VV_QUIZ_TEXT_DOMAIN);
    $label_primary = isset($settings['label_primary']) ? esc_html($settings['label_primary']) : __('2. Primary Ingredient:', VV_QUIZ_TEXT_DOMAIN);
    $label_secondary = isset($settings['label_secondary']) ? esc_html($settings['label_secondary']) : __('3. Secondary Ingredient:', VV_QUIZ_TEXT_DOMAIN);
    $cta_button_text = isset($settings['button_cta']) ? esc_html($settings['button_cta']) : __('FIND YOUR LIQUID', VV_QUIZ_TEXT_DOMAIN);
    $clear_button_text = isset($settings['button_clear_cta']) ? esc_html($settings['button_clear_cta']) : __('CLEAR', VV_QUIZ_TEXT_DOMAIN);

    $placeholder_type = isset($settings['placeholder_type']) ? esc_attr($settings['placeholder_type']) : __('-- Select Profile --', VV_QUIZ_TEXT_DOMAIN);
    $placeholder_primary = isset($settings['placeholder_primary']) ? esc_attr($settings['placeholder_primary']) : __('-- Select Primary Ingredient --', VV_QUIZ_TEXT_DOMAIN);
    $placeholder_secondary = isset($settings['placeholder_secondary']) ? esc_attr($settings['placeholder_secondary']) : __('-- Select Secondary Ingredient --', VV_QUIZ_TEXT_DOMAIN);

    // Get required status from settings
    $is_type_required_attr = !empty($settings['is_type_required']) ? 'required' : '';
    $is_primary_required_attr = !empty($settings['is_primary_required']) ? 'required' : '';
    $is_secondary_required_attr = !empty($settings['is_secondary_required']) && $show_third_field ? 'required' : '';

    $use_custom = isset($settings['use_custom_attributes']) ? $settings['use_custom_attributes'] : false;
    $type_taxonomy_slug = $use_custom && isset($settings['attribute_type_slug']) && !empty($settings['attribute_type_slug'])
        ? $settings['attribute_type_slug']
        : 'pa_geuseis';
    $ingredient_taxonomy_slug = $use_custom && isset($settings['attribute_ingredient_slug']) && !empty($settings['attribute_ingredient_slug'])
        ? $settings['attribute_ingredient_slug']
        : 'pa_quiz-ingredient';

    $form_filter_type_name = str_replace('pa_', 'filter_', $type_taxonomy_slug);
    $form_filter_ingredient_name = str_replace('pa_', 'filter_', $ingredient_taxonomy_slug);

    $shop_url = get_permalink(wc_get_page_id('shop'));

    $flavor_type_terms = get_terms(array(
        'taxonomy' => $type_taxonomy_slug,
        'hide_empty' => true,
    ));

    // We get all terms here, the AJAX will handle the dynamic filtering
    $ingredient_terms = get_terms(array(
        'taxonomy' => $ingredient_taxonomy_slug,
        'hide_empty' => true,
    ));

    if (empty($type_taxonomy_slug) || empty($ingredient_taxonomy_slug) || is_wp_error($flavor_type_terms)) {
        return '<p style="color: red;">' . __('[Configuration Error]: Please configure the Global Attributes in the Quiz Info page.', VV_QUIZ_TEXT_DOMAIN) . '</p>';
    }

    $btn_bg_color = isset($settings['btn_bg_color']) ? esc_html($settings['btn_bg_color']) : '#e21e51';
    $btn_bg_hover_color = isset($settings['btn_bg_hover_color']) ? esc_html($settings['btn_bg_hover_color']) : '#c91a48';
    $btn_txt_color = isset($settings['btn_txt_color']) ? esc_html($settings['btn_txt_color']) : '#FFFFFF';
    $btn_txt_hover_color = isset($settings['btn_txt_hover_color']) ? esc_html($settings['btn_txt_hover_color']) : '#FFFFFF';
    $clear_btn_bg_color = isset($settings['clear_btn_bg_color']) ? esc_html($settings['clear_btn_bg_color']) : '#6c757d';
    $clear_btn_bg_hover_color = isset($settings['clear_btn_bg_hover_color']) ? esc_html($settings['clear_btn_bg_hover_color']) : '#5a6268';
    $clear_btn_txt_color = isset($settings['clear_btn_txt_color']) ? esc_html($settings['clear_btn_txt_color']) : '#FFFFFF';
    $clear_btn_txt_hover_color = isset($settings['clear_btn_txt_hover_color']) ? esc_html($settings['clear_btn_txt_hover_color']) : '#FFFFFF';

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
            position: relative;
            overflow: hidden;
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

        /* Disabled Button State */
        .vv-quiz-container .vv-cta-button:disabled {
            background-color: #cccccc;
            color: #666666;
            cursor: not-allowed;
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
        .vv-quiz-container label.vv-required:after {
            content: " *";
            color: #d9534f;
            font-weight: bold;
        }

        .vv-quiz-container select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            box-sizing: border-box;
        }

        .vv-quiz-container select.vv-field-error {
            border-color: #d9534f;
            box-shadow: 0 0 5px rgba(217, 83, 79, 0.5);
        }

        .vv-field-error-message {
            color: #D8000C;
            font-size: 12px;
            font-weight: bold;
            padding-top: 5px;
            display: none;
        }

        /* --- DESKTOP STYLING --- */
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

        <form method="get" action="<?php echo esc_url($shop_url); ?>" id="vv-recommender-form" novalidate>

            <input type="hidden" name="filter_applied" value="1">

            <div class="vv-select-row">

                <div class="vv-select-col">
                    <label for="flavor_type"
                        class="<?php echo $is_type_required_attr ? 'vv-required' : ''; ?>"><?php echo $label_type; ?></label>
                    <select name="<?php echo esc_attr($form_filter_type_name); ?>" id="flavor_type" class="vv-quiz-select"
                        <?php echo $is_type_required_attr; ?>>
                        <option value=""><?php echo $placeholder_type; ?></option>
                        <?php
                        if (!is_wp_error($flavor_type_terms) && !empty($flavor_type_terms)) {
                            foreach ($flavor_type_terms as $term) {
                                echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                            }
                        }
                        ?>
                    </select>
                    <div class="vv-field-error-message" id="error-for-flavor_type"></div>
                </div>

                <div class="vv-select-col">
                    <label for="flavor_ingredient"
                        class="<?php echo $is_primary_required_attr ? 'vv-required' : ''; ?>"><?php echo $label_primary; ?></label>
                    <select name="<?php echo esc_attr($form_filter_ingredient_name); ?>" id="flavor_ingredient"
                        class="vv-quiz-select" <?php echo $is_primary_required_attr; ?> disabled>
                        <option value=""><?php echo $placeholder_primary; ?></option>
                        <?php
                        // if (!is_wp_error($ingredient_terms) && !empty($ingredient_terms)) {
                        //     foreach ($ingredient_terms as $term) {
                        //         echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                        //     }
                        // }
                        ?>
                    </select>
                    <div class="vv-field-error-message" id="error-for-flavor_ingredient"></div>
                </div>

                <?php if ($show_third_field): ?>
                    <div class="vv-select-col">
                        <label for="flavor_ingredient_optional"
                            class="<?php echo $is_secondary_required_attr ? 'vv-required' : ''; ?>"><?php echo $label_secondary; ?></label>
                        <select name="<?php echo esc_attr($form_filter_ingredient_name); ?>-optional"
                            id="flavor_ingredient_optional" class="vv-quiz-select" <?php echo $is_secondary_required_attr; ?>
                            disabled>
                            <option value=""><?php echo $placeholder_secondary; ?></option>
                            <?php
                            // if (!is_wp_error($ingredient_terms) && !empty($ingredient_terms)) {
                            //     foreach ($ingredient_terms as $term) {
                            //         echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                            //     }
                            // }
                            ?>
                        </select>
                        <div class="vv-field-error-message" id="error-for-flavor_ingredient_optional"></div>
                    </div>
                <?php endif; ?>

            </div>

            <div class="vv-button-row">
                <button type="button" class="button vv-clear-button">
                    <?php echo $clear_button_text; ?>
                </button>
                <button type="submit" class="button vv-cta-button">
                    <?php echo $cta_button_text; ?>
                </button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('vapevida_quiz', 'vv_recommender_quiz_shortcode');