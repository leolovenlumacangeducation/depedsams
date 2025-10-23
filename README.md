# SAMSPIKPOK - Inventory and Asset Management System

This is a comprehensive, web-based inventory and asset management system, likely designed for educational institutions. It facilitates the tracking of assets from procurement to disposal, including consumables, semi-expendable property (SEP), and property, plant, and equipment (PPE).

## Database Schema

The database is structured to manage the entire lifecycle of school assets. Key tables include:

*   **Core & Users:**
    *   `tbl_school`: Basic school information.
    *   `tbl_user`, `tbl_role`, `tbl_position`: Manages users, roles (Admin, User), and official positions.
    *   `tbl_officers`: Assigns users to specific approval roles (e.g., Approving Officer).

*   **Procurement:**
    *   `tbl_supplier`: Stores vendor information.
    *   `tbl_po`, `tbl_po_item`: Manages purchase orders and the items within them.
    *   `tbl_delivery`, `tbl_delivery_item`: Tracks the receipt of ordered items.

*   **Inventory & Assets:**
    *   `tbl_inventory_type`, `tbl_category`: A two-tier system to classify items (e.g., Consumable -> Office Supplies).
    *   `tbl_consumable`: For tracking stock items like office supplies.
    *   `tbl_sep`: For semi-expendable property.
    *   `tbl_ppe`: For property, plant, and equipment.

*   **Transactions & Accountability:**
    *   `tbl_issuance`, `tbl_issuance_item`: Records the issuance of consumable items.
    *   `tbl_ics`, `tbl_ics_item`: Manages Inventory Custodian Slips for SEP.
    *   `tbl_par`, `tbl_par_item`: Manages Property Acknowledgment Receipts for PPE.
    *   `tbl_iirup`, `tbl_iirup_item`: For handling the disposal of unserviceable assets.

*   **Reporting:**
    *   `tbl_rpci`, `tbl_rpcppe`: Tables for generating physical count reports.

*   **Number Sequences:** A series of `tbl_*_number` tables are used to generate unique, formatted numbers for documents like POs, ICS, PARs, etc.

## Installation

1.  **Database Setup:**
    *   Import the `full_setup.sql` file into your MySQL server. This will create the `samspikpok_db` database, all tables, and insert default data.

2.  **Dependencies:**
    *   Run `composer install` to install the PHP dependencies.

3.  **Default Admin User:**
    *   The `full_setup.sql` script inserts a list of users, but the passwords are placeholders (`$2y$10$your_secure_hash_here`). You will need to update the `hashed_password` for the users in the `tbl_user` table. Alternatively, you can use the `default_admin.php` script to create a default admin user (username: admin, password: admin).

## Usage

1.  **Login:** Access the application via a web server and log in.
2.  **Dashboard:** View a summary of inventory levels and recent activities.
3.  **Manage Inventory:**
    *   **Procurement:** Create and track purchase orders and deliveries.
    *   **Inventory:** Manage detailed records for consumables, SEPs, and PPEs, including QR code generation.
    *   **Custody & Accountability:** Issue items to personnel using Inventory Custodian Slips (ICS) and Property Acknowledgment Receipts (PAR).
4.  **Reporting:** Generate and view various inventory reports, such as the Report of Physical Count of Inventories (RPCI) and Report on the Physical Count of Property, Plant and Equipment (RPCPPE).
5.  **Admin:** Manage users, school settings, and other system parameters.

