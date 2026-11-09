[CmdletBinding()]
param()

# Optional enterprise trust bootstrap. Only the public certificate is imported;
# never distribute or commit the corresponding private signing key.
$ErrorActionPreference = 'Stop'
$identity = [Security.Principal.WindowsIdentity]::GetCurrent()
$principal = [Security.Principal.WindowsPrincipal]::new($identity)
if (-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    throw 'Run this script as Administrator.'
}

$certificatePath = Join-Path $PSScriptRoot 'UNICOIL-CodeSigning.cer'
if (-not (Test-Path -LiteralPath $certificatePath)) {
    throw 'UNICOIL-CodeSigning.cer must be in the same folder as this script.'
}

$certificate = [Security.Cryptography.X509Certificates.X509Certificate2]::new($certificatePath)
if ($certificate.Subject -ne 'CN=UNICOIL') {
    throw 'The selected certificate is not the UNICOIL publisher certificate.'
}

Import-Certificate -FilePath $certificatePath -CertStoreLocation 'Cert:\LocalMachine\Root' | Out-Null
Import-Certificate -FilePath $certificatePath -CertStoreLocation 'Cert:\LocalMachine\TrustedPublisher' | Out-Null

Write-Host 'UNICOIL publisher certificate installed successfully.' -ForegroundColor Green
Write-Host ("Thumbprint: " + $certificate.Thumbprint)
Write-Host 'Windows can now verify Agent installers signed with this certificate.'
