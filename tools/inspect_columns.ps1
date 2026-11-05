Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Let's inspect column by column between x = 1000 and 1150:
# For each x, what is the darkest pixel?
for ($x = 1080; $x -le 1250; $x += 10) {
    $c = $bmp.GetPixel($x, 335)
    Write-Output ("x={0} at y=335: #{1:X2}{2:X2}{3:X2}" -f $x, $c.R, $c.G, $c.B)
}
$bmp.Dispose()
