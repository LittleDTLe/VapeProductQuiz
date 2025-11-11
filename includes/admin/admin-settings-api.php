<?php
/**
 * Settings API Registration Structure.
 * Defines all sections and field structures.
 */

if (!defined('ABSPATH'))
    exit;

// --- 8. Register Settings Fields ---
function vv_quiz_register_settings()
{
    register_setting('vv_quiz_options_group', 'vv_quiz_settings');

    // 1. REQUIRED FIELDS SECTION (TOP)
    add_settings_section(
        'vv_quiz_required_section',
        'Ρυθμίσεις Απαιτούμενων Πεδίων (Required)',
        'vv_quiz_required_section_callback', // Defined in admin-callbacks.php
        'vapevida-quiz-details'
    );
    add_settings_field('is_type_required', 'Απαιτείται ο Τύπος Υγρού;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_type_required']);
    add_settings_field('is_primary_required', 'Απαιτείται το Βασικό Συστατικό;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_primary_required']);
    add_settings_field('is_secondary_required', 'Απαιτείται το Δευτερεύον Συστατικό;', 'vv_quiz_checkbox_callback', 'vapevida-quiz-details', 'vv_quiz_required_section', ['field_id' => 'is_secondary_required']);

    // 2. MAIN FORM / LABELS SECTION
    add_settings_section(
        'vv_quiz_main_section',
        'Ρυθμίσεις Φόρμας και Ετικετών',
        'vv_quiz_main_section_callback', // Defined in admin-callbacks.php
        'vapevida-quiz-details'
    );

    // Custom Attributes Toggle
    add_settings_field('use_custom_attributes', 'Χρήση Προσαρμοσμένων Attributes', 'vv_quiz_custom_attributes_toggle_callback', 'vapevida-quiz-details', 'vv_quiz_main_section');

    // Attribute Selectors (conditional)
    add_settings_field('attribute_type_slug', '1. Attribute για Τύπο', 'vv_quiz_attribute_select_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'attribute_type_slug']);
    add_settings_field('attribute_ingredient_slug', '2. Attribute για Συστατικό', 'vv_quiz_attribute_select_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'attribute_ingredient_slug']);

    // COLOR PICKERS
    add_settings_field('btn_bg_color', 'Χρώμα Κουμπιού (Background)', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'btn_bg_color', 'default' => '#e21e51']);
    add_settings_field('btn_txt_color', 'Χρώμα Κουμπιού (Κείμενο)', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'btn_txt_color', 'default' => '#FFFFFF']);
    add_settings_field('btn_bg_hover_color', 'Χρώμα Hover (Background)', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'btn_bg_hover_color', 'default' => '#c91a48']);
    add_settings_field('btn_txt_hover_color', 'Χρώμα Hover (Κείμενο)', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'btn_txt_hover_color', 'default' => '#FFFFFF']);

    // Clear Button Colors
    add_settings_field('clear_btn_bg_color', 'Κουμπί Καθαρισμού - Χρώμα Φόντου', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'clear_btn_bg_color', 'default' => '#6c757d']);
    add_settings_field('clear_btn_txt_color', 'Κουμπί Καθαρισμού - Χρώμα Κειμένου', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'clear_btn_txt_color', 'default' => '#FFFFFF']);
    add_settings_field('clear_btn_bg_hover_color', 'Κουμπί Καθαρισμού - Hover Φόντο', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'clear_btn_bg_hover_color', 'default' => '#5a6268']);
    add_settings_field('clear_btn_txt_hover_color', 'Κουμπί Καθαρισμού - Hover Κείμενο', 'vv_quiz_color_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'clear_btn_txt_hover_color', 'default' => '#FFFFFF']);

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
    add_settings_field('button_clear_cta', 'Κείμενο Κουμπιού (ΚΑΘΑΡΙΣΜΟΣ)', 'vv_quiz_text_field_callback', 'vapevida-quiz-details', 'vv_quiz_main_section', ['field_id' => 'button_clear_cta', 'default' => 'ΚΑΘΑΡΙΣΜΟΣ']);
}
add_action('admin_init', 'vv_quiz_register_settings');