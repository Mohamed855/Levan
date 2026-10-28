param(
    [Parameter(Mandatory = $true)][string]$Pdf,
    [Parameter(Mandatory = $true)][ValidateSet('outputs', 'report')][string]$Type,
    [Parameter(Mandatory = $true)][int]$Pages,
    [Parameter(Mandatory = $true)][string]$PythonBinary
)

$ErrorActionPreference = 'Stop'
$width = if ($Type -eq 'outputs') { 595.5 } else { 595.276 }
$height = if ($Type -eq 'outputs') { 842.2499787 } else { 841.89 }
& $PythonBinary (Join-Path $PSScriptRoot 'compare-pages.py') --pdf $Pdf --pages $Pages --width $width --height $height
if ($LASTEXITCODE -ne 0) { throw 'PDF contract failed.' }
