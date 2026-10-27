param(
    [Parameter(Mandatory = $true)][string]$Pdf,
    [Parameter(Mandatory = $true)][ValidateSet('outputs', 'report')][string]$Type,
    [Parameter(Mandatory = $true)][int]$Pages,
    [Parameter(Mandatory = $true)][string]$PythonBinary
)

$ErrorActionPreference = 'Stop'
$width = if ($Type -eq 'outputs') { 595.92 } else { 594.96 }
& $PythonBinary (Join-Path $PSScriptRoot 'compare-pages.py') --pdf $Pdf --pages $Pages --width $width --height 841.92
if ($LASTEXITCODE -ne 0) { throw 'PDF contract failed.' }
