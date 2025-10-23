-- Create IAR (Inspection and Acceptance Report) table
CREATE TABLE IF NOT EXISTS `tbl_iar` (
  `iar_id` INT PRIMARY KEY AUTO_INCREMENT,
  `iar_number` VARCHAR(50) NOT NULL UNIQUE,
  `po_id` INT NOT NULL,
  `delivery_id` INT NOT NULL,
  `date_inspected` DATE NOT NULL,
  `inspected_by_user_id` INT NULL,
  `accepted_by_user_id` INT NULL,
  `inspection_report` TEXT NULL,
  `remarks` TEXT NULL,
  `date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX (`po_id`),
  INDEX (`delivery_id`),
  INDEX (`inspected_by_user_id`),
  INDEX (`accepted_by_user_id`),
  FOREIGN KEY (`po_id`) REFERENCES `tbl_po`(`po_id`) ON DELETE CASCADE,
  FOREIGN KEY (`delivery_id`) REFERENCES `tbl_delivery`(`delivery_id`) ON DELETE CASCADE,
  FOREIGN KEY (`inspected_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL,
  FOREIGN KEY (`accepted_by_user_id`) REFERENCES `tbl_user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create IAR Items table to track inspection details for each delivered item
CREATE TABLE IF NOT EXISTS `tbl_iar_item` (
  `iar_item_id` INT PRIMARY KEY AUTO_INCREMENT,
  `iar_id` INT NOT NULL,
  `delivery_item_id` INT NOT NULL,
  `is_passed` BOOLEAN NOT NULL DEFAULT TRUE,
  `remarks` TEXT NULL,
  INDEX (`iar_id`),
  INDEX (`delivery_item_id`),
  FOREIGN KEY (`iar_id`) REFERENCES `tbl_iar`(`iar_id`) ON DELETE CASCADE,
  FOREIGN KEY (`delivery_item_id`) REFERENCES `tbl_delivery_item`(`delivery_item_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add number sequence for IAR numbers
CREATE TABLE IF NOT EXISTS `tbl_iar_number` (
  `iar_number_id` INT PRIMARY KEY AUTO_INCREMENT,
  `serial` VARCHAR(50) NOT NULL,
  `year` YEAR NOT NULL,
  `start_count` INT NOT NULL DEFAULT 1,
  `iar_number_format` VARCHAR(100) NULL,
  UNIQUE KEY uk_iar_serial_year (serial, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default number sequence for IAR
INSERT INTO `tbl_iar_number` (`serial`, `year`, `iar_number_format`, `start_count`) 
VALUES ('default', '2025', 'IAR-{YYYY}-{NNNN}', 1) 
ON DUPLICATE KEY UPDATE year = VALUES(year);