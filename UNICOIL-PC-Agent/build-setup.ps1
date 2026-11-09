[CmdletBinding()]
param(
    [string]$OutputName = 'UNICOILAssetAgentSetup.exe',
    [string]$SigningThumbprint = ''
)

# Build the Windows setup executable from the C# UI and embedded PowerShell
# client scripts. No server/API source is included in the resulting installer.
$ErrorActionPreference = 'Stop'
$root = $PSScriptRoot
$compiler = "$env:WINDIR\Microsoft.NET\Framework64\v4.0.30319\csc.exe"
if (-not (Test-Path -LiteralPath $compiler)) {
    throw 'The .NET Framework 4.x C# compiler was not found.'
}

$agentScripts = @(
    (Join-Path $root 'UnicoilAssetAgent.ps1'),
    (Join-Path $root 'Install-UnicoilAssetAgent.ps1'),
    (Join-Path $root 'Uninstall-UnicoilAssetAgent.ps1')
)
# Stop before compilation if any embedded PowerShell source has syntax errors.
foreach ($script in $agentScripts) {
    $tokens = $null
    $parseErrors = $null
    [System.Management.Automation.Language.Parser]::ParseFile(
        $script,
        [ref]$tokens,
        [ref]$parseErrors
    ) | Out-Null

    if ($parseErrors.Count -gt 0) {
        foreach ($parseError in $parseErrors) {
            Write-Error ("{0}, line {1}: {2}" -f
                (Split-Path $script -Leaf),
                $parseError.Extent.StartLineNumber,
                $parseError.Message)
        }
        throw 'PowerShell validation failed. Correct the errors shown above before building.'
    }
    Write-Host ("Validated: " + (Split-Path $script -Leaf))
}

$outputDirectory = Join-Path $root 'dist'
New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
$outputFile = Join-Path $outputDirectory $OutputName

$arguments = @(
    '/nologo', '/target:winexe', '/platform:anycpu', '/optimize+',
    ('/out:' + $outputFile),
    ('/win32manifest:' + (Join-Path $root 'setup\app.manifest')),
    ('/resource:' + (Join-Path $root 'UnicoilAssetAgent.ps1') + ',UnicoilAssetAgent.ps1'),
    ('/resource:' + (Join-Path $root 'Install-UnicoilAssetAgent.ps1') + ',Install-UnicoilAssetAgent.ps1'),
    ('/resource:' + (Join-Path $root 'Uninstall-UnicoilAssetAgent.ps1') + ',Uninstall-UnicoilAssetAgent.ps1'),
    '/reference:System.dll',
    '/reference:System.Drawing.dll',
    '/reference:System.Windows.Forms.dll',
    '/reference:System.Web.Extensions.dll',
    (Join-Path $root 'setup\Program.cs')
)

& $compiler $arguments
if ($LASTEXITCODE -ne 0) {
    throw "Setup compilation failed with exit code $LASTEXITCODE."
}

$certificate = $null
if ($SigningThumbprint) {
    $certificate = Get-Item -LiteralPath ("Cert:\CurrentUser\My\" + $SigningThumbprint) -ErrorAction SilentlyContinue
} else {
    $certificate = Get-ChildItem Cert:\CurrentUser\My | Where-Object {
        $_.Subject -eq 'CN=UNICOIL' -and
        $_.HasPrivateKey -and
        $_.NotAfter -gt (Get-Date) -and
        $_.EnhancedKeyUsageList.ObjectId -contains '1.3.6.1.5.5.7.3.3'
    } | Sort-Object NotAfter -Descending | Select-Object -First 1
}

if ($certificate) {
    $signature = Set-AuthenticodeSignature -FilePath $outputFile -Certificate $certificate -HashAlgorithm SHA256
    if ($signature.Status -ne 'Valid') {
        throw "Setup signing failed: $($signature.Status) - $($signature.StatusMessage)"
    }
    Export-Certificate -Cert $certificate -FilePath (Join-Path $outputDirectory 'UNICOIL-CodeSigning.cer') -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $root 'Install-UNICOILPublisherCertificate.ps1') -Destination $outputDirectory -Force
    Write-Host "Signed publisher: $($certificate.Subject)"
    Write-Host "Certificate expires: $($certificate.NotAfter.ToString('yyyy-MM-dd'))"
} else {
    Write-Warning 'No UNICOIL code-signing certificate with a private key was found. The executable is unsigned.'
}

$hash = (Get-FileHash -LiteralPath $outputFile -Algorithm SHA256).Hash
Write-Host "Created: $outputFile"
Write-Host "SHA256: $hash"
