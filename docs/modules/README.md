# Module Index

The application is logically separated here for maintainability without moving runtime MVC files.

| Module | Guide | Runtime source |
| --- | --- | --- |
| Asset lifecycle | [ASSET_LIFECYCLE.md](ASSET_LIFECYCLE.md) | `Asset.php`, `Asset_model.php`, `views/asset` |
| Accessory inventory | [ACCESSORY_INVENTORY.md](ACCESSORY_INVENTORY.md) | `Accessory.php`, `Accessory_model.php`, `views/accessory` |
| Approval/request workflow | [APPROVAL_FLOW.md](APPROVAL_FLOW.md) | `Request.php`, `Request_model.php`, `views/request` |
| Administration/security | [ADMIN_FLOW.md](ADMIN_FLOW.md) | Employee/Department/User/master-data controllers/models/views |
| Reports/documents | [REPORTS_AND_DOCUMENTS.md](REPORTS_AND_DOCUMENTS.md) | Asset report actions/views and `SimplePdfReport.php` |
| Email | [EMAIL_NOTIFICATIONS.md](EMAIL_NOTIFICATIONS.md) | Mailer/transports and `integrations/google-apps-script` |
| Windows Agent | [WINDOWS_AGENT.md](WINDOWS_AGENT.md) | `agent-module`, compatibility loaders, client source |
| SaaS platform | [SAAS_PLATFORM.md](SAAS_PLATFORM.md) | SaaS core/controllers/views and platform DB |

When changing a module, preserve its controller authorization, model transaction boundaries, view inputs, database constraints, audit events, and documents as one contract.
