Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\.figma\renders\1180-71383.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)
Write-Output "Image size: $($bmp.Width) x $($bmp.Height)"

# Earlier we found:
# Banner is at y = 64 to 564 (height 500px at 2x = 250px at 1x)
# Width is 2592px (at 2x = 1296px at 1x)

# Let's crop the entire banner at 2x
$bannerBmp = New-Object System.Drawing.Bitmap(2592, 500)
$g = [System.Drawing.Graphics]::FromImage($bannerBmp)
$g.DrawImage($bmp, [System.Drawing.Rectangle]::new(0, 0, 2592, 500), [System.Drawing.Rectangle]::new(0, 64, 2592, 500), [System.Drawing.GraphicsUnit]::Pixel)

$bannerBmp.Save("c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png", [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output "Saved full 2x banner to public/images/store/banners/figma-hero-banner-full.png"

# Now let's crop JUST the right artwork side (the megaphone, box of veggies, yellow sparkles)
# Right side starts around x = 1200 (out of 2592)
# Let's inspect where the text ends on the left:
# Text ends around x = 588 * 2 = 1176px.
# So from x = 1200 to 2592 is the artwork!
$artWidth = 2592 - 1200
$artBmp = New-Object System.Drawing.Bitmap($artWidth, 500)
$gArt = [System.Drawing.Graphics]::FromImage($artBmp)
$gArt.DrawImage($bmp, [System.Drawing.Rectangle]::new(0, 0, $artWidth, 500), [System.Drawing.Rectangle]::new(1200, 64, $artWidth, 500), [System.Drawing.GraphicsUnit]::Pixel)

$artBmp.Save("c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-artwork.png", [System.Drawing.Imaging.ImageFormat]::Png)
Write-Output "Saved artwork to public/images/store/banners/figma-hero-artwork.png"

$g.Dispose()
$bannerBmp.Dispose()
$gArt.Dispose()
$artBmp.Dispose()
$bmp.Dispose()
