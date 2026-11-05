Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\.figma\renders\1180-71368.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)
Write-Output "Dimensions of full listing: $($bmp.Width) x $($bmp.Height)"

# In this 720px wide render (scale 0.5 of 1440px):
# Top is header (y from 0 to 100)
# Bottom is footer (y from 800 to 1099)

# Let's find the yellow cart icon in the logo in the header
# Yellow has high R and G, low B (e.g. R>200, G>180, B<50)
$headerLogoX = 0
for ($y = 20; $y -lt 80; $y++) {
    for ($x = 10; $x -lt 100; $x++) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -gt 220 -and $c.G -gt 180 -and $c.B -lt 60) {
            $headerLogoX = $x
            break
        }
    }
    if ($headerLogoX -ne 0) { break }
}

# Now find the yellow cart icon in the logo in the footer
$footerLogoX = 0
for ($y = 800; $y -lt 1050; $y++) {
    for ($x = 10; $x -lt 100; $x++) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -gt 220 -and $c.G -gt 180 -and $c.B -lt 60) {
            $footerLogoX = $x
            break
        }
    }
    if ($footerLogoX -ne 0) { break }
}

Write-Output "Header Logo Yellow X (at 0.5x scale): $headerLogoX -> (1x scale): $($headerLogoX * 2) px"
Write-Output "Footer Logo Yellow X (at 0.5x scale): $footerLogoX -> (1x scale): $($footerLogoX * 2) px"

# Let's also check the left edge of the Hero banner
$bannerLeftX = 0
for ($x = 10; $x -lt 100; $x++) {
    $c = $bmp.GetPixel($x, 200)
    # Background of banner is #FAFAFA, outside is white #FFFFFF or similar
    if ($c.R -lt 253 -or $c.G -lt 253 -or $c.B -lt 253) {
        $bannerLeftX = $x
        break
    }
}
Write-Output "Hero Banner Left X (at 0.5x scale): $bannerLeftX -> (1x scale): $($bannerLeftX * 2) px"

$bmp.Dispose()
