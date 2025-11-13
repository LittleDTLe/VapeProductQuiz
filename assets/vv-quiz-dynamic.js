jQuery(document).ready(function ($) {
    const form = $("#vv-recommender-form");
    if (form.length === 0) {
        return; // Exit if form isn't on the page
    }

    // --- CACHE SELECTORS ---
    const typeSelect = $("#flavor_type");
    const primaryIngredientSelect = $("#flavor_ingredient");
    const secondaryIngredientSelect = $("#flavor_ingredient_optional");
    const ctaButton = $(".vv-cta-button");
    
    // Get localized data
    const i18n = vv_quiz_ajax.i18n || {};
    const ajax_url = vv_quiz_ajax.ajax_url;
    const nonce = vv_quiz_ajax.nonce;

    /**
     * ===================================================================
     * AJAX FUNCTION: Update dropdowns and count
     * ===================================================================
     */
    function updateIngredientsAndCount() {
        const selectedType = typeSelect.val();
        const selectedPrimary = primaryIngredientSelect.val();
        const selectedSecondary = secondaryIngredientSelect.val();

        // --- 1. SET LOADING STATE ---
        ctaButton.prop("disabled", true);
        ctaButton.text(i18n.loading || "Searching...");
        
        // NEW: Clear inline errors on change
        form.find('.vv-field-error-message').hide().empty();
        form.find('.vv-field-error').removeClass('vv-field-error');
        // REMOVED: Toast clearing logic

        // --- 2. RESET LOGIC (No Type selected) ---
        if (!selectedType) {
            const defaultCtaText = vv_quiz_ajax.cta_text_default || i18n.cta_default;
            ctaButton.text(defaultCtaText);
            ctaButton.prop("disabled", false);

            primaryIngredientSelect.html('<option value="">' + vv_quiz_ajax.placeholder_primary + "</option>").prop("disabled", false);
            secondaryIngredientSelect.html('<option value="">' + vv_quiz_ajax.placeholder_secondary + "</option>").prop("disabled", false);
            return;
        }

        // --- 3. AJAX REQUEST ---
        $.ajax({
            url: ajax_url,
            type: "POST",
            data: {
                action: "vv_filter_ingredients",
                security: nonce,
                // ==========================================================
                // THE FIX: Reverted from '' to 'pa_' to send the correct
                // taxonomy slug (e.g., 'filter_geuseis' -> 'pa_geuseis')
                // ==========================================================
                type_slug: typeSelect.attr("name").replace('filter_', 'pa_'),
                type_term_slug: selectedType,
                primary_ingredient: selectedPrimary || '',
                secondary_ingredient: selectedSecondary || ''
            },
            success: function (response) {
                if (!response || response.indexOf('|||') === -1) {
                    let errorMsg = i18n.error_loading || "⚠️ DATA ERROR";
                    if (response.includes("Nonce verification failed")) {
                        errorMsg = "Security check failed. Please refresh the page.";
                    }
                    ctaButton.text(errorMsg).prop("disabled", true);
                    return;
                }

                const parts = response.split('|||');
                const count = parseInt(parts[0]) || 0;
                const primaryOptions = parts[1] || '';
                const secondaryOptions = parts[2] || '';

                updateButtonText(count);
                updateDropdown(primaryIngredientSelect, primaryOptions, vv_quiz_ajax.placeholder_primary, selectedPrimary);
                updateDropdown(secondaryIngredientSelect, secondaryOptions, vv_quiz_ajax.placeholder_secondary, selectedSecondary);
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", status, error);
                ctaButton.text(i18n.error_loading || "⚠️ DATA ERROR").prop("disabled", true);
                primaryIngredientSelect.html('<option value="">' + (i18n.error_loading_options || "Loading Error") + '</option>');
                secondaryIngredientSelect.html('<option value="">' + (i18n.error_loading_options || "Loading Error") + '</option>');
            },
        });
    }

    /**
     * ===================================================================
     * HELPER: Update CTA Button Text
     * ===================================================================
     */
    function updateButtonText(count) {
        const defaultCtaText = vv_quiz_ajax.cta_text_default;
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
     * ===================================================================
     * HELPER: Update Dropdown HTML
     * ===================================================================
     */
    function updateDropdown(selectElement, optionsHtml, placeholderText, currentValue) {
        const placeholderOption = '<option value="">' + placeholderText + "</option>";
        selectElement.html(placeholderOption + optionsHtml).prop("disabled", false);

        if (currentValue && selectElement.find('option[value="' + currentValue + '"]').length > 0) {
            selectElement.val(currentValue);
        } else {
            selectElement.val('');
        }
    }

    /**
     * ===================================================================
     * EVENT LISTENER: Form Submission (Validation)
     * ===================================================================
     */
    form.on("submit", function (event) {
        let errors = [];

        // Clear previous errors
        form.find('.vv-field-error-message').hide().empty(); // Clear inline errors
        form.find('.vv-field-error').removeClass('vv-field-error');

        // Check Type
        if (vv_quiz_ajax.is_type_required && !typeSelect.val()) {
            errors.push(i18n.error_required_type || 'Please select a Flavor Type.');
            typeSelect.addClass('vv-field-error');
            // REMOVED: if (!firstErrorField) firstErrorField = typeSelect;
            showInlineError("#error-for-flavor_type", i18n.error_required_type);
        }
        // Check Primary Ingredient
        if (vv_quiz_ajax.is_primary_required && !primaryIngredientSelect.val()) {
            errors.push(i18n.error_required_primary || 'Please select a Primary Ingredient.');
            primaryIngredientSelect.addClass('vv-field-error');
            // REMOVED: if (!firstErrorField) firstErrorField = primaryIngredientSelect;
            showInlineError("#error-for-flavor_ingredient", i18n.error_required_primary);
        }
        // Check Secondary Ingredient (only if it's required *and* visible)
        if (vv_quiz_ajax.is_secondary_required && !secondaryIngredientSelect.val()) {
            errors.push(i18n.error_required_secondary || 'Please select a Secondary Ingredient.');
            secondaryIngredientSelect.addClass('vv-field-error');
            // REMOVED: if (!firstErrorField) firstErrorField = secondaryIngredientSelect;
            showInlineError("#error-for-flavor_ingredient_optional", i18n.error_required_secondary);
        }

        // If there are errors, stop submission
        if (errors.length > 0) {
            event.preventDefault(); // Stop the form from submitting
            
            // ==========================================================
            // === THE FIX ===
            // REMOVED: All 'firstErrorField' and 'animate' logic
            // ==========================================================
        }
        // If no errors, the form submits as normal.
    });

    /**
     * ===================================================================
     * NEW HELPER: Show Inline Error with Timeout
     * ===================================================================
     */
    function showInlineError(selector, message) {
        const errorElement = $(selector);
        if (errorElement.length) {
            errorElement.text(message).show();
            
            // Set timer to hide the error
            setTimeout(function() {
                errorElement.fadeOut();
            }, 3000); // 3 seconds (3000ms)
        }
    }

    /**
     * ===================================================================
     * EVENT LISTENER: Dropdown Changes (AJAX)
     * ===================================================================
     */
    form.on("change", "select", function() {
        // Clear field-specific error highlights when user makes a selection
        if ($(this).val()) {
            $(this).removeClass('vv-field-error');
            // NEW: Hide specific inline error
            $("#error-for-" + $(this).attr('id')).hide();
        }
        updateIngredientsAndCount();
    });

    /**
     * ===================================================================
     * EVENT LISTENER: Clear Button
     * ===================================================================
     */
    $('.vv-clear-button').on('click', function () {
        form.find('select').each(function() {
            $(this).val('').removeClass('vv-field-error');
        });
        
        // NEW: Hide all inline errors
        form.find('.vv-field-error-message').hide().empty();
        // REMOVED: Toast clearing logic

        typeSelect.trigger('change');
    });

    /**
     * ===================================================================
     * INITIALIZATION: Run on Page Load
     * ===================================================================
     */
    if (typeSelect.val()) {
        updateIngredientsAndCount();
    } else {
        ctaButton.text(vv_quiz_ajax.cta_text_default).prop("disabled", false);
    }
});