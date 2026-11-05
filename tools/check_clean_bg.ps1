Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Check all pixels between x = 1040 and 1085 across the entire height
$hasNonBg = $false
for ($y = 0; $y -lt $bmp.Height; $y++) {
    for ($x = 1040; $x -lt 1085; $x++) {
        $c = $bmp.GetPixel($x, $y)
        # Background is white/light gray: R > 240, G > 240, B > 240
        if ($c.R -lt 240 -or $c.G -lt 240 -or $c.B -lt 240) {
            Write-Output ("Non-bg pixel at x={0}, y={1}: Color=#{2:X2}{3:X2}{4:X2}" -f $x, $y, $c.R, $c.G, $c.B)
            $hasNonBg = $true
            break
        }
    }
    if ($hasNonBg) { break }
}

if (-not $hasNonBg) {
    Write-Output "x=1040..1085 is 100% pure clean background across all 500px height!"
}

$bmp.Dispose()
