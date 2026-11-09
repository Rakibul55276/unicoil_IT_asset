# Reports and Documents Module

## Reports

- Assigned asset history
- Return history
- In-stock history
- Warranty/expiry history
- Asset transaction audit
- Personal assigned assets/history
- Request transaction history when workflow is enabled

Primary code is in report actions of `Asset.php`, queries in `Asset_model.php`, `app/views/asset` report pages, and request history actions/views.

## Documents

`app/core/SimplePdfReport.php` creates server-side PDFs for assignment, return, audit, and exports. `transaction-form.php` and print-specific views provide browser print layouts. Labels use `print-label.php` and `print-labels.php` with QR generation.

## Reporting boundaries

- Asset audit contains physical lifecycle events after completion: receipt, assignment, return, update, eligible scrap outcomes, and deletion.
- Approval/request detail stays in Request Transaction History to keep management audit readable.
- Current condition is displayed from the asset master; transaction condition is the historical snapshot.

## Safe changes

Keep headers, columns, row data, export data, and filter definitions aligned. Test long/wrapped text, eight-row documents, missing images/logo, empty results, date/time with AM/PM, page breaks, and print margins.
