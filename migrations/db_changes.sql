-- Database changes tracking file
-- Format: YYYY-MM-DD Description
-- Add new changes at the top

-- 2025-10-21 Add version tracking table
CREATE TABLE IF NOT EXISTS db_version (
    id INT PRIMARY KEY AUTO_INCREMENT,
    version VARCHAR(50) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT
);

-- 2025-10-21 Add indexes for foreign key relationships
ALTER TABLE tbl_user
ADD INDEX idx_role_id (role_id),
ADD INDEX idx_position_id (position_id),
ADD FOREIGN KEY (role_id) REFERENCES tbl_role(role_id) ON DELETE RESTRICT ON UPDATE CASCADE,
ADD FOREIGN KEY (position_id) REFERENCES tbl_position(position_id) ON DELETE RESTRICT ON UPDATE CASCADE;

-- Add these if missing (for existing databases)
ALTER TABLE tbl_user
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL AFTER position_id,
ADD COLUMN IF NOT EXISTS last_login DATETIME DEFAULT NULL AFTER photo_path;

-- Add indexes for inventory tracking
ALTER TABLE tbl_ppe
ADD INDEX idx_category (category_id),
ADD INDEX idx_inventory_type (inventory_type_id),
ADD INDEX idx_unit (unit_id),
ADD INDEX idx_user (assigned_to);

ALTER TABLE tbl_sep
ADD INDEX idx_category (category_id),
ADD INDEX idx_inventory_type (inventory_type_id),
ADD INDEX idx_unit (unit_id),
ADD INDEX idx_user (assigned_to);

ALTER TABLE tbl_consumable
ADD INDEX idx_category (category_id),
ADD INDEX idx_inventory_type (inventory_type_id),
ADD INDEX idx_unit (unit_id),
ADD INDEX idx_user (issued_to);

-- Update sequence tables to ensure proper auto-increment
ALTER TABLE tbl_po_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_pn_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_item_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_ris_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_ics_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_par_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_rpci_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_rpcppe_number AUTO_INCREMENT = 1;
ALTER TABLE tbl_iirup_number AUTO_INCREMENT = 1;

-- Add these if missing (for existing databases)
ALTER TABLE tbl_ppe
ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL AFTER remarks,
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL AFTER qr_code;

ALTER TABLE tbl_sep
ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL AFTER remarks,
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL AFTER qr_code;

ALTER TABLE tbl_consumable
ADD COLUMN IF NOT EXISTS qr_code VARCHAR(255) DEFAULT NULL AFTER remarks,
ADD COLUMN IF NOT EXISTS photo_path VARCHAR(255) DEFAULT NULL AFTER qr_code;

-- Record this version
INSERT INTO db_version (version, description)
SELECT '2025.10.21.1', 'Added indexes, foreign keys, and ensured QR/photo columns exist'
WHERE NOT EXISTS (
    SELECT 1 FROM db_version WHERE version = '2025.10.21.1'
);

-- Template for future changes:
/*
-- YYYY-MM-DD Description
ALTER TABLE table_name
ADD COLUMN column_name datatype constraints;

-- Don't forget to update db_version after each change:
INSERT INTO db_version (version, description) 
VALUES ('YYYY.MM.DD.1', 'Description of changes');
*/