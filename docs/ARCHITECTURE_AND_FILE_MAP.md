# Architecture and File Map

## Runtime structure

The application uses a custom PHP MVC pattern:

1. `.htaccess` routes browser requests to `index.php`.
2. `index.php` loads `app/init.php`.
3. `app/core/App.php` resolves `/Controller/method/parameter`.
4. A controller validates session/role/input and calls its model.
5. The model uses `app/core/Database.php` for PDO transactions and queries.
6. The controller renders files under `app/views` or returns a document/JSON response.

## Root files and folders

| Path | Purpose |
| --- | --- |
| `.htaccess` | Front-controller routing and protection of private/source files. Preserve on Apache/cPanel. |
| `index.php` | Main web entry point. |
| `config.example.php` | Safe configuration template. Copy to private `config.php`. |
| `config.php` | Private credentials and feature switches; never commit. |
| `agent-module/` | Canonical Windows Agent server/API implementation. |
| `bootstrap.php`, `health.php`, `sync.php`, `dashboard.php`, `device.php`, `login.php`, `logout.php` | Compatibility entry loaders that forward existing Agent URLs into `agent-module`. |
| `app/` | MVC application, business logic, views, mail, PDFs, QR library, and SaaS services. |
| `assets/`, `css/`, `js/`, `img/` | Browser styles, scripts, theme files, and company images. |
| `database/` | Schema installers, incremental migrations, migration runner, and support scripts. |
| `integrations/google-apps-script/` | Optional HTTPS email relay source and setup notes. |
| `public/uploads/` | Runtime images, invoices, documents, and archived evidence; not source-controlled. |
| `output/`, `tmp/` | Generated/runtime files; do not treat as source. |
| `UNICOIL-PC-Agent/` | Windows client source, build scripts, installer assets, and documentation. Generated `dist` output is excluded. |
| `docs/` | Handover, user, deployment, module, and maintenance documentation. |

## Core and authentication files

| File | Purpose |
| --- | --- |
| `app/init.php` | Loads configuration, helpers, core classes, and session context. |
| `app/config/config.php` | Converts the private root config into application constants such as `BASEURL` and feature flags. |
| `app/core/App.php` | Router and disabled legacy-controller protection. |
| `app/core/Controller.php` | Base controller, model/view loading, role checks, and common guards. |
| `app/core/Database.php` | PDO connection, prepared queries, binding, transactions, and result helpers. |
| `app/core/Auth.php` | Signed-in user, roles, employee link, and permission helpers. |
| `app/core/Flasher.php` | Session success/error messages after redirects. |
| `app/controllers/Home.php` | Login, logout, password recovery/registration-compatible entry actions. |
| `app/models/Home_model.php` | Login lookup and password verification. |
| `app/views/home/index.php` | Branded sign-in page. |
| `app/views/templates/header_a.php`, `footer_a.php` | Shared authenticated layout, navigation, notifications, scripts, and feature visibility. |

## Application modules

| Module | Controller | Model | Main views |
| --- | --- | --- | --- |
| Dashboard | `Dashboard.php` | `Dashboard_model.php` | `views/dashboard/index.php`, `analytics.php` |
| Asset lifecycle | `Asset.php` | `Asset_model.php` | `views/asset/*` |
| Equipment approvals | `Request.php` | `Request_model.php` | `views/request/*` |
| Scrap approval | `Scrap.php` | `Scrap_model.php` | `views/scrap/*` |
| Employees | `Employee.php` | `Employee_model.php` | `views/employee/*` |
| Departments | `Department.php` | `Department_model.php` | `views/department/*` |
| Categories | `Assetkat.php` | `Assetkat_model.php` | `views/assetkat/*` |
| Locations | `Location.php` | `Location_model.php` | `views/location/*` |
| Units | `Unit.php` | `Unit_model.php` | `views/unit/*` |
| Vendors | `Vendor.php` | `Vendor_model.php` | `views/vendor/*` |
| Users/profile | `User.php` | `User_model.php` | `views/user/*` |
| Optional SaaS platform | `Platform.php`, `Subscription.php` | SaaS classes in `app/core` | `views/platform/*`, `views/subscription/*` |
| Windows Agent compatibility | `Agent.php`, `AgentApi.php` | `Agent_model.php`, `AgentInventory_model.php`, `AgentDashboardRepository.php` | Canonical UI/API under `agent-module/` |

## Asset view file purposes

| View | Purpose |
| --- | --- |
| `asset/receive.php` | Asset receipt/batch entry form. |
| `asset/assign.php` | Manual guided assignment form. |
| `asset/return.php` | Manual/issuer return form with batch selection and per-asset condition. |
| `asset/active.php` | Assigned assets listing and bulk-label actions. |
| `asset/inactive.php` | In-stock assets listing, filters, edit, and labels. |
| `asset/return-history.php` | Complete return history, grouping, filters, and documents. |
| `asset/audit-report.php` | Physical asset lifecycle audit and exports. |
| `asset/warranty-timeline.php` | Searchable/sortable expiry-duration report. |
| `asset/archive.php`, `disposed.php` | Deletion archive and scrap list. |
| `asset/my-assets.php` | Current user/department custody and return actions. |
| `asset/transaction-form.php` | Assignment/return printable transaction form. |
| `asset/print-history.php` | Single-asset lifecycle print layout. |
| `asset/print-label.php`, `print-labels.php` | Single and bulk adhesive label layouts. |
| `asset/update.php` | Asset update form and evidence handling. |
| `asset/return-requests.php` | Issuer queue for employee return requests. |

## Document, QR, and email files

| File/path | Purpose |
| --- | --- |
| `app/core/SimplePdfReport.php` | Assignment, return, audit, report, and lifecycle PDF generation. |
| `app/phpqrcode/` | Bundled QR generation library. Treat as third-party code. |
| `app/core/AssignmentMailer.php` | Builds transaction mail and chooses a configured transport. |
| `app/core/GoogleAppsScriptTransport.php` | HTTPS mail relay integration. |
| `app/core/SmtpTransport.php` | Direct SMTP integration where hosting permits it. |
| `app/config/mail_secret.example.php` | Safe mail secret template. |
| `app/config/mail_secret.php` | Private mail secret; never commit. |

## SaaS files

| File | Purpose |
| --- | --- |
| `app/core/SaasGate.php` | Enables/disables the subscription gate and protects tenant access. |
| `app/core/SaasPlatform.php` | Platform database access for companies, vouchers, and administrators. |
| `app/core/SaasTenantContext.php` | Resolves current company and tenant database connection. |
| `app/core/SaasCrypto.php` | Encrypts/decrypts tenant database credentials. |
| `app/controllers/Platform.php` | Platform administrator setup/login/company/voucher operations. |
| `app/controllers/Subscription.php` | Company voucher redemption and subscription page. |

## Files not part of production flow

`About.php`, `Mahasiswa.php`, `Member.php` and matching models/views are legacy/demo remnants. `app/core/App.php` disables these controllers. Do not re-enable or copy their patterns into production modules; they can be removed only in a separately tested cleanup release.

## Where to make a change

- Page appearance: matching file in `app/views`, then shared CSS if needed.
- Validation/authorization: controller and model; never JavaScript alone.
- Database query/transaction: model and an incremental migration.
- Navigation/feature visibility: shared template/sidebar plus backend role guard.
- PDF layout: `SimplePdfReport.php` or the named print view.
- Feature switch: private `config.php`, corresponding constant in `app/config/config.php`, and guarded controller/view.
- Agent API: `agent-module`; keep root loader URLs compatible.

Before editing, read the related file, locate every caller with `rg`, preserve database transactions, and test both an allowed and denied role.
