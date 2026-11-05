Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-artwork.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# We want the left 200px to smoothly fade from alpha=0 to alpha=255
$fadeWidth = 200

$outBmp = New-Object System.Drawing.Bitmap($bmp.Width, $bmp.Height, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)

for ($y = 0; $y -lt $bmp.Height; $y++) {
    for ($x = 0; $x -lt $bmp.Width; $x++) {
        $c = $bmp.GetPixel($x, $y)
        if ($x -lt $fadeWidth) {
            $alphaRatio = $x / $fadeWidth
            # Also if the background is near-white (#F5F5F6 / #FAFAFA), we blend it to transparent
            $isBg = ($c.R -gt 240 -and $c.G -gt 240 -and $c.B -gt 240)
            $alpha = [int]($c.A * $alphaRatio)
            $outBmp.SetPixel($x, $y, [System.Drawing.Color]::FromArgb($alpha, $c.R, $c.G, $c.B))
        } else {
            $outBmp.SetPixel($x, $y, $c)
        }
    }
}

$bmp.Dispose()
$dest = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\recipes-hero-art.png"
$outBmp.Save($dest, [System.Drawing.Imaging.ImageFormat]::Png)
$outBmp.Dispose()

Write-Output "Successfully updated recipes-hero-art.png with high-res Figma artwork & alpha feathering!"
