-- Migration 999: Remove Document Management tables, view, and ICS columns
-- Run this on your development database or via phpMyAdmin. This will
-- permanently delete document records and related history. BACKUP DB first.

SET FOREIGN_KEY_CHECKS=0;

-- Drop view that depends on document tables
DROP VIEW IF EXISTS vw_document_requirements;

-- Remove document tracking columns from tbl_ics (if present)
ALTER TABLE tbl_ics
    DROP COLUMN IF EXISTS has_complete_docs,
    DROP COLUMN IF EXISTS last_doc_check;

-- Drop document version/history, main document, and types
DROP TABLE IF EXISTS tbl_document_version;
DROP TABLE IF EXISTS tbl_document;
DROP TABLE IF EXISTS tbl_document_type;

SET FOREIGN_KEY_CHECKS=1;

-- Optionally, you can insert a record into migrations table to mark this run
-- INSERT INTO migrations (migration) VALUES ('999_remove_document_management');
