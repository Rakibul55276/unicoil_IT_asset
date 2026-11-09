# Administrator Guide

## Administrator responsibilities

The Administrator has full system access, but routine physical operations should still be performed through the Issuer workflow so the audit actor is clear. Administrators maintain master data, accounts, feature switches, platform settings, backups, and corrective workflow actions.

## User and role model

Every active account may be linked to one employee. The employee link supplies identity, email, department, and position. One employee must not have multiple active logins.

Additional roles are:

| Role | Main authority |
| --- | --- |
| Administrator | Full access, master configuration, corrective status actions, deletion where allowed. |
| Issuer | Receive, assign, return, label, issue approved requests, and operational reports. |
| IT Manager | IT approval and controlled master/report access; operational entry/return is not its primary role. |
| Department Manager | Request for self/department and approve eligible department requests other than own. |
| HR Requester | Request for self or another employee and maintain employees when authorized. |
| Linked employee | Self-service request, receiver acceptance, assigned assets, return request, and personal history. |

Backend role checks protect operations even if a navigation item is hidden. Do not rely on menu visibility as the only permission control.

## Create and maintain users

Open **User Management > User Access**.

1. Select the linked employee first.
2. Name and email populate from the employee record when present; enter an email if it is missing.
3. Use the employee ID as the username unless company policy specifies a role account.
4. Select only the additional roles required.
5. Set a temporary strong password and require the user to change it.

Only an Administrator may edit an employee ID. Because employee IDs participate in user and transaction relationships, take a database backup and verify the affected login and history after changing one.

## Master data sequence

Configure master data in this order:

1. Departments (including area/building and one department head where applicable)
2. Employees and department mapping
3. Locations
4. Units
5. Vendors
6. Asset categories and number prefixes
7. User accounts and roles

A category, department, employee, vendor, unit, or location that is already referenced must not be deleted. The application reports the dependency or associated asset where supported. Deactivate or correct the record instead.

## Feature switches

Feature switches are stored only in the private root `config.php`:

```php
'workflow_ui_enabled' => true,
'accessory_inventory_enabled' => true,
'issuer_ui_enabled' => true,
'scrap_approval_enabled' => true,
'bulk_label_print_enabled' => true,
'agent_enabled' => true,
'saas_voucher_enabled' => false,
```

| Switch | When `true` | When `false` |
| --- | --- | --- |
| `workflow_ui_enabled` | Equipment requests, approvals, return requests, and workflow notifications/history are available by role. | General workflow UI and related navigation are hidden; manual asset operations remain. Existing workflow data is not deleted. |
| `accessory_inventory_enabled` | Administrator and Issuer can manage quantity-based accessories; their immutable movements appear in the Asset Audit report. | Accessory navigation/routes and accessory audit events are disabled. Serialized asset behavior and stored accessory records are unchanged. |
| `issuer_ui_enabled` | Issuer asset entry, assignment, return, and relevant reports remain visible independently of the full workflow UI. | Issuer-only navigation is hidden. This switch never exposes User Access or delete controls to an Issuer. |
| `scrap_approval_enabled` | Scrap Approval and Scrap List are available to authorized roles. | Scrap Approval and Scrap List navigation are hidden, and scrap approval routes are disabled independently of the general workflow UI. Existing scrap data is not deleted. |
| `bulk_label_print_enabled` | Administrator and Issuer can select and print multiple labels. | Bulk controls are hidden; permitted single-label printing remains. |
| `agent_enabled` | Agent API/runtime can synchronize authorized Windows devices. | Agent endpoints reject/disable synchronization; manual asset functions remain. |
| `saas_voucher_enabled` | Company/voucher/tenant database gate is active. | Original single-company database operation remains. |

Change only one switch at a time, sign out/in, and test the affected role. Never delete module files to disable a feature.

## Audit and corrective actions

- Use the Asset Audit Transaction Report for physical lifecycle evidence.
- Use Request Transaction History for request/approval detail.
- Cancel requests only through the provided administrative action and permitted state.
- Do not delete issued/returned rows directly in phpMyAdmin.
- Scrapped asset deletion must use the application so the deletion snapshot is retained.
- Condition corrections should use asset edit so an `ASSET_UPDATE` audit event is created.

## Routine schedule

Daily: review failed actions, pending returns, expiry alerts, and database/server logs.

Weekly: verify backups, inactive users, unresolved requests, and storage usage.

Monthly: restore-test a backup, review role assignments, export management reports, rotate integration tokens if required, and review warranty renewals.
