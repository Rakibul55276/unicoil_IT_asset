# Windows Agent Module

## Purpose

The optional Agent collects authorized Windows device hardware, disk, operating-system, and installed-software inventory and synchronizes it to the server. It supplements manual asset registration; it does not replace manual custody, assignment, or return workflows.

## Server structure

- Canonical implementation: `agent-module/`
- Compatibility endpoints at project root: `bootstrap.php`, `health.php`, `sync.php`, `dashboard.php`, `device.php`, `login.php`, `logout.php`
- Database tables: `agent_api_token`, `agent_device`, `agent_disk`, `agent_software`, `agent_sync_log`
- Installer/client source: `UNICOIL-PC-Agent/`

Root loaders preserve previously distributed Agent URLs. Make new server changes in `agent-module`, not a duplicated `server-api` copy.

## Enable/disable

Set in private `config.php`:

```php
'agent_enabled' => true,
```

When false, Agent synchronization/runtime integration is disabled while the manual asset system remains unchanged.

## Setup outline

1. Import `database/agent_inventory_install.sql` if Agent tables are absent.
2. Generate a `uia_...` token using the approved token tool/SQL; only its hash is retained.
3. Set the client sync URL to the public root `sync.php` compatibility endpoint.
4. Test root `health.php`.
5. Install the Windows client with administrative permission and the token.
6. Run Sync Now once, then verify device, hardware, disk, software, and sync-log records.

The same token may be provisioned to the intended fleet, but rotation/revocation affects all clients using it. Use separate named tokens for isolation where operationally practical.

## Build/release

Build scripts and client source belong in Git. Generated `bin`, `obj`, `dist`, installers, and certificates/private signing keys do not. Publish approved installers in GitHub Releases and retain signing material in a secured certificate store.
