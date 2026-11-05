Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Scan from x = 0 to 1050
# If color is not near white/gray, print it!
$nonBgFound = 0
for ($y = 0; $y -lt $bmp.Height; $y += 5) {
    for ($x = 0; $x -lt 1050; $x += 5) {
        $c = $bmp.GetPixel($x, $y)
        # Text is dark (R < 150)
        # Background is light (R > 230, G > 230, B > 230)
        if ($c.R -ge 150 -and ($c.R -lt 230 -or $c.G -lt 230 -or $c.B -lt 230)) {
            $nonBgFound++
            if ($nonBgFound -le 10) {
                Write-Output ("Intermediate color at x={0}, y={1}: #{2:X2}{3:X2}{4:X2}" -f $x, $y, $c.R, $c.G, $c.B)
            }
        }
    }
}

Write-Output "Total non-background intermediate pixels found: $nonBgFound"
$bmp.Dispose()
