<?php
/**
 * Guide Section Partial
 * 
 * Contains the usage instructions and maintenance guide
 * for the VapeVida Quiz admin page.
 *
 * @package VapeVida_Quiz
 * @since 0.9.3
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div id="vv-guide-section" style="margin-top: 30px;">
    <div id="vv-main-instructions" class="postbox">
        <h2 class="hndle">
            <span>
                <span class="dashicons dashicons-book-alt"></span>
                <?php esc_html_e('Usage Instructions & Maintenance', 'vapevida-quiz'); ?>
            </span>
        </h2>
        <div class="inside">
            <p><?php esc_html_e('This plugin works as a **Quick Product Guide** (Product Finder). It is based on URL filters for immediate liquid discovery by the customer.', 'vapevida-quiz'); ?>
            </p>

            <h3>
                <span class="dashicons dashicons-admin-home"></span>
                <?php esc_html_e('Quiz Display & Text Customization', 'vapevida-quiz'); ?>
            </h3>

            <div style="background: #f0f0f0; padding: 15px; border-left: 4px solid #4a67b2; margin-bottom: 20px;">
                <p><strong><?php esc_html_e('Display Code (Shortcode):', 'vapevida-quiz'); ?></strong>
                    <?php esc_html_e('Use the following code to display the form on any page (e.g. Home):', 'vapevida-quiz'); ?>
                </p>

                <div class="vv-shortcode-container"
                    style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 10px;">
                    <code id="vv-shortcode-code"
                        style="flex-grow: 1; padding: 8px 10px; background: #fff; border: 1px dashed #ccc; font-weight: bold; border-radius: 3px;">
                        <span class="dashicons dashicons-editor-code" style="vertical-align: middle; margin-right: 5px;"></span>[vapevida_quiz]
                    </code>

                    <button type="button" id="vv-copy-shortcode-btn"
                        class="button button-secondary dashicons-before dashicons-admin-page" style="flex-shrink: 0;"
                        onclick="vvCopyShortcode(this)">
                        <?php esc_html_e('Copy', 'vapevida-quiz'); ?>
                    </button>
                </div>
            </div>

            <p><?php esc_html_e('All texts (Titles, Labels, CTA) and the Quiz structure are configured from the **"Quiz Settings"** section located directly above. In the same section, **Filter Attributes** and **Colors** are configured.', 'vapevida-quiz'); ?>
            </p>

            <ul style="padding-left: 20px; color: #555;">
                <li>
                    <span
                        style="font-weight: bold;"><?php esc_html_e('Attribute Customization:', 'vapevida-quiz'); ?></span>
                    <?php esc_html_e('Use the "Use custom Global Attributes" checkbox to change the Global Attributes that the form uses (e.g., from `pa_geuseis` to `pa_color`).', 'vapevida-quiz'); ?>
                </li>
                <li>
                    <span style="font-weight: bold;"><?php esc_html_e('Color Palette:', 'vapevida-quiz'); ?></span>
                    <?php esc_html_e('**Background/Hover** fields are configured via Color Pickers.', 'vapevida-quiz'); ?>
                </li>
                <li>
                    <span style="font-weight: bold;"><?php esc_html_e('Required Fields:', 'vapevida-quiz'); ?></span>
                    <?php esc_html_e('Check which fields must be filled in for form submission.', 'vapevida-quiz'); ?>
                </li>
            </ul>

            <h3 style="margin-top: 30px;">
                <span class="dashicons dashicons-editor-ul"></span>
                <?php esc_html_e('Flavor Management Guide', 'vapevida-quiz'); ?>
            </h3>
            <p style="margin-bottom: 15px;">
                <?php esc_html_e('Adding new options to the Quiz is done **automatically**, as long as the correct settings are updated in the WooCommerce Global Attributes.', 'vapevida-quiz'); ?>
            </p>

            <div style="background: #eef7ff; padding: 15px; border: 1px solid #cceeff; border-radius: 5px;">
                <p style="font-weight: bold; margin-top: 0;">
                    <?php esc_html_e('Step 1: Find the Correct Folder', 'vapevida-quiz'); ?></p>
                <p style="margin-left: 10px;">
                    <?php esc_html_e('Go to: **Products → Attributes**. The two Attributes that populate the Quiz are:', 'vapevida-quiz'); ?>
                </p>
                <ul style="padding-left: 15px;">
                    <li><strong><?php esc_html_e('Flavor Type:', 'vapevida-quiz'); ?></strong> <code>pa_geuseis</code>
                    </li>
                    <li><strong><?php esc_html_e('Ingredient (e.g. Strawberry, Cream):', 'vapevida-quiz'); ?></strong>
                        <code>pa_quiz-ingredient</code></li>
                </ul>
            </div>

            <div
                style="background: #fdf5e6; padding: 15px; border: 1px solid #ffaa00; border-radius: 5px; margin-top: 10px;">
                <p style="font-weight: bold; margin-top: 0;">
                    <?php esc_html_e('Step 2: Add New Term', 'vapevida-quiz'); ?></p>
                <p style="margin-left: 10px;">
                    <?php esc_html_e('Click on **"Configure Terms"** next to the Attribute **Ingredient (Quiz)**.', 'vapevida-quiz'); ?>
                </p>
                <ul style="padding-left: 15px; list-style-type: square;">
                    <li>
                        <strong><?php esc_html_e('Name:', 'vapevida-quiz'); ?></strong>
                        <?php esc_html_e('Write the full name (e.g. \'Kiwi\') and a clean slug (e.g. \'kiwi\').', 'vapevida-quiz'); ?>
                    </li>
                </ul>
            </div>

            <div
                style="background: #f0fff0; padding: 15px; border: 1px solid #3c763d; border-radius: 5px; margin-top: 10px;">
                <p style="font-weight: bold; margin-top: 0;">
                    <?php esc_html_e('Step 3: Link to Product & Check', 'vapevida-quiz'); ?></p>
                <p style="margin-left: 10px;">
                    <?php esc_html_e('For the new Term to appear in the Quiz, it must be associated with at least one published product.', 'vapevida-quiz'); ?>
                </p>
                <p style="margin-left: 10px; color: #d9534f; font-weight: bold;">
                    <span class="dashicons dashicons-warning" style="font-size: 1.2em; vertical-align: middle;"></span>
                    <strong><?php esc_html_e('Very Important:', 'vapevida-quiz'); ?></strong>
                    <?php esc_html_e('If a term **does not** have products, the Quiz ignores it (to avoid leading to empty results).', 'vapevida-quiz'); ?>
                </p>
            </div>
        </div>
    </div>
</div>