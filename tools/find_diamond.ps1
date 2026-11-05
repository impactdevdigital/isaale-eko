Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\public\images\store\banners\figma-hero-banner-full.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

$minX = 9999
$maxX = 0
$minY = 9999
$maxY = 0

for ($y = 0; $y -lt $bmp.Height; $y++) {
    for ($x = 600; $x -lt 1300; $x++) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.R -gt 200 -and $c.G -gt 140 -and $c.B -lt 60) {
            if ($x -lt $minX) { $minX = $x }
            if ($x -gt $maxX) { $maxX = $x }
            if ($y -lt $minY) { $minY = $y }
            if ($y -gt $maxY) { $maxY = $y }
        }
    }
}

Write-Output "Diamond bounds at 2x: x=$minX..$maxX, y=$minY..$maxY"
Write-Output "Diamond bounds at 1x: x=$($minX/2)..$($maxX/2), y=$($minY/2)..$($maxY/2)"
Write-Output "Diamond width=$((($maxX - $minX + 1)/2))px, height=$((($maxY - $minY + 1)/2))px"

$bmp.Dispose()
