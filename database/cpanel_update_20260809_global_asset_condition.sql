-- IT Asset Management - current asset condition and normalized live status
-- Run once in phpMyAdmin after selecting the application database.

INSERT INTO `assetstat` (`assetStatus`,`Description`)
VALUES ('ASSIGNED','Assigned')
ON DUPLICATE KEY UPDATE `Description`=VALUES(`Description`);

ALTER TABLE `assetdata`
  ADD COLUMN `asset_condition`
    ENUM('NEW','GOOD','FAIR','DAMAGED','BEYOND_REPAIR')
    NOT NULL DEFAULT 'NEW'
    AFTER `assetStat`;

UPDATE `assetdata` a
SET a.`asset_condition`=COALESCE(
  (
    SELECT open_issue.`condition_out`
    FROM `asset_issue` open_issue
    WHERE open_issue.`assetNum`=a.`assetNum`
      AND open_issue.`status`='ISSUED'
    ORDER BY open_issue.`issue_date` DESC,open_issue.`issue_id` DESC
    LIMIT 1
  ),
  (
    SELECT r.`condition_in`
    FROM `asset_return` r
    INNER JOIN `asset_issue` returned_issue
      ON returned_issue.`issue_id`=r.`issue_id`
    WHERE returned_issue.`assetNum`=a.`assetNum`
    ORDER BY r.`return_date` DESC,r.`return_id` DESC
    LIMIT 1
  ),
  (
    SELECT receipt_item.`condition_on_receipt`
    FROM `asset_receipt_item` receipt_item
    WHERE receipt_item.`assetNum`=a.`assetNum`
    ORDER BY receipt_item.`receipt_item_id` DESC
    LIMIT 1
  ),
  'NEW'
);

UPDATE `assetdata`
SET `assetStat`='ASSIGNED'
WHERE `assetStat` IN ('ISSUED_NEW','ISSUED_USED');

UPDATE `assetdata` a
SET a.`assetStat`=CASE
  WHEN a.`assetStat`='SCRAPPED' THEN 'SCRAPPED'
  WHEN EXISTS (
    SELECT 1
    FROM `asset_issue` open_issue
    WHERE open_issue.`assetNum`=a.`assetNum`
      AND open_issue.`status`='ISSUED'
  ) THEN 'ASSIGNED'
  ELSE 'IN_STOCK'
END;

ALTER TABLE `assetdata`
  ADD INDEX `idx_asset_status_condition_number`
    (`assetStat`,`asset_condition`,`assetNum`);

DELETE FROM `assetstat`
WHERE `assetStatus` IN ('ISSUED_NEW','ISSUED_USED');
