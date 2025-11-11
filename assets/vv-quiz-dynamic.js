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
				// 1. Create a jQuery object from the raw HTML response string
				let $options = $("<div>").html(response).find("option");

				// 2. Client-Side Safety Filter: Remove malformed options
				// This filter keeps only options that have a value (i.e., not a malformed placeholder)
				$options = $options.filter(function () {
					const text = $(this).text().trim();
					const value = $(this).val();

					// We only keep the option if it has a non-empty value (i.e., it's a real term, not an empty placeholder)
					return value !== "";
				});

				// 3. Convert the filtered jQuery object back to a complete HTML string
				const cleaned_html = $options
					.map(function () {
						return this.outerHTML;
					})
					.get()
					.join("");

				// 4. Update the HTML in both ingredient dropdowns
				allIngredientSelects.forEach(function (select) {
					// --- CREATE THE PLACEHOLDER LOCALLY AND FORCE IT FIRST ---
					const isPrimary = select.attr("id") === "flavor_ingredient";
					const placeholderText = isPrimary
						? vv_quiz_ajax.placeholder_primary
						: vv_quiz_ajax.placeholder_secondary;

					const localPlaceholder =
						'<option value="">' + placeholderText + "</option>";

					// CRITICAL FIX: Insert the guaranteed, locally-created placeholder, followed by the cleaned terms.
					select.html(localPlaceholder + cleaned_html);
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
