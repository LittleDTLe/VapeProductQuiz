<?php
/**
 * Admin Rendering Logic - FULLY LOCALIZED
 */

if (!defined('ABSPATH'))
    exit;

function vv_render_details_page()
{
    if (!current_user_can('manage_options'))
        return;

    $shop_url = get_permalink(wc_get_page_id('shop'));
    $git_url = 'https://github.com/LittleDTLe/VapeProductQuiz';

    $settings = get_option('vv_quiz_settings');

    $chosen_type_slug = isset($settings['attribute_type_slug']) ? esc_html($settings['attribute_type_slug']) : 'pa_geuseis (' . __('Default', 'vapevida-quiz') . ')';
    $chosen_ingredient_slug = isset($settings['attribute_ingredient_slug']) ? esc_html($settings['attribute_ingredient_slug']) : 'pa_quiz-ingredient (' . __('Default', 'vapevida-quiz') . ')';

    $all_headers = array('Version' => 'Version', 'Author' => 'Author', 'VersionNotes' => 'VersionNotes', 'Features' => 'Features');
    $plugin_file_path = dirname(__FILE__, 3) . '/vapevida-quiz.php';
    $plugin_data_raw = get_file_data($plugin_file_path, $all_headers, 'plugin');

    $plugin_version = isset($plugin_data_raw['Version']) ? esc_html($plugin_data_raw['Version']) : 'N/A';
    $plugin_author = isset($plugin_data_raw['Author']) ? esc_html($plugin_data_raw['Author']) : __('Unknown', 'vapevida-quiz');
    $version_notes_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['VersionNotes']) ? $plugin_data_raw['VersionNotes'] : '')));
    $features_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['Features']) ? $plugin_data_raw['Features'] : '')));

    ?>
    <style>
        #vv-admin-top-flex {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        #vv-main-content-wrapper {
            flex: 2;
            min-width: 0;
        }

        #vv-sidebar-metadata {
            flex: 1;
            min-width: 300px;
        }

        #vv-guide-section {
            margin-top: 30px;
        }

        .vv-custom-attributes-toggle {
            background: #fff;
            padding: 15px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .vv-custom-attributes-fields {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .vv-custom-attributes-fields.hidden {
            display: none;
        }

        .vv-attribute-field-row {
            margin-bottom: 15px;
        }

        .vv-attribute-field-row:last-child {
            margin-bottom: 0;
        }

        @media screen and (max-width: 1024px) {
            #vv-admin-top-flex {
                gap: 20px;
            }

            #vv-sidebar-metadata {
                min-width: 250px;
            }
        }

        @media screen and (max-width: 768px) {
            #vv-admin-top-flex {
                flex-direction: column;
                gap: 20px;
            }

            #vv-main-content-wrapper,
            #vv-sidebar-metadata {
                flex: 1;
                min-width: 100%;
            }

            #vv-guide-section {
                margin-top: 20px;
            }

            #vv-main-instructions .inside>div {
                padding: 12px !important;
            }

            #vv-main-instructions ul {
                padding-left: 15px !important;
            }
        }

        @media screen and (max-width: 480px) {
            .wrap h1 {
                font-size: 1.5em;
            }

            .about-text {
                font-size: 0.9em;
            }

            #vv-admin-top-flex {
                gap: 15px;
            }

            #vv-settings-form .inside,
            #vv-sidebar-metadata .inside,
            #vv-main-instructions .inside {
                padding: 10px;
            }

            .regular-text {
                max-width: 100%;
            }
        }
    </style>
    <script>
        function vvCopyShortcode(button) {
            const shortcode = '[vapevida_quiz]';

            navigator.clipboard.writeText(shortcode).then(function () {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<span class="dashicons dashicons-yes"></span><?php echo esc_js(__('Copied!', 'vapevida-quiz')); ?>';
                button.classList.add('copied');

                setTimeout(function () {
                    button.innerHTML = originalHTML;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(function (err) {
                console.error('Failed to copy: ', err);
                alert('<?php echo esc_js(__('Copy failed. Please copy manually.', 'vapevida-quiz')); ?>');
            });
        }

        function vvToggleCustomAttributes(checkbox) {
            const allDefaultInfos = document.querySelectorAll('.vv-default-info');
            const allSelectWrappers = document.querySelectorAll('.vv-attribute-select-wrapper');

            if (checkbox.checked) {
                allDefaultInfos.forEach(function (info) {
                    info.style.display = 'none';
                });
                allSelectWrappers.forEach(function (wrapper) {
                    wrapper.style.display = 'block';
                });
            } else {
                allDefaultInfos.forEach(function (info) {
                    info.style.display = 'block';
                });
                allSelectWrappers.forEach(function (wrapper) {
                    wrapper.style.display = 'none';
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const useCustomCheckbox = document.getElementById('use_custom_attributes');
            if (useCustomCheckbox) {
                vvToggleCustomAttributes(useCustomCheckbox);
            }
        });
    </script>

    <div class="wrap">
        <h1><?php _e('VapeVida Flavorshot Recommender Quiz', 'vapevida-quiz'); ?></h1>
        <p class="about-text">
            <?php _e('Usage instructions and technical information for managing the custom plugin.', 'vapevida-quiz'); ?>
        </p>

        <hr class="wp-header-end">

        <div id="vv-admin-top-flex">

            <div id="vv-main-content-wrapper" style="flex: 2; min-width: 0;">

                <div id="vv-settings-form" class="postbox" style="margin-bottom: 20px;">
                    <h2 class="hndle">
                        <span>
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('Quiz Settings', 'vapevida-quiz'); ?>
                        </span>
                    </h2>
                    <div class="inside">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields('vv_quiz_options_group');
                            do_settings_sections('vapevida-quiz-details');
                            submit_button();
                            ?>
                        </form>
                    </div>
                </div>

            </div>

            <div id="vv-sidebar-metadata" class="postbox-container" style="flex: 1; min-width: 300px;">
                <div class="postbox">
                    <h2 class="hndle">
                        <span>
                            <span class="dashicons dashicons-info" style="font-size: 1.2em; vertical-align: middle;"></span>
                            <?php _e('Plugin Information', 'vapevida-quiz'); ?>
                        </span>
                    </h2>
                    <div class="inside">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 8px;">
                                <strong><?php _e('Author:', 'vapevida-quiz'); ?></strong>
                                <span style="font-weight: bold;"><?php echo $plugin_author; ?></span>
                            </li>
                            <li style="margin-bottom: 8px;">
                                <strong><?php _e('Plugin Version:', 'vapevida-quiz'); ?></strong>
                                <?php echo $plugin_version; ?>
                            </li>

                            <li style="margin-top: 15px; margin-bottom: 5px;">
                                <strong><?php _e('Version Notes:', 'vapevida-quiz'); ?></strong>
                            </li>
                            <ul style="padding-left: 20px; margin-top: 0;">
                                <?php
                                foreach ($version_notes_list as $note) {
                                    if (!empty($note)) {
                                        echo '<li><span class="dashicons dashicons-yes-alt" style="color: #4CAF50; font-size: 14px; vertical-align: sub;"></span> ' . esc_html($note) . '</li>';
                                    }
                                }
                                ?>
                            </ul>

                            <li style="margin-top: 15px; margin-bottom: 5px;">
                                <strong><?php _e('Features:', 'vapevida-quiz'); ?></strong>
                            </li>
                            <ul style="padding-left: 20px; margin-top: 0;">
                                <?php
                                foreach ($features_list as $feature) {
                                    if (!empty($feature)) {
                                        echo '<li><span class="dashicons dashicons-star-filled" style="color: #FFC107; font-size: 14px; vertical-align: sub;"></span> ' . esc_html($feature) . '</li>';
                                    }
                                }
                                ?>
                            </ul>

                            <li style="border-top: 1px solid #eee; padding-top: 12px; margin-top: 15px;">
                                <strong><?php _e('Shop URL:', 'vapevida-quiz'); ?></strong>
                                <a href="<?php echo esc_url($shop_url); ?>"
                                    target="_blank"><?php _e('Open Shop', 'vapevida-quiz'); ?></a>
                            </li>
                            <li style="margin-top: 5px;">
                                <strong><?php _e('Github Repository:', 'vapevida-quiz'); ?></strong>
                                <a href="<?php echo esc_url($git_url); ?>"
                                    target="_blank"><?php _e('Go to Github', 'vapevida-quiz'); ?></a>
                            </li>
                        </ul>

                        <h3 style="margin-top: 20px;"><?php _e('Attribute Slugs', 'vapevida-quiz'); ?></h3>
                        <p><?php _e('The required Global Attributes for the Quiz are:', 'vapevida-quiz'); ?></p>
                        <ul style="padding-left: 20px;">
                            <li><strong><?php _e('Flavor Type:', 'vapevida-quiz'); ?></strong>
                                <code><?php echo $chosen_type_slug; ?></code></li>
                            <li><strong><?php _e('Ingredient:', 'vapevida-quiz'); ?></strong>
                                <code><?php echo $chosen_ingredient_slug; ?></code></li>
                        </ul>

                        <h3 style="margin-top: 30px;">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Filter Technical Logic', 'vapevida-quiz'); ?>
                        </h3>
                        <p><?php _e('The Query Logic code ensures that filtering is done with **absolute accuracy** (AND Logic) between all selected fields.', 'vapevida-quiz'); ?>
                        </p>
                        <div style="background: #f7f7f7; padding: 15px; border-left: 4px solid #e21e51; margin-top: 15px;">
                            <p><strong>&bull; <?php _e('Flavor Type:', 'vapevida-quiz'); ?></strong> `filter_geuseis`
                                (<?php _e('Single Select', 'vapevida-quiz'); ?>)</p>
                            <p><strong>&bull; <?php _e('Primary & Secondary Ingredient:', 'vapevida-quiz'); ?></strong>
                                `filter_quiz-ingredient` (<?php _e('Combined AND Logic', 'vapevida-quiz'); ?>)</p>
                            <p style="margin-top: 10px; font-style: italic;">
                                <?php _e('The system checks if the product has the "Flavor Type" AND both selected "Ingredients" simultaneously.', 'vapevida-quiz'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <!-- Continuation of admin-rendering.php -->
        <div id="vv-guide-section" style="margin-top: 30px;">
            <div id="vv-main-instructions" class="postbox">
                <h2 class="hndle">
                    <span>
                        <span class="dashicons dashicons-book-alt"></span>
                        <?php _e('Usage Instructions & Maintenance', 'vapevida-quiz'); ?>
                    </span>
                </h2>
                <div class="inside">
                    <p><?php _e('This plugin works as a **Product Finder Guide**. It is based on URL filters for immediate liquid search by the customer.', 'vapevida-quiz'); ?>
                    </p>

                    <h3>
                        <span class="dashicons dashicons-admin-home"></span>
                        <?php _e('Quiz Display & Text Customization', 'vapevida-quiz'); ?>
                    </h3>

                    <div style="background: #f0f0f0; padding: 15px; border-left: 4px solid #4a67b2; margin-bottom: 20px;">
                        <p><strong><?php _e('Display Code (Shortcode):', 'vapevida-quiz'); ?></strong>
                            <?php _e('Use the following code to display the form on any page (e.g. Homepage):', 'vapevida-quiz'); ?>
                        </p>

                        <div class="vv-shortcode-container"
                            style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">

                            <code id="vv-shortcode-code"
                                style="flex-grow: 1; padding: 8px 10px; background: #fff; border: 1px dashed #ccc; font-weight: bold; border-radius: 3px;">
                                            <span class="dashicons dashicons-editor-code" style="vertical-align: middle; margin-right: 5px;"></span>[vapevida_quiz]
                                        </code>

                            <button type="button" id="vv-copy-shortcode-btn"
                                class="button button-secondary dashicons-before dashicons-admin-page"
                                style="flex-shrink: 0;" onclick="vvCopyShortcode(this)">
                                <?php _e('Copy', 'vapevida-quiz'); ?>
                            </button>
                        </div>
                    </div>

                    <p><?php
                    printf(
                        /* translators: %s: Section name in bold */
                        __('All texts (Titles, Labels, CTA) and Quiz structure are configured from the %s section above.', 'vapevida-quiz'),
                        '<strong>"' . __('Quiz Settings', 'vapevida-quiz') . '"</strong>'
                    );
                    ?></p>

                    <ul style="padding-left: 20px; color: #555;">
                        <li>
                            <span style="font-weight: bold;"><?php _e('Titles (H2/P):', 'vapevida-quiz'); ?></span>
                            <?php _e('Change the main message for seasonal or promotional themes.', 'vapevida-quiz'); ?>
                        </li>
                        <li>
                            <span style="font-weight: bold;"><?php _e('Required Fields:', 'vapevida-quiz'); ?></span>
                            <?php _e('Control which fields must be filled for form submission.', 'vapevida-quiz'); ?>
                        </li>
                        <li>
                            <span style="font-weight: bold;"><?php _e('Enable 3rd Field:', 'vapevida-quiz'); ?></span>
                            <?php _e('Choose if the third field (Secondary Ingredient) is displayed in the form.', 'vapevida-quiz'); ?>
                        </li>
                    </ul>

                    <h3 style="margin-top: 30px;">
                        <span class="dashicons dashicons-editor-ul"></span>
                        <?php _e('Flavor Management Guide', 'vapevida-quiz'); ?>
                    </h3>
                    <p style="margin-bottom: 15px;">
                        <?php _e('Adding new options to the Quiz happens **automatically**, as long as the correct settings are updated in the WooCommerce Global Attributes.', 'vapevida-quiz'); ?>
                    </p>

                    <div style="background: #eef7ff; padding: 15px; border: 1px solid #cceeff; border-radius: 5px;">
                        <p style="font-weight: bold; margin-top: 0;">
                            <?php _e('Step 1: Access the Correct Folder', 'vapevida-quiz'); ?>
                        </p>
                        <p style="margin-left: 10px;">
                            <?php _e('Navigate to: **Products → Attributes**. The two Attributes that populate the Quiz are:', 'vapevida-quiz'); ?>
                        </p>
                        <ul style="padding-left: 15px;">
                            <li><strong><?php _e('Flavor Type:', 'vapevida-quiz'); ?></strong> <code>pa_geuseis</code></li>
                            <li><strong><?php _e('Ingredient (e.g. Strawberry, Cream):', 'vapevida-quiz'); ?></strong>
                                <code>pa_quiz-ingredient</code>
                            </li>
                        </ul>
                    </div>

                    <div
                        style="background: #fdf5e6; padding: 15px; border: 1px solid #ffaa00; border-radius: 5px; margin-top: 10px;">
                        <p style="font-weight: bold; margin-top: 0;"><?php _e('Step 2: Add New Term', 'vapevida-quiz'); ?>
                        </p>
                        <p style="margin-left: 10px;">
                            <?php _e('Click on **"Configure Terms"** next to the Attribute **Ingredient (Quiz)**.', 'vapevida-quiz'); ?>
                        </p>
                        <ul style="padding-left: 15px; list-style-type: square;">
                            <li><strong><?php _e('Name:', 'vapevida-quiz'); ?></strong>
                                <?php _e('Write the full name (e.g. \'Kiwi\') and a clean slug (e.g. \'kiwi\').', 'vapevida-quiz'); ?>
                            </li>
                        </ul>
                    </div>

                    <div
                        style="background: #f0fff0; padding: 15px; border: 1px solid #3c763d; border-radius: 5px; margin-top: 10px;">
                        <p style="font-weight: bold; margin-top: 0;">
                            <?php _e('Step 3: Connect to Product & Check', 'vapevida-quiz'); ?>
                        </p>
                        <p style="margin-left: 10px;">
                            <?php _e('For the new term to appear in the Quiz, it must be assigned to at least one published product.', 'vapevida-quiz'); ?>
                        </p>
                        <p style="margin-left: 10px; color: #d9534f; font-weight: bold;">
                            <span class="dashicons dashicons-warning"
                                style="font-size: 1.2em; vertical-align: middle;"></span>
                            <strong><?php _e('Very Important:', 'vapevida-quiz'); ?></strong>
                            <?php _e('If a term **does not** have products, the Quiz ignores it (to avoid leading to empty results).', 'vapevida-quiz'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
