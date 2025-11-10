<?php
/**
 * Admin Page Renderer
 * 
 * Handles the rendering of the main admin dashboard page.
 * Organized into logical sections for maintainability.
 *
 * @package VapeVida_Quiz
 * @since 0.9.3
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin Page Renderer Class
 */
class VV_Admin_Page_Renderer
{

    /**
     * Plugin data
     * @var array
     */
    private $plugin_data;

    /**
     * Current settings
     * @var array
     */
    private $settings;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->load_plugin_data();
        $this->settings = VV_Admin_Settings::get_settings();
    }

    /**
     * Load plugin metadata
     */
    private function load_plugin_data()
    {
        $headers = array(
            'Version' => 'Version',
            'Author' => 'Author',
            'VersionNotes' => 'VersionNotes',
            'Features' => 'Features'
        );

        $this->plugin_data = get_file_data(
            VV_QUIZ_DIR . 'vapevida-quiz.php',
            $headers,
            'plugin'
        );
    }

    /**
     * Main render method
     */
    public function render()
    {
        ?>
        <div class="wrap">
            <?php
            $this->render_header();
            $this->render_styles();
            $this->render_scripts();
            $this->render_content();
            ?>
        </div>
        <?php
    }

    /**
     * Render page header
     */
    private function render_header()
    {
        ?>
        <h1><?php esc_html_e('VapeVida Flavorshot Recommender Quiz', 'vapevida-quiz'); ?></h1>
        <p class="about-text">
            <?php esc_html_e('Usage instructions and technical information for managing the custom plugin.', 'vapevida-quiz'); ?>
        </p>
        <hr class="wp-header-end">
        <?php
    }

    /**
     * Render inline styles
     */
    private function render_styles()
    {
        ?>
        <style>
            /* Responsive Layout Styles */
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

            /* Conditional Attribute Settings */
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

            /* Tablet breakpoint */
            @media screen and (max-width: 1024px) {
                #vv-admin-top-flex {
                    gap: 20px;
                }

                #vv-sidebar-metadata {
                    min-width: 250px;
                }
            }

            /* Mobile breakpoint - stack vertically */
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

            /* Small mobile */
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
        <?php
    }

    /**
     * Render inline scripts
     */
    private function render_scripts()
    {
        ?>
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

            // Toggle custom attributes visibility
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

            // Initialize on page load
            document.addEventListener('DOMContentLoaded', function () {
                const useCustomCheckbox = document.getElementById('use_custom_attributes');
                if (useCustomCheckbox) {
                    vvToggleCustomAttributes(useCustomCheckbox);
                }
            });
        </script>
        <?php
    }

    /**
     * Render main content
     */
    private function render_content()
    {
        ?>
        <div id="vv-admin-top-flex">
            <?php
            $this->render_main_column();
            $this->render_sidebar();
            ?>
        </div>

        <?php
        $this->render_guide_section();
    }

    /**
     * Render main content column
     */
    private function render_main_column()
    {
        ?>
        <div id="vv-main-content-wrapper">
            <div id="vv-settings-form" class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle">
                    <span>
                        <span class="dashicons dashicons-admin-generic"></span>
                        <?php esc_html_e('Quiz Settings', 'vapevida-quiz'); ?>
                    </span>
                </h2>
                <div class="inside">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields(VV_Admin_Settings::OPTION_GROUP);
                        do_settings_sections(VV_Admin_Menu::get_page_slug());
                        submit_button();
                        ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render sidebar
     */
    private function render_sidebar()
    {
        $shop_url = get_permalink(wc_get_page_id('shop'));
        $git_url = 'https://github.com/LittleDTLe/VapeProductQuiz';

        // Get chosen attribute slugs
        $use_custom = isset($this->settings['use_custom_attributes']) ? $this->settings['use_custom_attributes'] : false;
        $chosen_type_slug = $use_custom && isset($this->settings['attribute_type_slug']) && !empty($this->settings['attribute_type_slug'])
            ? esc_html($this->settings['attribute_type_slug'])
            : 'pa_geuseis';
        $chosen_ingredient_slug = $use_custom && isset($this->settings['attribute_ingredient_slug']) && !empty($this->settings['attribute_ingredient_slug'])
            ? esc_html($this->settings['attribute_ingredient_slug'])
            : 'pa_quiz-ingredient';

        // Plugin metadata
        $plugin_version = isset($this->plugin_data['Version']) ? esc_html($this->plugin_data['Version']) : 'N/A';
        $plugin_author = isset($this->plugin_data['Author']) ? esc_html($this->plugin_data['Author']) : __('Unknown', 'vapevida-quiz');
        $version_notes = array_map('trim', array_filter(explode(',', isset($this->plugin_data['VersionNotes']) ? $this->plugin_data['VersionNotes'] : '')));
        $features = array_map('trim', array_filter(explode(',', isset($this->plugin_data['Features']) ? $this->plugin_data['Features'] : '')));

        ?>
        <div id="vv-sidebar-metadata" class="postbox-container">
            <div class="postbox">
                <h2 class="hndle">
                    <span>
                        <span class="dashicons dashicons-info" style="font-size: 1.2em; vertical-align: middle;"></span>
                        <?php esc_html_e('Plugin Information', 'vapevida-quiz'); ?>
                    </span>
                </h2>
                <div class="inside">
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;">
                            <strong><?php esc_html_e('Author:', 'vapevida-quiz'); ?></strong>
                            <span style="font-weight: bold;"><?php echo $plugin_author; ?></span>
                        </li>
                        <li style="margin-bottom: 8px;">
                            <strong><?php esc_html_e('Plugin Version:', 'vapevida-quiz'); ?></strong>
                            <?php echo $plugin_version; ?>
                        </li>

                        <li style="margin-top: 15px; margin-bottom: 5px;">
                            <strong><?php esc_html_e('Version Notes:', 'vapevida-quiz'); ?></strong>
                        </li>
                        <ul style="padding-left: 20px; margin-top: 0;">
                            <?php foreach ($version_notes as $note):
                                if (!empty($note)): ?>
                                    <li>
                                        <span class="dashicons dashicons-yes-alt"
                                            style="color: #4CAF50; font-size: 14px; vertical-align: sub;"></span>
                                        <?php echo esc_html($note); ?>
                                    </li>
                                <?php endif; endforeach; ?>
                        </ul>

                        <li style="margin-top: 15px; margin-bottom: 5px;">
                            <strong><?php esc_html_e('Features:', 'vapevida-quiz'); ?></strong>
                        </li>
                        <ul style="padding-left: 20px; margin-top: 0;">
                            <?php foreach ($features as $feature):
                                if (!empty($feature)): ?>
                                    <li>
                                        <span class="dashicons dashicons-star-filled"
                                            style="color: #FFC107; font-size: 14px; vertical-align: sub;"></span>
                                        <?php echo esc_html($feature); ?>
                                    </li>
                                <?php endif; endforeach; ?>
                        </ul>

                        <li style="border-top: 1px solid #eee; padding-top: 12px; margin-top: 15px;">
                            <strong><?php esc_html_e('Shop URL:', 'vapevida-quiz'); ?></strong>
                            <a href="<?php echo esc_url($shop_url); ?>" target="_blank">
                                <?php esc_html_e('Open Shop', 'vapevida-quiz'); ?>
                            </a>
                        </li>
                        <li style="margin-top: 5px;">
                            <strong><?php esc_html_e('Github Repository:', 'vapevida-quiz'); ?></strong>
                            <a href="<?php echo esc_url($git_url); ?>" target="_blank">
                                <?php esc_html_e('Go to Github', 'vapevida-quiz'); ?>
                            </a>
                        </li>
                    </ul>

                    <h3 style="margin-top: 20px;"><?php esc_html_e('Attribute Slugs', 'vapevida-quiz'); ?></h3>
                    <p><?php esc_html_e('The required Global Attributes for the Quiz are:', 'vapevida-quiz'); ?></p>
                    <ul style="padding-left: 20px;">
                        <li>
                            <strong><?php esc_html_e('Flavor Type:', 'vapevida-quiz'); ?></strong>
                            <code><?php echo $chosen_type_slug; ?></code>
                        </li>
                        <li>
                            <strong><?php esc_html_e('Ingredient:', 'vapevida-quiz'); ?></strong>
                            <code><?php echo $chosen_ingredient_slug; ?></code>
                        </li>
                    </ul>

                    <h3 style="margin-top: 30px;">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Filter Technical Logic', 'vapevida-quiz'); ?>
                    </h3>
                    <p><?php esc_html_e('The Query Logic code ensures that filtering is done with **absolute precision** (AND Logic) between all selected fields.', 'vapevida-quiz'); ?>
                    </p>
                    <div style="background: #f7f7f7; padding: 15px; border-left: 4px solid #e21e51; margin-top: 15px;">
                        <p>
                            <strong>&bull; <?php esc_html_e('Flavor Type:', 'vapevida-quiz'); ?></strong>
                            `filter_geuseis` (<?php esc_html_e('Single Select', 'vapevida-quiz'); ?>)
                        </p>
                        <p>
                            <strong>&bull; <?php esc_html_e('Primary & Secondary Ingredient:', 'vapevida-quiz'); ?></strong>
                            `filter_quiz-ingredient` (<?php esc_html_e('Combined AND Logic', 'vapevida-quiz'); ?>)
                        </p>
                        <p style="margin-top: 10px; font-style: italic;">
                            <?php esc_html_e('The system checks if the product has the \'Flavor Type\' **AND** both selected \'Ingredients\' simultaneously.', 'vapevida-quiz'); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render guide section - Continued in next method
     * This method signature allows for extending rendering
     */
    private function render_guide_section()
    {
        // Guide section rendering is split for readability
        // See render_guide_content() method
        $this->render_guide_content();
    }

    /**
     * Render guide content section
     * Split into a separate method for maintainability
     */
    private function render_guide_content()
    {
        // This would continue with the guide rendering
        // Implementation in the next part to keep file manageable
        include VV_QUIZ_DIR . 'includes/admin/partials/guide-section.php';
    }
}