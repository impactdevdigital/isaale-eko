Add-Type -AssemblyName System.Drawing

function Find-Logo($imgPath, $name) {
    $bmp = [System.Drawing.Bitmap]::FromFile($imgPath)
    $minX = 9999; $minY = 9999; $maxX = 0; $maxY = 0;
    
    # Yellow cart in logo: R>200, G>180, B<60
    for ($y = 0; $y -lt $bmp.Height; $y++) {
        for ($x = 0; $x -lt 200; $x++) {
            $c = $bmp.GetPixel($x, $y)
            if ($c.R -gt 210 -and $c.G -gt 170 -and $c.B -lt 70) {
                if ($x -lt $minX) { $minX = $x }
                if ($x -gt $maxX) { $maxX = $x }
                if ($y -lt $minY) { $minY = $y }
                if ($y -gt $maxY) { $maxY = $y }
            }
        }
    }
    Write-Output "$name Logo Bounds (at 0.5x scale): x=$minX to $maxX, y=$minY to $maxY -> (1x): x=$($minX*2) to $($maxX*2)"
    $bmp.Dispose()
}

Find-Logo "c:\Users\USER\dev\isaale-eko\public\header_crop.png" "Header"
Find-Logo "c:\Users\USER\dev\isaale-eko\public\footer_crop.png" "Footer"
