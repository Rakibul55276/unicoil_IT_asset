[CmdletBinding()]
param(
    [string]$InstallPath = "$env:ProgramData\UNICOIL\AssetAgent",
    [int]$WaitForProcessId = 0
)

# Removes the local Agent task, files, and shortcuts. Server inventory history
# remains available to authorized administrators for audit purposes.
$ErrorActionPreference = 'Stop'
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this uninstaller from an elevated PowerShell window.'
}

if ($WaitForProcessId -gt 0) {
    Wait-Process -Id $WaitForProcessId -ErrorAction SilentlyContinue
}

$taskName = 'UNICOIL Asset Inventory Agent'
# Stop future collection before deleting the installed client files.
Unregister-ScheduledTask -TaskName $taskName -Confirm:$false -ErrorAction SilentlyContinue
if (Test-Path -LiteralPath $InstallPath) {
    Remove-Item -LiteralPath $InstallPath -Recurse -Force
}
$shortcutLocations = @(
    [Environment]::GetFolderPath([Environment+SpecialFolder]::CommonDesktopDirectory),
    [Environment]::GetFolderPath([Environment+SpecialFolder]::CommonPrograms)
)
foreach ($shortcutLocation in $shortcutLocations) {
    if (-not [string]::IsNullOrWhiteSpace($shortcutLocation)) {
        Remove-Item -LiteralPath (Join-Path $shortcutLocation 'UNICOIL Asset Agent.lnk') -Force -ErrorAction SilentlyContinue
        Remove-Item -LiteralPath (Join-Path $shortcutLocation 'UNICOIL Asset Agent.url') -Force -ErrorAction SilentlyContinue
    }
}
Write-Host 'UNICOIL Asset Agent removed.'
