# 📝 VapeVida Flavorshot Recommender Quiz Documentation

**Plugin Version:** 1.1.6

**Version Notes:** Modularization: Admin, Modularisation: Frontend, Dynamic Text Configuration, Dynamic Required Fields, Attribute Selection, Button Color Control, Responsive Admin Page, Easy Shortcode Copy, Dynamic Clear Button, Dynamic Custom Attribute Selectors, WooCommerce Active Checker, Uninstall Script, Modularisation of Admin File, Dynamic Cascading Filters, Real-Time Result Preview, Full Localization Support, Advanced Analytics Dashboard, Sales & Revenue Conversion Tracking, Search Combination Normalization, Stepped Form Logic, Search in Selects, Export to CSV, Find Quiz Link, Dashboard Widget.

**Author:** Panagiotis Drougas

---

## 🚀 Overview

The **VapeVida Flavorshot Recommender Quiz** is a custom WooCommerce utility designed to transform product discovery. It provides a simple, dynamic, and fully managed frontend form that guides customers to the exact e-liquids they desire.

This tool ensures maintainability by allowing store managers to control all aspects of the form and its content from the WordPress dashboard, minimizing the need for code changes.

---

## 🛠️ Key Features (Currently Implemented)

- **Analytics Dashboard:** A dedicated admin page (`VapeVida Quiz -> Analytics`) that visualizes user behavior. Features include:
  - Key metric cards for **Total Revenue**, **Total Sales**, **Conversion Rate**, and **Total Searches**.
  - Bar charts for "Top Popular Types" and "Top Popular Ingredients" to show what users search for most.
  - Top 10 tables for Types and Ingredients, showing _only_ converting items, sorted by the highest revenue.
  - A "Top 10 Products Sold by Quiz" table, populated from a dedicated tracking table.
  - Side-by-side tables comparing "Top Converting" (most profitable) search combinations vs. "Top Popular" (most searched) combinations.
- **Sales & Revenue Tracking:** Automatically links quiz usage to sales.
  - Logs a conversion when a user buys a product that matches their _last quiz search_.
  - Accurately tracks the **subtotal** of _only the matched items_, not the entire cart.
  - Handles variable products by checking the parent product for attribute terms.
  - Tracks sales anonymously using a persistent `user_id_hash`.
  - Saves matched items, quantities, and subtotals to a dedicated `wp_vv_quiz_conversion_items` database table.
- **Search Combination Normalization:** Ensures that search combinations are tracked accurately regardless of the order ingredients are selected. For example, `(Type: X, Ing1: Apple, Ing2: Banana)` and `(Type: X, Ing1: Banana, Ing2: Apple)` are now logged and grouped as the same identical search, providing cleaner and more accurate analytics.
- **Dynamic Cascading Filters:** Dropdowns update dynamically based on the prior selection, ensuring customers are never led to a 'zero results' page.
- **Real-Time Result Preview:** The CTA button displays the number of matching products immediately after filter selection, enhancing user experience.
- **Full Localization Support:** The plugin is fully prepared for translation using `.pot`, `.po`, and `.mo` files, supporting locales like `el_GR` and generic `el`.
- **Dynamic Filtering (AND Logic):** The system ensures that products must match ALL selections (Flavor Type AND Ingredients) to appear in the filtered results.
- **Dynamic Content Control:** All user-facing text (Headings, Labels, Placeholders, and Button Text) is editable via the Admin Settings.
- **Button Color Control:** The background and text colors for the CTA button in both Idle and Hover states are configurable via color swatches.
- **Conditional Visibility:** The third dropdown (Secondary Ingredient) can be instantly toggled ON or OFF from the settings page.
- **Required Field Control:** The admin can set which fields (Type, Primary, Secondary) are mandatory for form submission.
- **Attribute Remapping:** The admin can select which Global Attributes (`pa\_...`) will fill the dropdown fields directly from the dashboard settings.
- **Auto-Populated Options:** Dropdown fields are populated automatically from your WooCommerce Global Attribute Terms.
- **Dynamic Clear Button:** A Reset Button is included to easily clear selections and URL filters.
- **Custom Error Messages:** Add custom error messages using jQuery intercepting the form submission.
- **Updated languages/ Files:** Update languages/ files to include additions made after introduction of localisation.
- **Stepped Form Logic:** Add Step Logic to the form select tags.
- **Search / Type in Selects:** Add search functionality to the dropdowns to easily find the term you are looking for.
- **Export Analytics:** Export Analytics to CSV file.
- **Live Preview Button:** Adds a direct button to immediately view this quiz instance on the frontend of your site, allowing for quick testing and verification of settings and attributes.
- **Dashboard Widget:** Add a Dashboard Analytics Widget with Last 7 Days filter.

---

## Working On

- [x] **Localisation Update:** Update Localisation Files

---

## 💡 Future Development & Features

This section outlines planned features that would require further development (potentially a premium version).

### Feature Ideas (Refactoring Required)

To implement the below features / ideas, there would need to be a complete refactoring of the plugin (e.g., transitioning to a Custom Post Type architecture).

- [ ] **Multi Quiz Support** Add Multiple Quiz Support to the plugin.
- [ ] **Category Page Quiz** Add Dynamic Quizes to Category pages with Ajax Filtering Capabilities.

---

## 1. Installation and Required Setup

### 1.1 Installation

1.  Place all plugin files (`vapevida-quiz.php`, `includes/`, etc.) into a folder named `vapevida-quiz` within your WordPress site's plugins directory (`wp-content/plugins/`).
2.  In your WordPress Dashboard, navigate to **Plugins** and **Activate** the "VapeVida Flavorshot Recommender Quiz".
3.  **If updating:** If you are updating to version 1.0.0 (with analytics), **Deactivate** and **Reactivate** the plugin once to force the new analytics database tables (`wp_vv_quiz_analytics` and `wp_vv_quiz_conversion_items`) to be created.

### 1.2 Required Data Mapping (Crucial)

The plugin requires two specific **Global Attributes** to be active and populated with your product data:

| Filter Purpose                 | Global Attribute Slug | WooCommerce Name | Data Responsibility                                                               |
| :----------------------------- | :-------------------- | :--------------- | :-------------------------------------------------------------------------------- |
| **Flavor Type (Field 1)**      | `pa_geuseis`          | Τύπος Γεύσης     | Used for broad selection (e.g., _Sweet_, _Tobacco_, _Fruity_).                    |
| **Ingredients (Fields 2 & 3)** | `pa_quiz-ingredient`  | Συστατικό (Quiz) | Must contain all individual ingredients (e.g., _Strawberry_, _Cream_, _Vanilla_). |

---

## 2. Admin Configuration and Management

### 2.1 Accessing the Admin Pages

The plugin creates a top-level **VapeVida Quiz** menu in the admin sidebar. This menu contains two sub-pages:

- **Settings:** This is where you configure the quiz's appearance, text, and filtering behavior.
- **Analytics:** A dashboard displaying user search data and sales conversions.

### 2.2 Settings Page: Form Structure and Control

Όλες οι δυναμικές ρυθμίσεις και οι προσαρμογές του κειμένου γίνονται μέσω των παρακάτω ενοτήτων στη σελίδα **VapeVida Quiz -> Settings**.

| Ομάδα Ρυθμίσεων                            | Ενεργά Πεδία / Παράμετροι                                                           | Σκοπός και Αποτέλεσμα                                                                                                                        |
| :----------------------------------------- | :---------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------- |
| **Απαιτούμενα Πεδία**                      | Checkboxes (Type, Primary, Secondary)                                               | Ελέγχει τη **συμπεριφορά επικύρωσης (validation)** της φόρμας. Ορίζει ποια πεδία ο πελάτης πρέπει να συμπληρώσει για να υποβάλει το Quiz.    |
| **Επιλογή Attributes**                     | **Attribute για Τύπο (Field 1)**, **Attribute για Συστατικό (Fields 2/3)**          | Ορίζει **δυναμικά** τους Global Attribute Slugs (`pa_...`) που θα χρησιμοποιηθούν για τη λήψη των Όρων (Terms) και την εκτέλεση του φίλτρου. |
| **Ετικέτες Φόρμας**                        | **Label 1, Label 2, Label 3**                                                       | Αλλάζει το εμφανιζόμενο κείμενο μπροστά από κάθε dropdown.                                                                                   |
| **Πλαίσια Κειμένου (Placeholders)**        | **Placeholder 1, Placeholder Primary, Placeholder Secondary**                       | Ορίζει το κείμενο που εμφανίζεται μέσα στα dropdowns πριν γίνει η επιλογή.                                                                   |
| **Ετικέτες Σφαλμάτων Υποχρεωτικών Πεδίων** | **Error: Type Rquired, Error: Primary Ingredient, Error: Secondary Ingredient**     | Ορίζονται **δυναμικά** τα μηνύματα σφαλμάτων για τα υποχρεωτικά πεδία.                                                                       |
| **Κεφαλίδες Φόρμας**                       | **Τίτλος Quiz (H2)**, **Υπότιτλος Quiz (P)**                                        | Ελέγχει τα κύρια μηνύματα marketing της φόρμας.                                                                                              |
| **Συμπεριφορά & Χρώματα**                  | **Ενεργοποίηση 3ου Πεδίου**, **Κείμενο Κουμπιού (CTA)**, **Χρώμα Background/Hover** | Ελέγχει τη συνολική δομή (εάν εμφανίζεται το 3ο πεδίο) και το πλήρες branding των κουμπιών.                                                  |

### 2.3 Analytics Dashboard

The **Analytics** page (`VapeVida Quiz -> Analytics`) provides a comprehensive overview of how your customers are using the quiz and what it's selling.

#### Key Metric Cards

- **Total Revenue from Quiz:** The total revenue generated _only_ from products that matched a user's quiz search.
- **Total Sales from Quiz:** The total number of individual converted searches.
- **Conversion Rate:** The percentage of searches that led to a sale.
- **Total Searches:** The total number of times the quiz filters were applied.
- **Complete Searches:** Number of searches where at least the Type and Primary Ingredient were selected (a key engagement metric).

#### Visualizations

- **Top Popular Charts:** Bar charts showing the most _popular_ (most searched) Flavor Types and Primary Ingredients, giving you insight into what customers are looking for.

#### Data Tables

- **Top 10 by Revenue:** Tables for "Flavor Types" and "Primary Ingredients" that show _only_ items with sales, sorted by the highest revenue first.
- **Top 10 Products Sold by Quiz:** A dedicated table showing the _exact_ products sold via the quiz, their total quantity, and the revenue they generated.
- **Top Converting Combinations:** A list of the most profitable search combinations (e.g., "Sweets + Vanilla"), showing only searches that led to a sale and sorted by revenue.
- **Top Popular Combinations:** A list of the most _frequently_ searched combinations, allowing you to compare popularity vs. profitability.

---

### 2.4 Frontend Functionality Upgrades

The quiz fields have been upgraded to use the Tom Select library, significantly enhancing the customer experience.

#### Searchable Fields

- **Modern UX:** Fields now include live search functionality, allowing users to **type the name of the flavor or ingredient** they are looking for instead of scrolling through long lists.
- **Performance:** This improves navigation speed and makes the quiz much faster to complete, especially on sites with a large number of flavor attributes.

#### CSV Data Export

A dedicated button has been added to the **Analytics** page to facilitate external data analysis.

- **Export Location:** The **Export to CSV** button is located next to the Date Range filter on the Analytics page.
- **Comprehensive Download:** When clicked, the button exports all visible data tables (Combinations, Top Products, Revenue by Type, etc.) into a single, structured CSV file.
- **Date Range Respect:** The export respects the date range currently selected on the Analytics page filter.

---

### 2.5 Admin Convenience

#### Live Preview Button

The **Live Preview** button provides a zero-click way for administrators to view the quiz on the frontend.

- **Self-Registration Logic:** The plugin automatically detects and stores the URL of the page where the shortcode `[vapevida_quiz]` is placed when the page is first visited.
- **Instant Access:** The button in the Admin Settings links directly to this detected URL, ensuring the preview link is always accurate, even if the page slug changes.

---

## 3. Maintenance Guide: Adding New Flavors

The system automatically manages the options list. Follow these steps to introduce a new flavor (e.g., "Kiwi") to the Quiz dropdowns:

1.  **Access Attributes:** Navigate to **Προϊόντα → Χαρακτηριστικά**.
2.  **Add New Term:** Find the Attribute **Συστατικό (Quiz)** (`pa_quiz-ingredient`). Click **"Ρύθμιση όρων"** (Configure Terms) and add the new flavor name (e.g., 'Kiwi') and its slug (e.g., 'kiwi').
3.  **Link to Product:** Open the product you are selling (the Kiwi e-liquid). In the **Attributes** tab, ensure you assign the new 'Kiwi' term under the **Συστατικό (Quiz)** attribute.
4.  **Auto-Update:** The new 'Kiwi' option will now automatically appear in the Quiz dropdowns on your homepage because it is associated with a product.

---

## 4. Troubleshooting Localization

If your translations (like Greek) stop working after an update, ensure you have both the generic and region-specific `.mo` files in your `/languages` folder:

- `vapevida-quiz-el.mo` (Generic Greek)
- `vapevida-quiz-el_GR.mo` (Greek, Greece)

---

## 5. Frontend Usage

To display the fully configured quiz form on your homepage or any other page, use the shortcode:

```markdown
[vapevida_quiz]
```
