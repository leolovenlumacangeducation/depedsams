CREATE TABLE IF NOT EXISTS tbl_system_log (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    action VARCHAR(50) NOT NULL,
    performed_by INT,
    details TEXT,
    date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (performed_by) REFERENCES tbl_user(user_id) ON DELETE SET NULL
);