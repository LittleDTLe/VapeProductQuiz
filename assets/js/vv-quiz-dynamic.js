jQuery(document).ready(function ($) {
    const form = $("#vv-recommender-form");
    const typeSelect = $("#flavor_type");
    const primaryIngredientSelect = $("#flavor_ingredient");
    const secondaryIngredientSelect = $("#flavor_ingredient_optional");
    const ctaButton = $(".vv-cta-button");

    // Get localized data from vv_quiz_ajax
    const i18n = vv_quiz_ajax.i18n || {};
    const settings = vv_quiz_ajax;

    // Timer for hiding error messages
    let errorTimer;

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
        ctaButton.text(i18n.loading || "Searching...");

        // --- RESET LOGIC (No Type selected) ---
        if (!selectedType) {
            const defaultCtaText = settings.cta_text_default || i18n.cta_default;
            ctaButton.text(defaultCtaText);
            
            // Check required fields to enable/disable button
            validateForm(true); // Run silent validation

            // Reset and LOCK both ingredient dropdowns
            primaryIngredientSelect.html('<option value="">' + settings.placeholder_primary + "</option>");
            primaryIngredientSelect.prop("disabled", true);
            
            secondaryIngredientSelect.html('<option value="">' + settings.placeholder_secondary + "</option>");
            secondaryIngredientSelect.prop("disabled", true);
            
            return;
        }

        // --- AJAX REQUEST WITH ALL FILTERS ---
        $.ajax({
            url: settings.ajax_url,
            type: "POST",
            data: {
                action: "vv_filter_ingredients",
                security: settings.nonce,
                type_slug: settings.type_slug, // Now passed from localized data
                type_term_slug: selectedType,
                primary_ingredient: selectedPrimary || '',
                secondary_ingredient: selectedSecondary || '',
                ingredient_slug: settings.ingredient_slug // Now passed from localized data
            },
            success: function (response) {
                // Parse the response: count|||primaryOptions|||secondaryOptions
                const parts = response.split('|||');
                
                // Handle potential errors from server
                if (parts.length < 3) {
                    console.error("AJAX response error: Unexpected format.", response);
                    ctaButton.text(i18n.error_loading || "⚠️ DATA ERROR");
                    ctaButton.prop("disabled", true);
                    primaryIngredientSelect.prop("disabled", true);
                    secondaryIngredientSelect.prop("disabled", true);
                    return;
                }

                const count = parseInt(parts[0]) || 0;
                const primaryOptions = parts[1] || '';
                const secondaryOptions = parts[2] || '';

                // --- UPDATE RESULT PREVIEW (CTA BUTTON) ---
                updateButtonText(count);

                // --- UPDATE PRIMARY INGREDIENT DROPDOWN ---
                updateDropdown(
                    primaryIngredientSelect,
                    primaryOptions,
                    settings.placeholder_primary,
                    selectedPrimary
                );

                // --- UPDATE SECONDARY INGREDIENT DROPDOWN ---
                updateDropdown(
                    secondaryIngredientSelect,
                    secondaryOptions,
                    settings.placeholder_secondary,
                    selectedSecondary
                );

                // --- NEW LOCKING LOGIC ---
                // After dropdowns are populated, re-apply locks based on state.
                
                // 1. Primary select is now populated and enabled (by updateDropdown).
                
                // 2. Check Secondary select:
                // If a Primary Ingredient IS selected, enable Secondary (if it has options).
                if (selectedPrimary) {
                    if(secondaryOptions.length > 0) {
                         secondaryIngredientSelect.prop("disabled", false);
                    } else {
                        // No compatible secondary options, keep it locked
                         secondaryIngredientSelect.prop("disabled", true);
                    }
                } else {
                    // If no Primary Ingredient is selected, LOCK Secondary.
                    secondaryIngredientSelect.prop("disabled", true);
                }

            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);
                ctaButton.text(i18n.error_loading || "⚠️ DATA ERROR");
                ctaButton.prop("disabled", true);
            },
        });
    }

    /**
     * Update button text based on product count
     */
    function updateButtonText(count) {
        const defaultCtaText = settings.cta_text_default;
        let newCtaText = "";

        if (count === 0) {
            newCtaText = i18n.no_results || "🛑 0 RESULTS";
            ctaButton.prop("disabled", true);
        } else if (count === 1) {
            newCtaText = i18n.one_result || "FIND 1 PRODUCT";
            ctaButton.prop("disabled", false);
        } else {
            newCtaText = (i18n.multiple_results || "FOUND {count} PRODUCTS").replace('{count}', count);
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
        
        // Only enable if there are actual options to choose from
        if (optionsHtml.length > 0) {
            selectElement.prop("disabled", false);
        } else {
            selectElement.prop("disabled", true);
        }

        // Preserve selection if it still exists in new options
        if (currentValue && selectElement.find('option[value="' + currentValue + '"]').length > 0) {
            selectElement.val(currentValue);
        } else {
            selectElement.val(''); // Reset if previous selection is no longer valid
        }
    }

    /**
     * Clears all error messages and borders
     */
    function clearErrors() {
        clearTimeout(errorTimer); // Stop any pending fade-outs
        form.find('.vv-field-error').removeClass('vv-field-error');
        form.find('.vv-field-error-message').fadeOut(200, function() {
            $(this).text('');
        });
    }

    /**
     * Displays an error for a specific field
     */
    function showError(field, message) {
        field.addClass('vv-field-error');
        const errorDiv = $('#error-for-' + field.attr('id'));
        errorDiv.text(message).fadeIn(200);

        // Set a timer to hide this specific error
        errorTimer = setTimeout(function() {
            field.removeClass('vv-field-error');
            errorDiv.fadeOut(300, function() {
                $(this).text('');
            });
        }, 3000); 
    }

    /**
     * Validates the form and returns true if valid
     * @param {boolean} silent - If true, won't show UI errors (used for button state)
     */
    function validateForm(silent = false) {
        clearErrors();
        let isValid = true;
        let firstErrorField = null; // To track the first error

        // 1. Check Type
        if (settings.is_type_required && !typeSelect.val()) {
            isValid = false;
            if (!silent) {
                showError(typeSelect, i18n.error_required_type);
                if (!firstErrorField) firstErrorField = typeSelect;
            }
        }

        // 2. Check Primary Ingredient
        if (settings.is_primary_required && !primaryIngredientSelect.val()) {
            isValid = false;

            if (!silent) { 
                 showError(primaryIngredientSelect, i18n.error_required_primary);
                 if (!firstErrorField) firstErrorField = primaryIngredientSelect;
            }
        }

        // 3. Check Secondary Ingredient (only if it's visible and required)
        if (settings.is_secondary_required && !secondaryIngredientSelect.val() && secondaryIngredientSelect.is(':visible')) {
            isValid = false;
            
            if (!silent) { 
                showError(secondaryIngredientSelect, i18n.error_required_secondary);
                if (!firstErrorField) firstErrorField = secondaryIngredientSelect;
            }
        }
        
        // Update button state based on validation (unless it's already showing results)
        if (isValid) {
             if (ctaButton.text() === (i18n.no_results || "🛑 0 RESULTS")) {
                ctaButton.prop("disabled", true);
             } else {
                ctaButton.prop("disabled", false);
             }
        } else {
            ctaButton.prop("disabled", true);
        }

        return isValid;
    }


    // --- EVENT LISTENERS ---

    // Use event delegation for change events
    form.on('change', 'select', function(e) {
        const selectId = $(this).attr('id');
        
        // Always clear errors on any change
        clearErrors();

        // If Type or Primary changes, run the full AJAX update
        if (selectId === 'flavor_type' || selectId === 'flavor_ingredient') {
            updateIngredientsAndCount();
        }
        
        // If Secondary changes, just update the count
        if (selectId === 'flavor_ingredient_optional') {
            updateIngredientsAndCount(); // This function now handles all logic
        }

        // Re-validate silently to update button state
        validateForm(true);
    });

    // Form submission validation
    form.on('submit', function (e) {
        const isValid = validateForm(false); // Run validation and SHOW errors
        
        if (!isValid) {
            e.preventDefault(); // Stop form submission
        }
        // If valid, let the form submit naturally
    });

    // Clear Button Logic
    $('.vv-clear-button').on('click', function () {
        clearErrors();
        
        // Reset and disable selects
        typeSelect.val('');
        primaryIngredientSelect.val('').html('<option value="">' + settings.placeholder_primary + "</option>").prop('disabled', true);
        secondaryIngredientSelect.val('').html('<option value="">' + settings.placeholder_secondary + "</option>").prop('disabled', true);

        // Reset button
        ctaButton.text(settings.cta_text_default).prop('disabled', false);

        // Re-validate silently to set button state (will disable if type is required)
        validateForm(true);
    });

    // --- INITIALIZATION ---
    
    // On page load, run a silent validation to set the initial button state
    validateForm(true);
});