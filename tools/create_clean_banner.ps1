Add-Type -AssemblyName System.Drawing

$srcFile = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($srcFile)
$cleanBmp = New-Object System.Drawing.Bitmap($bmp.Width, $bmp.Height)
$g = [System.Drawing.Graphics]::FromImage($cleanBmp)
$g.DrawImage($bmp, 0, 0, $bmp.Width, $bmp.Height)

# We want to remove the text from x = 0 to 1200.
# The background in this region is a smooth gradient from top to bottom.
# For each row y, let's find the true background color of that row by sampling near x = 50 or x = 100 where there is no text, or sampling the light background pixels.
# Let's verify background color at x = 20 for each y:
# x = 20 is before "DISCOVER" (which starts at x = 60).
for ($y = 0; $y -lt $bmp.Height; $y++) {
    $bg = $bmp.GetPixel(20, $y)
    
    # In each row, if x < 1200 and the pixel is text, replace with $bg
    for ($x = 40; $x -lt 1200; $x++) {
        # Do NOT touch the yellow diamond! (x >= 1080 and y <= 300)
        if ($x -ge 1080 -and $y -le 300) {
            continue
        }
        
        $c = $bmp.GetPixel($x, $y)
        
        # Text is anything noticeably darker than the background
        # Background is very light (R > 240, G > 240, B > 240)
        if ($c.R -lt 230 -or $c.G -lt 230 -or $c.B -lt 230) {
            # Check if this pixel is yellow (celebration ray or diamond or confetti)
            # Yellow has high R (>200), high G (>150), and low B (<100)
            if ($c.R -gt 180 -and $c.G -gt 130 -and $c.B -lt 100) {
                # Keep yellow accents!
                continue
            }
            
            # This is text: replace with smooth background
            $cleanBmp.SetPixel($x, $y, $bg)
        }
    }
}

$outputFile = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\recipes-hero-banner-clean.png"
$cleanBmp.Save($outputFile, [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output "Successfully created clean banner at $outputFile"

$g.Dispose()
$cleanBmp.Dispose()
$bmp.Dispose()
