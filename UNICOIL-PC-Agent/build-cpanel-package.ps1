[CmdletBinding()]
param()

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$source = Join-Path $PSScriptRoot 'server-api'
$outputDirectory = Join-Path $PSScriptRoot 'dist'
$output = Join-Path $outputDirectory 'UNICOIL-IT-Agent-Complete-cPanel.zip'
if (-not (Test-Path -LiteralPath $source)) {
    throw 'The standalone server-api folder was not found.'
}
New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
if (Test-Path -LiteralPath $output) {
    Remove-Item -LiteralPath $output -Force
}

$archive = [System.IO.Compression.ZipFile]::Open(
    $output,
    [System.IO.Compression.ZipArchiveMode]::Create
)
try {
    foreach ($file in Get-ChildItem -LiteralPath $source -Recurse -File -Force) {
        $relative = $file.FullName.Substring($source.Length).TrimStart('\', '/').Replace('\', '/')
        if ($relative -eq 'config.php' -or
            $relative -eq 'app/config/config.php' -or
            $relative -eq 'app/config/mail_secret.php' -or
            $relative.StartsWith('public/uploads/')) {
            continue
        }
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $archive,
            $file.FullName,
            $relative,
            [System.IO.Compression.CompressionLevel]::Optimal
        ) | Out-Null
    }
} finally {
    $archive.Dispose()
}

$hash = (Get-FileHash -LiteralPath $output -Algorithm SHA256).Hash
Write-Host "Created: $output"
Write-Host "SHA256: $hash"
