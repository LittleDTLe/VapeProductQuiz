<?php
/**
 * Frontend Quiz Shortcode VIEW
 *
 * This file contains all the HTML for the quiz.
 * It is included by class-vv-frontend.php
 *
 * It expects all variables (like $quiz_heading, $btn_bg_color)
 * to be defined in the controller that includes it.
 */

if (!defined('ABSPATH'))
    exit;
?>

<style>
    /* Dynamic color styles are injected here by class-vv-frontend.php */
    .vv-quiz-container .vv-cta-button {
        background-color:
            <?php echo $btn_bg_color; ?>
        ;
        color:
            <?php echo $btn_txt_color; ?>
        ;
    }

    .vv-quiz-container .vv-cta-button:hover {
        background-color:
            <?php echo $btn_bg_hover_color; ?>
        ;
        color:
            <?php echo $btn_txt_hover_color; ?>
        ;
    }

    .vv-quiz-container .vv-clear-button {
        background-color:
            <?php echo $clear_btn_bg_color; ?>
        ;
        color:
            <?php echo $clear_btn_txt_color; ?>
        ;
    }

    .vv-quiz-container .vv-clear-button:hover {
        background-color:
            <?php echo $clear_btn_bg_hover_color; ?>
        ;
        color:
            <?php echo $clear_btn_txt_hover_color; ?>
        ;
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