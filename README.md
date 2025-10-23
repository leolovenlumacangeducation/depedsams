# SAMS: DepEd Supply and Asset Management System

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-orange.svg)](https://www.mysql.com/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-purple.svg)](https://getbootstrap.com/)

SAMS is a comprehensive inventory and asset management system designed specifically to meet the requirements of the Department of Education (DepEd) in the Philippines. It streamlines the entire lifecycle of school property and supplies, from procurement to disposal, by automating the generation of all standard DepEd supply and property forms.

**Live Demo:** A live version of this project is available at: [http://sams.deped.education/](http://sams.deped.education/)

---

## 🎯 Project Goal

The primary goal of this system is to digitize and simplify the complex, paper-based inventory process in DepEd schools. By providing a centralized database for all assets (Consumables, SEP, and PPE) and generating compliant forms, SAMS helps school property custodians save time, reduce errors, and maintain accurate, auditable records.

---

## ✨ Core Features

### Automated DepEd Form Generation

This system automatically generates and prepares for printing all necessary forms for property and supply management, based on the data entered into the system.

**Procurement & Receiving:**
* Canvass Form
* Appendix 60 - Purchase Request (PR)
* Appendix 61 - Purchase Order (PO)
* Appendix 62 - Inspection and Acceptance Report (IAR)

**Inventory & Stock Management:**
* Appendix 58 - Stock Card (SC)
* Appendix 69 - Property Card (PC)
* Appendix 63 - Requisition and Issue Slip (RIS)
* Appendix 64 - Report of Supplies and Materials Issued (RSMI)

**Accountability & Issuance:**
* Appendix 59 - Inventory Custodian Slip (ICS) - *For Semi-Expendable Property*
* Appendix 71 - Property Acknowledgement Receipt (PAR) - *For Property, Plant & Equipment*

**Reporting & Physical Count:**
* Appendix 66 - Report on the Physical Count of Inventories (RPCI)
* Appendix 73 - Report on the Physical Count of Property, Plant and Equipment (RPCPPE)

**Disposal & Transfers:**
* Appendix 65 - Waste Materials Report (WMR)
* Appendix 74 - Inventory and Inspection Report of Unserviceable Property (IIRUP)
* Appendix 75 - Report of Lost, Stolen, Damaged or Destroyed Property (RLSDDP)
* Appendix 76 - Property Transfer Report (PTR)

### System & Management Features

* **User Management:** Role-based access control (Admin, User) with user accounts tied to official DepEd positions.
* **Asset Tracking:** Manages three distinct types of inventory:
    * **Consumables:** (e.g., office supplies) with stock card tracking (`vw_consumable_stock_card`).
    * **Semi-Expendable Property (SEP):** Items valued at less than P50K, tracked via ICS.
    * **Property, Plant & Equipment (PPE):** Major assets valued over P50K, tracked via PAR.
* **Procurement Workflow:** A full procurement module to manage suppliers, create Purchase Orders, and log deliveries.
* **QR Code Generation:** (As indicated by schema fields `qr_code`) The system is built to support QR code generation and tagging for easy asset identification.
* **Document Management:** Attach scanned documents (receipts, warranty cards, transfer forms) directly to asset records (`tbl_document`).
* **Dashboard & Reporting:** Provides a central view of inventory levels, upcoming reports, and asset accountability.

---

## 💻 Technology Stack

* **Backend:** **PHP** (procedural or object-oriented)
* **Database:** **MySQL** (using **PDO** for secure database connections)
* **Frontend:** **Bootstrap 5** (for a responsive, mobile-first user interface)
* **Client-side:** **JavaScript** / **jQuery** (for dynamic forms, modals, and AJAX operations)

---

## 🔧 Installation and Setup

To run this project locally, you will need a web server environment like XAMPP, WAMP, or LAMP.

1.  **Clone the Repository**
    ```bash
    git clone [https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git](https://github.com/YOUR_USERNAME/YOUR_REPO_NAME.git)
    cd YOUR_REPO_NAME
    ```

2.  **Database Setup**
    * Open your MySQL management tool (like phpMyAdmin).
    * Create a new database named `samspikpok_db` with `utf8mb4_unicode_ci` collation.
    * Import the `schema.sql` file (containing the schema you provided) into the `samspikpok_db` database.
        ```bash
        mysql -u root -p samspikpok_db < path/to/your/schema.sql
        ```

3.  **Run the Admin Creation Script**
    * The database schema requires a default admin user (ID=1) to be created before other data (like officers) can be linked.
    * Place the project folder in your server's root directory (e.g., `C:/xampp/htdocs/sams`).
    * In your browser, navigate to the `default_admin.php` script to create the initial user:
        **`http://localhost/sams/default_admin.php`**
    * This will create the default admin account (e.g., **Username:** `admin`, **Password:** `admin`). *Please change this password immediately after logging in.*

4.  **Configure Database Connection**
    * Find the PHP file responsible for the database connection (e.g., `config.php`, `includes/db.php`, or similar).
    * Update the file with your MySQL database credentials:
    ```php
    <?php
    $host = 'localhost';
    $dbname = 'samspikpok_db';
    $username = 'root'; // Your MySQL username
    $password = ''; // Your MySQL password

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Could not connect to the database $dbname :" . $e->getMessage());
    }
    ?>
    ```

5.  **Run the Application**
    * Access the project in your web browser:
        **`http://localhost/sams/`**

---

## 🗃️ Database Schema Overview

The schema is normalized and revolves around a few key concepts:

* **Users & Roles:** `tbl_user`, `tbl_role`, `tbl_position`, and `tbl_officers` manage user accounts and their official designations (e.g., Approving Officer).
* **Procurement:** `tbl_supplier`, `tbl_po` (Purchase Order), and `tbl_delivery` track the purchasing lifecycle.
* **Core Assets:**
    * `tbl_consumable`: For items that are used up (e.g., paper, ink).
    * `tbl_sep`: For semi-expendable property.
    * `tbl_ppe`: For major property, plant, and equipment.
* **Transactions:**
    * `tbl_issuance` & `tbl_issuance_item`: Manages the release of consumables (generates RIS).
    * `tbl_ics` & `tbl_ics_item`: Manages accountability for SEP.
    * `tbl_par` & `tbl_par_item`: Manages accountability for PPE.
* **Reporting:** `tbl_rpci` (Report on Physical Count of Inventories) and `tbl_rpcppe` (Report on Physical Count of PPE) store historical snapshot reports.
* **Disposal:** `tbl_iirup` (Inventory and Inspection Report of Unserviceable Property) handles the disposal workflow.
* **Utilities:** `tbl_document` allows file attachments, and various `tbl_*_number` tables manage the auto-incrementing serial numbers for forms.

---

## 🤝 Contributing

Contributions are welcome! If you'd like to help improve SAMS, please follow these steps:

1.  Fork the repository.
2.  Create a new branch (`git checkout -b feature/YourFeatureName`).
3.  Commit your changes (`git commit -m 'Add some feature'`).
4.  Push to the branch (`git push origin feature/YourFeatureName`).
5.  Open a Pull Request.

---

## 📄 License

(Pending)
