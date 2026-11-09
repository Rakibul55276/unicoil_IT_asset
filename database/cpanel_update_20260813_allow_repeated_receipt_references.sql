-- Allow the same SAP GRN number to be used for more than one asset receipt.
-- PO Number and Invoice Number are already non-unique and require no change.
-- Run this once against an existing cPanel database.

ALTER TABLE `asset_receipt`
  DROP INDEX `uq_receipt_no`,
  ADD INDEX `idx_receipt_no` (`receipt_no`);
