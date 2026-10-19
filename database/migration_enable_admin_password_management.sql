-- Run this once in phpMyAdmin for an existing Murang'a portal database.
-- Select the muranga_citizen database before importing/running this file.
ALTER TABLE users ADD COLUMN can_reset_password TINYINT(1) NOT NULL DEFAULT 0 AFTER role;
