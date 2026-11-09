[CmdletBinding()]
param(
    [ValidatePattern('^https://')][string]$ServerUrl = '',
    [string]$ApiToken = '',
    [string]$AssetNumber = '',
    [ValidateRange(15, 1440)][int]$IntervalMinutes = 360,
    [string]$InstallPath = "$env:ProgramData\UNICOIL\AssetAgent",
    [string]$SetupConfigPath = '',
    [string]$SetupExecutablePath = ''
)

# Elevated Windows client installer. The web API is maintained separately in
# the main application's agent-module folder and is never copied to a PC.
$ErrorActionPreference = 'Stop'
$principal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this installer from an elevated PowerShell window.'
}

if ($SetupConfigPath) {
    # The GUI passes secrets through a temporary ACL-protected file so the API
    # token is not exposed in the PowerShell process command line.
    if (-not (Test-Path -LiteralPath $SetupConfigPath)) {
        throw 'The protected setup configuration file was not found.'
    }
    $setupConfiguration = Get-Content -LiteralPath $SetupConfigPath -Raw | ConvertFrom-Json
    $ServerUrl = [string]$setupConfiguration.serverUrl
    $ApiToken = [string]$setupConfiguration.apiToken
    $AssetNumber = [string]$setupConfiguration.assetNumber
    if ($setupConfiguration.intervalMinutes) {
        $IntervalMinutes = [int]$setupConfiguration.intervalMinutes
    }
    if ($setupConfiguration.PSObject.Properties.Name -contains 'setupExecutablePath') {
        $SetupExecutablePath = [string]$setupConfiguration.setupExecutablePath
    }
}
if ($ServerUrl -notmatch '^https://') {
    throw 'ServerUrl must use HTTPS.'
}
if ($ApiToken -notmatch '^uia_[0-9a-fA-F]{64}$') {
    throw 'ApiToken is not a valid UNICOIL agent token.'
}

$sourceAgent = Join-Path $PSScriptRoot 'UnicoilAssetAgent.ps1'
if (-not (Test-Path -LiteralPath $sourceAgent)) {
    throw "Agent script not found beside installer: $sourceAgent"
}

New-Item -ItemType Directory -Path $InstallPath -Force | Out-Null
$targetAgent = Join-Path $InstallPath 'UnicoilAssetAgent.ps1'
$configPath = Join-Path $InstallPath 'config.json'
Copy-Item -LiteralPath $sourceAgent -Destination $targetAgent -Force
$targetApplication = Join-Path $InstallPath 'UNICOILAssetAgent.exe'
if ($SetupExecutablePath -and (Test-Path -LiteralPath $SetupExecutablePath)) {
    Copy-Item -LiteralPath $SetupExecutablePath -Destination $targetApplication -Force
}

$deviceId = [guid]::NewGuid().ToString()
if (Test-Path -LiteralPath $configPath) {
    try {
        $existingConfiguration = Get-Content -LiteralPath $configPath -Raw | ConvertFrom-Json
        $parsedDeviceId = [guid]::Empty
        if ([guid]::TryParse([string]$existingConfiguration.deviceId, [ref]$parsedDeviceId)) {
            $deviceId = $parsedDeviceId.ToString()
        }
    } catch {
        Write-Warning 'Existing configuration is invalid; a new device ID will be generated.'
    }
}

$configuration = [ordered]@{
    serverUrl = $ServerUrl.TrimEnd('/')
    apiToken = $ApiToken.Trim()
    deviceId = $deviceId
    assetNumber = $AssetNumber.Trim()
}
$configuration | ConvertTo-Json | Set-Content -LiteralPath $configPath -Encoding UTF8

# Protect the token-bearing installation directory for SYSTEM/Administrators.
& icacls.exe $InstallPath /inheritance:r /grant:r 'SYSTEM:(OI)(CI)F' 'Administrators:(OI)(CI)F' | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw 'Unable to secure the agent installation directory.'
}

$taskName = 'UNICOIL Asset Inventory Agent'
# Run at startup and periodically as SYSTEM. Reinstallation safely replaces the
# existing task instead of creating duplicate background jobs.
$arguments = '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File "{0}" -ConfigPath "{1}"' -f $targetAgent, $configPath
$action = New-ScheduledTaskAction -Execute "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe" -Argument $arguments
$startupTrigger = New-ScheduledTaskTrigger -AtStartup
$repeatTriggerParameters = @{
    Once = $true
    At = (Get-Date).AddMinutes(1)
    RepetitionInterval = (New-TimeSpan -Minutes $IntervalMinutes)
    RepetitionDuration = (New-TimeSpan -Days 3650)
}
$repeatTrigger = New-ScheduledTaskTrigger @repeatTriggerParameters
$settingsParameters = @{
    StartWhenAvailable = $true
    MultipleInstances = 'IgnoreNew'
    ExecutionTimeLimit = (New-TimeSpan -Minutes 10)
}
$settings = New-ScheduledTaskSettingsSet @settingsParameters
$taskPrincipal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -LogonType ServiceAccount -RunLevel Highest
$task = New-ScheduledTask -Action $action -Trigger @($startupTrigger, $repeatTrigger) -Settings $settings -Principal $taskPrincipal
Register-ScheduledTask -TaskName $taskName -InputObject $task -Force | Out-Null
Start-ScheduledTask -TaskName $taskName

# These shortcuts open only the installed local status application.
$shortcutLocations = @(
    [Environment]::GetFolderPath([Environment+SpecialFolder]::CommonDesktopDirectory),
    [Environment]::GetFolderPath([Environment+SpecialFolder]::CommonPrograms)
)
$shell = New-Object -ComObject WScript.Shell
foreach ($shortcutLocation in $shortcutLocations) {
    if (-not [string]::IsNullOrWhiteSpace($shortcutLocation)) {
        New-Item -ItemType Directory -Path $shortcutLocation -Force | Out-Null
        Remove-Item -LiteralPath (Join-Path $shortcutLocation 'UNICOIL Asset Agent.url') -Force -ErrorAction SilentlyContinue
        $shortcut = $shell.CreateShortcut((Join-Path $shortcutLocation 'UNICOIL Asset Agent.lnk'))
        if (Test-Path -LiteralPath $targetApplication) {
            $shortcut.TargetPath = $targetApplication
            $shortcut.Arguments = '--status'
            $shortcut.IconLocation = "$targetApplication,0"
        } else {
            $shortcut.TargetPath = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
            $shortcut.Arguments = '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File "{0}" -ConfigPath "{1}"' -f $targetAgent, $configPath
            $shortcut.IconLocation = "$env:SystemRoot\System32\imageres.dll,109"
        }
        $shortcut.WorkingDirectory = $InstallPath
        $shortcut.Description = 'Open the installed UNICOIL Asset Agent status window'
        $shortcut.WindowStyle = 1
        $shortcut.Save()
    }
}

Write-Host "UNICOIL Asset Agent installed successfully."
Write-Host "Task: $taskName"
Write-Host "Configuration: $configPath"
Write-Host 'Local Agent shortcuts created on the desktop and Start Menu.'
