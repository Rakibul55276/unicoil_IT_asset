# UNICOIL Windows PC Agent

This folder contains only the Windows client used to collect PC hardware and
installed-software inventory. The server/API implementation is maintained once,
in the main project at `../agent-module/`.

The Agent does not replace manual asset entry. It only synchronizes technical
inventory for a Windows PC and can optionally link that device to an existing
manual asset number.

## Folder contents

| File | Purpose |
| --- | --- |
| `UnicoilAssetAgent.ps1` | Collects PC inventory and sends it to the configured HTTPS sync URL. |
| `Install-UnicoilAssetAgent.ps1` | Installs the collector, protected config, scheduled task, and shortcuts. |
| `Uninstall-UnicoilAssetAgent.ps1` | Removes the local task, files, and shortcuts. |
| `setup/Program.cs` | Graphical setup and installed status-window source. |
| `build-setup.ps1` | Validates the scripts and builds the setup executable. |
| `Install-UNICOILPublisherCertificate.ps1` | Optionally trusts the public company signing certificate. |
| `config.example.json` | Example configuration format; never place a real token in this file. |
| `DEVELOPER-GUIDE.md` | Maintainer guide, execution flow, build, and troubleshooting. |

The former `server-api` duplicate and cPanel packaging script were removed. Do
not add server PHP, SQL, production configuration, or database credentials to
this folder.

## Normal installation

1. Build `dist\UNICOILAssetAgentSetup.exe` using `build-setup.ps1`.
2. Copy the setup executable to the target PC.
3. Run it as Administrator.
4. Enter the deployed sync URL and an active Agent API token.
5. Optionally enter an existing asset number to link this PC immediately.
6. Select **Test Server**, then **Install Agent**.

The installed status shortcut provides only **Sync Now**, **Refresh**, and
**Uninstall**. Inventory continues automatically through a SYSTEM scheduled task
when the status window is closed.

The sync URL depends on deployment. Examples:

```text
https://example.com/IT-Assets/sync.php
https://example.com/agent-api/sync.php
```

Use the URL that maps to the compatibility `sync.php` loader in the deployed web
application. The corresponding `health.php` URL should return JSON before an
installation is attempted.

## Manual PowerShell installation

Run an elevated PowerShell window in this folder:

```powershell
.\Install-UnicoilAssetAgent.ps1 `
  -ServerUrl 'https://example.com/IT-Assets/sync.php' `
  -ApiToken 'uia_your_complete_token' `
  -AssetNumber 'DT0001' `
  -IntervalMinutes 360
```

Do not email, commit, screenshot, or store a real token in documentation. The
installer stores it under `C:\ProgramData\UNICOIL\AssetAgent` and restricts that
directory to SYSTEM and local Administrators.

## Build

From an elevated or normal PowerShell window:

```powershell
.\build-setup.ps1
```

The script validates all embedded PowerShell sources before compiling the C#
setup application. If no suitable company code-signing certificate is found,
the executable is produced unsigned and a warning is shown.

## Server-side setup

Server installation, database SQL, token creation, dashboard access, and API
maintenance are documented in `../agent-module/README.md` and the main project
documentation. They are intentionally not packaged with this Windows client.

## Uninstall

Open the installed Agent shortcut and select **Uninstall**, run the setup again
and select **Uninstall**, or run this script as Administrator:

```powershell
.\Uninstall-UnicoilAssetAgent.ps1
```

Uninstalling a PC does not delete its server inventory history.
