-- Document Types Table
CREATE TABLE IF NOT EXISTS tbl_document_type (
    document_type_id INT PRIMARY KEY AUTO_INCREMENT,
    type_name VARCHAR(100) NOT NULL,
    description TEXT,
    file_types VARCHAR(255) COMMENT 'Allowed file extensions, comma-separated',
    max_file_size INT COMMENT 'Maximum file size in bytes',
    is_required BOOLEAN DEFAULT FALSE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Document Storage Table
CREATE TABLE IF NOT EXISTS tbl_document (
    document_id INT PRIMARY KEY AUTO_INCREMENT,
    document_type_id INT NOT NULL,
    reference_type ENUM('SEP', 'PPE', 'ICS', 'PAR') NOT NULL,
    reference_id INT NOT NULL COMMENT 'ID of the related item (sep_id, ppe_id, etc.)',
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    mime_type VARCHAR(100),
    uploaded_by INT,
    upload_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    notes TEXT,
    FOREIGN KEY (document_type_id) REFERENCES tbl_document_type(document_type_id),
    FOREIGN KEY (uploaded_by) REFERENCES tbl_user(user_id),
    INDEX (reference_type, reference_id)
);

-- Document Version History
CREATE TABLE IF NOT EXISTS tbl_document_version (
    version_id INT PRIMARY KEY AUTO_INCREMENT,
    document_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    version_number INT NOT NULL,
    changed_by INT,
    change_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    change_notes TEXT,
    FOREIGN KEY (document_id) REFERENCES tbl_document(document_id),
    FOREIGN KEY (changed_by) REFERENCES tbl_user(user_id)
);

-- Insert default document types
INSERT INTO tbl_document_type (type_name, description, file_types, max_file_size, is_required) VALUES
('ICS Form', 'Inventory Custodian Slip', 'pdf,jpg,png', 5242880, TRUE),
('Warranty Card', 'Product warranty documentation', 'pdf,jpg,png', 5242880, FALSE),
('User Manual', 'Product user manual or guide', 'pdf', 10485760, FALSE),
('Repair Report', 'Maintenance or repair documentation', 'pdf,jpg,png,doc,docx', 5242880, FALSE),
('Transfer Form', 'Asset transfer documentation', 'pdf,jpg,png', 5242880, TRUE),
('Disposal Form', 'Asset disposal documentation', 'pdf,jpg,png', 5242880, TRUE),
('Purchase Receipt', 'Original purchase documentation', 'pdf,jpg,png', 5242880, TRUE),
('Inspection Report', 'Asset condition inspection report', 'pdf,jpg,png,doc,docx', 5242880, FALSE);

-- Add document tracking to ICS table
ALTER TABLE tbl_ics
ADD COLUMN has_complete_docs BOOLEAN DEFAULT FALSE COMMENT 'Indicates if all required documents are uploaded',
ADD COLUMN last_doc_check DATETIME COMMENT 'Last time document completeness was verified';

-- Add document requirement status view
CREATE OR REPLACE VIEW vw_document_requirements AS
SELECT 
    i.ics_id,
    i.ics_number,
    i.date_issued,
    i.issued_to_user_id,
    u.full_name as issued_to_name,
    COUNT(DISTINCT dt.document_type_id) as required_docs,
    COUNT(DISTINCT d.document_id) as uploaded_docs,
    CASE 
        WHEN COUNT(DISTINCT dt.document_type_id) = COUNT(DISTINCT d.document_id) THEN TRUE 
        ELSE FALSE 
    END as is_complete
FROM tbl_ics i
JOIN tbl_user u ON i.issued_to_user_id = u.user_id
CROSS JOIN tbl_document_type dt
LEFT JOIN tbl_document d ON d.reference_type = 'ICS' 
    AND d.reference_id = i.ics_id 
    AND d.document_type_id = dt.document_type_id
WHERE dt.is_required = TRUE
GROUP BY i.ics_id, i.ics_number, i.date_issued, i.issued_to_user_id, u.full_name;