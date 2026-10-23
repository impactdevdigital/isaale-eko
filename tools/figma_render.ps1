# Renders Figma nodes to PNG in .figma/renders/ using the token in api_fig.txt.
# Usage: powershell -File tools/figma_render.ps1 -Ids "286:3299,290:5959" [-Scale 1]
param(
    [Parameter(Mandatory = $true)][string]$Ids,
    [double]$Scale = 1,
    [string]$FileKey = 'cgF4nr09kMwsi6bunzkKcR'
)

$root = Split-Path -Parent $PSScriptRoot
$token = (Get-Content (Join-Path $root 'api_fig.txt') -Raw).Trim()
$headers = @{ 'X-Figma-Token' = $token }
$out = Join-Path $root '.figma/renders'
New-Item -ItemType Directory -Force $out | Out-Null

$resp = Invoke-RestMethod -Headers $headers "https://api.figma.com/v1/images/$FileKey`?ids=$([uri]::EscapeDataString($Ids))&format=png&scale=$Scale"
foreach ($p in $resp.images.PSObject.Properties) {
    if (-not $p.Value) { Write-Output "no image for $($p.Name)"; continue }
    $file = Join-Path $out (($p.Name -replace ':', '-') + '.png')
    Invoke-WebRequest -UseBasicParsing $p.Value -OutFile $file
    Write-Output $file
}
