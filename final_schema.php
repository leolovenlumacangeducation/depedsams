<?php
/**
 * final_schema.php
 * 
 * SAMS - SCHEMA CREATION SCRIPT
 * This script creates all necessary tables, views, and inserts reference data.
 * It uses CREATE TABLE IF NOT EXISTS so it can be re-run safely.
 * 
 * To execute:
 * 1. Via command line: php final_schema.php
 * 2. Via browser: open in web browser if PHP execution is allowed
 * 
 * Features:
 * - Creates full database schema with proper relationships
 * - Sets up all necessary indexes and constraints
 * - Initializes reference data (units, categories, etc.)
 * - Creates views for reporting and analysis
 */

// Prefer to reuse existing db.php if present to avoid duplicate credentials
$pdo = null;
if (file_exists(__DIR__ . '/db.php')) {
  // db.php will instantiate $pdo or exit with an error page. Use that.
  require_once __DIR__ . '/db.php';
  if (isset($pdo) && $pdo instanceof PDO) {
    // Reuse existing PDO
  } else {
    // If db.php didn't provide $pdo, fall back to local connection below
    $pdo = null;
  }
}

// Ensure DB charset variable is available for SQL templates.
// `db.php` uses `$charset`; map it to `$DB_CHARSET` if needed.
if (!isset($DB_CHARSET)) {
    if (isset($charset) && is_string($charset) && $charset !== '') {
        $DB_CHARSET = $charset;
    } else {
        $DB_CHARSET = 'utf8mb4';
    }
}

if (!isset($pdo) || !$pdo instanceof PDO) {
  // Local fallback credentials (used when running this script standalone)
  $DB_HOST = 'localhost';
  $DB_NAME = 'samspikpok_db';
  $DB_USER = 'root';
  $DB_PASS = 'root';
  $DB_CHARSET = 'utf8mb4';

  $options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ];

  try {
    $dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
  } catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
  }
}


$statements = [];

// migrations table
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// school
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_school` (
  `school_id` INT PRIMARY KEY AUTO_INCREMENT,
  `school_name` VARCHAR(255) NOT NULL,
  `school_code` VARCHAR(50) UNIQUE,
  `logo` VARCHAR(255) NULL,
  `address` TEXT NULL,
  `division_name` VARCHAR(100),
  `region_name` VARCHAR(100),
  `contact_number` VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// positions, roles, users
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_position` (
  `position_id` INT PRIMARY KEY AUTO_INCREMENT,
  `position_name` VARCHAR(150) NOT NULL UNIQUE
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_role` (
  `role_id` INT PRIMARY KEY AUTO_INCREMENT,
  `role_name` VARCHAR(50) NOT NULL UNIQUE
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_user` (
  `user_id` INT PRIMARY KEY AUTO_INCREMENT,
  `full_name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `hashed_password` VARCHAR(255) NOT NULL,
  `role_id` INT NOT NULL,
  `position_id` INT NULL,
  `is_active` BOOLEAN NOT NULL DEFAULT TRUE,
  `photo` VARCHAR(255) DEFAULT 'default_user.png',
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`role_id`),
  INDEX (`position_id`),
  FOREIGN KEY (`position_id`) REFERENCES `tbl_position`(`position_id`) ON DELETE SET NULL,
  FOREIGN KEY (`role_id`) REFERENCES `tbl_role`(`role_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// officers
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_officers` (
  `officer_id` INT PRIMARY KEY AUTO_INCREMENT,
  `officer_type` VARCHAR(100) NOT NULL UNIQUE,
  `user_id` INT NULL,
  INDEX (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// suppliers and lookup tables
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_supplier` (
  `supplier_id` INT PRIMARY KEY AUTO_INCREMENT,
  `supplier_name` VARCHAR(255) NOT NULL,
  `address` TEXT NULL,
  `tin` VARCHAR(50) NULL UNIQUE,
  `contact_person` VARCHAR(150) NULL,
  `contact_no` VARCHAR(50) NULL
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_purchase_mode` (
  `purchase_mode_id` INT PRIMARY KEY AUTO_INCREMENT,
  `mode_name` VARCHAR(100) NOT NULL UNIQUE
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_delivery_place` (
  `delivery_place_id` INT PRIMARY KEY AUTO_INCREMENT,
  `place_name` VARCHAR(255) NOT NULL
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_delivery_term` (
  `delivery_term_id` INT PRIMARY KEY AUTO_INCREMENT,
  `term_description` VARCHAR(255) NOT NULL
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_payment_term` (
  `payment_term_id` INT PRIMARY KEY AUTO_INCREMENT,
  `term_description` VARCHAR(255) NOT NULL
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// unit
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_unit` (
  `unit_id` INT PRIMARY KEY AUTO_INCREMENT,
  `unit_name` VARCHAR(50) NOT NULL UNIQUE
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// inventory types and categories
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_inventory_type` (
  `inventory_type_id` INT PRIMARY KEY AUTO_INCREMENT,
  `inventory_type_name` VARCHAR(100) NOT NULL UNIQUE
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_category` (
  `category_id` INT PRIMARY KEY AUTO_INCREMENT,
  `category_name` VARCHAR(150) NOT NULL UNIQUE,
  `uacs_object_code` VARCHAR(50) NULL,
  `inventory_type_id` INT NOT NULL,
  INDEX (`inventory_type_id`),
  FOREIGN KEY (`inventory_type_id`) REFERENCES `tbl_inventory_type`(`inventory_type_id`) ON DELETE RESTRICT
)
ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// number sequence tables (explicit names to match FK references)
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_po_number` (
  `po_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `po_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_pn_number` (
  `pn_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `pn_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_pn_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_item_number` (
  `item_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `item_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_item_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_ris_number` (
  `ris_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `ris_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_ris_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_ics_number` (
  `ics_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `ics_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_ics_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_par_number` (
  `par_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `par_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_par_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_rpci_number` (
  `rpci_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `rpci_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_rpci_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_rpcppe_number` (
  `rpcppe_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `rpcppe_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_rpcppe_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_iirup_number` (
  `iirup_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `iirup_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_iirup_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// Note: the above generic sequence creation will be followed by more specific
// table definitions (po, po_item, delivery, etc.)

// Purchase order header and items
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_po` (
  `po_id` INT PRIMARY KEY AUTO_INCREMENT,
  `po_number` VARCHAR(50) NOT NULL UNIQUE,
  `supplier_id` INT NOT NULL,
  `purchase_mode_id` INT NOT NULL,
  `delivery_place_id` INT NOT NULL,
  `delivery_term_id` INT NOT NULL,
  `payment_term_id` INT NOT NULL,
  `order_date` DATE NOT NULL,
  INDEX (`supplier_id`),
  INDEX (`purchase_mode_id`),
  INDEX (`delivery_place_id`),
  INDEX (`delivery_term_id`),
  INDEX (`payment_term_id`),
  `status` ENUM('Pending', 'Delivered') NOT NULL DEFAULT 'Pending',
  FOREIGN KEY (`supplier_id`) REFERENCES `tbl_supplier`(`supplier_id`),
  FOREIGN KEY (`purchase_mode_id`) REFERENCES `tbl_purchase_mode`(`purchase_mode_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`delivery_place_id`) REFERENCES `tbl_delivery_place`(`delivery_place_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`delivery_term_id`) REFERENCES `tbl_delivery_term`(`delivery_term_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`payment_term_id`) REFERENCES `tbl_payment_term`(`payment_term_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_po_item` (
  `po_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `po_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `description` TEXT NOT NULL,
  `quantity` INT NOT NULL,
  `unit_id` INT NOT NULL,
  `unit_cost` DECIMAL(10, 2) NOT NULL,
  INDEX (`po_id`),
  INDEX (`category_id`),
  INDEX (`unit_id`),
  FOREIGN KEY (`po_id`) REFERENCES `tbl_po`(`po_id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `tbl_category`(`category_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`unit_id`) REFERENCES `tbl_unit`(`unit_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_delivery` (
  `delivery_id` INT PRIMARY KEY AUTO_INCREMENT,
  `po_id` INT NOT NULL,
  `delivery_receipt_no` VARCHAR(100) NOT NULL,
  `date_received` DATE NOT NULL,
  `received_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`po_id`),
  INDEX (`received_by_user_id`),
  FOREIGN KEY (`po_id`) REFERENCES `tbl_po`(`po_id`) ON DELETE CASCADE,
  FOREIGN KEY (`received_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  UNIQUE KEY uk_po_dr (`po_id`, `delivery_receipt_no`)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_delivery_item` (
  `delivery_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `delivery_id` INT NOT NULL,
  `po_item_id` INT NOT NULL,
  `quantity_delivered` INT NOT NULL,
  INDEX (`delivery_id`),
  INDEX (`po_item_id`),
  FOREIGN KEY (`delivery_id`) REFERENCES `tbl_delivery`(`delivery_id`) ON DELETE CASCADE,
  FOREIGN KEY (`po_item_id`) REFERENCES `tbl_po_item`(`po_item_id`) ON DELETE RESTRICT,
  UNIQUE KEY uk_delivery_po_item (`delivery_id`, `po_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// Incoming ICS
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_incoming_ics` (
`incoming_ics_id` int(11) NOT NULL AUTO_INCREMENT,
`ics_number` varchar(100) NOT NULL,
`source_office` varchar(255) NOT NULL,
`issued_by_name` varchar(255) NOT NULL,
`issued_by_position` varchar(150) DEFAULT NULL,
`date_received` date NOT NULL,
`received_by_user_id` int(11) NOT NULL,
`date_created` datetime NOT NULL DEFAULT current_timestamp(),
PRIMARY KEY (`incoming_ics_id`),
UNIQUE KEY `uk_ics_number_source` (`ics_number`,`source_office`),
KEY `received_by_user_id` (`received_by_user_id`),
CONSTRAINT `fk_incoming_ics_user` FOREIGN KEY (`received_by_user_id`) REFERENCES `tbl_user` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_incoming_ics_item` (
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
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// Consumable (more detailed)
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_consumable` (
  `consumable_id` INT PRIMARY KEY AUTO_INCREMENT,
  `po_item_id` INT(11) NULL,
  `incoming_ics_item_id` INT(11) NULL DEFAULT NULL,
  `stock_number` VARCHAR(100) NULL,
  `stock_number_id` INT NULL,
  `quantity_received` INT NOT NULL,
  `unit_id` INT NOT NULL, -- The unit of the item at the time of this entry
  `unit_cost` DECIMAL(10, 2) NOT NULL,
  `current_stock` INT NOT NULL,
  `date_acquired` DATE NOT NULL,
  `delivery_id` INT NULL,
  `parent_consumable_id` INT NULL,
  `custodian_user_id` INT NULL, -- The user responsible for this stock
  `photo` VARCHAR(255) DEFAULT 'consumable_default.png',
  INDEX (`po_item_id`),
  INDEX (`stock_number_id`),
  INDEX (`delivery_id`),
  INDEX (`parent_consumable_id`),
  INDEX (`unit_id`),
  FOREIGN KEY (`po_item_id`) REFERENCES `tbl_po_item`(`po_item_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`stock_number_id`) REFERENCES `tbl_item_number`(`item_number_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`delivery_id`) REFERENCES `tbl_delivery`(`delivery_id`) ON DELETE SET NULL,
  FOREIGN KEY (`parent_consumable_id`) REFERENCES `tbl_consumable`(`consumable_id`) ON DELETE SET NULL,
  FOREIGN KEY (`custodian_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`incoming_ics_item_id`) REFERENCES `tbl_incoming_ics_item`(`incoming_ics_item_id`) ON DELETE RESTRICT -- Prevent deletion of source if item is inventoried
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// SEP (detailed)
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_sep` (
  `sep_id` INT PRIMARY KEY AUTO_INCREMENT,
  `po_item_id` INT(11) NULL,
  `incoming_ics_item_id` INT NULL DEFAULT NULL,
  `property_number` VARCHAR(100) NOT NULL UNIQUE,
  `pn_number_id` INT(11) NULL,
  `serial_number` VARCHAR(100) NULL,
  `brand_name` VARCHAR(150) NULL,
  `estimated_useful_life` INT NULL,
  `date_acquired` DATE NOT NULL,
  `delivery_id` INT NULL,
  `current_location` VARCHAR(255),
  `current_condition` ENUM('Serviceable', 'Unserviceable', 'For Repair', 'Disposed') NOT NULL DEFAULT 'Serviceable',
  `assigned_to_user_id` INT,
  `photo` VARCHAR(255) DEFAULT 'sep_default.png',
  `has_been_assigned` BOOLEAN NOT NULL DEFAULT 0,
  INDEX (`po_item_id`),
  INDEX (`pn_number_id`),
  INDEX (`assigned_to_user_id`),
  INDEX (`delivery_id`),
  FOREIGN KEY (`po_item_id`) REFERENCES `tbl_po_item`(`po_item_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`pn_number_id`) REFERENCES `tbl_pn_number`(`pn_number_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`delivery_id`) REFERENCES `tbl_delivery`(`delivery_id`) ON DELETE SET NULL,
  FOREIGN KEY (`incoming_ics_item_id`) REFERENCES `tbl_incoming_ics_item`(`incoming_ics_item_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// PPE (detailed)
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_ppe` (
  `ppe_id` INT PRIMARY KEY AUTO_INCREMENT,
  `po_item_id` INT(11) NULL,
  `incoming_ics_item_id` INT(11) NULL DEFAULT NULL,
  `property_number` VARCHAR(100) NOT NULL UNIQUE,
  `pn_number_id` INT(11) NULL,
  `model_number` VARCHAR(100) NULL,
  `serial_number` VARCHAR(100) NULL,
  `date_acquired` DATE NOT NULL,
  `delivery_id` INT NULL,
  `date_disposed` DATE NULL,
  `current_location` VARCHAR(255),
  `current_condition` ENUM('Serviceable', 'Unserviceable', 'For Repair', 'Disposed') NOT NULL DEFAULT 'Serviceable',
  `assigned_to_user_id` INT NULL,
  `photo` VARCHAR(255) DEFAULT 'ppe_default.png',
  `has_been_assigned` BOOLEAN NOT NULL DEFAULT 0,
  INDEX (`po_item_id`),
  INDEX (`pn_number_id`),
  INDEX (`assigned_to_user_id`),
  INDEX (`delivery_id`),
  FOREIGN KEY (`po_item_id`) REFERENCES `tbl_po_item`(`po_item_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`pn_number_id`) REFERENCES `tbl_pn_number`(`pn_number_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`assigned_to_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`delivery_id`) REFERENCES `tbl_delivery`(`delivery_id`) ON DELETE SET NULL,
  UNIQUE KEY `uk_ppe_serial_number` (`serial_number`)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// Unit conversion
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_unit_conversion` (
  `conversion_id` INT PRIMARY KEY AUTO_INCREMENT,
  `from_consumable_id` INT NOT NULL,
  `to_consumable_id` INT NOT NULL,
  `quantity_converted` INT NOT NULL,
  `conversion_factor` INT NOT NULL,
  `converted_by_user_id` INT NOT NULL,
  `date_converted` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`to_consumable_id`),
  INDEX (`converted_by_user_id`),
  FOREIGN KEY (`from_consumable_id`) REFERENCES `tbl_consumable`(`consumable_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`to_consumable_id`) REFERENCES `tbl_consumable`(`consumable_id`) ON DELETE RESTRICT,
  FOREIGN KEY (`converted_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE RESTRICT,
  UNIQUE KEY uk_from_consumable (`from_consumable_id`)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// Issuance and items
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_issuance` (
  `issuance_id` INT PRIMARY KEY AUTO_INCREMENT,
  `ris_number` VARCHAR(50) NOT NULL UNIQUE,
  `issued_to` VARCHAR(255) NOT NULL,
  `date_issued` DATE NOT NULL,
  `issued_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`issued_by_user_id`),
  FOREIGN KEY (`issued_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_issuance_item` (
  `issuance_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `issuance_id` INT NOT NULL,
  `consumable_id` INT NOT NULL,
  `quantity_issued` INT NOT NULL,
  INDEX (`issuance_id`),
  INDEX (`consumable_id`),
  FOREIGN KEY (`issuance_id`) REFERENCES `tbl_issuance`(`issuance_id`) ON DELETE CASCADE,
  FOREIGN KEY (`consumable_id`) REFERENCES `tbl_consumable`(`consumable_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// ICS and items
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_ics` (
  `ics_id` INT PRIMARY KEY AUTO_INCREMENT,
  `ics_number` VARCHAR(50) NOT NULL UNIQUE,
  `issued_to_user_id` INT NULL,
  `location` VARCHAR(255) NULL,
  `date_issued` DATE NOT NULL,
  `status` ENUM('Active', 'Voided') NOT NULL DEFAULT 'Active',
  `issued_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`issued_to_user_id`),
  INDEX (`issued_by_user_id`),
  FOREIGN KEY (`issued_to_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`issued_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_ics_item` (
  `ics_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `ics_id` INT NOT NULL,
  `sep_id` INT NOT NULL,
  INDEX (`ics_id`),
  INDEX (`sep_id`),
  FOREIGN KEY (`ics_id`) REFERENCES `tbl_ics`(`ics_id`) ON DELETE CASCADE,
  FOREIGN KEY (`sep_id`) REFERENCES `tbl_sep`(`sep_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// PAR and items
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_par` (
  `par_id` INT PRIMARY KEY AUTO_INCREMENT,
  `par_number` VARCHAR(50) NOT NULL UNIQUE,
  `issued_to_user_id` INT NULL,
  `location` VARCHAR(255) NULL,
  `date_issued` DATE NOT NULL,
  `status` ENUM('Active', 'Voided') NOT NULL DEFAULT 'Active',
  `issued_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`issued_to_user_id`),
  INDEX (`issued_by_user_id`),
  FOREIGN KEY (`issued_to_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`issued_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_par_item` (
  `par_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `par_id` INT NOT NULL,
  `ppe_id` INT NOT NULL,
  INDEX (`par_id`),
  INDEX (`ppe_id`),
  FOREIGN KEY (`par_id`) REFERENCES `tbl_par`(`par_id`) ON DELETE CASCADE,
  FOREIGN KEY (`ppe_id`) REFERENCES `tbl_ppe`(`ppe_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// PPE history (detailed)
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_ppe_history` (
  `history_id` INT PRIMARY KEY AUTO_INCREMENT,
  `ppe_id` INT NOT NULL,
  `transaction_date` DATE NOT NULL,
  `transaction_type` ENUM('Receipt', 'Assignment', 'Return', 'Disposal') NOT NULL,
  `reference` VARCHAR(255) NULL,
  `from_user_id` INT NULL,
  `to_user_id` INT NULL,
  `notes` VARCHAR(255) NULL,
  INDEX (`ppe_id`),
  INDEX (`from_user_id`),
  INDEX (`to_user_id`),
  FOREIGN KEY (`ppe_id`) REFERENCES `tbl_ppe`(`ppe_id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`to_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// IIRUP and items
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_iirup` (
  `iirup_id` INT PRIMARY KEY AUTO_INCREMENT,
  `iirup_number` VARCHAR(50) NOT NULL UNIQUE,
  `as_of_date` DATE NOT NULL,
  `disposal_method` VARCHAR(100) NULL,
  `status` ENUM('Draft', 'For Approval', 'Approved', 'Disposed') NOT NULL DEFAULT 'Draft',
  `created_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`created_by_user_id`),
  FOREIGN KEY (`created_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_iirup_item` (
  `iirup_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `iirup_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `asset_type` ENUM('PPE', 'SEP') NOT NULL,
  `remarks` TEXT NULL,
  INDEX (`iirup_id`),
  FOREIGN KEY (`iirup_id`) REFERENCES `tbl_iirup`(`iirup_id`) ON DELETE CASCADE,
  UNIQUE KEY uk_iirup_asset (`iirup_id`, `asset_id`, `asset_type`)
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// Reporting tables (RPCI, RPCPPE)
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_rpci` (
  `rpci_id` INT PRIMARY KEY AUTO_INCREMENT,
  `rpci_number` VARCHAR(50) NOT NULL UNIQUE,
  `as_of_date` DATE NOT NULL,
  `created_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`created_by_user_id`),
  FOREIGN KEY (`created_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_rpci_item` (
  `rpci_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `rpci_id` INT NOT NULL,
  `consumable_id` INT NOT NULL,
  `balance_per_card` INT NOT NULL,
  `on_hand_per_count` INT NOT NULL,
  `shortage_qty` INT NOT NULL,
  `shortage_value` DECIMAL(10,2) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  INDEX (`rpci_id`),
  INDEX (`consumable_id`),
  FOREIGN KEY (`rpci_id`) REFERENCES `tbl_rpci`(`rpci_id`) ON DELETE CASCADE,
  FOREIGN KEY (`consumable_id`) REFERENCES `tbl_consumable`(`consumable_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_rpcppe` (
  `rpcppe_id` INT PRIMARY KEY AUTO_INCREMENT,
  `rpcppe_number` VARCHAR(50) NOT NULL UNIQUE,
  `as_of_date` DATE NOT NULL,
  `created_by_user_id` INT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`created_by_user_id`),
  FOREIGN KEY (`created_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_rpcppe_item` (
  `rpcppe_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `rpcppe_id` INT NOT NULL,
  `ppe_id` INT NOT NULL,
  `on_hand_per_count` INT NOT NULL,
  `shortage_qty` INT NOT NULL,
  `shortage_value` DECIMAL(10,2) NOT NULL,
  `remarks` VARCHAR(255) NULL,
  INDEX (`rpcppe_id`),
  INDEX (`ppe_id`),
  FOREIGN KEY (`rpcppe_id`) REFERENCES `tbl_rpcppe`(`rpcppe_id`) ON DELETE CASCADE,
  FOREIGN KEY (`ppe_id`) REFERENCES `tbl_ppe`(`ppe_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// system log
$statements[] = <<<SQL
CREATE TABLE IF NOT EXISTS `tbl_system_log` (
  `log_id` INT PRIMARY KEY AUTO_INCREMENT,
  `action` VARCHAR(50) NOT NULL,
  `performed_by` INT,
  `details` TEXT,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`performed_by`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET={$DB_CHARSET} COLLATE=utf8mb4_unicode_ci;
SQL;

// view: consumable stock card (create after tables)
$statements[] = <<<SQL
CREATE OR REPLACE VIEW `vw_consumable_stock_card` AS
(
  SELECT
    c.consumable_id, 'Receipt' as transaction_type, c.date_received as transaction_date,
    COALESCE(d.date_created, c.date_acquired) as transaction_datetime,
    c.quantity_received as quantity_in, NULL as quantity_out, c.unit_cost,
    CONCAT('PO Receipt from DR# ', d.delivery_receipt_no) as reference,
    COALESCE(u_receipt.full_name, 'System') as person_in_charge
  FROM tbl_consumable c
  LEFT JOIN tbl_delivery d ON c.delivery_id = d.delivery_id
  LEFT JOIN tbl_user u_receipt ON d.received_by_user_id = u_receipt.user_id
  WHERE c.po_item_id IS NOT NULL
)
UNION ALL
(
  SELECT
    c.consumable_id, 'Receipt' as transaction_type, c.date_received as transaction_date,
    iics.date_created as transaction_datetime, -- Note: date_received is aliased from consumable table
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
SQL;

// Execute statements one-by-one (safer than executing big multi-statement string)
foreach ($statements as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK] Statement " . ($i+1) . " executed.\n";
    } catch (PDOException $e) {
        echo "[ERROR] Statement " . ($i+1) . " failed: " . $e->getMessage() . "\n";
        // Stop on error to avoid partially applied schema
        exit(1);
    }
}

// Insert default reference data (idempotent). These are safe to run multiple times.
$defaultInserts = [
  // school
  "INSERT INTO `tbl_school` (`school_id`, `school_name`, `school_code`, `logo`, `address`, `division_name`, `region_name`, `contact_number`) VALUES (1, 'Pagadian City National Comprehensive High School', '303906', 'pcnchs logo - icon.png', 'Banale, Pagadian City', 'Pagadian City', 'Region IX', '09764063979') ON DUPLICATE KEY UPDATE school_name = VALUES(school_name)",

  // roles
  "INSERT INTO `tbl_role` (`role_id`, `role_name`) VALUES (1, 'Admin'), (2, 'User') ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)",

  // positions
  "INSERT INTO `tbl_position` (position_id, position_name) VALUES
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
(29, 'School Librarian II')
ON DUPLICATE KEY UPDATE position_name = VALUES(position_name)",

  // units
  "INSERT INTO `tbl_unit` (`unit_id`, `unit_name`) VALUES
(1, 'Bag'),(2, 'Bottle'),(3, 'Box'),(4, 'Can'),(5, 'Cartridge'),(6, 'Centimeter'),(7, 'Dozen'),(8, 'Gallon'),(9, 'Gram'),(10, 'Kilo'),(11, 'Kilogram'),(12, 'Liter'),(13, 'Lot'),(14, 'Meter'),(15, 'Milliliter'),(16, 'Pack'),(17, 'Pair'),(18, 'Pouch'),(19, 'Piece'),(20, 'Ream'),(21, 'Roll'),(22, 'Set'),(23, 'Sheet'),(24, 'Spool'),(25, 'Unit') ON DUPLICATE KEY UPDATE unit_name = VALUES(unit_name)",

  // purchase modes
  "INSERT INTO `tbl_purchase_mode` (`purchase_mode_id`, `mode_name`) VALUES (1, 'Public Bidding'), (2, 'Shopping') ON DUPLICATE KEY UPDATE mode_name = VALUES(mode_name)",

  // delivery places
  "INSERT INTO `tbl_delivery_place` (`delivery_place_id`, `place_name`) VALUES (1, 'Pagadian City National Comprehensive High School') ON DUPLICATE KEY UPDATE place_name = VALUES(place_name)",

  // payment terms
  "INSERT INTO `tbl_payment_term` (`payment_term_id`, `term_description`) VALUES
(1, 'Cash on Delivery (COD)'),
(2, '30 Days Credit'),
(3, '45 Days Credit'),
(4, '60 Days Credit'),
(5, '90 Days Credit'),
(6, 'Staggered Payment')
ON DUPLICATE KEY UPDATE term_description = VALUES(term_description)",

  // delivery terms
  "INSERT INTO `tbl_delivery_term` (`delivery_term_id`, `term_description`) VALUES
(1, 'Immediate / Upon Delivery'),
(2, '7 Calendar Days'),
(3, '15 Calendar Days'),
(4, '30 Calendar Days'),
(5, '45 Calendar Days'),
(6, '60 Calendar Days'),
(7, '90 Calendar Days')
ON DUPLICATE KEY UPDATE term_description = VALUES(term_description)",

  // default supplier
  "INSERT INTO `tbl_supplier` (`supplier_id`, `supplier_name`, `address`, `tin`, `contact_person`, `contact_no`) VALUES (1, 'Golden Daughter Enterprises', 'Santiago District', '137-193-138-000', 'Leo Loven Dablo Lumacang', '09764063979') ON DUPLICATE KEY UPDATE supplier_name = VALUES(supplier_name)",

  // inventory types
  "INSERT INTO `tbl_inventory_type` (`inventory_type_id`, `inventory_type_name`) VALUES (1, 'Consumable'), (2, 'SEP'), (3, 'PPE') ON DUPLICATE KEY UPDATE inventory_type_name = VALUES(inventory_type_name)",

  // categories
  "INSERT INTO `tbl_category` (`category_id`, `category_name`, `uacs_object_code`, `inventory_type_id`) VALUES
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
(16, 'Motor Vehicles', '5020333944', 3)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name), uacs_object_code = VALUES(uacs_object_code), inventory_type_id = VALUES(inventory_type_id)",

  // number sequences (year placeholder - safe to run)
  "INSERT INTO `tbl_po_number` (`serial`, `year`, `po_number_format`, `start_count`) VALUES ('default', '2025', 'PO-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_pn_number` (`serial`, `year`, `pn_number_format`, `start_count`) VALUES ('default', '2025', 'PN-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_item_number` (`serial`, `year`, `item_number_format`, `start_count`) VALUES ('default', '2025', 'SN-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_ris_number` (`serial`, `year`, `ris_number_format`, `start_count`) VALUES ('default', '2025', 'RIS-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_ics_number` (`serial`, `year`, `ics_number_format`, `start_count`) VALUES ('default', '2025', 'ICS-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_par_number` (`serial`, `year`, `par_number_format`, `start_count`) VALUES ('default', '2025', 'PAR-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_rpci_number` (`serial`, `year`, `rpci_number_format`, `start_count`) VALUES ('default', '2025', 'RPCI-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_rpcppe_number` (`serial`, `year`, `rpcppe_number_format`, `start_count`) VALUES ('default', '2025', 'RPCPPE-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",
  "INSERT INTO `tbl_iirup_number` (`serial`, `year`, `iirup_number_format`, `start_count`) VALUES ('default', '2025', 'IIRUP-{YYYY}-{NNNN}', 1) ON DUPLICATE KEY UPDATE year = VALUES(year)",

  // users (generate a bcrypt hash and build the VALUES list safely to avoid $ interpolation)
];

// generate a random password and hash it for seeded users
try {
  $randomPassword = bin2hex(random_bytes(8)); // 16 hex chars
} catch (Exception $e) {
  // fallback
  $randomPassword = bin2hex(openssl_random_pseudo_bytes(8));
}
$defaultHash = password_hash($randomPassword, PASSWORD_BCRYPT);
$quotedHash = $pdo->quote($defaultHash);

$userValues = [
  "('Acojedo, Ceceil Pulmano', 'acojedoceceilpulmano', {$quotedHash}, 2, 14, 1, 'default_user.png')",
  "('Alangcas, Mirda Jane Cabrido', 'alangcasmirdajanecabrido', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Apog, Patrick Jamili', 'apogpatrickjamili', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Baga, Susan Salison', 'bagasusansalison', {$quotedHash}, 2, 9, 1, 'default_user.png')",
  "('Basinang, Marven Morre', 'basinangmarvenmorre', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Bejenia, Marisa Martinez', 'bejeniamarisamartinez', {$quotedHash}, 2, 11, 1, 'default_user.png')",
  "('Blen, Ezekiel Caba', 'blenezekielcaba', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Blen, Nelfa Ayawan', 'blennelfaayawan', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Cabrera, Rowel Repayo', 'cabrerarowelrepayo', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Cabual, Rosalyn Picpic', 'cabualrosalynpicpic', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Calo, Beverly Amacanin', 'calobeverlyamacanin', {$quotedHash}, 2, 13, 1, 'default_user.png')",
  "('Canicon, Jeshila Comique', 'caniconjeshilacomique', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Cantila, Roselie Nano', 'cantilaroselienano', {$quotedHash}, 2, 26, 1, 'default_user.png')",
  "('Daclan, Genevive Alajeño', 'daclangenevivealajeño', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Dela Cerna, Mariecon Oronce', 'delacernamarieconoronce', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Dela Rama, Maria Crisanie Pearl Quipot', 'delaramamariacrisaniepearlquipot', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Dolia, Jessie P', 'doliajessiep', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Duldulao, Ednalyn Claveria', 'duldulaoednalynclaveria', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Esma, Arceli Luna', 'esmaarceliluna', {$quotedHash}, 2, 11, 1, 'default_user.png')",
  "('Espanol, Luz-Concepcion Sumalinog', 'espanolluzconcepcionsumalinog', {$quotedHash}, 2, 14, 1, 'default_user.png')",
  "('Fulloso, Nathalie Salamania', 'fullosonathaliesalamania', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Gahapon, Rheyma Balignot', 'gahaponrheymabalignot', {$quotedHash}, 2, 3, 1, 'default_user.png')",
  "('Gangoso, Mary Jane Gamose', 'gangosomaryjanegamose', {$quotedHash}, 2, 27, 1, 'default_user.png')",
  "('Guerra, Rene Cedeño', 'guerrarenecedeño', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Guillena, Myrel Kilme', 'guillenamyrelkilme', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Gumibao, Neco Neptali Ellorando', 'gumibaoneconeptaliellorando', {$quotedHash}, 2, 28, 1, 'default_user.png')",
  "('Halasan, Nancy May Navarro', 'halasannancymaynavarro', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Ignacio, Almika Deluvio', 'ignacioalmikadeluvio', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Juquino, Maricel Salvacion', 'juquinomaricelsalvacion', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Kagatan, Rosa Obnimaga', 'kagatanrosaobnimaga', {$quotedHash}, 2, 13, 1, 'default_user.png')",
  "('Lasala, Alna Acuña', 'lasalaalnaacuña', {$quotedHash}, 2, 18, 1, 'default_user.png')",
  "('Lumacang, Leo Loven Dablo', 'lumacangleolovendablo', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Lusay, Julito D', 'lusayjulitod', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Montealto, Gina Gimena', 'montealtoginagimena', {$quotedHash}, 2, 10, 1, 'default_user.png')",
  "('Montegrande, Cresemie Megano', 'montegrandecresemiemegano', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Niez, Viverly Umbania', 'niezviverlyumbani', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Pano, Alvin Bertulfo', 'panoalvinbertulfo', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Pascua, Rhea Ceniza', 'pascuarheaceniza', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Posadas, Richard Jr Soledad', 'posadasrichardjrsoledad', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Puerto, Gerilee Grace Babao', 'puertogerileegracebabao', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Quieta, Ronnil Cabillon', 'quietaronnilcabillon', {$quotedHash}, 2, 14, 1, 'default_user.png')",
  "('Quipot, Ma. Luisa Napolereyes', 'quipotmaluisanapolereyes', {$quotedHash}, 2, 8, 1, 'default_user.png')",
  "('Rebugar, Jumaima Adbul Rahim', 'rebugarjumaimaadbulrahim', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Rivera, Karen Agnes Zuasola', 'riverakarenagneszuasola', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Roda, Genelyn Nillas', 'rodagenelynnillas', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Salomes, Recille Jane Cristoria', 'salomesrecillejanecristoria', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Sapong, Ronaldo Saldua', 'sapongronaldosaldua', {$quotedHash}, 2, 7, 1, 'default_user.png')",
  "('Sarmiento, Elizabeth Cudal', 'sarmientoelizabethcudal', {$quotedHash}, 2, 23, 1, 'default_user.png')",
  "('Sebial, Rhea Jastia', 'sebialrheajastia', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Suan, Mary Jean Coyme', 'suanmaryjeancoyme', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Takilid, Jovelyn Ungang', 'takilidjovelynungang', {$quotedHash}, 2, 22, 1, 'default_user.png')",
  "('Tolorio, Al James Donaire', 'tolorioaljamesdonaire', {$quotedHash}, 2, 26, 1, 'default_user.png')",
  "('Tomol, Ruth Trio', 'tomolruthtrio', {$quotedHash}, 2, 14, 1, 'default_user.png')",
  "('Uy, Anecita Luna', 'uyanecitaluna', {$quotedHash}, 2, 10, 1, 'default_user.png')",
  "('Vallejo, Ellen Pasco', 'vallejoellenpasco', {$quotedHash}, 2, 24, 1, 'default_user.png')",
  "('Villahermosa, Neil Panes', 'villahermosaneilpanes', {$quotedHash}, 2, 22, 1, 'default_user.png')",
];

$defaultInserts[] = "INSERT INTO tbl_user (full_name, username, hashed_password, role_id, position_id, is_active, photo) VALUES\n" . implode(",\n", $userValues) . " ON DUPLICATE KEY UPDATE user_id = user_id";

// inform the user of the generated plaintext password (you may want to change it)
echo "[INFO] Generated seed password (plaintext): {$randomPassword}\n";
echo "[INFO] A bcrypt hash was generated and used for seeded users.\n";

// officers (assign default user id 1 where applicable)
$defaultInserts[] = "INSERT INTO `tbl_officers` (`officer_id`, `officer_type`, `user_id`) VALUES (1, 'Approving Officer', 1), (2, 'Funds Available Officer', 1), (3, 'Accountable Officer', 1) ON DUPLICATE KEY UPDATE officer_type = VALUES(officer_type)";

foreach ($defaultInserts as $i => $sql) {
  try {
    $pdo->exec($sql);
    echo "[OK] Insert " . ($i+1) . " executed.\n";
  } catch (PDOException $e) {
    echo "[ERROR] Insert " . ($i+1) . " failed: " . $e->getMessage() . "\n";
    // continue with other inserts instead of stopping the whole script
  }
}

echo "Schema creation completed successfully.\n";

// Notes for use:
// - Edit $DB_HOST, $DB_NAME, $DB_USER, $DB_PASS at top before running.
// - This script creates minimal columns. Add additional columns or constraints
//   if your application requires them.

?>
