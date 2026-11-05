Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\.figma\renders\1180-71368.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Let's save a crop of the header and a crop of the footer
$headerCrop = New-Object System.Drawing.Bitmap(720, 100)
$g1 = [System.Drawing.Graphics]::FromImage($headerCrop)
$g1.DrawImage($bmp, [System.Drawing.Rectangle]::new(0, 0, 720, 100), [System.Drawing.Rectangle]::new(0, 0, 720, 100), [System.Drawing.GraphicsUnit]::Pixel)
$headerCrop.Save("c:\Users\USER\dev\isaale-eko\public\header_crop.png", [System.Drawing.Imaging.ImageFormat]::Png)
$g1.Dispose()
$headerCrop.Dispose()

$footerCrop = New-Object System.Drawing.Bitmap(720, 200)
$g2 = [System.Drawing.Graphics]::FromImage($footerCrop)
$g2.DrawImage($bmp, [System.Drawing.Rectangle]::new(0, 0, 720, 200), [System.Drawing.Rectangle]::new(0, 899, 720, 200), [System.Drawing.GraphicsUnit]::Pixel)
$footerCrop.Save("c:\Users\USER\dev\isaale-eko\public\footer_crop.png", [System.Drawing.Imaging.ImageFormat]::Png)
$g2.Dispose()
$footerCrop.Dispose()

$bmp.Dispose()
Write-Output "Saved header_crop.png and footer_crop.png"
