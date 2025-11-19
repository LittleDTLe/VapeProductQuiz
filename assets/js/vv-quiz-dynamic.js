jQuery(document).ready(function ($) {
    const form = $("#vv-recommender-form");
    
    const typeSelect = "#flavor_type";
    const primaryIngredientSelect = "#flavor_ingredient";
    const secondaryIngredientSelect = "#flavor_ingredient_optional";
    
    const ctaButton = $(".vv-cta-button");

    // Get localized data from vv_quiz_ajax
    const i18n = vv_quiz_ajax.i18n || {};
    const settings = vv_quiz_ajax;

    // Timer for hiding error messages
    let errorTimer;

    // Store TomSelect instances
    let tsType, tsPrimary, tsSecondary;

    // --- NEW: Create a shared config for TomSelect ---
    // --- NEW: Create a shared config for TomSelect ---
const tomSelectSettings = {
    allowEmptyOption: true,
    maxOptions: null, // Show all options (remove any limit)
    dropdownParent: 'body', // This helps with positioning/overflow issues
    render: {
        no_results: function(data, escape) {
            return '<div class="no-results">' + escape(i18n.search_no_results || 'No results found') + '</div>';
        },
        loading: function(data, escape) {
            return '<div class="loading">' + escape(i18n.search_loading || 'Loading...') + '</div>';
        }
    }
};


    /**
     * Main function to update ingredient dropdowns AND result count
     * @param {boolean} shouldUpdatePrimary - Reload the primary dropdown?
     * @param {boolean} shouldUpdateSecondary - Reload the secondary dropdown?
     */
    function updateIngredientsAndCount(shouldUpdatePrimary, shouldUpdateSecondary) {
        const selectedType = tsType.getValue();
        const selectedPrimary = tsPrimary.getValue();
        const selectedSecondary = tsSecondary.getValue();

        // --- SET LOADING STATE ---
        ctaButton.prop("disabled", true);
        ctaButton.text(i18n.loading || "Searching...");

        // Lock fields during update
        if (shouldUpdatePrimary) tsPrimary.disable();
        if (shouldUpdateSecondary) tsSecondary.disable();


        // --- RESET LOGIC (No Type selected) ---
        if (!selectedType) {
            const defaultCtaText = settings.cta_text_default || i18n.cta_default;
            ctaButton.text(defaultCtaText);
            validateForm(true); 

            // Reset and LOCK both ingredient dropdowns
            tsPrimary.clearOptions();
            tsPrimary.clear();
            tsPrimary.disable();
            
            tsSecondary.clearOptions();
            tsSecondary.clear();
            tsSecondary.disable();
            
            return;
        }

        // --- AJAX REQUEST WITH ALL FILTERS (NOW EXPECTS JSON) ---
        $.ajax({
            url: settings.ajax_url,
            type: "POST",
            dataType: "json", // Expect a JSON response
            data: {
                action: "vv_filter_ingredients",
                security: settings.nonce,
                type_slug: settings.type_slug,
                type_term_slug: selectedType,
                primary_ingredient: selectedPrimary || '',
                secondary_ingredient: selectedSecondary || '',
                ingredient_slug: settings.ingredient_slug
            },
            success: function (response) {
                console.log("VapeVida Quiz AJAX Response:", response);

                if (!response.success || typeof response.data === 'undefined') {
                    handleAjaxError();
                    return;
                }
                
                const data = response.data;
                const count = data.count || 0;

                // --- UPDATE RESULT PREVIEW (CTA BUTTON) ---
                updateButtonText(count);

                // --- UPDATE PRIMARY INGREDIENT DROPDOWN ---
                if (shouldUpdatePrimary) {
                    updateDropdown(
                        tsPrimary,
                        data.primaryOptions || [],
                        selectedPrimary
                    );
                }

                // --- UPDATE SECONDARY INGREDIENT DROPDOWN ---
                if (shouldUpdateSecondary) {
                    updateDropdown(
                        tsSecondary,
                        data.secondaryOptions || [],
                        selectedSecondary
                    );
                }

                // --- LOCKING LOGIC (RUNS EVERY TIME) ---
                // 1. Primary select is now populated and enabled (by updateDropdown).
                
                // 2. Check Secondary select:
                if (selectedPrimary) {
                    // Only enable if it just got new options
                    if (shouldUpdateSecondary) {
                        if (data.secondaryOptions && data.secondaryOptions.length > 0) {
                            tsSecondary.enable();
                        } else {
                            tsSecondary.disable();
                        }
                    } else {
                        // Otherwise, just ensure it's enabled
                        tsSecondary.enable();
                    }
                } else {
                    tsSecondary.disable();
                }
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);
                handleAjaxError();
            },
        });
    }

    /**
     * Helper for AJAX error state
     */
    function handleAjaxError() {
        ctaButton.text(i18n.error_loading || "⚠️ DATA ERROR");
        ctaButton.prop("disabled", true);
        tsPrimary.disable();
        tsSecondary.disable();
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
     * Update a single TomSelect dropdown with new options
     */
    function updateDropdown(tsInstance, options, currentValue) {
        tsInstance.clearOptions(); // Clear existing
        tsInstance.addOption(options); // Add new
        
        // Only enable if there are actual options to choose from
        if (options.length > 0) {
            tsInstance.enable();
        } else {
            tsInstance.disable();
        }

        // Preserve selection if it still exists in new options
        if (currentValue && tsInstance.getOption(currentValue)) {
            tsInstance.setValue(currentValue, true); // true = silent, no event
        } else {
            tsInstance.clear(true);
        }
    }

    /**
     * Clears all error messages and borders
     */
    function clearErrors() {
        clearTimeout(errorTimer); // Stop any pending fade-outs
        // Target TomSelect wrapper
        form.find('.ts-control.vv-field-error').removeClass('vv-field-error');
        
        form.find('.vv-field-error-message').fadeOut(200, function() {
            $(this).text('');
        });
    }

    /**
     * Displays an error for a specific field
     */
    function showError(fieldSelector, message) {
        const fieldElement = $(fieldSelector);
        
        // Target TomSelect wrapper
        fieldElement.parent().find('.ts-control').addClass('vv-field-error');
        
        const fieldID = fieldSelector.replace('#', '');
        const errorDiv = $('#error-for-' + fieldID);
        errorDiv.text(message).fadeIn(200);

        // Set a timer to hide this specific error
        errorTimer = setTimeout(function() {
            fieldElement.parent().find('.ts-control').removeClass('vv-field-error');
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
        if (settings.is_type_required && !tsType.getValue()) {
            isValid = false;
            if (!silent) {
                showError(typeSelect, i18n.error_required_type);
                if (!firstErrorField) firstErrorField = typeSelect;
            }
        }

        // 2. Check Primary Ingredient
        if (settings.is_primary_required && !tsPrimary.getValue()) {
            isValid = false;
            if (!silent) { 
                 showError(primaryIngredientSelect, i18n.error_required_primary);
                 if (!firstErrorField) firstErrorField = primaryIngredientSelect;
            }
        }

        // 3. Check Secondary Ingredient (only if it's visible and required)
        if (settings.is_secondary_required && !tsSecondary.getValue() && $(secondaryIngredientSelect).is(':visible')) {
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

    function onTypeChange(value) {
        clearErrors();
        // Update BOTH primary and secondary
        updateIngredientsAndCount(true, true); 
        validateForm(true);
    }

    function onPrimaryChange(value) {
        clearErrors();
        // Update ONLY secondary (and count)
        updateIngredientsAndCount(false, true); 
        validateForm(true);
    }
    
    function onSecondaryChange(value) {
        clearErrors();
        // Update ONLY the count
        updateIngredientsAndCount(false, false); 
        validateForm(true);
    }

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
        
        // Reset and disable selects using TomSelect API
        tsType.clear(); // This will trigger onTypeChange
        
        // The 'onTypeChange' event will call updateIngredientsAndCount(true, true),
        // which will automatically trigger the reset logic.

        // Reset button
        ctaButton.text(settings.cta_text_default).prop("disabled", false);

        // Re-validate silently to set button state (will disable if type is required)
        validateForm(true);
    });

    // --- INITIALIZATION ---
    
    // Initialize TomSelect for each field
    
    tsType = new TomSelect(typeSelect, {
        ...tomSelectSettings,
        placeholder: settings.placeholder_type, 
        onChange: onTypeChange,
        maxOptions: null
    });
    
    tsPrimary = new TomSelect(primaryIngredientSelect, {
        ...tomSelectSettings, 
        placeholder: settings.placeholder_primary,
        onChange: onPrimaryChange,
        maxOptions: null
    });

    tsSecondary = new TomSelect(secondaryIngredientSelect, {
        ...tomSelectSettings, 
        placeholder: settings.placeholder_secondary,
        onChange: onSecondaryChange,
        maxOptions: null
    });

    // Run initial reset to lock fields
    updateIngredientsAndCount(true, true);
    
    // On page load, run a silent validation to set the initial button state
    validateForm(true);
});