<?php

return [
    "version" => "20260809_000002_reconcile_global_asset_condition",
    "description" => "Reconcile current asset condition and live lifecycle status",
    "up" => function (PDO $pdo): void {
        // An open assignment is newer and more authoritative than an earlier
        // return. Historical issue/return conditions remain unchanged.
        $pdo->exec(
            "UPDATE assetdata a
             SET a.asset_condition=COALESCE(
                 (SELECT open_issue.condition_out
                  FROM asset_issue open_issue
                  WHERE open_issue.assetNum=a.assetNum
                    AND open_issue.status='ISSUED'
                  ORDER BY open_issue.issue_date DESC,open_issue.issue_id DESC
                  LIMIT 1),
                 (SELECT r.condition_in
                  FROM asset_return r
                  JOIN asset_issue returned_issue ON returned_issue.issue_id=r.issue_id
                  WHERE returned_issue.assetNum=a.assetNum
                  ORDER BY r.return_date DESC,r.return_id DESC
                  LIMIT 1),
                 (SELECT receipt_item.condition_on_receipt
                  FROM asset_receipt_item receipt_item
                  WHERE receipt_item.assetNum=a.assetNum
                  ORDER BY receipt_item.receipt_item_id DESC
                  LIMIT 1),
                 a.asset_condition,
                 'NEW'
             )",
        );

        $pdo->exec(
            "UPDATE assetdata a
             SET a.assetStat=CASE
                 WHEN a.assetStat='SCRAPPED' THEN 'SCRAPPED'
                 WHEN EXISTS (
                     SELECT 1 FROM asset_issue open_issue
                     WHERE open_issue.assetNum=a.assetNum
                       AND open_issue.status='ISSUED'
                 ) THEN 'ASSIGNED'
                 ELSE 'IN_STOCK'
             END",
        );
    },
];
