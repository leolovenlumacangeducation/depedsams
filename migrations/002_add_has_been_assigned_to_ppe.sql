ALTER TABLE `tbl_ppe`
ADD COLUMN `has_been_assigned` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Tracks if the item has ever been assigned, to distinguish from brand new items.' AFTER `photo`;