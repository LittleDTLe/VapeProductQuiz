jQuery(document).ready(function ($) {
    const typeSelect = $("#flavor_type");
    const primaryIngredientSelect = $("#flavor_ingredient");
    const secondaryIngredientSelect = $("#flavor_ingredient_optional");

    // Initialize select elements array and CTA button in the outer scope
    const allIngredientSelects = [
        primaryIngredientSelect,
        secondaryIngredientSelect,
    ];
    const ctaButton = $(".vv-cta-button");


    // Function to handle the AJAX request and update ingredient dropdowns
    function updateIngredientDropdowns(selectedTypeSlug) {
        
        // --- 1. SET LOADING STATE ---
        ctaButton.prop("disabled", true);
        ctaButton.text("Αναζήτηση...");

        allIngredientSelects.forEach(function (select) {
            select.prop("disabled", true);
            select.html("<option>Φόρτωση...</option>");
        });

        // --- RESET LOGIC (Type is empty) ---
        if (!selectedTypeSlug) {
            const defaultCtaText = typeof vv_quiz_ajax !== 'undefined' ? vv_quiz_ajax.cta_text_default : 'ΒΡΕΣ ΤΟ ΥΓΡΟ ΣΟΥ';
            ctaButton.text(defaultCtaText);
            ctaButton.prop("disabled", false);
            
            // Reset dropdowns to their placeholders
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
            // NOTE: We do NOT set dataType: 'json'. We expect a plain string.
            data: {
                action: "vv_filter_ingredients", 
                security: vv_quiz_ajax.nonce,
                // Pass attribute name as pa_slug and the term slug
                type_slug: typeSelect.attr("name").replace('filter_', 'pa_'), 
                type_term_slug: selectedTypeSlug,
            },
            success: function (response) {
                // --- CRITICAL FIX: Parse the Delimited String ---
                const parts = response.split('|||');
                const count = parseInt(parts[0]) || 0; // First part is the count
                const htmlOptions = parts[1] || '';    // Second part is the HTML options
                
                const defaultCtaText = vv_quiz_ajax.cta_text_default;

                // --- PART A: UPDATE RESULT PREVIEW (CTA BUTTON) ---
                let newCtaText = "";

                if (count === 0) {
                    newCtaText = "🛑 0 ΑΠΟΤΕΛΕΣΜΑΤΑ";
                    ctaButton.prop("disabled", true); 
                } else if (count === 1) {
                    newCtaText = "ΒΡΕΣ 1 ΠΡΟΪΟΝ";
                    ctaButton.prop("disabled", false);
                } else {
                    newCtaText = `ΒΡΕΘΗΚΑΝ ${count} ΠΡΟΪΟΝΤΑ`;
                    ctaButton.prop("disabled", false);
                }
                ctaButton.text(newCtaText || defaultCtaText); 

                // --- PART B: UPDATE CASCADING DROPDOWNS ---
                allIngredientSelects.forEach(function (select) {
                    // Retrieve the correct placeholder for the dropdown
                    const isPrimary = select.attr("id") === "flavor_ingredient";
                    const placeholderText = isPrimary
                        ? vv_quiz_ajax.placeholder_primary
                        : vv_quiz_ajax.placeholder_secondary;

                    // CRITICAL FIX: Insert the guaranteed placeholder + the raw HTML string
                    const localPlaceholderOption =
                        '<option value="">' + placeholderText + "</option>";

                    select.html(localPlaceholderOption + htmlOptions);
                    select.prop("disabled", false); // Re-enable the dropdown
                });
            },
            error: function (xhr, status, error) {
                console.error("AJAX Error: ", error);
                
                ctaButton.text("⚠️ Σφάλμα ΔΕΔΟΜΕΝΩΝ");
                ctaButton.prop("disabled", true);
                
                allIngredientSelects.forEach(function (select) {
                    select.html('<option value="">Σφάλμα Φόρτωσης</option>');
                    select.prop("disabled", false);
                });
            },
        });
    }

    // -------------------------------------------------------------
    // --- EVENT LISTENERS (Triggering AJAX and Reset) ---
    // -------------------------------------------------------------
    
    // 1. Listen for changes on the main Type dropdown (Triggers AJAX)
    typeSelect.on("change", function () {
        const selectedType = $(this).val();
        updateIngredientDropdowns(selectedType);
    });
    
    // 2. Clear Button Logic (vvClearQuizForm)
    $('.vv-clear-button').on('click', function() {
        const form = document.getElementById('vv-recommender-form');
        
        // Reset all select elements to their first option (empty value)
        form.querySelectorAll('select').forEach(select => {
            select.selectedIndex = 0;
            // Trigger the change event for cascading to reset the ingredients
            $(select).trigger('change'); 
        });

        // Redirect the user to the base shop URL to clear filters from the address bar
        const formAction = form.getAttribute('action');
        const baseShopUrl = formAction.split('?')[0];
        window.location.href = baseShopUrl;
    });

    // 3. Initialize on page load (If the user hits 'Back' button)
    if (typeSelect.val()) {
        updateIngredientDropdowns(typeSelect.val());
    }
});