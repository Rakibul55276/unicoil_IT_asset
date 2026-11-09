# First-party File Reference

This reference lists the maintained application files by purpose. Third-party theme, JavaScript, font, QR-library internals, and generated assets are intentionally described by folder rather than line-by-line; edit those only when upgrading the dependency.

## Entry and configuration

| File | Purpose |
| --- | --- |
| `.htaccess` | Routes MVC URLs and blocks private/source access. |
| `index.php` | Main application entry. |
| `config.example.php` | Safe configuration template. |
| `config.php` | Private environment configuration; ignored by Git. |
| `app/init.php` | Initializes session, core classes, helpers, and config. |
| `app/config/config.php` | Defines application URL/database/feature constants from root config. |
| `app/config/mail_secret.example.php` | Safe mail integration template. |

## Core classes

| File | Purpose |
| --- | --- |
| `app/core/App.php` | URL router/controller dispatcher. |
| `app/core/Controller.php` | Base controller, views/models, role authorization. |
| `app/core/Database.php` | PDO connection/query/transaction wrapper. |
| `app/core/Auth.php` | Session user, roles, and permission context. |
| `app/core/Flasher.php` | Redirect-safe flash messages. |
| `app/core/SimplePdfReport.php` | Assignment, return, audit, and report PDFs. |
| `app/core/AssignmentMailer.php` | Transaction notification composition/delivery logging. |
| `app/core/GoogleAppsScriptTransport.php` | HTTPS mail transport. |
| `app/core/SmtpTransport.php` | SMTP mail transport. |
| `app/core/SaasCrypto.php` | Tenant credential encryption. |
| `app/core/SaasGate.php` | Optional subscription access gate. |
| `app/core/SaasPlatform.php` | Platform companies/vouchers/admin data. |
| `app/core/SaasTenantContext.php` | Current tenant database resolution. |

## Controllers and models

| Feature | Controller | Model |
| --- | --- | --- |
| Login/home | `app/controllers/Home.php` | `app/models/Home_model.php` |
| Role dashboard | `app/controllers/Dashboard.php` | `app/models/Dashboard_model.php` |
| Asset lifecycle/reports | `app/controllers/Asset.php` | `app/models/Asset_model.php` |
| Equipment requests | `app/controllers/Request.php` | `app/models/Request_model.php` |
| Scrap workflow | `app/controllers/Scrap.php` | `app/models/Scrap_model.php` |
| Employees | `app/controllers/Employee.php` | `app/models/Employee_model.php` |
| Departments | `app/controllers/Department.php` | `app/models/Department_model.php` |
| Categories | `app/controllers/Assetkat.php` | `app/models/Assetkat_model.php` |
| Locations | `app/controllers/Location.php` | `app/models/Location_model.php` |
| Units | `app/controllers/Unit.php` | `app/models/Unit_model.php` |
| Vendors | `app/controllers/Vendor.php` | `app/models/Vendor_model.php` |
| Users/profile | `app/controllers/User.php` | `app/models/User_model.php` |
| SaaS platform | `app/controllers/Platform.php`, `Subscription.php` | SaaS core classes |
| Agent compatibility | `app/controllers/Agent.php`, `AgentApi.php` | `Agent_model.php`, `AgentInventory_model.php`, `AgentDashboardRepository.php` |

Legacy/demo files `About.php`, `Mahasiswa.php`, `Member.php` and matching models/views are disabled by the router and are not production features.

## Views

### Shared layout

- `app/views/templates/header_a.php`: authenticated head/top bar and role/feature-driven navigation.
- `app/views/templates/footer_a.php`: shared scripts, tables, modal helpers.
- `app/views/templates/asset_details_modal.php`: authorized asset details and transactions.
- `header.php` and `footer.php`: lightweight/legacy public layout.

### Authentication, dashboard, and profile

- `app/views/home/index.php`: sign-in screen.
- `app/views/home/results.php`: recovery/result screen.
- `app/views/dashboard/index.php`: role dashboard.
- `app/views/dashboard/analytics.php`: management analytics.
- `app/views/user/index.php`, `create.php`, `profile.php`: user access and profile.

### Asset lifecycle and reports

- `receive.php`: receipt/asset entry.
- `assign.php`: manual assignment.
- `return.php`: return processing.
- `active.php`: assigned/active list.
- `inactive.php`: in-stock list.
- `my-assets.php`: current personal/department custody.
- `return-requests.php`: Issuer return-request queue.
- `return-history.php`: return report.
- `audit-report.php`: asset transaction audit.
- `warranty-timeline.php`: expiry report.
- `archive.php`: deletion archive.
- `disposed.php`: scrap list.
- `update.php`: asset update.
- `transaction-form.php`: transaction print layout.
- `print-history.php`: individual lifecycle print view.
- `print-label.php`, `print-labels.php`: single/bulk label layouts.

### Request and scrap

- `app/views/request/index.php`: request queues/lists.
- `app/views/request/create.php`: new equipment request.
- `app/views/request/edit.php`: permitted pre-approval edit.
- `app/views/request/view.php`: summary, route, approvals, allocation, acceptance.
- `app/views/request/history.php`: separate request transaction history.
- `app/views/scrap/create.php`, `index.php`: create/review scrap requests.

### Master data

- Employees: `employee/index.php`, `employee-list.php`, `employee-dept.php`, `emp-assignment.php`, `former.php`.
- Departments: `department/index.php`, `department-list.php`.
- Categories: `assetkat/index.php`, `add.php`.
- Locations: `location/index.php`, `form.php`.
- Units: `unit/index.php`, `add.php`.
- Vendors: `vendor/index.php`, `form.php`.

### SaaS

- `platform/setup.php`: one-time platform administrator setup.
- `platform/login.php`: platform login.
- `platform/index.php`: companies/vouchers/status dashboard.
- `subscription/index.php`: tenant voucher redemption/status.

## Agent server files

| Path | Purpose |
| --- | --- |
| `agent-module/bootstrap.php` | Loads feature/config and Agent classes. |
| `agent-module/public/health.php` | Health JSON endpoint. |
| `agent-module/public/sync.php` | Token-authenticated inventory sync. |
| `agent-module/public/login.php`, `logout.php` | Agent dashboard authentication. |
| `agent-module/public/dashboard.php`, `device.php` | Device list/detail UI entry. |
| `agent-module/src/controllers/Agent.php`, `AgentApi.php` | Dashboard and API actions. |
| `agent-module/src/models/Agent_model.php` | Agent device data operations. |
| `agent-module/src/models/AgentInventory_model.php` | Sync inventory persistence. |
| `agent-module/src/models/AgentDashboardRepository.php` | Agent dashboard queries. |
| `agent-module/src/core/DashboardAuth.php` | Agent admin session guard. |
| `agent-module/src/core/DashboardView.php` | Agent view rendering. |
| `agent-module/views/index.php`, `device.php` | Agent list/detail pages. |
| `agent-module/assets/` | Agent-only styles. |

Root files named `health.php`, `sync.php`, `dashboard.php`, `device.php`, `login.php`, `logout.php`, and `bootstrap.php` are compatibility loaders. Keep them even when the Agent navigation is hidden.

## Windows client files

| File | Purpose |
| --- | --- |
| `UNICOIL-PC-Agent/UnicoilAssetAgent.ps1` | Collects inventory and sends sync payloads. |
| `Install-UnicoilAssetAgent.ps1` | Installs scheduled/runtime Agent files. |
| `Uninstall-UnicoilAssetAgent.ps1` | Removes the Agent safely. |
| `Install-UNICOILPublisherCertificate.ps1` | Installs the approved publisher certificate. |
| `build-setup.ps1` | Builds the Windows setup executable. |
| `build-cpanel-package.ps1` | Builds Agent server deployment package. |
| `config.example.json` | Client configuration template. |
| `README.md`, `DEVELOPER-GUIDE.md` | Client user/build documentation. |

## Integrations and data scripts

- `integrations/google-apps-script/Code.gs`: company Gmail HTTPS mail relay.
- `integrations/google-apps-script/README.md`: relay deployment/configuration.
- `database/migrate.php`: migration status/execution tool.
- `database/install.sql`: Agent-compatible base install component.
- `database/agent_inventory_install.sql`: Agent tables.
- `database/request_workflow_install.sql`: request/approval tables.
- `database/saas_platform_install.sql`: optional platform tables.
- `database/large_scale_performance_migration.sql`: large-list indexes/sequences.
- `database/cpanel_update_*.sql`: dated incremental production updates.
- `database/generate-token.sql`: Agent token generation/rotation helper.
- `database/reset_admin_password.php`, `local_password_hash.php`: controlled support tools; remove/deny web access after use.

Customer data exports and generated deployment packages are excluded from Git and are not installation source files.

## Third-party/static folders

- `app/phpqrcode`: QR dependency.
- `assets`, `css`, `js`: admin theme, DataTables/plugins, and application styling/scripts.
- `img`: logos and UI images.

Upgrade third-party dependencies separately and test the entire interface; do not run broad formatting tools over bundled libraries.
