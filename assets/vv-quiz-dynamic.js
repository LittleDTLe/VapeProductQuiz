jQuery(document).ready(function ($) {
	const typeSelect = $("#flavor_type");
	const primaryIngredientSelect = $("#flavor_ingredient");
	const secondaryIngredientSelect = $("#flavor_ingredient_optional");

	// Function to handle the AJAX request and update ingredient dropdowns
	function updateIngredientDropdowns(selectedTypeSlug) {
		// 1. Disable and show loading state on ingredient dropdowns
		const allIngredientSelects = [
			primaryIngredientSelect,
			secondaryIngredientSelect,
		];

		allIngredientSelects.forEach(function (select) {
			select.prop("disabled", true);
			select.html("<option>Φόρτωση...</option>");
		});

		if (!selectedTypeSlug) {
			// Reset dropdowns if no type is selected
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

		// 2. Make the AJAX request
		$.ajax({
			url: vv_quiz_ajax.ajax_url,
			type: "POST",
			data: {
				action: "vv_filter_ingredients",
				type_slug: typeSelect.attr("name").replace("filter_", "pa_"), // Get the pa_ attribute slug
				type_term_slug: selectedTypeSlug,
				security: vv_quiz_ajax.nonce,
			},
			success: function (response) {
				// 1. Create a temporary HTML element to hold the options
				let $tempDiv = $("<div>").html(response);

				// 2. Client-Side Safety Filter: Remove any options that are empty or malformed
				let $cleanOptions = $tempDiv.find("option").filter(function () {
					const text = $(this).text().trim();
					const value = $(this).val();

					// **CRITICAL FIX:** We remove any option where the value is missing or the text is "undefined"
					// This ensures no option without a value="" is processed.
					return value !== "" && text.toLowerCase() !== "undefined";
				});

				// 3. Get the combined HTML string of the clean options
				const cleaned_html = $cleanOptions
					.map(function () {
						return this.outerHTML;
					})
					.get()
					.join("");

				// 4. Update the HTML in both ingredient dropdowns
				allIngredientSelects.forEach(function (select) {
					const placeholder =
						select.attr("id") === "flavor_ingredient"
							? vv_quiz_ajax.placeholder_primary
							: vv_quiz_ajax.placeholder_secondary;

					// CRITICAL: Insert the correct placeholder and the CLEANED HTML string
					select.html(
						'<option value="">' + placeholder + "</option>" + cleaned_html
					);
					select.prop("disabled", false);
				});
			},
			error: function (xhr, status, error) {
				console.error("AJAX Error: ", error);
				allIngredientSelects.forEach(function (select) {
					select.html('<option value="">Σφάλμα Φόρτωσης</option>');
					select.prop("disabled", false);
				});
			},
		});
	}

	// --- Event Listener ---
	// Listen for changes on the main Type dropdown
	typeSelect.on("change", function () {
		const selectedType = $(this).val();
		updateIngredientDropdowns(selectedType);
	});

	// Execute on load if a value is already present (e.g., after back button)
	if (typeSelect.val()) {
		updateIngredientDropdowns(typeSelect.val());
	}
});
