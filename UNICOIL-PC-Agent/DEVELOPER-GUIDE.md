# Windows Agent Developer Guide

## Boundary of this module

`UNICOIL-PC-Agent` is the Windows endpoint client. Its responsibilities are:

1. collect hardware, operating-system, disk, network, and installed-software data;
2. authenticate to the configured HTTPS sync endpoint;
3. install and maintain one Windows scheduled task;
4. provide a small local status window;
5. remove the local installation cleanly.

Server PHP, SQL, dashboard authorization, device linking, and inventory storage
belong to `../agent-module/`. Keeping this boundary prevents the Windows build
from accidentally distributing database credentials or production server files.

## Runtime flow

```text
Setup executable
  -> protected temporary setup JSON
  -> Install-UnicoilAssetAgent.ps1
  -> C:\ProgramData\UNICOIL\AssetAgent
  -> SYSTEM scheduled task
  -> UnicoilAssetAgent.ps1
  -> HTTPS POST to sync.php
  -> agent-module AgentApi controller
  -> Agent inventory tables
```

## Source map

### `UnicoilAssetAgent.ps1`

- Reads `config.json`.
- Collects computer, BIOS, OS, CPU, memory, disk, network, and software data.
- Uses registry uninstall entries for software; it does not query
  `Win32_Product`.
- Builds schema version 1 JSON.
- Sends the API token in Bearer and compatibility headers.
- Rotates `agent.log` after it exceeds 5 MB.

Change this file when collected inventory or the payload changes. Any payload
change must remain compatible with `../agent-module/src/controllers/AgentApi.php`.

### `Install-UnicoilAssetAgent.ps1`

- Requires Administrator rights.
- Accepts values directly or from the setup application's protected temporary file.
- Preserves a valid existing device UUID during reinstall/upgrade.
- restricts the installation directory to SYSTEM and local Administrators;
- creates one SYSTEM scheduled task at startup and at the configured interval;
- creates public Desktop and Start Menu shortcuts to the local status UI.

Change this file only for installation location, schedule, permissions, or
shortcut behavior.

### `Uninstall-UnicoilAssetAgent.ps1`

Removes the scheduled task, installed local files, and shortcuts. It intentionally
does not call the server to delete device history.

### `setup/Program.cs`

Contains two Windows Forms:

- `SetupForm`: server test, installation, and uninstall controls.
- `AgentStatusForm`: installed status, sync-now, refresh, and uninstall controls.

It embeds the three PowerShell scripts at build time. When one of those scripts
changes, rebuild the executable.

### `build-setup.ps1`

1. parses all embedded PowerShell scripts and stops on syntax errors;
2. locates the .NET Framework C# compiler;
3. embeds the PowerShell scripts into the setup executable;
4. optionally signs the result using a valid code-signing certificate;
5. places distributable output in `dist`.

No PHP or SQL is included in the output.

## Configuration contract

Installed `config.json` contains:

```json
{
  "serverUrl": "https://example.com/IT-Assets/sync.php",
  "apiToken": "uia_secret_token",
  "deviceId": "generated-guid",
  "assetNumber": "optional-existing-asset-number"
}
```

- `serverUrl` must be HTTPS outside local development.
- `apiToken` is issued and validated by the server; never commit a live value.
- `deviceId` identifies one Windows installation and is preserved on reinstall.
- `assetNumber` is optional and links the device to a manual asset.

## Safe edit rules

- Do not put production credentials, tokens, private keys, or database dumps here.
- Do not duplicate `agent-module` under this folder.
- Do not add asset assignment, approval, return, or scrap business logic here.
- Keep payload field names synchronized with the server controller.
- Keep the reported Agent version and setup assembly version intentionally aligned.
- Validate scripts and build the setup executable after every client change.

## Validation before release

### PowerShell syntax

```powershell
$files = Get-ChildItem *.ps1
foreach ($file in $files) {
    $tokens = $null
    $errors = $null
    [System.Management.Automation.Language.Parser]::ParseFile(
        $file.FullName,
        [ref]$tokens,
        [ref]$errors
    ) | Out-Null

    if ($errors.Count) {
        $errors
        throw "PowerShell validation failed: $($file.Name)"
    }
}
```

### Build

```powershell
.\build-setup.ps1
```

### Test PC checklist

1. Run the setup as Administrator.
2. Confirm the health URL responds.
3. Test the server using the full API token.
4. Install and confirm the first sync succeeds.
5. Confirm the scheduled task runs as SYSTEM.
6. Confirm hardware, disks, and software appear in the web Agent dashboard.
7. Confirm **Sync Now**, **Refresh**, and **Uninstall** work.
8. Reinstall once and confirm the device UUID is preserved.

## Troubleshooting

- **Invalid token:** verify that the complete `uia_...` value is being used and
  that its server record is active and not expired.
- **Connection timed out:** verify HTTPS access, DNS, firewall/proxy policy, and
  the exact deployed sync URL.
- **Test Server fails but health works:** confirm the setup derives `health.php`
  from the chosen `sync.php` URL correctly.
- **Hardware missing:** run the collector as Administrator and inspect
  `C:\ProgramData\UNICOIL\AssetAgent\agent.log`.
- **Software missing:** verify the application has a Windows uninstall registry
  entry; portable software may not appear.
- **Duplicate device:** confirm each PC has its own generated `deviceId`; do not
  copy an installed `config.json` between computers.
