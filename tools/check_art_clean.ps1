Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-artwork.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)
Write-Output "Artwork dimensions: $($bmp.Width) x $($bmp.Height)"

# Check left column of artwork (x from 0 to 150):
$hasDarkText = $false
for ($y = 50; $y -lt $bmp.Height - 50; $y++) {
    for ($x = 0; $x -lt 150; $x++) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -lt 100 -and $c.G -lt 100 -and $c.B -lt 100) {
            $hasDarkText = $true
            Write-Output "Dark pixel at x=$x, y=$y"
            break
        }
    }
    if ($hasDarkText) { break }
}
if (-not $hasDarkText) {
    Write-Output "SUCCESS: Left side of artwork has NO text! It is pure clean background."
}

$bmp.Dispose()
