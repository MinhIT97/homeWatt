-- =====================================================================
-- HomeWatt Database User Setup
-- =====================================================================
-- This script creates a dedicated MySQL user for the HomeWatt application.
-- Run as MySQL root on a fresh database server.
--
-- Usage:
--   mysql -u root -p < database/sql/create-app-user.sql
-- =====================================================================

-- Create database (if not exists)
CREATE DATABASE IF NOT EXISTS homewatt CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Drop existing app user (clean state)
DROP USER IF EXISTS 'homewatt_app'@'%';

-- Create dedicated app user with minimal required privileges
-- The app needs: SELECT, INSERT, UPDATE, DELETE on application tables
-- and full privileges on its own database only
CREATE USER 'homewatt_app'@'%' IDENTIFIED BY 'CHANGE_ME_TO_SECURE_RANDOM_PASSWORD';

-- Grant application privileges
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES,
      CREATE TEMPORARY TABLES, LOCK TABLES, EXECUTE, SHOW VIEW
    ON homewatt.*
    TO 'homewatt_app'@'%';

-- Grant access to mysql.proc for stored procedures (if used)
GRANT SELECT ON mysql.proc TO 'homewatt_app'@'%';

-- Apply changes
FLUSH PRIVILEGES;

-- Display confirmation
SELECT 'HomeWatt application user created successfully' AS status;
SELECT User, Host FROM mysql.user WHERE User = 'homewatt_app';