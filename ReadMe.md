# 📝 VapeVida Flavorshot Recommender Quiz Documentation

**Plugin Version:** 0.8.9

**Version Notes:** Modularization of Plugin, Dynamic Text Configuration, Dynamic Required Fields, Attribute Selection, Button Color Control, Responsive Admin Page, Easy Shortcode Copy.

**Author:** Panagiotis Drougas

---

## 🚀 Overview

The **VapeVida Flavorshot Recommender Quiz** is a custom WooCommerce utility designed to transform product discovery. It provides a simple, dynamic, and fully managed frontend form that guides customers to the exact e-liquids they desire.

This tool ensures **maintainability** by allowing store managers to control all aspects of the form and its content from the WordPress dashboard, minimizing the need for code changes.

---

## 🛠️ Key Features (Currently Implemented)

- **Dynamic Filtering (AND Logic):** The system ensures that products must match **ALL** selections (Flavor Type AND Ingredients) to appear in the filtered results.
- **Dynamic Content Control:** All user-facing text (Headings, Labels, Placeholders, and Button Text) is editable via the Admin Settings.
- **Button Color Control:** The background and text colors for the CTA button in both **Idle** and **Hover** states are configurable via color swatches.
- **Conditional Visibility:** The third dropdown (Secondary Ingredient) can be instantly toggled **ON** or **OFF** from the settings page.
- **Admin Dashboard Responsiveness:** The settings page automatically adjusts its layout for mobile, tablet, and desktop screens.
- **Required Field Control:** The admin can set which fields (Type, Primary, Secondary) are mandatory for form submission.
- **Attribute Remapping:** The admin can select which Global Attributes (`pa_...`) will fill the dropdown fields directly from the dashboard settings.
- **Auto-Populated Options:** Dropdown fields are populated automatically from your WooCommerce Global Attribute Terms.
- **Reset Button:** Add a Reset Button to easily clear selections.

---

## 💡 Future Development & Features

This section outlines planned features that would require further development (potentially a premium version).

### To Implement Features (Planned for Next Releases)

- [ ] **Dynamic Cascading Filters:** Add Ajax Dropdown Filter logic to the quiz, so that there are no Zero Result Pages.
- [ ] **Result Preview:** Combined with the Dynamic CTA, the user can see how many results there are to their search.
- [ ] **Analytics & Tracking:** Create a Hook (before rediraction), to track user behavior and overall usage.
- [ ] **Default Attribute Toggle:** Add a checkbox to easily toggle between using default attributes or custom.
- [ ] **Custom Error Messages:** Add custom error messages using jQuery intercepting the form submission.
- [ ] **Search / Type in Selects:** Add search functionality to the dropdowns to easily find the term you are looking for.
- [ ] **Live Preview Button:** Adds a direct button to immediately view this quiz instance on the frontend of your site, allowing for quick testing and verification of settings and attributes.

### Feature Ideas (Refactoring Required)

To implement the below features / ideas, there would need to be a complete refactoring of the plugin (e.g., transitioning to a Custom Post Type architecture).

- [ ] **Multi Quiz Support** Add Multiple Quiz Support to the plugin.
- [ ] **Category Page Quiz** Add Dynamic Quizes to Category pages with Ajax Filtering Capabilities.

---

## 1. Installation and Required Setup

### 1.1 Installation

1.  Place all plugin files (`vapevida-quiz.php`, `includes/`, etc.) into a folder named `vapevida-quiz` within your WordPress site's plugins directory (`wp-content/plugins/`).
2.  In your WordPress Dashboard, navigate to **Plugins** and **Activate** the "VapeVida Flavorshot Recommender Quiz".

### 1.2 Required Data Mapping (Crucial)

The plugin requires two specific **Global Attributes** to be active and populated with your product data:

| Filter Purpose                 | Global Attribute Slug | WooCommerce Name | Data Responsibility                                                               |
| :----------------------------- | :-------------------- | :--------------- | :-------------------------------------------------------------------------------- |
| **Flavor Type (Field 1)**      | `pa_geuseis`          | Τύπος Γεύσης     | Used for broad selection (e.g., _Sweet_, _Tobacco_, _Fruity_).                    |
| **Ingredients (Fields 2 & 3)** | `pa_quiz-ingredient`  | Συστατικό (Quiz) | Must contain all individual ingredients (e.g., _Strawberry_, _Cream_, _Vanilla_). |

---

## 2. Admin Configuration and Management

### 2.1 Accessing the Settings

Navigate to the main sidebar and click on the **VapeVida Quiz** menu item.

### 2.2 Form Structure and Control

Όλες οι δυναμικές ρυθμίσεις και οι προσαρμογές του κειμένου γίνονται μέσω των παρακάτω ενοτήτων στη σελίδα **VapeVida Quiz** (Admin).

| Ομάδα Ρυθμίσεων                     | Ενεργά Πεδία / Παράμετροι                                                           | Σκοπός και Αποτέλεσμα                                                                                                                        |
| :---------------------------------- | :---------------------------------------------------------------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------- |
| **Απαιτούμενα Πεδία**               | Checkboxes (Type, Primary, Secondary)                                               | Ελέγχει τη **συμπεριφορά επικύρωσης (validation)** της φόρμας. Ορίζει ποια πεδία ο πελάτης πρέπει να συμπληρώσει για να υποβάλει το Quiz.    |
| **Επιλογή Attributes**              | **Attribute για Τύπο (Field 1)**, **Attribute για Συστατικό (Fields 2/3)**          | Ορίζει **δυναμικά** τους Global Attribute Slugs (`pa_...`) που θα χρησιμοποιηθούν για τη λήψη των Όρων (Terms) και την εκτέλεση του φίλτρου. |
| **Ετικέτες Φόρμας**                 | **Label 1, Label 2, Label 3**                                                       | Αλλάζει το εμφανιζόμενο κείμενο μπροστά από κάθε dropdown.                                                                                   |
| **Πλαίσια Κειμένου (Placeholders)** | **Placeholder 1, Placeholder Primary, Placeholder Secondary**                       | Ορίζει το κείμενο που εμφανίζεται μέσα στα dropdowns πριν γίνει η επιλογή.                                                                   |
| **Κεφαλίδες Φόρμας**                | **Τίτλος Quiz (H2)**, **Υπότιτλος Quiz (P)**                                        | Ελέγχει τα κύρια μηνύματα marketing της φόρμας.                                                                                              |
| **Συμπεριφορά & Χρώματα**           | **Ενεργοποίηση 3ου Πεδίου**, **Κείμενο Κουμπιού (CTA)**, **Χρώμα Background/Hover** | Ελέγχει τη συνολική δομή (εάν εμφανίζεται το 3ο πεδίο) και το πλήρες branding των κουμπιών.                                                  |

---

## 3. Maintenance Guide: Adding New Flavors

The system automatically manages the options list. Follow these steps to introduce a new flavor (e.g., "Kiwi") to the Quiz dropdowns:

1.  **Access Attributes:** Navigate to **Προϊόντα → Χαρακτηριστικά**.
2.  **Add New Term:** Find the Attribute **Συστατικό (Quiz)** (`pa_quiz-ingredient`). Click **"Ρύθμιση όρων"** (Configure Terms) and add the new flavor name (e.g., 'Kiwi') and its slug (e.g., 'kiwi').
3.  **Link to Product:** Open the product you are selling (the Kiwi e-liquid). In the **Attributes** tab, ensure you assign the new 'Kiwi' term under the **Συστατικό (Quiz)** attribute.
4.  **Auto-Update:** The new 'Kiwi' option will now automatically appear in the Quiz dropdowns on your homepage because it is associated with a product.

---

## 4. Frontend Usage

To display the fully configured quiz form on your homepage or any other page, use the shortcode:

```markdown
[vapevida_quiz]
```
