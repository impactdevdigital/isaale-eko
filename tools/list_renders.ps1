Add-Type -AssemblyName System.Drawing
Get-ChildItem c:\Users\USER\dev\isaale-eko\.figma\renders\*.png | ForEach-Object {
    $bmp = [System.Drawing.Bitmap]::FromFile($_.FullName)
    Write-Output "$($_.Name): $($bmp.Width) x $($bmp.Height)"
    $bmp.Dispose()
}
