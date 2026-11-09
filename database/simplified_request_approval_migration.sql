-- Simplified equipment request approval flow migration.
-- Run after request_workflow_install.sql and approval_hierarchy_install.sql.
-- Existing supervisor data is preserved for future use but is no longer used by new request routes.
SET NAMES utf8mb4;

-- No new table is required. New routes use only MANAGER and IT_MANAGER
-- approval steps. Administrator is not an approval stage; an Administrator
-- can only move a request backward or forward with a mandatory audited reason.
-- The existing APPROVED status means "Waiting for Issuer to Issue".

-- Optional cleanup for requests that have not yet been acted on:
-- Re-create them through the application if they still contain old superior-manager routes.
-- Historical routes are intentionally retained for audit integrity.
