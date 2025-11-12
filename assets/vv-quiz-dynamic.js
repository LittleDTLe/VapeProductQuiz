jQuery(document).ready(function ($) {
    const typeSelect = $("#flavor_type");
    const primaryIngredientSelect = $("#flavor_ingredient");
    const secondaryIngredientSelect = $("#flavor_ingredient_optional");

    const allIngredientSelects = [
        primaryIngredientSelect,
        secondaryIngredientSelect,
    ];
    const ctaButton = $(".vv-cta-button");

    // Get translated strings from localized object
    const i18n = vv_quiz_ajax.i18n || {};

    function updateIngredientDropdowns(selectedTypeSlug) {
        
        // --- 1. SET LOADING STATE ---
        ctaButton.prop("disabled", true);
        ctaButton.text(i18n.loading || "Αναζήτηση...");

        allIngredientSelects.forEach(function (select) {
            select.prop("disabled", true);
            select.html("<option>" + (i18n.loading_options || "Φόρτωση...") + "</option>");
        });

        // --- RESET LOGIC (Type is empty) ---
        if (!selectedTypeSlug) {
            const defaultCtaText = vv_quiz_ajax.cta_text_default || i18n.cta_default;
            ctaButton.text(defaultCtaText);
            ctaButton.prop("disabled", false);
            
            allIngredientSelects.forEach(function (select) {
                const placeholder =
                    select.attr("id") === "flavor_ingredient"
                        ? vv_quiz_ajax.placeholder_primary
                        : vv_quiz_ajax.placeholder_secondary;

                select.html('<option value="">' + placeholder + "</option>");
                select.prop("disabled", false);
            });
            return;
        }

        // --- 2. AJAX REQUEST ---
        $.ajax({
            url: vv_quiz_ajax.ajax_url,
            type: "POST",
            data: {
                action: "vv_filter_ingredients", 
                security: vv_quiz_ajax.nonce,
                type_slug: typeSelect.attr("name").replace('filter_', 'pa_'), 
                type_term_slug: selectedTypeSlug,
            },
            success: function (response) {
                const parts = response.split('|||');
                const count = parseInt(parts[0]) || 0;
                const htmlOptions = parts[1] || '';
                
                const defaultCtaText = vv_quiz_ajax.cta_text_default;

                // --- UPDATE RESULT PREVIEW (CTA BUTTON) ---
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

                // --- UPDATE CASCADING DROPDOWNS ---
                allIngredientSelects.forEach(function (select) {
                    const isPrimary = select.attr("id") === "flavor_ingredient";
                    const placeholderText = isPrimary
                        ? vv_quiz_ajax.placeholder_primary
                        : vv_quiz_ajax.placeholder_secondary;

                    const localPlaceholderOption =
                        '<option value="">' + placeholderText + "</option>";

                    select.html(localPlaceholderOption + htmlOptions);
                    select.prop("disabled", false);
                });
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);
                
                ctaButton.text(i18n.error_loading || "⚠️ Σφάλμα ΔΕΔΟΜΕΝΩΝ");
                ctaButton.prop("disabled", true);
                
                allIngredientSelects.forEach(function (select) {
                    select.html('<option value="">' + (i18n.error_loading_options || "Σφάλμα Φόρτωσης") + '</option>');
                    select.prop("disabled", false);
                });
            },
        });
    }

    // --- EVENT LISTENERS ---
    
    typeSelect.on("change", function () {
        const selectedType = $(this).val();
        updateIngredientDropdowns(selectedType);
    });
    
    $('.vv-clear-button').on('click', function() {
        const form = document.getElementById('vv-recommender-form');
        
        form.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
            $(select).trigger('change'); 
        });

        const formAction = form.getAttribute('action');
        const baseShopUrl = formAction.split('?')[0];
        window.location.href = baseShopUrl;
    });

    if (typeSelect.val()) {
        updateIngredientDropdowns(typeSelect.val());
    }
});