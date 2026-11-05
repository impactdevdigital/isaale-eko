Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Sample background color across top, middle, bottom
Write-Output "Sampling background colors across figma-hero-banner-full.png:"
for ($x = 100; $x -lt 1200; $x += 200) {
    $c = $bmp.GetPixel($x, 50)
    Write-Output ("x={0}: #{1:X2}{2:X2}{3:X2}" -f $x, $c.R, $c.G, $c.B)
}

# Also let's check where the Megaphone starts on the right
# Scan horizontally from x = 1000 to 2500 at y = 250
$megaStart = 0
for ($x = 1000; $x -lt 2500; $x += 5) {
    $c = $bmp.GetPixel($x, 250)
    # Background is #FFFFFF or #FAFAFA, check when color is not background
    if ($c.R -lt 240 -or $c.B -lt 200) {
        $megaStart = $x
        break
    }
}
Write-Output "Megaphone/Artwork starts around 2x x=$megaStart -> (1x): $($megaStart / 2) px"

$bmp.Dispose()
