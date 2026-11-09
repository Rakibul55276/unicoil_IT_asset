# IT Asset Management System

Web-based IT asset custody and lifecycle management for receiving, tagging, assigning, returning, auditing, and reporting company equipment. The same installation can optionally enable quantity-based accessory inventory, equipment-request approvals, bulk label printing, Windows Agent inventory, and voucher-based multi-company operation.

This repository is organized for operational handover. Start with the [User Manual](docs/USER_MANUAL.md), then use the [Administrator Guide](docs/ADMINISTRATOR_GUIDE.md) and [Deployment Guide](docs/DEPLOYMENT_GUIDE.md).

## Main capabilities

- Register individual assets or batches and generate category-based asset numbers.
- Maintain a single current status and condition for every asset.
- Assign up to eight assets in one ISS batch to an employee or department.
- Return assets from the same ISS batch in one RET transaction, with a condition per asset.
- Generate assignment and return PDFs, QR labels, and bulk adhesive-printer labels.
- View active, in-stock, returned, expired-warranty, and full audit histories.
- Maintain employees, departments, department heads, locations, units, vendors, categories, and user access.
- Optionally run an equipment-request approval workflow and scrap approval workflow.
- Optionally manage cables, connectors, and other quantity stock independently from serialized assets.
- Optionally synchronize Windows hardware and software through the Agent API.
- Optionally operate database-per-company subscriptions using administrator-issued vouchers.

## Documentation

| Document | Purpose |
| --- | --- |
| [User Manual](docs/USER_MANUAL.md) | Daily instructions for users, issuers, managers, and administrators. |
| [Administrator Guide](docs/ADMINISTRATOR_GUIDE.md) | Roles, master data, user access, feature switches, and routine administration. |
| [Workflow Guide](docs/WORKFLOWS_AND_ROLES.md) | Assignment, return, approval, acceptance, scrap, and role rules. |
| [Architecture and File Map](docs/ARCHITECTURE_AND_FILE_MAP.md) | Purpose of every application area and the files used by each module. |
| [First-party File Reference](docs/FILE_REFERENCE.md) | Quick lookup for the project's maintained PHP, view, integration, Agent, and database files. |
| [Database Guide](docs/DATABASE_GUIDE.md) | Table groups, migrations, backup, and safe database handling. |
| [Deployment Guide](docs/DEPLOYMENT_GUIDE.md) | Localhost and cPanel installation, upgrade, permissions, and verification. |
| [Windows Agent Guide](docs/modules/WINDOWS_AGENT.md) | Agent server/client structure, token setup, enable switch, and testing. |
| [SaaS Platform Guide](docs/modules/SAAS_PLATFORM.md) | Optional companies, vouchers, platform administrator, and tenant databases. |
| [Accessory Inventory Guide](docs/modules/ACCESSORY_INVENTORY.md) | Optional quantity stock, issue/return controls, audit events, and feature switch. |
| [Troubleshooting](docs/TROUBLESHOOTING.md) | Login/DB errors, HTTP 500, uploads, PDFs, mail, Agent, and diagnostics. |
| [Handover Checklist](docs/HANDOVER_CHECKLIST.md) | Final delivery, security, backup, and acceptance checklist. |

## Quick local setup

Requirements: Apache, PHP 8.1 or later, MariaDB/MySQL, PDO MySQL, OpenSSL, Fileinfo, GD, and mbstring.

1. Place the project under `C:\xampp\htdocs\IT-asset-new_`.
2. Create a database and import the applicable schema/install SQL described in [Database Guide](docs/DATABASE_GUIDE.md).
3. Copy `config.example.php` to `config.php`.
4. Enter the local database credentials and set `app_url` to `http://localhost/IT-asset-new_`.
5. Start Apache and MySQL in XAMPP.
6. Open `http://localhost/IT-asset-new_/` and sign in with an active application user.

`config.php` is intentionally excluded from Git because it contains credentials and private keys.

## Safe change rule

The project follows a small custom MVC structure. Do not move controllers, models, views, root endpoints, or `agent-module` compatibility loaders without updating routing and testing every workflow. To understand or change a feature, begin with the matching guide under [`docs/modules`](docs/modules/README.md).

## Production security

- Never commit `config.php`, `mail_secret.php`, API tokens, database exports containing employee data, private certificate keys, logs, or uploaded files.
- Use unique production database credentials and least-privilege database users.
- Keep HTTPS enabled and preserve the supplied `.htaccess` protections.
- Store installers as release artifacts rather than source-controlled build output.
- Back up both the database and `public/uploads` before every deployment.

## License and ownership

See [LICENSE](LICENSE). Confirm organizational ownership, third-party-library obligations, and delivery authorization before distributing the system outside the intended company.
