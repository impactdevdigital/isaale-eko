param(
    [string]$NodeId = '1180:71368',
    [int]$Depth = 4
)

$root = Split-Path -Parent $PSScriptRoot
$token = (Get-Content (Join-Path $root 'api_fig.txt') -Raw).Trim()
$headers = @{ 'X-Figma-Token' = $token }
$fileKey = 'cgF4nr09kMwsi6bunzkKcR'
$url = "https://api.figma.com/v1/files/$fileKey/nodes?ids=$([uri]::EscapeDataString($NodeId))&depth=$Depth"

try {
    $resp = Invoke-RestMethod -Headers $headers -Uri $url
    $outFile = Join-Path $root (".figma/node_" + ($NodeId -replace ':', '_') + ".json")
    $resp | ConvertTo-Json -Depth 10 | Set-Content -Path $outFile -Encoding utf8
    Write-Output "Saved to $outFile"
    Write-Output "Document Name: $($resp.nodes.$NodeId.document.name)"
    Write-Output "Type: $($resp.nodes.$NodeId.document.type)"
    Write-Output "Children count: $($resp.nodes.$NodeId.document.children.Count)"
    foreach ($child in $resp.nodes.$NodeId.document.children) {
        Write-Output " - $($child.name) [$($child.id)] ($($child.type))"
    }
} catch {
    Write-Error $_.Exception.ToString()
    if ($_.Exception.Response) {
        $stream = $_.Exception.Response.GetResponseStream()
        $reader = New-Object System.IO.StreamReader($stream)
        Write-Output $reader.ReadToEnd()
    }
}
