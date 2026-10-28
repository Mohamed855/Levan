param(
    [Parameter(Mandatory = $true)][string]$PythonBinary
)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
Push-Location $projectRoot
try {
    & php scripts/auction-reports/render-backup-fixtures.php
    if ($LASTEXITCODE -ne 0) { throw 'File-only acceptance rendering failed.' }
    foreach ($auction in @('haram', 'asir')) {
        foreach ($type in @('outputs', 'report')) {
            $measurement = & php scripts/auction-reports/measure-pages.php "output/pdf/remediation-acceptance/$auction-$type.html"
            if ($LASTEXITCODE -ne 0) { throw "Geometry measurement failed: $auction-$type" }
            $geometry = ($measurement -join [Environment]::NewLine) | ConvertFrom-Json
            if ($geometry.overflow_count -gt 0) { throw "Text overflows remain in $auction-$type; inspect its geometry JSON." }
        }
    }
    & php scripts/auction-reports/render-link-proof.php
    if ($LASTEXITCODE -ne 0) { throw 'PDF link proof rendering failed.' }
    & $PythonBinary scripts/auction-reports/verify-remediation-pdfs.py
    if ($LASTEXITCODE -ne 0) { throw 'PDF text, geometry, identity or link contract failed.' }
    & $PythonBinary scripts/auction-reports/compare-pages.py --receipt output/pdf/remediation-acceptance/receipt.json
    if ($LASTEXITCODE -ne 0) { throw 'Unmasked comparison failed; inspect output/pdf/remediation-comparison.' }
    Write-Output 'Functional contracts passed. Unmasked overlays still require visual review; source gaps are not an automatic full-reference acceptance.'
} finally {
    Pop-Location
}
