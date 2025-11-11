<?php
/**
 * Admin Rendering Logic.
 * Contains the final HTML structure and calls the necessary settings and scripts.
 */

if (!defined('ABSPATH'))
    exit;

function vv_render_details_page()
{
    // SECURITY CHECK
    if (!current_user_can('manage_options'))
        return;

    // --- ΔΥΝΑΜΙΚΗ ΑΝΑΚΤΗΣΗ ΔΕΔΟΜΕΝΩΝ (Plugin Header) ---
    // NOTE: We assume VV_QUIZ_DIR is defined in the main loader file
    $shop_url = get_permalink(wc_get_page_id('shop'));
    $git_url = 'https://github.com/LittleDTLe/VapeProductQuiz';

    $settings = get_option('vv_quiz_settings');

    $chosen_type_slug = isset($settings['attribute_type_slug']) ? esc_html($settings['attribute_type_slug']) : 'pa_geuseis (Default)';
    $chosen_ingredient_slug = isset($settings['attribute_ingredient_slug']) ? esc_html($settings['attribute_ingredient_slug']) : 'pa_quiz-ingredient (Default)';

    $all_headers = array('Version' => 'Version', 'Author' => 'Author', 'VersionNotes' => 'VersionNotes', 'Features' => 'Features');
    // Using a absolute path for the main plugin file
    $main_plugin_file = dirname(__FILE__, 3) . '/vapevida-quiz.php'; // 3 levels up from /includes/admin/

    // Safety check for the constant (if not defined in the main file)
    if (defined('VV_QUIZ_DIR')) {
        $main_plugin_file = VV_QUIZ_DIR . 'vapevida-quiz.php';
    } else {
        // Fallback calculation if the constant is not present
        $main_plugin_file = trailingslashit(WPMU_PLUGIN_DIR) . 'vapevida-quiz/vapevida-quiz.php';
        // NOTE: This fallback depends entirely on your specific directory structure.
    }

    // Ανάγνωση δεδομένων με τη σωστή διαδρομή:
    $plugin_data_raw = get_file_data($main_plugin_file, $all_headers, 'plugin');

    // Safe assignment and List Conversion
    $plugin_version = isset($plugin_data_raw['Version']) ? esc_html($plugin_data_raw['Version']) : 'N/A';
    $plugin_author = isset($plugin_data_raw['Author']) ? esc_html($plugin_data_raw['Author']) : 'Unknown';
    $version_notes_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['VersionNotes']) ? $plugin_data_raw['VersionNotes'] : '')));
    $features_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['Features']) ? $plugin_data_raw['Features'] : '')));

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
    <script>
        function vvCopyShortcode(button) {
            const shortcode = '[vapevida_quiz]';

            navigator.clipboard.writeText(shortcode).then(function () {
                const originalHTML = button.innerHTML;
                button.innerHTML = '<span class="dashicons dashicons-yes"></span>Αντιγράφηκε!';
                button.classList.add('copied');

                setTimeout(function () {
                    button.innerHTML = originalHTML;
                    button.classList.remove('copied');
                }, 2000);
            }).catch(function (err) {
                console.error('Failed to copy: ', err);
                alert('Αποτυχία αντιγραφής. Παρακαλώ αντιγράψτε χειροκίνητα.');
            });
        }

        // Toggle custom attributes visibility (instant, no save required)
        function vvToggleCustomAttributes(checkbox) {
            // Find ALL default info boxes and select wrappers on the page (not just in one container)
            const allDefaultInfos = document.querySelectorAll('.vv-default-info');
            const allSelectWrappers = document.querySelectorAll('.vv-attribute-select-wrapper');

            console.log('Found default infos:', allDefaultInfos.length);
            console.log('Found select wrappers:', allSelectWrappers.length);

            if (checkbox.checked) {
                // Custom mode: hide all default info, show all dropdowns
                allDefaultInfos.forEach(function (info) {
                    console.log('Hiding default info');
                    info.style.display = 'none';
                });
                allSelectWrappers.forEach(function (wrapper) {
                    console.log('Showing select wrapper');
                    wrapper.style.display = 'block';
                });
            } else {
                // Default mode: show all default info, hide all dropdowns
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

    <div class="wrap">
        <h1>VapeVida Flavorshot Recommender Quiz</h1>
        <p class="about-text">Οδηγίες χρήσης και τεχνικές πληροφορίες για τη διαχείριση του custom plugin.</p>

        <hr class="wp-header-end">

        <div id="vv-admin-top-flex">

            <div id="vv-main-content-wrapper" style="flex: 2; min-width: 0;">

                <div id="vv-settings-form" class="postbox" style="margin-bottom: 20px;">
                    <h2 class="hndle"><span><span class="dashicons dashicons-admin-generic"></span> Ρυθμίσεις Quiz</span>
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
                    <h2 class="hndle"><span><span class="dashicons dashicons-info"
                                style="font-size: 1.2em; vertical-align: middle;"></span> Πληροφορίες Plugin</span></h2>
                    <div class="inside">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 8px;"><strong>Συντάκτης:</strong> <span
                                    style="font-weight: bold;"><?php echo $plugin_author; ?></span></li>
                            <li style="margin-bottom: 8px;"><strong>Έκδοση Plugin:</strong> <?php echo $plugin_version; ?>
                            </li>

                            <li style="margin-top: 15px; margin-bottom: 5px;"><strong>Version Notes:</strong></li>
                            <ul style="padding-left: 20px; margin-top: 0;">
                                <?php
                                foreach ($version_notes_list as $note) {
                                    if (!empty($note)) {
                                        echo '<li><span class="dashicons dashicons-yes-alt" style="color: #4CAF50; font-size: 14px; vertical-align: sub;"></span> ' . esc_html($note) . '</li>';
                                    }
                                }
                                ?>
                            </ul>

                            <li style="margin-top: 15px; margin-bottom: 5px;"><strong>Features:</strong></li>
                            <ul style="padding-left: 20px; margin-top: 0;">
                                <?php
                                foreach ($features_list as $feature) {
                                    if (!empty($feature)) {
                                        echo '<li><span class="dashicons dashicons-star-filled" style="color: #FFC107; font-size: 14px; vertical-align: sub;"></span> ' . esc_html($feature) . '</li>';
                                    }
                                }
                                ?>
                            </ul>

                            <li style="border-top: 1px solid #eee; padding-top: 12px; margin-top: 15px;"><strong>URL
                                    Καταστήματος:</strong> <a href="<?php echo esc_url($shop_url); ?>"
                                    target="_blank">Άνοιγμα Καταστήματος</a></li>
                            <li style="margin-top: 5px;"><strong>Github Repository:</strong> <a
                                    href="<?php echo esc_url($git_url); ?>" target="_blank">Μεταφέρσου στο Github</a></li>
                        </ul>

                        <h3 style="margin-top: 20px;">Slugs Χαρακτηριστικών</h3>
                        <p>Τα απαραίτητα Global Attributes για το Quiz είναι:</p>
                        <ul style="padding-left: 20px;">
                            <li><strong>Τύπος Γεύσης:** <code><?php echo esc_html($chosen_type_slug); ?></code></li>
                            <li><strong>Συστατικό:** <code><?php echo esc_html($chosen_ingredient_slug); ?></code></li>
                        </ul>

                        <h3 style="margin-top: 30px;"><span class="dashicons dashicons-search"></span> Τεχνική Λογική
                            Φίλτρου</h3>
                        <p>Ο κώδικας Query Logic εξασφαλίζει ότι το φιλτράρισμα γίνεται με **απόλυτη ακρίβεια** (AND Logic)
                            ανάμεσα σε όλα τα επιλεγμένα πεδία.</p>
                        <div style="background: #f7f7f7; padding: 15px; border-left: 4px solid #e21e51; margin-top: 15px;">
                            <p><strong>&bull; Τύπος Γεύσης:</strong> `filter_geuseis` (Single Select)</p>
                            <p><strong>&bull; Βασικό & Δευτερεύον Συστατικό:</strong> `filter_quiz-ingredient` (Combined AND
                                Logic)</p>
                            <p style="margin-top: 10px; font-style: italic;">Το σύστημα ελέγχει αν το προϊόν έχει το 'Τύπος
                                Γεύσης' **ΚΑΙ** και τα δύο επιλεγμένα 'Συστατικά' ταυτόχρονα.</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        <div id="vv-guide-section" style="margin-top: 30px;">
            <div id="vv-main-instructions" class="postbox">
                <h2 class="hndle"><span><span class="dashicons dashicons-book-alt"></span> Οδηγίες Χρήσης & Συντήρηση</span>
                </h2>
                <div class="inside">
                    <p>Αυτό το plugin λειτουργεί ως ένας **Γρήγορος Οδηγός Προϊόντων** (Product Finder). Βασίζεται σε φίλτρα
                        URL για την άμεση εύρεση υγρών από τον πελάτη.</p>

                    <h3><span class="dashicons dashicons-admin-home"></span> Εμφάνιση Quiz & Προσαρμογή Κειμένων</h3>

                    <div style="background: #f0f0f0; padding: 15px; border-left: 4px solid #4a67b2; margin-bottom: 20px;">
                        <p><strong>Κωδικός Εμφάνισης (Shortcode):</strong> Χρησιμοποιήστε τον παρακάτω κωδικό για να
                            εμφανίσετε τη φόρμα σε οποιαδήποτε σελίδα (π.χ. Αρχική):</p>

                        <div class="vv-shortcode-container"
                            style="display: flex; align-items: center; justify-content: space-between; gap: 10px;">

                            <code id="vv-shortcode-code"
                                style="flex-grow: 1; padding: 8px 10px; background: #fff; border: 1px dashed #ccc; font-weight: bold; border-radius: 3px;">
                                                            <span class="dashicons dashicons-editor-code" style="vertical-align: middle; margin-right: 5px;"></span>[vapevida_quiz]
                                                        </code>

                            <button type="button" id="vv-copy-shortcode-btn"
                                class="button button-secondary dashicons-before dashicons-admin-page"
                                style="flex-shrink: 0;" onclick="vvCopyShortcode(this)">
                                Αντιγραφή
                            </button>
                        </div>
                    </div>

                    <p>Όλα τα κείμετενα (Τίτλοι, Ετικέτες, CTA) και η δομή του Quiz ρυθμίζονται από την ενότητα
                        <strong>«Ρυθμίσεις Quiz»</strong> που βρίσκεται ακριβώς επάνω:
                    </p>

                    <ul style="padding-left: 20px; color: #555;">
                        <li><span style="font-weight: bold;">Τίτλοι (H2/P):</span> Αλλάξτε το κύριο μήνυμα για εποχιακά ή
                            προωθητικά θέματα.</li>
                        <li><span style="font-weight: bold;">Υποχρεωτικότητα (Required):</span> Ελέγξτε ποια πεδία πρέπει να
                            είναι συμπληρωμένα για την υποβολή της φόρμας.</li>
                        <li><span style="font-weight: bold;">Ενεργοποίηση 3ου Πεδίου:</span> Επιλέξτε εάν το τρίτο πεδίο
                            (Δευτερεύον Συστατικό) εμφανίζεται στη φόρμα.</li>
                    </ul>

                    <h3 style="margin-top: 30px;"><span class="dashicons dashicons-editor-ul"></span> Οδηγός Διαχείρισης
                        Γεύσεων</h3>
                    <p style="margin-bottom: 15px;">Η προσθήκη νέων επιλογών στο Quiz γίνεται **αυτόματα**, αρκεί να
                        ενημερωθούν οι σωστές ρυθμίσεις στα Global Attributes του WooCommerce.</p>

                    <div style="background: #eef7ff; padding: 15px; border: 1px solid #cceeff; border-radius: 5px;">
                        <p style="font-weight: bold; margin-top: 0;">Βήμα 1: Εύρεση του Σωστού Φακέλου</p>
                        <p style="margin-left: 10px;">Πηγαίνετε: <strong>Προϊόντα &rarr; Χαρακτηριστικά</strong>. Τα δύο
                            Attributes που γεμίζουν το Quiz είναι:</p>
                        <ul style="padding-left: 15px;">
                            <li>**Τύπος Γεύσης:** <code>pa_geuseis</code></li>
                            <li>**Συστατικό (π.χ. Φράουλα, Κρέμα):** <code>pa_quiz-ingredient</code></li>
                        </ul>
                    </div>

                    <div
                        style="background: #fdf5e6; padding: 15px; border: 1px solid #ffaa00; border-radius: 5px; margin-top: 10px;">
                        <p style="font-weight: bold; margin-top: 0;">Βήμα 2: Προσθήκη Νέου Όρου (Term)</p>
                        <p style="margin-left: 10px;">Κάντε κλικ στο **"Ρύθμιση όρων"** (Configure Terms) δίπλα στο
                            Attribute **Συστατικό (Quiz)**.</p>
                        <ul style="padding-left: 15px; list-style-type: square;">
                            <li>**Ονομασία (Name):** Γράψτε την πλήρη ονομασία (π.χ. 'Ακτινίδιο') και ένα καθαρό slug (π.χ.
                                'actinidio').</li>
                        </ul>
                    </div>

                    <div
                        style="background: #f0fff0; padding: 15px; border: 1px solid #3c763d; border-radius: 5px; margin-top: 10px;">
                        <p style="font-weight: bold; margin-top: 0;">Βήμα 3: Σύνδεση με Προϊόν & Έλεγχος</p>
                        <p style="margin-left: 10px;">Για να εμφανιστεί ο νέος Όρος στο Quiz, πρέπει να είναι
                            αντιστοιχισμένος σε τουλάχουν ένα δημοσιευμένο προϊόν.</p>
                        <p style="margin-left: 10px; color: #d9534f; font-weight: bold;">
                            <span class="dashicons dashicons-warning"
                                style="font-size: 1.2em; vertical-align: middle;"></span> **Πολύ Σημαντικό:** Αν ένας όρος
                            **δεν** έχει προϊόντα, το Quiz τον αγνοεί (για να μην οδηγεί σε κενά αποτελέσματα).
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}