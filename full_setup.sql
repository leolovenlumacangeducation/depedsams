-- =================================================================
-- SAMS - FULL DATABASE SETUP SCRIPT
-- This script creates the database, all tables, views, and
-- inserts all default reference data.
-- =================================================================

-- Create and use the database
CREATE DATABASE IF NOT EXISTS `samspikpok_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `samspikpok_db`;

-- #################################################################
-- # MIGRATIONS TABLE                                              #
-- #################################################################
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mark migrations that are represented in this full schema so
-- migration runners don't attempt to re-apply them immediately.
-- NOTE: keep this list in sync with your migrations/ folder.
INSERT INTO migrations (migration)
SELECT '001_create_system_log' WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '001_create_system_log');
INSERT INTO migrations (migration)
SELECT '002_add_has_been_assigned_to_ppe' WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '002_add_has_been_assigned_to_ppe');
INSERT INTO migrations (migration)
SELECT '003_add_has_been_assigned_to_sep' WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '003_add_has_been_assigned_to_sep');
INSERT INTO migrations (migration)
SELECT '012_document_management' WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '012_document_management');
-- NOTE: 999_remove_document_management intentionally not marked here. Keep it out if you want the document tables preserved.
INSERT INTO migrations (migration)
SELECT '2024_01_25_add_iar_tables' WHERE NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2024_01_25_add_iar_tables');


-- #################################################################
-- # 1. SCHOOL, USER, AND CORE REFERENCE TABLES                    #
-- #################################################################

-- 1.1 tbl_school: Stores basic school information (e.g., Pagadian City National Comprehensive High School)
CREATE TABLE tbl_school (
    school_id INT PRIMARY KEY AUTO_INCREMENT,
    school_name VARCHAR(255) NOT NULL,
    school_code VARCHAR(50) UNIQUE,
    logo VARCHAR(255) NULL,
    address TEXT NULL,
    division_name VARCHAR(100),
    region_name VARCHAR(100),
    contact_number VARCHAR(50)
);

-- 1.2 tbl_position: Stores official school positions (e.g., Teacher I, Principal)
CREATE TABLE tbl_position (
    position_id INT PRIMARY KEY AUTO_INCREMENT,
    position_name VARCHAR(150) NOT NULL UNIQUE
);

-- 1.3 tbl_role: Stores user roles (Admin, User, etc.) for better scalability
CREATE TABLE tbl_role (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE
);

-- 1.3 tbl_user: Stores user accounts, linked to a position
CREATE TABLE tbl_user (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    username VARCHAR(100) NOT NULL UNIQUE,
    hashed_password VARCHAR(255) NOT NULL,
    role_id INT NOT NULL, -- FK to tbl_role
    position_id INT NULL, -- FK to tbl_position
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    photo VARCHAR(255) DEFAULT 'default_user.png',
    -- Optional: path variant and last login timestamp added by later migrations
    photo_path VARCHAR(255) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (role_id),
    INDEX (position_id),

    FOREIGN KEY (position_id) REFERENCES tbl_position(position_id) ON DELETE SET NULL,
    FOREIGN KEY (role_id) REFERENCES tbl_role(role_id) ON DELETE RESTRICT
);

-- 1.4 tbl_officers: Stores the type of approval roles and the current person assigned to them
CREATE TABLE tbl_officers (
    officer_id INT PRIMARY KEY AUTO_INCREMENT,
    officer_type VARCHAR(100) NOT NULL UNIQUE, -- e.g., 'Approving Officer'
    user_id INT NULL, -- FK to tbl_user for the currently assigned person
    INDEX (user_id),

    FOREIGN KEY (user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 1.5 tbl_supplier: Stores vendor details
CREATE TABLE tbl_supplier (
    supplier_id INT PRIMARY KEY AUTO_INCREMENT,
    supplier_name VARCHAR(255) NOT NULL,
    address TEXT NULL,
    tin VARCHAR(50) NULL UNIQUE, -- Tax Identification Number (TIN)
    contact_person VARCHAR(150) NULL,
    contact_no VARCHAR(50) NULL
);

-- 1.6 General Transaction Lookups
CREATE TABLE tbl_purchase_mode (
    purchase_mode_id INT PRIMARY KEY AUTO_INCREMENT,
    mode_name VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE tbl_delivery_place (
    delivery_place_id INT PRIMARY KEY AUTO_INCREMENT,
    place_name VARCHAR(255) NOT NULL
);

CREATE TABLE tbl_delivery_term (
    delivery_term_id INT PRIMARY KEY AUTO_INCREMENT,
    term_description VARCHAR(255) NOT NULL
);

CREATE TABLE tbl_payment_term (
    payment_term_id INT PRIMARY KEY AUTO_INCREMENT,
    term_description VARCHAR(255) NOT NULL
);

-- 1.7 Item Unit Lookups
CREATE TABLE tbl_unit (
    unit_id INT PRIMARY KEY AUTO_INCREMENT,
    unit_name VARCHAR(50) NOT NULL UNIQUE
);


-- #################################################################
-- # 2. CLASSIFICATION AND CATEGORY TABLES (2-TIER STRUCTURE)      #
-- #################################################################

-- 2.1 tbl_inventory_type: Defines the top-level grouping (Consumable, SEP, PPE)
CREATE TABLE tbl_inventory_type (
    inventory_type_id INT PRIMARY KEY AUTO_INCREMENT,
    inventory_type_name VARCHAR(100) NOT NULL UNIQUE
);

-- 2.2 tbl_category: Defines the specific category (Supplies, Furniture), linked to an inventory type
CREATE TABLE tbl_category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(150) NOT NULL UNIQUE,
    uacs_object_code VARCHAR(50) NULL,
    inventory_type_id INT NOT NULL,
    INDEX (inventory_type_id),

    FOREIGN KEY (inventory_type_id) REFERENCES tbl_inventory_type(inventory_type_id) ON DELETE RESTRICT
);


-- #################################################################
-- # 3. NUMBER SEQUENCE TABLES (For Generating Unique IDs)         #
-- #################################################################

-- 3.1 Purchase Order Number Sequence
CREATE TABLE tbl_po_number (
    po_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    po_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_serial_year (serial, year)
);

-- 3.2 Property Number Sequence (for SEP/PPE tracking)
CREATE TABLE tbl_pn_number (
    pn_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    pn_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_pn_serial_year (serial, year)
);

-- 3.3 Item/Stock Number Sequence (for Consumables tracking)
CREATE TABLE tbl_item_number (
    item_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    item_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_item_serial_year (serial, year)
);

-- 3.4 Requisition and Issue Slip (RIS) Number Sequence
CREATE TABLE tbl_ris_number (
    ris_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    ris_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_ris_serial_year (serial, year)
);

-- 3.5 Inventory Custodian Slip (ICS) Number Sequence
CREATE TABLE tbl_ics_number (
    ics_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    ics_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_ics_serial_year (serial, year)
);

-- 3.6 Property Acknowledgment Receipt (PAR) Number Sequence
CREATE TABLE tbl_par_number (
    par_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    par_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_par_serial_year (serial, year)
);

-- 3.7 Report of Physical Count of Inventories (RPCI) Number Sequence
CREATE TABLE tbl_rpci_number (
    rpci_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    rpci_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_rpci_serial_year (serial, year)
);

-- 3.8 Report on the Physical Count of Property, Plant and Equipment (RPCPPE) Number Sequence
CREATE TABLE tbl_rpcppe_number (
    rpcppe_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    rpcppe_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_rpcppe_serial_year (serial, year)
);

-- 3.9 IIRUP Number Sequence
CREATE TABLE tbl_iirup_number (
    iirup_number_id INT PRIMARY KEY AUTO_INCREMENT,
    serial VARCHAR(50) NOT NULL,
    year YEAR NOT NULL,
    start_count INT NOT NULL DEFAULT 1,
    iirup_number_format VARCHAR(100) NULL,
    UNIQUE KEY uk_iirup_serial_year (serial, year)
);

-- #################################################################
-- # 4. PURCHASE ORDER TABLES                                      #
-- #################################################################

-- 4.1 Purchase Order Header
CREATE TABLE tbl_po (
    po_id INT PRIMARY KEY AUTO_INCREMENT,
    po_number VARCHAR(50) NOT NULL UNIQUE,
    supplier_id INT NOT NULL,
    purchase_mode_id INT NOT NULL,
    delivery_place_id INT NOT NULL,
    delivery_term_id INT NOT NULL,
    payment_term_id INT NOT NULL,
    order_date DATE NOT NULL,
    INDEX (supplier_id),
    INDEX (purchase_mode_id),
    INDEX (delivery_place_id),
    INDEX (delivery_term_id),
    INDEX (payment_term_id),
    status ENUM('Pending', 'Delivered') NOT NULL DEFAULT 'Pending',

    FOREIGN KEY (supplier_id) REFERENCES tbl_supplier(supplier_id),
    FOREIGN KEY (purchase_mode_id) REFERENCES tbl_purchase_mode(purchase_mode_id) ON DELETE RESTRICT,
    FOREIGN KEY (delivery_place_id) REFERENCES tbl_delivery_place(delivery_place_id) ON DELETE RESTRICT,
    FOREIGN KEY (delivery_term_id) REFERENCES tbl_delivery_term(delivery_term_id) ON DELETE RESTRICT,
    FOREIGN KEY (payment_term_id) REFERENCES tbl_payment_term(payment_term_id) ON DELETE RESTRICT
);

-- 4.2 Purchase Order Item/Detail
CREATE TABLE tbl_po_item (
    po_item_id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    category_id INT NOT NULL, -- Links item to a category (and indirectly, its inventory type)
    description TEXT NOT NULL,
    quantity INT NOT NULL,
    unit_id INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    INDEX (po_id),
    INDEX (category_id),
    INDEX (unit_id),

    FOREIGN KEY (po_id) REFERENCES tbl_po(po_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES tbl_category(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (unit_id) REFERENCES tbl_unit(unit_id) ON DELETE RESTRICT
);

-- 4.3 Delivery Tracking
CREATE TABLE tbl_delivery (
    delivery_id INT PRIMARY KEY AUTO_INCREMENT,
    po_id INT NOT NULL,
    delivery_receipt_no VARCHAR(100) NOT NULL,
    date_received DATE NOT NULL,
    received_by_user_id INT NULL,
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (po_id),
    INDEX (received_by_user_id),

    FOREIGN KEY (po_id) REFERENCES tbl_po(po_id) ON DELETE CASCADE,
    FOREIGN KEY (received_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    UNIQUE KEY uk_po_dr (po_id, delivery_receipt_no)
);

-- 4.4 Delivery Items (What was delivered)
CREATE TABLE tbl_delivery_item (
    delivery_item_id INT PRIMARY KEY AUTO_INCREMENT,
    delivery_id INT NOT NULL,
    po_item_id INT NOT NULL,
    quantity_delivered INT NOT NULL,
    INDEX (delivery_id),
    INDEX (po_item_id),

    FOREIGN KEY (delivery_id) REFERENCES tbl_delivery(delivery_id) ON DELETE CASCADE,
    FOREIGN KEY (po_item_id) REFERENCES tbl_po_item(po_item_id) ON DELETE RESTRICT,
    UNIQUE KEY uk_delivery_po_item (delivery_id, po_item_id)
);
 
-- #################################################################
-- # 5. INCOMING ICS TABLES                                        #
-- #################################################################

-- 5.1. tbl_incoming_ics: Stores the header information for the ICS document received.
CREATE TABLE `tbl_incoming_ics` (
`incoming_ics_id` int(11) NOT NULL AUTO_INCREMENT,
`ics_number` varchar(100) NOT NULL,
`source_office` varchar(255) NOT NULL COMMENT 'e.g., Division Office, Name of other school',
`issued_by_name` varchar(255) NOT NULL COMMENT 'Name of the person who issued the items',
`issued_by_position` varchar(150) DEFAULT NULL COMMENT 'Position of the issuer',
`date_received` date NOT NULL,
`received_by_user_id` int(11) NOT NULL,
`date_created` datetime NOT NULL DEFAULT current_timestamp(),
PRIMARY KEY (`incoming_ics_id`),
UNIQUE KEY `uk_ics_number_source` (`ics_number`,`source_office`),
KEY `received_by_user_id` (`received_by_user_id`),
CONSTRAINT `fk_incoming_ics_user` FOREIGN KEY (`received_by_user_id`) REFERENCES `tbl_user` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores records of items received from other entities via ICS.';

-- 5.2. tbl_incoming_ics_item: Stores the details of each item listed on the incoming ICS.
CREATE TABLE `tbl_incoming_ics_item` (
`incoming_ics_item_id` int(11) NOT NULL AUTO_INCREMENT,
`incoming_ics_id` int(11) NOT NULL,
`category_id` int(11) NOT NULL,
`description` text NOT NULL,
`quantity` int(11) NOT NULL,
`unit_id` int(11) NOT NULL,
`unit_cost` decimal(10,2) NOT NULL,
PRIMARY KEY (`incoming_ics_item_id`),
KEY `incoming_ics_id` (`incoming_ics_id`),
KEY `category_id` (`category_id`),
KEY `unit_id` (`unit_id`),
CONSTRAINT `fk_incoming_item_ics` FOREIGN KEY (`incoming_ics_id`) REFERENCES `tbl_incoming_ics` (`incoming_ics_id`) ON DELETE CASCADE,
CONSTRAINT `fk_incoming_item_category` FOREIGN KEY (`category_id`) REFERENCES `tbl_category` (`category_id`) ON DELETE RESTRICT,
CONSTRAINT `fk_incoming_item_unit` FOREIGN KEY (`unit_id`) REFERENCES `tbl_unit` (`unit_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- #################################################################
-- # 6. INVENTORY/ASSET TABLES                                     #
-- #################################################################

-- 6.1 Consumable Inventory (Stock Number Items)
CREATE TABLE tbl_consumable (
    consumable_id INT PRIMARY KEY AUTO_INCREMENT,
    po_item_id INT(11) NULL,
    incoming_ics_item_id INT(11) NULL DEFAULT NULL,
    stock_number VARCHAR(100) NULL,
    stock_number_id INT NULL,
    quantity_received INT NOT NULL,
    unit_id INT NOT NULL,
    unit_cost DECIMAL(10, 2) NOT NULL,
    current_stock INT NOT NULL,
    date_received DATE NOT NULL,
    delivery_id INT NULL,
    parent_consumable_id INT NULL,
    custodian_user_id INT NULL,
    photo VARCHAR(255) DEFAULT 'consumable_default.png',
    -- QR and explicit path columns added by migrations (optional)
    qr_code VARCHAR(255) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    INDEX (po_item_id),
    INDEX (stock_number_id),
    INDEX (delivery_id),
    INDEX (parent_consumable_id),
    INDEX (unit_id),

    FOREIGN KEY (po_item_id) REFERENCES tbl_po_item(po_item_id) ON DELETE RESTRICT,        
    FOREIGN KEY (stock_number_id) REFERENCES tbl_item_number(item_number_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (delivery_id) REFERENCES tbl_delivery(delivery_id) ON DELETE SET NULL,
    FOREIGN KEY (parent_consumable_id) REFERENCES tbl_consumable(consumable_id) ON DELETE SET NULL,
    FOREIGN KEY (custodian_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    FOREIGN KEY (incoming_ics_item_id) REFERENCES tbl_incoming_ics_item(incoming_ics_item_id) ON DELETE RESTRICT
);

-- 6.2 SEP (Semi-Expendable Property - Property Number Items)
CREATE TABLE tbl_sep (
    sep_id INT PRIMARY KEY AUTO_INCREMENT,
    po_item_id INT(11) NULL,
    incoming_ics_item_id INT NULL DEFAULT NULL,
    property_number VARCHAR(100) NOT NULL UNIQUE,        
    pn_number_id INT(11) NULL,
    serial_number VARCHAR(100) NULL,
    brand_name VARCHAR(150) NULL,
    estimated_useful_life INT NULL,
    date_acquired DATE NOT NULL,
    delivery_id INT NULL,
    current_location VARCHAR(255),
    current_condition ENUM('Serviceable', 'Unserviceable', 'For Repair', 'Disposed') NOT NULL DEFAULT 'Serviceable',
    assigned_to_user_id INT,
    photo VARCHAR(255) DEFAULT 'sep_default.png',
    -- QR and explicit path columns added by migrations (optional)
    qr_code VARCHAR(255) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    has_been_assigned BOOLEAN NOT NULL DEFAULT 0,
    INDEX (po_item_id),
    INDEX (pn_number_id),
    INDEX (assigned_to_user_id),
    INDEX (delivery_id),

    FOREIGN KEY (po_item_id) REFERENCES tbl_po_item(po_item_id) ON DELETE RESTRICT,        
    FOREIGN KEY (pn_number_id) REFERENCES tbl_pn_number(pn_number_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (assigned_to_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    FOREIGN KEY (delivery_id) REFERENCES tbl_delivery(delivery_id) ON DELETE SET NULL,
    INDEX (incoming_ics_item_id)
);

-- 6.3 PPE (Property, Plant, & Equipment - Model Number Items)
CREATE TABLE tbl_ppe (
    ppe_id INT PRIMARY KEY AUTO_INCREMENT,
    po_item_id INT(11) NULL,
    incoming_ics_item_id INT NULL DEFAULT NULL,
    property_number VARCHAR(100) NOT NULL UNIQUE,
    pn_number_id INT(11) NULL,
    model_number VARCHAR(100) NULL,        
    serial_number VARCHAR(100) NULL UNIQUE,
    date_acquired DATE NOT NULL,
    delivery_id INT NULL,
    date_disposed DATE NULL,
    current_location VARCHAR(255),
    current_condition ENUM('Serviceable', 'Unserviceable', 'For Repair', 'Disposed') NOT NULL DEFAULT 'Serviceable',
    assigned_to_user_id INT NULL,
    photo VARCHAR(255) DEFAULT 'ppe_default.png',
    -- QR and explicit path columns added by migrations (optional)
    qr_code VARCHAR(255) DEFAULT NULL,
    photo_path VARCHAR(255) DEFAULT NULL,
    `has_been_assigned` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Tracks if the item has ever been assigned, to distinguish from brand new items.',
    INDEX (po_item_id),
    INDEX (pn_number_id),
    INDEX (assigned_to_user_id),
    INDEX (delivery_id),

    FOREIGN KEY (po_item_id) REFERENCES tbl_po_item(po_item_id) ON DELETE RESTRICT,        
    FOREIGN KEY (pn_number_id) REFERENCES tbl_pn_number(pn_number_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (assigned_to_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    FOREIGN KEY (delivery_id) REFERENCES tbl_delivery(delivery_id) ON DELETE SET NULL,
    INDEX (incoming_ics_item_id)
);

-- 6.4 Unit Conversion Tracking
CREATE TABLE tbl_unit_conversion (
    conversion_id INT PRIMARY KEY AUTO_INCREMENT,
    from_consumable_id INT NOT NULL,
    to_consumable_id INT NOT NULL,
    quantity_converted INT NOT NULL,
    conversion_factor INT NOT NULL,
    converted_by_user_id INT NOT NULL,
    date_converted DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (to_consumable_id),
    INDEX (converted_by_user_id),


    FOREIGN KEY (from_consumable_id) REFERENCES tbl_consumable(consumable_id) ON DELETE RESTRICT,
    FOREIGN KEY (to_consumable_id) REFERENCES tbl_consumable(consumable_id) ON DELETE RESTRICT,
    FOREIGN KEY (converted_by_user_id) REFERENCES tbl_user(user_id) ON DELETE RESTRICT,
    UNIQUE KEY uk_from_consumable (from_consumable_id)
);


-- #################################################################
-- # 7. TRANSACTION TABLES                                         #
-- #################################################################

-- 7.1 Issuance Header (Who, When)
CREATE TABLE tbl_issuance (
    issuance_id INT PRIMARY KEY AUTO_INCREMENT,
    ris_number VARCHAR(50) NOT NULL UNIQUE,
    issued_to VARCHAR(255) NOT NULL,
    date_issued DATE NOT NULL,
    issued_by_user_id INT NULL, -- Can be NULL if the issuing user is deleted
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (issued_by_user_id),

    FOREIGN KEY (issued_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 7.2 Issuance Items (What, How Many)
CREATE TABLE tbl_issuance_item (
    issuance_item_id INT PRIMARY KEY AUTO_INCREMENT,
    issuance_id INT NOT NULL,
    consumable_id INT NOT NULL,
    quantity_issued INT NOT NULL,
    INDEX (issuance_id),
    INDEX (consumable_id),

    FOREIGN KEY (issuance_id) REFERENCES tbl_issuance(issuance_id) ON DELETE CASCADE,
    FOREIGN KEY (consumable_id) REFERENCES tbl_consumable(consumable_id) ON DELETE RESTRICT
);

-- 7.3 ICS Header (For SEP/PPE Assignment)
CREATE TABLE tbl_ics (
    ics_id INT PRIMARY KEY AUTO_INCREMENT,
    ics_number VARCHAR(50) NOT NULL UNIQUE,
    issued_to_user_id INT NULL, -- Can be NULL if the assigned user is deleted
    location VARCHAR(255) NULL,
    date_issued DATE NOT NULL,
    status ENUM('Active', 'Voided') NOT NULL DEFAULT 'Active',
    issued_by_user_id INT NULL, -- Can be NULL if the issuing user is deleted
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (issued_to_user_id),
    INDEX (issued_by_user_id),

    FOREIGN KEY (issued_to_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 7.4 ICS Items (Linking SEP to an ICS)
CREATE TABLE tbl_ics_item (
    ics_item_id INT PRIMARY KEY AUTO_INCREMENT,
    ics_id INT NOT NULL,
    sep_id INT NOT NULL,
    INDEX (ics_id),
    INDEX (sep_id),

    FOREIGN KEY (ics_id) REFERENCES tbl_ics(ics_id) ON DELETE CASCADE,
    FOREIGN KEY (sep_id) REFERENCES tbl_sep(sep_id) ON DELETE RESTRICT
);

-- 7.5 PAR Header (For PPE Assignment)
CREATE TABLE tbl_par (
    par_id INT PRIMARY KEY AUTO_INCREMENT,
    par_number VARCHAR(50) NOT NULL UNIQUE,
    issued_to_user_id INT NULL, -- Can be NULL if the assigned user is deleted
    location VARCHAR(255) NULL,
    date_issued DATE NOT NULL,
    status ENUM('Active', 'Voided') NOT NULL DEFAULT 'Active',
    issued_by_user_id INT NULL, -- Can be NULL if the issuing user is deleted
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (issued_to_user_id),
    INDEX (issued_by_user_id),

    FOREIGN KEY (issued_to_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 7.6 PAR Items (Linking PPE to a PAR)
CREATE TABLE tbl_par_item (
    par_item_id INT PRIMARY KEY AUTO_INCREMENT,
    par_id INT NOT NULL,
    ppe_id INT NOT NULL,
    INDEX (par_id),
    INDEX (ppe_id),

    FOREIGN KEY (par_id) REFERENCES tbl_par(par_id) ON DELETE CASCADE,
    FOREIGN KEY (ppe_id) REFERENCES tbl_ppe(ppe_id) ON DELETE RESTRICT
);

-- 7.7 PPE History (for Property Card - Appendix 69)
CREATE TABLE tbl_ppe_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    ppe_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    transaction_type ENUM('Receipt', 'Assignment', 'Return', 'Disposal') NOT NULL,
    reference VARCHAR(255) NULL,
    from_user_id INT NULL,
    to_user_id INT NULL,
    notes VARCHAR(255) NULL,
    INDEX (ppe_id),
    INDEX (from_user_id),
    INDEX (to_user_id),

    FOREIGN KEY (ppe_id) REFERENCES tbl_ppe(ppe_id) ON DELETE CASCADE,
    FOREIGN KEY (from_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL,
    FOREIGN KEY (to_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 7.8 Inventory and Inspection Report of Unserviceable Property (IIRUP)
CREATE TABLE tbl_iirup (
    iirup_id INT PRIMARY KEY AUTO_INCREMENT,
    iirup_number VARCHAR(50) NOT NULL UNIQUE,
    as_of_date DATE NOT NULL,
    disposal_method VARCHAR(100) NULL, -- e.g., 'Sold', 'Destroyed', 'Transferred'
    status ENUM('Draft', 'For Approval', 'Approved', 'Disposed') NOT NULL DEFAULT 'Draft',
    created_by_user_id INT NULL, -- Can be NULL if the creating user is deleted
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (created_by_user_id),

    FOREIGN KEY (created_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 7.9 Inventory and Inspection Report of Unserviceable Property (IIRUP) Items
CREATE TABLE tbl_iirup_item (
    iirup_item_id INT PRIMARY KEY AUTO_INCREMENT,
    iirup_id INT NOT NULL,
    asset_id INT NOT NULL, -- Can be ppe_id or sep_id
    asset_type ENUM('PPE', 'SEP') NOT NULL,
    remarks TEXT NULL,
    INDEX (iirup_id),

    FOREIGN KEY (iirup_id) REFERENCES tbl_iirup(iirup_id) ON DELETE CASCADE,
    UNIQUE KEY uk_iirup_asset (iirup_id, asset_id, asset_type)
);

-- #################################################################
-- # 8. REPORTING TABLES                                           #
-- #################################################################

-- 8.1 Report of Physical Count of Inventories (RPCI)
CREATE TABLE tbl_rpci (
    rpci_id INT PRIMARY KEY AUTO_INCREMENT,
    rpci_number VARCHAR(50) NOT NULL UNIQUE,
    as_of_date DATE NOT NULL,
    created_by_user_id INT NULL, -- Can be NULL if the creating user is deleted
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (created_by_user_id),
    FOREIGN KEY (created_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 8.2 Report of Physical Count of Inventories (RPCI) Items
CREATE TABLE tbl_rpci_item (
    rpci_item_id INT PRIMARY KEY AUTO_INCREMENT,
    rpci_id INT NOT NULL,
    consumable_id INT NOT NULL,
    balance_per_card INT NOT NULL,
    on_hand_per_count INT NOT NULL,
    shortage_qty INT NOT NULL,
    shortage_value DECIMAL(10,2) NOT NULL,
    remarks VARCHAR(255) NULL,
    INDEX (rpci_id),
    INDEX (consumable_id),

    FOREIGN KEY (rpci_id) REFERENCES tbl_rpci(rpci_id) ON DELETE CASCADE,
    FOREIGN KEY (consumable_id) REFERENCES tbl_consumable(consumable_id) ON DELETE RESTRICT
);

-- 8.3 Report on the Physical Count of Property, Plant and Equipment (RPCPPE)
CREATE TABLE tbl_rpcppe (
    rpcppe_id INT PRIMARY KEY AUTO_INCREMENT,
    rpcppe_number VARCHAR(50) NOT NULL UNIQUE,
    as_of_date DATE NOT NULL,
    created_by_user_id INT NULL, -- Can be NULL if the creating user is deleted
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (created_by_user_id),
    FOREIGN KEY (created_by_user_id) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- 8.4 Report on the Physical Count of Property, Plant and Equipment (RPCPPE) Items
CREATE TABLE tbl_rpcppe_item (
    rpcppe_item_id INT PRIMARY KEY AUTO_INCREMENT,
    rpcppe_id INT NOT NULL,
    ppe_id INT NOT NULL,
    on_hand_per_count INT NOT NULL,
    shortage_qty INT NOT NULL,
    shortage_value DECIMAL(10,2) NOT NULL,
    remarks VARCHAR(255) NULL,
    INDEX (rpcppe_id),
    INDEX (ppe_id),

    FOREIGN KEY (rpcppe_id) REFERENCES tbl_rpcppe(rpcppe_id) ON DELETE CASCADE,
    FOREIGN KEY (ppe_id) REFERENCES tbl_ppe(ppe_id) ON DELETE RESTRICT
);

-- 8.5 System Log Table
CREATE TABLE IF NOT EXISTS tbl_system_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(50) NOT NULL,
    performed_by INT,
    details TEXT,
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);

-- #################################################################
-- # 9. DATABASE VIEWS                                             #
-- #################################################################

-- 9.1 View for Consumable Stock Card
CREATE OR REPLACE VIEW vw_consumable_stock_card AS
(
    -- Receipts from Purchase Orders
    SELECT
        c.consumable_id, 'Receipt' as transaction_type, c.date_received as transaction_date,
        COALESCE(d.date_created, c.date_received) as transaction_datetime,
        c.quantity_received as quantity_in, NULL as quantity_out, c.unit_cost,
        CONCAT('PO Receipt from DR# ', d.delivery_receipt_no) as reference,
        COALESCE(u_receipt.full_name, 'System') as person_in_charge
    FROM tbl_consumable c
    JOIN tbl_delivery d ON c.delivery_id = d.delivery_id
    LEFT JOIN tbl_user u_receipt ON d.received_by_user_id = u_receipt.user_id
    WHERE c.po_item_id IS NOT NULL
)
UNION ALL
(
    -- Receipts from Incoming ICS
    SELECT
        c.consumable_id, 'Receipt' as transaction_type, c.date_received as transaction_date,
        iics.date_created as transaction_datetime,
        c.quantity_received as quantity_in, NULL as quantity_out, c.unit_cost,
        CONCAT('Incoming ICS# ', iics.ics_number, ' from ', iics.source_office) as reference,
        COALESCE(u_receipt.full_name, 'System') as person_in_charge
    FROM tbl_consumable c
    JOIN tbl_incoming_ics_item iici ON c.incoming_ics_item_id = iici.incoming_ics_item_id
    JOIN tbl_incoming_ics iics ON iici.incoming_ics_id = iics.incoming_ics_id
    LEFT JOIN tbl_user u_receipt ON iics.received_by_user_id = u_receipt.user_id
    WHERE c.incoming_ics_item_id IS NOT NULL
)
UNION ALL
(
    -- Issuances via RIS
    SELECT
        ii.consumable_id,
        'Issuance' as transaction_type,
        i.date_issued as transaction_date,
        i.date_created as transaction_datetime,
        NULL as quantity_in,
        ii.quantity_issued as quantity_out,
        c.unit_cost,
        CONCAT('Issued to: ', i.issued_to) as reference,
        u_issuance.full_name as person_in_charge
    FROM tbl_issuance_item ii
    JOIN tbl_issuance i ON ii.issuance_id = i.issuance_id
    JOIN tbl_user u_issuance ON i.issued_by_user_id = u_issuance.user_id
    JOIN tbl_consumable c ON ii.consumable_id = c.consumable_id
)
UNION ALL
(
    -- Unit Conversions (Stock Out)
    SELECT
        uc.from_consumable_id as consumable_id,
        'Conversion Out' as transaction_type,
        uc.date_converted as transaction_date,
        uc.date_converted as transaction_datetime,
        NULL as quantity_in,
        uc.quantity_converted as quantity_out,
        c.unit_cost,
        'Converted to smaller units' as reference,
        u_conv.full_name as person_in_charge
    FROM tbl_unit_conversion uc
    JOIN tbl_user u_conv ON uc.converted_by_user_id = u_conv.user_id
    JOIN tbl_consumable c ON uc.from_consumable_id = c.consumable_id
)
UNION ALL
(
    -- Unit Conversions (Stock In)
    SELECT
        uc.to_consumable_id as consumable_id,
        'Conversion In' as transaction_type,
        uc.date_converted as transaction_date,
        uc.date_converted as transaction_datetime,
        (uc.quantity_converted * uc.conversion_factor) as quantity_in,
        NULL as quantity_out,
        c.unit_cost,
        'Converted from larger unit' as reference,
        u_conv.full_name as person_in_charge
    FROM tbl_unit_conversion uc
    JOIN tbl_user u_conv ON uc.converted_by_user_id = u_conv.user_id
    JOIN tbl_consumable c ON uc.to_consumable_id = c.consumable_id
);

-- #################################################################
-- # SAMS - DEFAULT REFERENCE DATA                                 #
-- #################################################################

-- 1. Insert default school information
INSERT INTO `tbl_school` (`school_id`, `school_name`, `school_code`, `logo`, `address`, `division_name`, `region_name`, `contact_number`) VALUES
(1, 'Pagadian City National Comprehensive High School', '303906', 'pcnchs logo - icon.png', 'Banale, Pagadian City', 'Pagadian City', 'Region IX', '09764063979');

-- 2. Insert user roles
INSERT INTO `tbl_role` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'User');

-- 3. Insert official school positions
INSERT INTO tbl_position (position_id, position_name) VALUES
(1, 'Administrative Aide VI'),
(2, 'Administrative Officer I'),
(3, 'Administrative Officer II'),
(4, 'Administrative Officer III'),
(5, 'Administrative Officer IV'),
(6, 'Administrative Officer V'),
(7, 'Head Teacher I'),
(8, 'Head Teacher II'),
(9, 'Head Teacher III'),
(10, 'Head Teacher IV'),
(11, 'Head Teacher V'),
(12, 'Head Teacher VI'),
(13, 'Master Teacher I'),
(14, 'Master Teacher II'),
(15, 'Master Teacher III'),
(16, 'Master Teacher IV'),
(17, 'Master Teacher V'),
(18, 'Principal I'),
(19, 'Principal II'),
(20, 'Principal III'),
(21, 'Principal IV'),
(22, 'Teacher I'),
(23, 'Teacher II'),
(24, 'Teacher III'),
(25, 'Teacher IV'),
(26, 'Administrative Assistant II'),
(27, 'Disbursing Officer II'),
(28, 'Senior Bookkeeper'),
(29, 'School Librarian II');


-- 4. Insert officer types (placeholders for user assignment)
-- NOTE: This data is now inserted after the users are created to prevent foreign key errors.

-- 5. Insert item units
INSERT INTO `tbl_unit` (`unit_id`, `unit_name`) VALUES
(1, 'Bag'),
(2, 'Bottle'),
(3, 'Box'),
(4, 'Can'),
(5, 'Cartridge'),
(6, 'Centimeter'),
(7, 'Dozen'),
(8, 'Gallon'),
(9, 'Gram'),
(10, 'Kilo'),
(11, 'Kilogram'),
(12, 'Liter'),
(13, 'Lot'),
(14, 'Meter'),
(15, 'Milliliter'),
(16, 'Pack'),
(17, 'Pair'),
(18, 'Pouch'),
(19, 'Piece'),
(20, 'Ream'),
(21, 'Roll'),
(22, 'Set'),
(23, 'Sheet'),
(24, 'Spool'),
(25, 'Unit');

-- 6. Insert purchase modes
INSERT INTO `tbl_purchase_mode` (`purchase_mode_id`, `mode_name`) VALUES
(1, 'Public Bidding'),
(2, 'Shopping');

-- 7. Insert delivery places
INSERT INTO `tbl_delivery_place` (`delivery_place_id`, `place_name`) VALUES
(1, 'Pagadian City National Comprehensive High School');

-- 8. Insert payment terms
INSERT INTO `tbl_payment_term` (`payment_term_id`, `term_description`) VALUES
(1, 'Cash on Delivery (COD)'),
(2, '30 Days Credit'),
(3, '45 Days Credit'),
(4, '60 Days Credit'),
(5, '90 Days Credit'),
(6, 'Staggered Payment');

-- 9. Insert delivery terms
INSERT INTO `tbl_delivery_term` (`delivery_term_id`, `term_description`) VALUES
(1, 'Immediate / Upon Delivery'),
(2, '7 Calendar Days'),
(3, '15 Calendar Days'),
(4, '30 Calendar Days'),
(5, '45 Calendar Days'),
(6, '60 Calendar Days'),
(7, '90 Calendar Days');

-- 10. Insert a default supplier
INSERT INTO `tbl_supplier` (`supplier_id`, `supplier_name`, `address`, `tin`, `contact_person`, `contact_no`) VALUES
(1, 'Golden Daughter Enterprises', 'Santiago District', '137-193-138-000', 'Leo Loven Dablo Lumacang', '09764063979');

-- 11. Insert top-level inventory types
INSERT INTO `tbl_inventory_type` (`inventory_type_id`, `inventory_type_name`) VALUES
(1, 'Consumable'),
(2, 'SEP'),
(3, 'PPE');

-- 12. Insert item categories with UACS codes, linked to inventory types
INSERT INTO `tbl_category` (`category_id`, `category_name`, `uacs_object_code`, `inventory_type_id`) VALUES
(1, 'Office Supplies', '5020399000', 1),
(2, 'Janitorial/Cleaning Supplies', '5020399010', 1),
(3, 'Laboratory and Chemical Supplies', '5020399100', 1),
(4, 'IT Consumables (e.g., Ink, Toner, CD/DVD)', '5020305000', 1),
(5, 'Training Supplies (e.g., Soldering Lead, Wires)', '1060502000', 1),
(6, 'Medical and Dental Supplies', '1060701000', 1),
(7, 'Small Tools and Equipment (<P50K)', '5020308000', 2),
(8, 'IT Accessories and Peripherals (<P50K)', '5003990200', 2),
(9, 'Semi-Expendable Furniture and Fixtures (<P50K)', '5020319000', 2),
(10, 'Learning Tools and Equipment (<P50K)', '5066399000', 2),
(11, 'ICT Equipment (Major, e.g., Servers, Projectors >P50K)', '1020399000', 3),
(12, 'Furniture and Fixtures (Major, >P50K)', '5020399230', 3),
(13, 'Technical-Vocational Livelihood (TVL) Equipment', '5020393300', 3),
(14, 'School Buildings and Structures', '5088399000', 3),
(15, 'Land Improvements', '5020393000', 3),
(16, 'Motor Vehicles', '5020333944', 3);

-- 13. Insert default number sequences for the current year
-- Note: The year is a placeholder. This should be dynamically set to the current year during initial setup by a PHP script.
INSERT INTO `tbl_po_number` (`serial`, `year`, `po_number_format`, `start_count`) VALUES ('default', '2025', 'PO-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_pn_number` (`serial`, `year`, `pn_number_format`, `start_count`) VALUES ('default', '2025', 'PN-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_item_number` (`serial`, `year`, `item_number_format`, `start_count`) VALUES ('default', '2025', 'SN-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_ris_number` (`serial`, `year`, `ris_number_format`, `start_count`) VALUES ('default', '2025', 'RIS-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_ics_number` (`serial`, `year`, `ics_number_format`, `start_count`) VALUES ('default', '2025', 'ICS-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_par_number` (`serial`, `year`, `par_number_format`, `start_count`) VALUES ('default', '2025', 'PAR-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_rpci_number` (`serial`, `year`, `rpci_number_format`, `start_count`) VALUES ('default', '2025', 'RPCI-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_rpcppe_number` (`serial`, `year`, `rpcppe_number_format`, `start_count`) VALUES ('default', '2025', 'RPCPPE-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;
INSERT INTO `tbl_iirup_number` (`serial`, `year`, `iirup_number_format`, `start_count`) VALUES ('default', '2025', 'IIRUP-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;

-- #################################################################
-- # IAR (Inspection and Acceptance Report) number sequence (from migrations)
-- #################################################################
INSERT INTO `tbl_iar_number` (`serial`, `year`, `iar_number_format`, `start_count`) VALUES ('default', '2025', 'IAR-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year=year;


INSERT INTO tbl_user (full_name, username, hashed_password, role_id, position_id, is_active, photo)
VALUES
('Acojedo, Ceceil Pulmano', 'acojedoceceilpulmano', '$2y$10$your_secure_hash_here', 2, 14, 1, 'default_user.png'), -- Master Teacher II
('Alangcas, Mirda Jane Cabrido', 'alangcasmirdajanecabrido', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Apog, Patrick Jamili', 'apogpatrickjamili', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Baga, Susan Salison', 'bagasusansalison', '$2y$10$your_secure_hash_here', 2, 9, 1, 'default_user.png'), -- Head Teacher III
('Basinang, Marven Morre', 'basinangmarvenmorre', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Bejenia, Marisa Martinez', 'bejeniamarisamartinez', '$2y$10$your_secure_hash_here', 2, 11, 1, 'default_user.png'), -- Head Teacher V
('Blen, Ezekiel Caba', 'blenezekielcaba', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Blen, Nelfa Ayawan', 'blennelfaayawan', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Cabrera, Rowel Repayo', 'cabrerarowelrepayo', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Cabual, Rosalyn Picpic', 'cabualrosalynpicpic', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Calo, Beverly Amacanin', 'calobeverlyamacanin', '$2y$10$your_secure_hash_here', 2, 13, 1, 'default_user.png'), -- Master Teacher I
('Canicon, Jeshila Comique', 'caniconjeshilacomique', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Cantila, Roselie Nano', 'cantilaroselienano', '$2y$10$your_secure_hash_here', 2, 26, 1, 'default_user.png'), -- Administrative Assistant II
('Daclan, Genevive Alajeño', 'daclangenevivealajeño', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Dela Cerna, Mariecon Oronce', 'delacernamarieconoronce', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Dela Rama, Maria Crisanie Pearl Quipot', 'delaramamariacrisaniepearlquipot', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Dolia, Jessie P', 'doliajessiep', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Duldulao, Ednalyn Claveria', 'duldulaoednalynclaveria', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Esma, Arceli Luna', 'esmaarceliluna', '$2y$10$your_secure_hash_here', 2, 11, 1, 'default_user.png'), -- Head Teacher V
('Espanol, Luz-Concepcion Sumalinog', 'espanolluzconcepcionsumalinog', '$2y$10$your_secure_hash_here', 2, 14, 1, 'default_user.png'), -- Master Teacher II
('Fulloso, Nathalie Salamania', 'fullosonathaliesalamania', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Gahapon, Rheyma Balignot', 'gahaponrheymabalignot', '$2y$10$your_secure_hash_here', 2, 3, 1, 'default_user.png'), -- Administrative Officer II
('Gangoso, Mary Jane Gamose', 'gangosomaryjanegamose', '$2y$10$your_secure_hash_here', 2, 27, 1, 'default_user.png'), -- Disbursing Officer II
('Guerra, Rene Cedeño', 'guerrarenecedeño', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Guillena, Myrel Kilme', 'guillenamyrelkilme', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Gumibao, Neco Neptali Ellorando', 'gumibaoneconeptaliellorando', '$2y$10$your_secure_hash_here', 2, 28, 1, 'default_user.png'), -- Senior Bookkeeper
('Halasan, Nancy May Navarro', 'halasannancymaynavarro', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Ignacio, Almika Deluvio', 'ignacioalmikadeluvio', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Juquino, Maricel Salvacion', 'juquinomaricelsalvacion', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Kagatan, Rosa Obnimaga', 'kagatanrosaobnimaga', '$2y$10$your_secure_hash_here', 2, 13, 1, 'default_user.png'), -- Master Teacher I
('Lasala, Alna Acuña', 'lasalaalnaacuña', '$2y$10$your_secure_hash_here', 2, 18, 1, 'default_user.png'), -- School Principal I
('Lumacang, Leo Loven Dablo', 'lumacangleolovendablo', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Lusay, Julito D', 'lusayjulitod', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Montealto, Gina Gimena', 'montealtoginagimena', '$2y$10$your_secure_hash_here', 2, 10, 1, 'default_user.png'), -- Head Teacher IV
('Montegrande, Cresemie Megano', 'montegrandecresemiemegano', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Niez, Viverly Umbania', 'niezviverlyumbani', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Pano, Alvin Bertulfo', 'panoalvinbertulfo', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Pascua, Rhea Ceniza', 'pascuarheaceniza', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Posadas, Richard Jr Soledad', 'posadasrichardjrsoledad', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Puerto, Gerilee Grace Babao', 'puertogerileegracebabao', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Quieta, Ronnil Cabillon', 'quietaronnilcabillon', '$2y$10$your_secure_hash_here', 2, 14, 1, 'default_user.png'), -- Master Teacher II
('Quipot, Ma. Luisa Napolereyes', 'quipotmaluisanapolereyes', '$2y$10$your_secure_hash_here', 2, 8, 1, 'default_user.png'), -- Head Teacher II
('Rebugar, Jumaima Adbul Rahim', 'rebugarjumaimaadbulrahim', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Rivera, Karen Agnes Zuasola', 'riverakarenagneszuasola', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Roda, Genelyn Nillas', 'rodagenelynnillas', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Salomes, Recille Jane Cristoria', 'salomesrecillejanecristoria', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Sapong, Ronaldo Saldua', 'sapongronaldosaldua', '$2y$10$your_secure_hash_here', 2, 7, 1, 'default_user.png'), -- Head Teacher I
('Sarmiento, Elizabeth Cudal', 'sarmientoelizabethcudal', '$2y$10$your_secure_hash_here', 2, 23, 1, 'default_user.png'), -- Teacher II
('Sebial, Rhea Jastia', 'sebialrheajastia', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Suan, Mary Jean Coyme', 'suanmaryjeancoyme', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Takilid, Jovelyn Ungang', 'takilidjovelynungang', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'), -- Teacher I
('Tolorio, Al James Donaire', 'tolorioaljamesdonaire', '$2y$10$your_secure_hash_here', 2, 26, 1, 'default_user.png'), -- Administrative Assistant II
('Tomol, Ruth Trio', 'tomolruthtrio', '$2y$10$your_secure_hash_here', 2, 14, 1, 'default_user.png'), -- Master Teacher II
('Uy, Anecita Luna', 'uyanecitaluna', '$2y$10$your_secure_hash_here', 2, 10, 1, 'default_user.png'), -- Head Teacher IV
('Vallejo, Ellen Pasco', 'vallejoellenpasco', '$2y$10$your_secure_hash_here', 2, 24, 1, 'default_user.png'), -- Teacher III
('Villahermosa, Neil Panes', 'villahermosaneilpanes', '$2y$10$your_secure_hash_here', 2, 22, 1, 'default_user.png'); -- Teacher I

-- 4. Insert officer types (now with a default user)
-- Assigning a default user (ID=1) to prevent errors in fresh installations where no officer is assigned yet.
INSERT INTO `tbl_officers` (`officer_id`, `officer_type`, `user_id`) VALUES
(1, 'Approving Officer', 1),
(2, 'Funds Available Officer', 1),
(3, 'Accountable Officer', 1);