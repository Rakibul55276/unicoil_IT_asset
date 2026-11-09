# Accessory Inventory

This optional module manages non-serialized quantity stock such as cables,
connectors, adapters, and consumable IT accessories. It does not read or write
`assetdata`, `asset_issue`, `asset_return`, or the approval workflow tables.

## Enable or disable

1. Import `database/accessory_inventory_install.sql` once.
2. Set `'accessory_inventory_enabled' => true` in the private root `config.php`.
3. Sign in as an Administrator or Issuer and open **Accessory Inventory** under
   **Asset Operations**.

Set the flag to `false` to hide navigation, block the controller routes, and
exclude accessory movements from the Asset Audit report. Stored records are
not deleted.

## Stock behavior

- Creating an item with opening quantity 100 records one opening receipt.
- Issuing 1 records the employee or department, mandatory justification, and
  changes the locked balance from 100 to 99.
- A return can never exceed the quantity currently held by that recipient.
- Outgoing issues/adjustments can never create a negative stock balance.
- **Low Stock Alert At** is the plain-language stock threshold. After an issue
  reaches or passes it, a centered warning popup appears for ten seconds; the issue is
  still completed normally.
- Receipt, issue, return, and adjustment rows are immutable audit evidence.
- Each balance change is atomic: the stock balance and audit row either both
  save or both roll back.

## Administrator maintenance

Administrators can edit the accessory code, name, category, unit, current
stock, low-stock alert quantity, and active status. A direct current-stock
correction requires remarks and is saved as an adjustment transaction. An
unused setup entry can be deleted; after issue, return, or adjustment history
exists, it must be marked **Inactive** so custody and audit evidence remain.

## Files

| Purpose | File |
| --- | --- |
| Feature switch | `config.php`, `config.example.php`, `app/config/config.php` |
| Authorization and actions | `app/controllers/Accessory.php` |
| Balance locking and ledger | `app/models/Accessory_model.php` |
| Forms, inventory and recent history | `app/views/accessory/index.php` |
| Navigation | `app/views/templates/header_a.php` |
| Shared audit report | `app/controllers/Asset.php`, `app/models/Asset_model.php`, `app/views/asset/audit-report.php` |
| Standalone upgrade SQL | `database/accessory_inventory_install.sql` |
| Fresh cPanel installation | `database/cpanel_clean_install_no_asset_data.sql` |
