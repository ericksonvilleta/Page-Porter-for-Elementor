# Page Porter for Elementor

**Page Porter for Elementor** is a powerful, lightweight utility designed to bridge the gap in Elementor's native export tools. Created by **Erick Villeta**, this plugin allows you to bulk export live WordPress pages and import them onto another site with intelligent handling of media and URLs.

---

## 🚀 Key Features

* **Bulk Page Export:** Select multiple pages or export your entire site structure into a single JSON file.
* **Smart Media Detection:** During import, the plugin checks if images already exist in your Media Library (by filename) to avoid cluttering your site with duplicates.
* **Recursive URL Migration:** Automatically detects the source domain and replaces it with the destination domain within Elementor's nested layout data.
* **Featured Image Preservation:** Automatically reconnects featured images to the imported pages.
* **Safe Import Workflow:** All imported pages are saved as **Drafts** with an "(Imported)" suffix, ensuring you can review them before going live.
* **Developer Friendly:** Follows WordPress Coding Standards (WPCS) with built-in security nonces and data sanitization.

---

## 🛠 Installation

1.  **Download:** Clone this repository or download the `.zip`.
2.  **Upload:** Place the `page-porter-for-elementor` folder into your `/wp-content/plugins/` directory.
3.  **Activate:** Go to the WordPress Dashboard > Plugins and click **Activate**.
4.  **Navigate:** Access the tool via **Elementor > Page Porter**.

---

## 📖 Usage

### Exporting
1.  Navigate to **Elementor > Page Porter**.
2.  Select the pages you want to move from the list.
3.  Click **Generate Export File**.

### Importing
1.  On the destination site, go to **Elementor > Page Porter**.
2.  Upload your `.json` export file.
3.  Click **Run Smart Import**.
4.  Review your new pages under the **Pages** menu.

---

## 📋 Requirements
* **WordPress:** 5.0 or higher
* **Elementor:** Free or Pro version
* **PHP:** 7.4 or higher

---

## 👤 Author
**Erick Villeta** [ericksonvilleta.com](https://ericksonvilleta.com)

## 📄 License
This project is licensed under the GPLv2 or later. See the [GNU General Public License](https://www.gnu.org/licenses/gpl-2.0.html) for more details.
