# Asset Lifecycle Module

## Responsibility

Receipts, current asset master/status/condition, images/documents, manual assignment, returns, labels, personal custody, warranties, scrap/archive display, reports, and lifecycle history.

## Files

- Controller: `app/controllers/Asset.php`
- Model: `app/models/Asset_model.php`
- Views: `app/views/asset/*.php`
- Shared modal: `app/views/templates/asset_details_modal.php`
- PDFs: `app/core/SimplePdfReport.php`
- QR: `app/phpqrcode/`
- Uploads: `public/uploads/asset`, `archive`, and `invoices`

## Critical invariants

- One physical asset has one unique asset number and serial identity as required.
- Current status and current condition come from the asset master.
- Transaction tables retain immutable issue/return snapshots.
- Assignment/return limits are enforced in both controller/model and UI.
- Multi-row saves use a database transaction.
- An ISS/RET batch PDF contains all selected rows, with per-asset condition where applicable.
- Deletion of a referenced category/master record must fail cleanly.
- Asset updates create audit events instead of rewriting history.

## Safe test set

Test a one-asset batch and an eight-asset batch; different return conditions; duplicate submission; wrong-role request; invalid/missing asset; upload failure; PDF grouping; status/condition rollback after a forced failure; search/sort/pagination; and QR/label output.
