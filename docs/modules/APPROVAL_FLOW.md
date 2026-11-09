# Approval and Request Module

## Responsibility

Equipment requests, route construction, approvals, rejection/cancellation, physical allocation/issue, receiver acceptance, return requests, scrap approval, notifications, and separate request audit history.

## Files

- Equipment controller/model/views: `app/controllers/Request.php`, `app/models/Request_model.php`, `app/views/request`
- Scrap controller/model/views: `app/controllers/Scrap.php`, `app/models/Scrap_model.php`, `app/views/scrap`
- Return-request actions/data: `Asset.php`, `Asset_model.php`, `views/asset/my-assets.php`, `return-requests.php`
- Navigation/notification rendering: authenticated header/footer template files
- Tables: `asset_request*`, `asset_return_request`, `asset_scrap_request`, `asset_scrap_event`

## Feature switch

`workflow_ui_enabled` controls the general request workflow presentation and its notifications/history. `scrap_approval_enabled` independently controls scrap approval. Disabling either switch hides its corresponding interface without deleting records or changing the manual lifecycle.

## Critical invariants

- Maximum eight total requested assets.
- No self-approval.
- Only current approver can act; future queued approvers cannot see/act early.
- Issuer issues after approval; issuance is not another approval.
- A request is issued once and accepted by its receiver.
- Status changes and administrator overrides create events.
- Return request does not change stock before physical completion.

Avoid adding ad-hoc route queries in views. Route/status decisions belong in `Request_model.php`, enforced by `Request.php`, and rendered read-only by the view.
