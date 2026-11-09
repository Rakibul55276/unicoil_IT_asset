[CmdletBinding()]
param(
    [string]$ConfigPath = (Join-Path $PSScriptRoot 'config.json')
)

# Windows inventory collector.
#
# Installed under C:\ProgramData\UNICOIL\AssetAgent and launched by the
# scheduled task created by Install-UnicoilAssetAgent.ps1. It reads the
# protected config.json, collects inventory, and posts one authenticated JSON
# payload to the server. It never changes asset assignment/return transactions.
$ErrorActionPreference = 'Stop'
$AgentVersion = '1.0.0'
$LogPath = Join-Path $PSScriptRoot 'agent.log'

function Write-AgentLog {
    param([string]$Message, [string]$Level = 'INFO')
    $line = '{0:u} [{1}] {2}' -f (Get-Date), $Level, $Message
    Add-Content -LiteralPath $LogPath -Value $line -Encoding UTF8
    if ((Test-Path -LiteralPath $LogPath) -and (Get-Item -LiteralPath $LogPath).Length -gt 5MB) {
        Move-Item -LiteralPath $LogPath -Destination ($LogPath + '.1') -Force
    }
}

function Convert-InstallDate {
    param([object]$Value)
    $text = [string]$Value
    if ($text -match '^(\d{4})(\d{2})(\d{2})$') {
        return '{0}-{1}-{2}' -f $Matches[1], $Matches[2], $Matches[3]
    }
    return $null
}

function Get-InstalledSoftware {
    # Use uninstall registry entries. Win32_Product is intentionally avoided
    # because querying it can trigger slow MSI consistency checks and repairs.
    $locations = @(
        @{ Path = 'HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*'; Architecture = 'x64' },
        @{ Path = 'HKLM:\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*'; Architecture = 'x86' }
    )
    $items = New-Object System.Collections.Generic.List[object]
    $seen = @{}

    foreach ($location in $locations) {
        $entries = Get-ItemProperty -Path $location.Path -ErrorAction SilentlyContinue
        foreach ($entry in $entries) {
            $name = ([string]$entry.DisplayName).Trim()
            if ([string]::IsNullOrWhiteSpace($name) -or $entry.SystemComponent -eq 1) {
                continue
            }
            $version = ([string]$entry.DisplayVersion).Trim()
            $publisher = ([string]$entry.Publisher).Trim()
            $key = ('{0}|{1}|{2}|{3}' -f $name, $version, $publisher, $location.Architecture).ToLowerInvariant()
            if ($seen.ContainsKey($key)) {
                continue
            }
            $seen[$key] = $true
            $items.Add([ordered]@{
                name = $name
                version = if ($version) { $version } else { $null }
                publisher = if ($publisher) { $publisher } else { $null }
                installDate = Convert-InstallDate $entry.InstallDate
                architecture = $location.Architecture
            })
        }
    }
    return @($items | Sort-Object { $_.name }, { $_.version })
}

function Get-NetworkIdentity {
    # Prefer an active adapter with a default gateway, then fall back to any
    # enabled adapter with a MAC address.
    $adapters = Get-CimInstance Win32_NetworkAdapterConfiguration -Filter 'IPEnabled=True' |
        Where-Object { $_.MACAddress } |
        Sort-Object -Property Index
    foreach ($adapter in $adapters) {
        $address = @($adapter.IPAddress | Where-Object {
            $_ -match '^\d{1,3}(\.\d{1,3}){3}$' -and $_ -notmatch '^127\.' -and $_ -notmatch '^169\.254\.'
        } | Select-Object -First 1)
        if ($address.Count -gt 0) {
            return @{ primaryIp = [string]$address[0]; macAddress = ([string]$adapter.MACAddress).Replace('-', ':').ToUpperInvariant() }
        }
    }
    return @{ primaryIp = $null; macAddress = $null }
}

function Get-DiskInventory {
    # Report fixed local disks only; removable/network drives are excluded.
    $items = New-Object System.Collections.Generic.List[object]
    $logicalDisks = Get-CimInstance Win32_LogicalDisk -Filter 'DriveType=3'
    foreach ($disk in $logicalDisks) {
        $items.Add([ordered]@{
            deviceKey = [string]$disk.DeviceID
            volumeLabel = if ($disk.VolumeName) { [string]$disk.VolumeName } else { $null }
            filesystem = if ($disk.FileSystem) { [string]$disk.FileSystem } else { $null }
            sizeBytes = if ($null -ne $disk.Size) { [int64]$disk.Size } else { $null }
            freeBytes = if ($null -ne $disk.FreeSpace) { [int64]$disk.FreeSpace } else { $null }
            healthStatus = 'Unknown'
        })
    }
    return $items.ToArray()
}

try {
    if (-not (Test-Path -LiteralPath $ConfigPath)) {
        throw "Configuration file not found: $ConfigPath"
    }
    $config = Get-Content -LiteralPath $ConfigPath -Raw | ConvertFrom-Json
    if ([string]::IsNullOrWhiteSpace([string]$config.serverUrl) -or
        [string]::IsNullOrWhiteSpace([string]$config.apiToken) -or
        [string]::IsNullOrWhiteSpace([string]$config.deviceId)) {
        throw 'Configuration requires serverUrl, apiToken, and deviceId.'
    }

    [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
# Build the versioned payload accepted by agent-module's AgentApi controller.
$computer = Get-CimInstance Win32_ComputerSystem
    $bios = Get-CimInstance Win32_BIOS
    $os = Get-CimInstance Win32_OperatingSystem
    $processors = @(Get-CimInstance Win32_Processor)
    $processor = $processors | Select-Object -First 1
    $network = Get-NetworkIdentity
    $assetNumber = ([string]$config.assetNumber).Trim()

    $payload = [ordered]@{
        schemaVersion = 1
        requestId = [guid]::NewGuid().ToString()
        collectedAt = (Get-Date).ToUniversalTime().ToString('o')
        agent = [ordered]@{ version = $AgentVersion }
        device = [ordered]@{
            deviceId = ([string]$config.deviceId).ToLowerInvariant()
            assetNumber = if ($assetNumber) { $assetNumber } else { $null }
            hostname = [Environment]::MachineName
            serialNumber = ([string]$bios.SerialNumber).Trim()
            manufacturer = ([string]$computer.Manufacturer).Trim()
            model = ([string]$computer.Model).Trim()
            osName = ([string]$os.Caption).Trim()
            osVersion = ([string]$os.Version).Trim()
            osBuild = ([string]$os.BuildNumber).Trim()
            osArchitecture = ([string]$os.OSArchitecture).Trim()
            cpuName = ([string]$processor.Name).Trim()
            cpuCores = [int](($processors | Measure-Object -Property NumberOfCores -Sum).Sum)
            memoryBytes = [int64]$computer.TotalPhysicalMemory
            primaryIp = $network.primaryIp
            macAddress = $network.macAddress
            lastBootAt = ([datetime]$os.LastBootUpTime).ToUniversalTime().ToString('o')
        }
        disks = @(Get-DiskInventory)
        software = @(Get-InstalledSoftware)
    }

    $json = $payload | ConvertTo-Json -Depth 7 -Compress
# Send the standard Bearer header plus the compatibility X-Agent-Token header.
$headers = @{
        'X-Agent-Token' = [string]$config.apiToken
        'Authorization' = 'Bearer ' + [string]$config.apiToken
    }
    $request = @{
        Uri = [string]$config.serverUrl
        Method = 'Post'
        Headers = $headers
        ContentType = 'application/json; charset=utf-8'
        Body = $json
        TimeoutSec = 120
    }
    $response = Invoke-RestMethod @request
    if (-not $response.ok) {
        throw 'Server rejected the inventory payload.'
    }
    Write-AgentLog ('Sync completed. Device ID {0}; disks {1}; software {2}.' -f $response.deviceId, $response.diskCount, $response.softwareCount)
    exit 0
} catch {
    $details = $_.Exception.Message
    if ($_.ScriptStackTrace) {
        $details += ' | ' + $_.ScriptStackTrace
    }
    Write-AgentLog $details 'ERROR'
    exit 1
}
