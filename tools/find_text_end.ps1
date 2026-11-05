Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Scan for dark text pixels (R < 50, G < 50, B < 50)
$maxTextX = 0
for ($y = 0; $y -lt $bmp.Height; $y++) {
    for ($x = 0; $x -lt 1200; $x++) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -lt 50 -and $c.G -lt 50 -and $c.B -lt 50) {
            if ($x -gt $maxTextX) { $maxTextX = $x }
        }
    }
}

Write-Output "Dark text ends at 2x x = $maxTextX -> 1x x = $($maxTextX / 2)"
$bmp.Dispose()
