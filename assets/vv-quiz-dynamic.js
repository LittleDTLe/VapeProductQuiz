jQuery(document).ready(function ($) {
    const typeSelect = $("#flavor_type");
    const primaryIngredientSelect = $("#flavor_ingredient");
    const secondaryIngredientSelect = $("#flavor_ingredient_optional");
    const ctaButton = $(".vv-cta-button");

    // Get translated strings from localized object
    const i18n = vv_quiz_ajax.i18n || {};

    /**
     * Main function to update ingredient dropdowns AND result count
     * Now considers ALL selected filters
     */
    function updateIngredientsAndCount() {
        const selectedType = typeSelect.val();
        const selectedPrimary = primaryIngredientSelect.val();
        const selectedSecondary = secondaryIngredientSelect.val();

        // --- SET LOADING STATE ---
        ctaButton.prop("disabled", true);
        ctaButton.text(i18n.loading || "Αναζήτηση...");

        // --- RESET LOGIC (No Type selected) ---
        if (!selectedType) {
            const defaultCtaText = vv_quiz_ajax.cta_text_default || i18n.cta_default;
            ctaButton.text(defaultCtaText);
            ctaButton.prop("disabled", false);
            
            // Reset both ingredient dropdowns
            primaryIngredientSelect.html('<option value="">' + vv_quiz_ajax.placeholder_primary + "</option>");
            primaryIngredientSelect.prop("disabled", false);
            
            secondaryIngredientSelect.html('<option value="">' + vv_quiz_ajax.placeholder_secondary + "</option>");
            secondaryIngredientSelect.prop("disabled", false);
            
            return;
        }

        // --- AJAX REQUEST WITH ALL FILTERS ---
        $.ajax({
            url: vv_quiz_ajax.ajax_url,
            type: "POST",
            data: {
                action: "vv_filter_ingredients", 
                security: vv_quiz_ajax.nonce,
                type_slug: typeSelect.attr("name").replace('filter_', 'pa_'), 
                type_term_slug: selectedType,
                primary_ingredient: selectedPrimary || '',
                secondary_ingredient: selectedSecondary || ''
            },
            success: function (response) {
                // Parse the response: count|||primaryOptions|||secondaryOptions
                const parts = response.split('|||');
                const count = parseInt(parts[0]) || 0;
                const primaryOptions = parts[1] || '';
                const secondaryOptions = parts[2] || '';
                
                // --- UPDATE RESULT PREVIEW (CTA BUTTON) ---
                updateButtonText(count);

                // --- UPDATE PRIMARY INGREDIENT DROPDOWN ---
                updateDropdown(
                    primaryIngredientSelect, 
                    primaryOptions, 
                    vv_quiz_ajax.placeholder_primary,
                    selectedPrimary
                );

                // --- UPDATE SECONDARY INGREDIENT DROPDOWN ---
                updateDropdown(
                    secondaryIngredientSelect, 
                    secondaryOptions, 
                    vv_quiz_ajax.placeholder_secondary,
                    selectedSecondary
                );
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);
                
                ctaButton.text(i18n.error_loading || "⚠️ Σφάλμα ΔΕΔΟΜΕΝΩΝ");
                ctaButton.prop("disabled", true);
                
                primaryIngredientSelect.html('<option value="">' + (i18n.error_loading_options || "Σφάλμα Φόρτωσης") + '</option>');
                primaryIngredientSelect.prop("disabled", false);
                
                secondaryIngredientSelect.html('<option value="">' + (i18n.error_loading_options || "Σφάλμα Φόρτωσης") + '</option>');
                secondaryIngredientSelect.prop("disabled", false);
            },
        });
    }

    /**
     * Update button text based on product count
     */
    function updateButtonText(count) {
        const defaultCtaText = vv_quiz_ajax.cta_text_default;
        let newCtaText = "";

        if (count === 0) {
            newCtaText = i18n.no_results || "🛑 0 ΑΠΟΤΕΛΕΣΜΑΤΑ";
            ctaButton.prop("disabled", true); 
        } else if (count === 1) {
            newCtaText = i18n.one_result || "ΒΡΕΣ 1 ΠΡΟΪΟΝ";
            ctaButton.prop("disabled", false);
        } else {
            newCtaText = (i18n.multiple_results || "ΒΡΕΘΗΚΑΝ {count} ΠΡΟΪΟΝΤΑ").replace('{count}', count);
            ctaButton.prop("disabled", false);
        }
        
        ctaButton.text(newCtaText || defaultCtaText);
    }

    /**
     * Update a single dropdown with new options, preserving selection if valid
     */
    function updateDropdown(selectElement, optionsHtml, placeholderText, currentValue) {
        const placeholderOption = '<option value="">' + placeholderText + "</option>";
        selectElement.html(placeholderOption + optionsHtml);
        selectElement.prop("disabled", false);
        
        // Preserve selection if it still exists in new options
        if (currentValue && selectElement.find('option[value="' + currentValue + '"]').length > 0) {
            selectElement.val(currentValue);
        } else {
            selectElement.val(''); // Reset if previous selection is no longer valid
        }
    }

    // --- EVENT LISTENERS ---
    
    // Type dropdown changes: Update everything
    typeSelect.on("change", function () {
        updateIngredientsAndCount();
    });
    
    // Primary ingredient changes: Update secondary dropdown and count
    primaryIngredientSelect.on("change", function () {
        updateIngredientsAndCount();
    });
    
    // Secondary ingredient changes: Update count only
    secondaryIngredientSelect.on("change", function () {
        updateIngredientsAndCount();
    });
    
    // Clear Button Logic
    $('.vv-clear-button').on('click', function() {
        const form = document.getElementById('vv-recommender-form');
        
        form.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
        });
        
        // Trigger change on type to reset everything
        $(typeSelect).trigger('change');

        // Redirect to clear URL filters
        const formAction = form.getAttribute('action');
        const baseShopUrl = formAction.split('?')[0];
        window.location.href = baseShopUrl;
    });

    // Initialize on page load (if user hits 'Back' button)
    if (typeSelect.val()) {
        updateIngredientsAndCount();
    }
});