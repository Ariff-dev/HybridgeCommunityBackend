-- ================================================
-- MIGRATION: Remove API Keys System
-- Description: Drop api_keys table - migrated to JWT
-- Date: 2025-12-27
-- Author: Hybridge Community Backend
-- ================================================

-- Drop api_keys table
DROP TABLE IF EXISTS api_keys;

-- Verification
-- After running this migration, execute:
-- SHOW TABLES;
-- Expected: api_keys should NOT appear in the list
