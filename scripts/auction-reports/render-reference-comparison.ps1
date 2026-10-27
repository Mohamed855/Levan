param(
    [Parameter(Mandatory = $true)][string]$ReferenceDirectory,
    [Parameter(Mandatory = $true)][string]$PythonBinary
)

$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..\..')).Path
Push-Location $projectRoot
try {
    & php scripts/auction-reports/render-fixtures.php $ReferenceDirectory
    if ($LASTEXITCODE -ne 0) { throw 'Fixture rendering failed.' }
    foreach ($design in @('haram', 'asir')) {
        & php scripts/auction-reports/measure-pages.php "output/pdf/reference-fixtures/$design.html"
        if ($LASTEXITCODE -ne 0) { throw "Geometry measurement failed: $design" }
        & pdftoppm -r 96 -png (Join-Path $ReferenceDirectory "$design-print.pdf") "tmp/pdfs/$design-reference"
        if ($LASTEXITCODE -ne 0) { throw "Reference rasterization failed: $design" }
        & pdftoppm -r 96 -png "output/pdf/reference-fixtures/$design.pdf" "tmp/pdfs/$design-generated"
        if ($LASTEXITCODE -ne 0) { throw "Generated PDF rasterization failed: $design" }
    }
    & php scripts/auction-reports/export-comparison-masks.php
    if ($LASTEXITCODE -ne 0) { throw 'Mask export failed.' }
    & $PythonBinary scripts/auction-reports/compare-pages.py $ReferenceDirectory
    if ($LASTEXITCODE -ne 0) { throw 'Visual/PDF acceptance failed; inspect comparison-summary.json and page diffs.' }
} finally {
    Pop-Location
}
