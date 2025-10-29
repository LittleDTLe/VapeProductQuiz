<?php
/**
 * Admin Logic for VapeVida Quiz.
 * Handles Menu creation, Settings registration (Settings API), and Dashboard rendering.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// --- 5. Add Admin Menu Page (Top-Level Unique Page) ---
function vv_add_plugin_admin_page() {
    add_menu_page(
        'VapeVida Quiz Details',
        'VapeVida Quiz',
        'manage_options',
        'vapevida-quiz-details',
        'vv_render_details_page',
        'dashicons-clipboard',
        70
    );
}
add_action( 'admin_menu', 'vv_add_plugin_admin_page' );

// --- 7. Add Settings Link to Plugins Page ---
// NOTE: Uses the directory structure to find the main plugin file
add_filter( 'plugin_action_links_' . plugin_basename( dirname(__FILE__, 2) . '/vapevida-quiz.php' ), 'vv_add_plugin_action_links' );

function vv_add_plugin_action_links( $actions ) {
    $settings_link = '<a href="admin.php?page=vapevida-quiz-details">' . __( 'Quiz Info & Help', 'vapevida-quiz' ) . '</a>';
    array_unshift( $actions, $settings_link );
    return $actions;
}


// -----------------------------------------------------------
// A. SETTINGS API IMPLEMENTATION & CALLBACKS
// -----------------------------------------------------------

// --- 8. Register Settings Fields ---
function vv_quiz_register_settings() {
    register_setting( 'vv_quiz_options_group', 'vv_quiz_settings' );

    // 1. REQUIRED FIELDS SECTION (TOP)
    add_settings_section(
        'vv_quiz_required_section',
        'Ρυθμίσεις Απαιτούμενων Πεδίων (Required)',
        'vv_quiz_required_section_callback',
        'vapevida-quiz-details'
    );
    add_settings_field('is_type_required', 'Απαιτείται ο Τύπος Υγρού;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_type_required']);
    add_settings_field('is_primary_required', 'Απαιτείται το Βασικό Συστατικό;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_primary_required']);
    add_settings_field('is_secondary_required', 'Απαιτείται το Δευτερεύον Συστατικό;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_secondary_required']);

    // 2. MAIN FORM / LABELS SECTION
    add_settings_section(
        'vv_quiz_main_section',
        'Ρυθμίσεις Φόρμας και Ετικετών',
        'vv_quiz_main_section_callback',
        'vapevida-quiz-details'
    );

    // Attribute Selectors
    add_settings_field('attribute_type_slug', '1. Attribute για Τύπο', 'vv_quiz_attribute_select_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'attribute_type_slug']);
    add_settings_field('attribute_ingredient_slug', '2. Attribute για Συστατικό', 'vv_quiz_attribute_select_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'attribute_ingredient_slug']);

    // Status / Visibility / CTA / Headers / Labels / Placeholders 
    add_settings_field('field_status', 'Ενεργοποίηση 3ου Πεδίου (Δευτ. Συστατικό)', 'vv_quiz_status_callback', 'vapevida-quiz-details', 'vv_quiz_main_section');
    add_settings_field('quiz_heading', 'Τίτλος Quiz (H2)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'quiz_heading', 'default' => 'Βρες το Ιδανικό Υγρό!']);
    add_settings_field('quiz_subtitle', 'Υπότιτλος Quiz (P)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'quiz_subtitle', 'default' => 'Επίλεξε το προφίλ γεύσης και τα συστατικά που προτιμάς.']);
    add_settings_field('label_type', 'Ετικέτα 1 (Τύπος Υγρού)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'label_type', 'default' => '1. Προφίλ Υγρού:']);
    add_settings_field('label_primary', 'Ετικέτα 2 (Βασικό Συστατικό)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'label_primary', 'default' => '2. Βασικό Συστατικό:']);
    add_settings_field('label_secondary', 'Ετικέτα 3 (Δευτ. Συστατικό)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'label_secondary', 'default' => '3. Δευτερεύον Συστατικό:']);
    add_settings_field('placeholder_type', 'Placeholder 1 (Τύπος Υγρού)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'placeholder_type', 'default' => '-- Επιλέξτε Προφίλ --']);
    add_settings_field('placeholder_primary', 'Placeholder Βασικού Συστατικού', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'placeholder_primary', 'default' => '-- Επιλογή Βασικού Συστατικού --']);
    add_settings_field('placeholder_secondary', 'Placeholder Δευτερεύοντος Συστατικού', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'placeholder_secondary', 'default' => '-- Επιλογή Δευτερεύον Συστατικού --']);
    add_settings_field('button_cta', 'Κείμενο Κουμπιού (CTA)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'button_cta', 'default' => 'ΒΡΕΣ ΤΟ ΥΓΡΟ ΣΟΥ']);
}
add_action( 'admin_init', 'vv_quiz_register_settings' );

// --- 9. Callbacks for Settings Fields ---

function vv_quiz_required_section_callback() {
    echo '<p>Επιλέξτε ποια από τα πεδία του Quiz πρέπει να είναι υποχρεωτικά.</p>';
}

function vv_quiz_checkbox_callback( $args ) {
    $options = get_option( 'vv_quiz_settings' );
    $field_id = $args['field_id'];
    $checked = isset( $options[$field_id] ) ? checked( 1, $options[$field_id], false ) : '';
    echo '<input type="checkbox" name="vv_quiz_settings[' . esc_attr($field_id) . ']" value="1" ' . $checked . '>';
}

function vv_quiz_main_section_callback() {
    echo '<p>Εδώ ορίζετε τις ετικέτες και τη δομή του Quiz που εμφανίζεται στην Αρχική Σελίδα.</p>';
}

function vv_quiz_status_callback() {
    $options = get_option( 'vv_quiz_settings' );
    $checked = isset( $options['field_status'] ) ? $options['field_status'] : false;
    echo '<input type="checkbox" name="vv_quiz_settings[field_status]" value="1" ' . checked( 1, $checked, false ) . ' />';
    echo '<label for="vv_quiz_settings[field_status]">Ενεργοποίηση 3ου Πεδίου (Δευτ. Συστατικό).</label>';
}

function vv_quiz_text_field_callback( $args ) {
    $options = get_option( 'vv_quiz_settings' );
    $field_id = $args['field_id'];
    $default_value = $args['default'];
    $current_value = isset( $options[$field_id] ) ? $options[$field_id] : $default_value;

    echo '<input type="text" id="' . esc_attr($field_id) . '" name="vv_quiz_settings[' . esc_attr($field_id) . ']" value="' . esc_attr( $current_value ) . '" class="regular-text" placeholder="' . esc_attr($default_value) . '" />';
}


// --- Callback to list all Global WooCommerce Attributes ---
function vv_quiz_attribute_select_callback( $args ) {
    $options = get_option( 'vv_quiz_settings' );
    $field_id = $args['field_id'];
    $current_slug = isset( $options[$field_id] ) ? $options[$field_id] : '';

    // Fetch all global WooCommerce attributes
    $attributes = wc_get_attribute_taxonomies();
    
    // Start the dropdown field
    echo '<select name="vv_quiz_settings[' . esc_attr($field_id) . ']" id="' . esc_attr($field_id) . '" class="regular-text">';
    echo '<option value="">-- Επιλέξτε Attribute --</option>';

    if ( ! empty( $attributes ) ) {
        foreach ( $attributes as $attribute ) {
            $taxonomy_slug = 'pa_' . $attribute->attribute_name;
            $selected = selected( $current_slug, $taxonomy_slug, false );
            
            // Display: Attribute Name (pa_slug)
            echo '<option value="' . esc_attr($taxonomy_slug) . '" ' . $selected . '>';
            echo esc_html($attribute->attribute_label) . ' (' . esc_html($taxonomy_slug) . ')';
            echo '</option>';
        }
    }
    echo '</select>';
    echo '<p class="description">Επιλέξτε τον Global Attribute που θα γεμίσει αυτό το πεδίο.</p>';
}

// -----------------------------------------------------------
// B. DASHBOARD RENDERING (vv_render_details_page)
// -----------------------------------------------------------

function vv_render_details_page() {
    // SECURITY CHECK
    if ( ! current_user_can( 'manage_options' ) ) return; 
    
    // --- ΔΥΝΑΜΙΚΗ ΑΝΑΚΤΗΣΗ ΔΕΔΟΜΕΝΩΝ (Plugin Header) ---
    $shop_url = get_permalink( wc_get_page_id( 'shop' ) );
    $git_url = 'https://github.com/LittleDTLe/VapeProductQuiz';
    
    $all_headers = array('Version' => 'Version', 'Author' => 'Author', 'VersionNotes' => 'VersionNotes', 'Features' => 'Features');
    // We assume VV_QUIZ_DIR is correctly defined in the main loader file
    $plugin_data_raw = get_file_data( dirname(__FILE__, 2) . '/vapevida-quiz.php', $all_headers, 'plugin' );
    
    // Safe assignment and List Conversion
    $plugin_version = isset($plugin_data_raw['Version']) ? esc_html($plugin_data_raw['Version']) : 'N/A';
    $plugin_author = isset($plugin_data_raw['Author']) ? esc_html($plugin_data_raw['Author']) : 'Unknown';
    $version_notes_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['VersionNotes']) ? $plugin_data_raw['VersionNotes'] : '')));
    $features_list = array_map('trim', array_filter(explode(',', isset($plugin_data_raw['Features']) ? $plugin_data_raw['Features'] : '')));

    ?>
    <div class="wrap">
        <h1>VapeVida Flavorshot Recommender Quiz</h1>
        <p class="about-text">Οδηγίες χρήσης και τεχνικές πληροφορίες για τη διαχείριση του custom plugin.</p>
        
        <hr class="wp-header-end">

        <div id="vv-admin-content-flex" style="display: flex; gap: 30px; margin-top: 20px;">
            
            <div id="vv-main-content-wrapper" style="flex: 2; min-width: 0;"> 
                
                <div id="vv-settings-form" class="postbox" style="margin-bottom: 20px;">
                    <h2 class="hndle"><span><span class="dashicons dashicons-admin-generic"></span> Ρυθμίσεις Quiz</span></h2>
                    <div class="inside">
                        <form method="post" action="options.php">
                            <?php 
                            settings_fields( 'vv_quiz_options_group' );
                            do_settings_sections( 'vapevida-quiz-details' );
                            submit_button();
                            ?>
                        </form>
                    </div>
                </div>
                
                <div id="vv-main-instructions" class="postbox">
                    <h2 class="hndle"><span><span class="dashicons dashicons-book-alt"></span> Οδηγίες Χρήσης & Συντήρηση</span></h2>
                    <div class="inside">
                        <p>Αυτό το plugin λειτουργεί ως ένας **Γρήγορος Οδηγός Προϊόντων** (Product Finder). Βασίζεται σε φίλτρα URL για την άμεση εύρεση υγρών από τον πελάτη.</p>
                        
                        <h3><span class="dashicons dashicons-admin-home"></span> Εμφάνιση Quiz & Προσαρμογή Κειμένων</h3>
                        
                        <div style="background: #f0f0f0; padding: 15px; border-left: 4px solid #4a67b2; margin-bottom: 20px;">
                            <p><strong>Κωδικός Εμφάνισης (Shortcode):</strong> Χρησιμοποιήστε τον παρακάτω κωδικό για να εμφανίσετε τη φόρμα σε οποιαδήποτε σελίδα (π.χ. Αρχική):</p>
                            <code style="display: block; padding: 5px; background: #fff; border: 1px dashed #ccc; font-weight: bold;">[vapevida_quiz]</code>
                        </div>
                        
                        <p>Όλα τα κείμενα (Τίτλοι, Ετικέτες, CTA) και η δομή του Quiz ρυθμίζονται από την ενότητα <strong>«Ρυθμίσεις Quiz»</strong> που βρίσκεται ακριβώς επάνω:</p>

                        <ul style="padding-left: 20px; color: #555;">
                            <li><span style="font-weight: bold;">Τίτλοι (H2/P):</span> Αλλάξτε το κύριο μήνυμα για εποχιακά ή προωθητικά θέματα.</li>
                            <li><span style="font-weight: bold;">Υποχρεωτικότητα (Required):</span> Ελέγξτε ποια πεδία πρέπει να είναι συμπληρωμένα για την υποβολή της φόρμας.</li>
                            <li><span style="font-weight: bold;">Ενεργοποίηση 3ου Πεδίου:</span> Επιλέξτε εάν το τρίτο πεδίο (Δευτερεύον Συστατικό) εμφανίζεται στη φόρμα.</li>
                        </ul>
                        
                        <h3 style="margin-top: 30px;"><span class="dashicons dashicons-editor-ul"></span> Οδηγός Διαχείρισης Γεύσεων (Βήματα Group B)</h3>
                        <p style="margin-bottom: 15px;">Η προσθήκη νέων επιλογών στο Quiz γίνεται **αυτόματα**, αρκεί να ενημερωθούν οι σωστές ρυθμίσεις στα Global Attributes του WooCommerce.</p>

                        <div style="background: #eef7ff; padding: 15px; border: 1px solid #cceeff; border-radius: 5px;">
                            <p style="font-weight: bold; margin-top: 0;">Βήμα 1: Εύρεση του Σωστού Φακέλου</p>
                            <p style="margin-left: 10px;">Πηγαίνετε: <strong>Προϊόντα &rarr; Χαρακτηριστικά</strong>. Τα δύο Attributes που γεμίζουν το Quiz είναι:</p>
                            <ul style="padding-left: 15px;">
                                <li>**Τύπος Υγρού (π.χ. Γλυκές, Καπνικές):** <code>pa_geuseis</code></li>
                                <li>**Συστατικό (π.χ. Φράουλα, Κρέμα):** <code>pa_quiz-ingredient</code></li>
                            </ul>
                        </div>
                        
                        <div style="background: #fdf5e6; padding: 15px; border: 1px solid #ffaa00; border-radius: 5px; margin-top: 10px;">
                            <p style="font-weight: bold; margin-top: 0;">Βήμα 2: Προσθήκη Νέου Όρου (Term)</p>
                            <p style="margin-left: 10px;">Κάντε κλικ στο **"Ρύθμιση όρων"** (Configure Terms) δίπλα στο Attribute **Συστατικό (Quiz)**.</p>
                            <ul style="padding-left: 15px; list-style-type: square;">
                                <li>**Ονομασία (Name):** Γράψτε την πλήρη ονομασία (π.χ. 'Ακτινίδιο') και ένα καθαρό slug (π.χ. 'actinidio').</li>
                            </ul>
                        </div>
                        
                        <div style="background: #f0fff0; padding: 15px; border: 1px solid #3c763d; border-radius: 5px; margin-top: 10px;">
                            <p style="font-weight: bold; margin-top: 0;">Βήμα 3: Σύνδεση με Προϊόν & Έλεγχος</p>
                            <p style="margin-left: 10px;">Για να εμφανιστεί ο νέος Όρος στο Quiz, πρέπει να είναι αντιστοιχισμένος σε τουλάχουν ένα δημοσιευμένο προϊόν.</p>
                            <p style="margin-left: 10px; color: #d9534f; font-weight: bold;">
                                <span class="dashicons dashicons-warning" style="font-size: 1.2em; vertical-align: middle;"></span> **Πολύ Σημαντικό:** Αν ένας όρος **δεν** έχει προϊόντα, το Quiz τον αγνοεί (για να μην οδηγεί σε κενά αποτελέσματα).
                            </p>
                        </div>

                        <h3 style="margin-top: 30px;"><span class="dashicons dashicons-search"></span> Τεχνική Λογική Φίλτρου</h3>
                        <p>Ο κώδικας Query Logic εξασφαλίζει ότι το φιλτράρισμα γίνεται με **απόλυτη ακρίβεια** (AND Logic) ανάμεσα σε όλα τα επιλεγμένα πεδία.</p>
                        <div style="background: #f7f7f7; padding: 15px; border-left: 4px solid #e21e51; margin-top: 15px;">
                            <p><strong>&bull; Τύπος Γεύσης:</strong> `filter_geuseis` (Single Select)</p>
                            <p><strong>&bull; Βασικό & Δευτερεύον Συστατικό:</strong> `filter_quiz-ingredient` (Combined AND Logic)</p>
                            <p style="margin-top: 10px; font-style: italic;">Το σύστημα ελέγχει αν το προϊόν έχει το 'Τύπος Γεύσης' **ΚΑΙ** και τα δύο επιλεγμένα 'Συστατικά' ταυτόχρονα.</p>
                        </div>
                    </div>
                </div>
            </div> <div id="vv-sidebar-metadata" class="postbox-container" style="flex: 1; min-width: 300px;">
                <div class="postbox">
                    <h2 class="hndle"><span><span class="dashicons dashicons-info" style="font-size: 1.2em; vertical-align: middle;"></span> Πληροφορίες Plugin</span></h2>
                    <div class="inside">
                        <ul style="list-style: none; padding: 0; margin: 0;">
                            <li style="margin-bottom: 8px;"><strong>Συντάκτης:</strong> <span style="font-weight: bold;"><?php echo $plugin_author; ?></span></li>
                            <li style="margin-bottom: 8px;"><strong>Έκδοση Plugin:</strong> <?php echo $plugin_version; ?></li>
                            
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
                            
                            <li style="border-top: 1px solid #eee; padding-top: 12px; margin-top: 15px;"><strong>URL Καταστήματος:</strong> <a href="<?php echo esc_url($shop_url); ?>" target="_blank">Άνοιγμα Καταστήματος</a></li>
                            <li style="margin-top: 5px;"><strong>Github Repository:</strong> <a href="<?php echo esc_url($git_url); ?>" target="_blank">Μεταφέρσου στο Github</a></li>
                        </ul>
                        
                        <h3 style="margin-top: 20px;">Slugs Χαρακτηριστικών</h3>
                        <p>Τα απαραίτητα Global Attributes για το Quiz είναι:</p>
                        <ul style="padding-left: 20px;">
                            <li>**Τύπος Γεύσης:** <code>pa_geuseis</code></li>
                            <li>**Συστατικό:** <code>pa_quiz-ingredient</code></li>
                        </ul>
                    </div>
                </div>
            </div> </div> </div>
    <?php
}