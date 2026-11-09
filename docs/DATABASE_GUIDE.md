# Database Guide

## Database ownership

The main asset database is configured through root `config.php`. Optional SaaS mode uses a platform database for companies/vouchers and a separate tenant database per company. Do not point a production tenant at another company's database.

## Table groups

| Area | Tables |
| --- | --- |
| Asset master | `assetdata`, `assetkat`, `assetstat`, `location`, `unit`, `vendor` |
| Receiving/media | `asset_receipt`, `asset_receipt_item`, `asset_media` |
| Custody/return | `asset_issue`, `asset_return`, `assignment_email_log` |
| Request workflow | `asset_request`, `asset_request_item`, `asset_request_allocation`, `asset_request_approval_step`, `asset_request_event`, `asset_return_request` |
| Scrap/deletion | `asset_scrap_request`, `asset_scrap_event`, `asset_deletion_audit` |
| Organization/access | `employee`, `department`, `department_head`, `empdept`, `users`, `user_role` |
| Windows Agent | `agent_api_token`, `agent_device`, `agent_disk`, `agent_software`, `agent_sync_log` |
| Migration control | `schema_migrations` |
| Optional SaaS platform | `saas_company`, `saas_platform_admin`, `saas_voucher`, `saas_voucher_redemption` |

Views beginning with `v_` or names such as `vassetdata` present combined data and do not own the original records. Back up the base tables; recreate views with `SQL SECURITY INVOKER` and without an incompatible `DEFINER` when moving between servers.

## Condition and status sources

- `assetdata.assetStat` is the current operational state, normally **IN STOCK** or **ASSIGNED**.
- `assetdata.current_condition` (or the installed equivalent column) is the current global condition.
- Issue/return records keep historical condition snapshots for the transaction.
- On return, the RET row receives the selected return condition and the asset's global condition is updated in the same model transaction.

Never rewrite old transaction conditions when correcting the current asset condition. Use the application so an update audit event is added.

## Install and migrate

The `database` folder contains a base installer and incremental migrations. Use the scripts appropriate to the target version; do not import every historical full dump into an existing database.

Migration runner from the project root:

```powershell
C:\xampp\php\php.exe database\migrate.php status
C:\xampp\php\php.exe database\migrate.php migrate
```

For a new production database, use a reviewed schema-only export or approved clean installer, then run the migration status command. Full employee/customer dumps are intentionally excluded from Git.

Historical company-directory import migrations are also excluded because they contain employee names. Import current employees from an approved HR source after the clean schema is installed.

Important optional installers include:

- `agent_inventory_install.sql`
- `request_workflow_install.sql`
- `saas_platform_install.sql`
- `large_scale_performance_migration.sql`

Files named `cpanel_update_YYYYMMDD_*.sql` are targeted incremental updates. Record every applied migration and never run the same destructive manual SQL twice.

## cPanel import rules

- Select the intended database before opening phpMyAdmin **Import** or **SQL**.
- Remove or avoid `DEFINER=user@host` from portable view statements.
- Use matching character sets/collations on joined key columns, preferably `utf8mb4_unicode_ci` throughout this project.
- Create referenced tables and matching column types before adding foreign keys.
- Use `SQL SECURITY INVOKER` for portable views.
- If a table already exists, stop and determine whether this is a new install or an upgrade; do not blindly drop production data.

## Backup and restore

Before deployment:

1. Put the application in a maintenance window.
2. Export all base tables, routines/triggers if used, and views.
3. Copy `public/uploads` separately.
4. Encrypt and store the backup outside the web root.
5. Test restoration to a separate database.

Suggested retention: daily 7 days, weekly 5 weeks, monthly according to company policy.

## Scaling

The application includes server paging and a large-scale migration for high asset volumes. Preserve indexed search fields, use the provided paged model methods, and never replace a paged screen with an unbounded `SELECT *`. Employee lists in the hundreds are supported; search selectors should remain server-friendly.
