# Complete User Manual

## 1. Sign in and personal profile

1. Open the application URL supplied by IT.
2. Enter your username and password.
3. Use the user menu in the top-right corner to open **My Profile** or sign out.
4. Your employee ID is controlled by the system administrator. Update only the profile fields made editable for your account.

The navigation is role-based. A missing menu normally means the account does not have that operational role or the related feature is disabled.

## 2. Dashboard

The dashboard presents information relevant to the signed-in role. Common items include personal requests, pending actions, assigned assets, stock totals, expiry alerts, and links to the related list.

Select a summary card to open its detailed page. Warranty notifications open the asset-duration/expiry report.

## 3. Asset entry (Issuer or Administrator)

Open **Asset Operations > Asset Entry**.

1. Select the category, vendor, location, unit, and receipt information.
2. Enter description, serial number, purchase/warranty details, quantity, condition, and remarks as required by the form.
3. Attach an image, invoice, or other document when available.
4. For multiple identical asset types, use the batch quantity and enter the unique serial information required for each physical asset.
5. Save the receipt.

Each physical asset receives its own asset number. New assets enter stock with the global condition recorded on `assetdata`. The receipt remains available in the audit history.

## 4. In-stock assets

Open **Reports > Asset InStock History** or the in-stock asset page exposed by the current navigation.

- Search by asset number, serial number, description, category, location, or condition.
- Select a sortable column header to reorder the list.
- Select an asset number to open the details modal.
- Administrators/issuers may edit permitted asset details and current condition.
- Every material update is retained as an asset update event.
- When bulk label printing is enabled, select assets and print their labels in one job.

## 5. Manual assignment (Issuer or Administrator)

Open **Asset Operations > Asset Assign**.

1. Choose whether custody is for a user or a department.
2. Search and select the employee/department.
3. Select between one and eight available assets.
4. Verify the asset list and current conditions. Assignment does not redefine the global condition.
5. Enter purpose/remarks and submit once.
6. Download or print the assignment PDF.

One submission creates one ISS batch. All assets in that batch appear on the same assignment document. The assets change from **IN STOCK** to **ASSIGNED**.

## 6. Manual return (Issuer or Administrator)

Open **Asset Operations > Asset Return**.

1. Search for an assigned asset, employee ID/name, category, or department.
2. The second selector loads the eligible assets from the selected ISS batch.
3. Select one to eight assets from that batch.
4. Select the return condition separately for every asset.
5. Enter return date/time and remarks, then complete the return once.
6. Download or print the combined return PDF.

The return creates one RET transaction for the selected assets. Each asset returns to **IN STOCK**, its new global condition is saved on the asset record, and the return snapshot remains in return history.

## 7. Equipment request workflow (when enabled)

Open **Equipment Requests**.

1. Create a request for yourself. HR Requesters may request for another employee, and Department Managers may request for their department.
2. Select simple equipment categories and quantities; users do not need to choose technical specifications.
3. A request may contain at most eight total assets across all selected categories.
4. Enter required date, location/business reason, and submit.
5. Before approval begins, edit the request if correction is required.
6. Follow the current and next approver in request details.
7. After final approval, the Issuer selects physical assets and issues the entire approved request once.
8. The receiver accepts custody; the request becomes completed.

Approvers cannot approve their own request. Queued approvers see the request only after the previous step is completed.

## 8. Return request workflow (when enabled)

From **My Assigned Assets**, select one to eight assets from the same ISS batch and create one return request.

- The request does not change stock immediately.
- The Issuer reviews it under **Return Requests** and completes the normal physical return.
- After completion, the return PDF and history are available to authorized users.
- A pending request can be cancelled only where the current role and state permit it.

## 9. My Assigned Assets and My Asset History

- **My Assigned Assets** shows current custody, grouped by ISS batch. It provides assignment documents and eligible return actions.
- **My Asset History** shows completed assignment, acceptance, return-request, and return activity. Related assets are grouped by their transaction batch.
- Asset numbers open the authorized detail view. Sensitive administrative fields remain hidden for ordinary users.

## 10. Scrap process (when enabled)

The Issuer requests scrap for an eligible asset and records the reason. The IT Manager (or an authorized administrator) approves or rejects it. Approved scrap assets move to the scrap list/archive. Deletion of a scrapped record is restricted to administrators and preserves its deletion audit snapshot.

## 11. Reports

The Reports menu contains:

- Asset Assigned History
- Asset Return History
- Asset InStock History
- Asset Expiry History
- Asset Audit Transaction Report
- My Assigned Assets and My Asset History for applicable users
- Request Transaction History when workflow is enabled and the role is authorized

Use page filters before exporting. Asset transaction audit is for physical lifecycle events such as receipt, assignment, return, update, scrap, and deletion. Request approval chatter is kept in the separate Request Transaction History.

## 12. QR and label printing

An asset label contains a QR code, asset number, compact category text, and the company logo. Scanning the QR opens the authorized asset detail page. Labels are designed for a dedicated adhesive label printer; verify media dimensions and print one sample before bulk printing.

## 13. Common operating rules

- Do not share user accounts.
- Confirm the serial number and asset number before assignment or return.
- Use one submission per physical handover/return batch.
- Do not refresh or resubmit while a transaction is being saved.
- Keep supporting documents free of confidential information unrelated to asset custody.
- Report incorrect custody immediately rather than deleting transaction history.
