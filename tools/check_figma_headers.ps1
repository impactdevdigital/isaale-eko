$token = (Get-Content api_fig.txt -Raw).Trim()
try {
    $req = [System.Net.HttpWebRequest]::Create('https://api.figma.com/v1/files/cgF4nr09kMwsi6bunzkKcR/nodes?ids=1180:71368')
    $req.Headers.Add('X-Figma-Token', $token)
    $resp = $req.GetResponse()
    Write-Output 'Success! Status 200'
} catch [System.Net.WebException] {
    $resp = $_.Exception.Response
    Write-Output "Status: $([int]$resp.StatusCode)"
    foreach ($k in $resp.Headers.AllKeys) {
        $val = $resp.Headers[$k]
        Write-Output "${k} = ${val}"
    }
    $reader = New-Object System.IO.StreamReader($resp.GetResponseStream())
    Write-Output "Body: $($reader.ReadToEnd())"
}
