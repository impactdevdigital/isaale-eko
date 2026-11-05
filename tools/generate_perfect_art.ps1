Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Let's crop from x = 1040 to 2592 (width = 1552, height = 500)
$cropX = 1040
$cropWidth = $bmp.Width - $cropX
$artBmp = New-Object System.Drawing.Bitmap($cropWidth, 500)
$g = [System.Drawing.Graphics]::FromImage($artBmp)
$g.DrawImage($bmp, [System.Drawing.Rectangle]::new(0, 0, $cropWidth, 500), [System.Drawing.Rectangle]::new($cropX, 0, $cropWidth, 500), [System.Drawing.GraphicsUnit]::Pixel)

# In the bottom area (y >= 295), from x = 0 to 180 (which corresponds to original x = 1040 to 1220):
# Fill with the background color sampled from x=0, y=295
$bgColor = $artBmp.GetPixel(5, 295)
Write-Output ("Sampling background color: #{0:X2}{1:X2}{2:X2}" -f $bgColor.R, $bgColor.G, $bgColor.B)

# Clear any paragraph text remnant in the bottom-left of the cropped artwork
for ($y = 295; $y -lt 500; $y++) {
    for ($x = 0; $x -lt 190; $x++) {
        # Only replace if not part of any megaphone shadow or yellow elements
        $c = $artBmp.GetPixel($x, $y)
        if ($c.R -gt 180 -and $c.G -gt 130 -and $c.B -lt 100) {
            # Yellow confetti/sparkle - keep!
            continue
        }
        $artBmp.SetPixel($x, $y, $bgColor)
    }
}

# Now feather / alpha-blend the left 40px (x = 0 to 40) into transparent
for ($x = 0; $x -lt 40; $x++) {
    $alpha = [int](255 * ($x / 40.0))
    for ($y = 0; $y -lt 500; $y++) {
        $c = $artBmp.GetPixel($x, $y)
        # Only fade background pixels, don't clip the diamond (diamond starts at x >= 49, since 1089 - 1040 = 49)
        $newA = [Math]::Min($c.A, $alpha)
        $artBmp.SetPixel($x, $y, [System.Drawing.Color]::FromArgb($newA, $c.R, $c.G, $c.B))
    }
}

$outputFile = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\recipes-hero-art.png"
$artBmp.Save($outputFile, [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output "Successfully updated recipes-hero-art.png with yellow diamond intact!"

$g.Dispose()
$artBmp.Dispose()
$bmp.Dispose()
