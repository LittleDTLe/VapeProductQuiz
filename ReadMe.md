# 📝 VapeVida Flavorshot Recommender Quiz Documentation

**Plugin Version:** 0.8.6

**Version Notes:** Modularization of Plugin, Dynamic Text Configuration, Dynamic Required Fields, Attribute Selection, Responsive Admin Page, Easy Shortcode Copy.

**Author:** Panagiotis Drougas

---

## 🚀 Overview

The **VapeVida Flavorshot Recommender Quiz** is a custom WooCommerce utility designed to transform product discovery. It provides a simple, dynamic, and fully managed frontend form that guides customers to the exact e-liquids they desire.

This tool ensures **maintainability** by allowing store managers to control all aspects of the form and its content from the WordPress dashboard, minimizing the need for code changes.

---

## 🛠️ Key Features

- [x] **Dynamic Filtering (AND Logic):** The system ensures that products must match **ALL** selections (Flavor Type AND Ingredients) to appear in the filtered results.
- [x] **Dynamic Content Control:** All user-facing text (Headings, Labels, Placeholders, and Button Text) is editable via the Admin Settings.
- [x] **Conditional Visibility:** The third dropdown (Secondary Ingredient) can be instantly toggled **ON** or **OFF** from the settings page.
- [x] **Auto-Populated Options:** Dropdown fields are populated automatically from your WooCommerce Global Attribute Terms. New flavors appear in the quiz the moment they are assigned to a product.

---

## To Implement Features

- [ ] **Button Color:** Add Admin Control of the CTA button color on all states (Idle, Hover, Active)
- [x] **Responsive Admin Dashboard:** Add responsiveness to the admin dashboard settings page of the plugin
- [ ] **Dynamic Cascading Filters:** Add Ajax Dropdown Filter logic to the quiz, so that there are no Zero Result Pages
- [ ] **Result Preview:** Combined with the Dynamic CTA, the user can see how many results there are to their search
- [ ] **Analytics & Tracking:** Create a Hook (before rediraction), to track user behavior and overall usage
- [ ] **Default Attribute Toggle:** Add a checkbox to easily toggle between using default attributes or custom

---

---

## Feature Ideas

To implement the below features / ideas, there would need to be a complete refactoring of the plugin.

It could be possible to either have them as seperate plugins or a **free** version, with the current feature-set and a **premium** version with the added features.

- [ ] **Multi Quiz Support** Add Multiple Quiz Support to the plugin
- [ ] **Category Page Quiz** Add Dynamic Quizes to Category pages with Ajax Filtering Capabilities

---

## 1. Installation and Required Setup

### 1.1 Installation

1.  Place all plugin files (`vapevida-quiz.php`, `includes/`, etc.) into a folder named `vapevida-quiz` within your WordPress site's plugins directory (`wp-content/plugins/`).
2.  In your WordPress Dashboard, navigate to **Plugins** and **Activate** the "VapeVida Flavorshot Recommender Quiz."

### 1.2 Required Data Mapping (Crucial)

The plugin requires two specific **Global Attributes** to be active and populated with your product data:

| Filter Purpose                 | Global Attribute Slug | WooCommerce Name | Data Responsibility                                                               |
| :----------------------------- | :-------------------- | :--------------- | :-------------------------------------------------------------------------------- |
| **Flavor Type (Field 1)**      | `pa_geuseis`          | Τύπος Γεύσης     | Must contain terms like _Sweet_, _Tobacco_, _Fruity_.                             |
| **Ingredients (Fields 2 & 3)** | `pa_quiz-ingredient`  | Συστατικό (Quiz) | Must contain all individual ingredients (e.g., _Strawberry_, _Cream_, _Vanilla_). |

---

## 2. Admin Configuration and Management

All dynamic settings and text modifications are handled in the dedicated plugin page.

### 2.1 Accessing the Settings

Navigate to the main sidebar and click on the **VapeVida Quiz** menu item.

### 2.2 Form Structure and Control

| Section                           | Setting                     | Purpose                                                                                                                                                                                     |
| :-------------------------------- | :-------------------------- | :------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **Ρυθμίσεις Απαιτούμενων Πεδίων** | Checkboxes (Is Required?)   | Controls **form validation**. Determines which fields the customer must fill out to submit the quiz.                                                                                        |
| **Ρυθμίσεις Φόρμας και Ετικετών** | **Ενεργοποίηση 3ου Πεδίου** | Toggles the visibility of the optional "Δευτερεύον Συστατικό" dropdown.                                                                                                                     |
| **Ρυθμίσεις Φόρμας και Ετικετών** | **Placeholder / Ετικέτες**  | Allows you to change the text for the main headings (H2), subtitle (P), all dropdown labels (e.g., "1. Προφίλ Υγρού:"), and the button CTA.                                                 |
| **Attribute Selectors**           | Attribute Dropdowns         | (For developers/advanced users) Allows remapping the quiz fields to different Global Attributes (e.g., changing from `pa_geuseis` to `pa_hardware`) if needed for a different quiz purpose. |

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
