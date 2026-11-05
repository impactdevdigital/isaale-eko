Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\.figma\renders\1180-71383.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

# Let's see what pixels exist below y=600
Write-Output "Sampling y from 550 to 1900 in steps of 50 at x=200, x=800, x=1300:"
for ($y = 550; $y -lt 1900; $y += 50) {
    $c1 = $bmp.GetPixel(200, $y)
    $c2 = $bmp.GetPixel(800, $y)
    $c3 = $bmp.GetPixel(1300, $y)
    Write-Output ("y={0,4}: x=200 A={1,3} R={2,3} G={3,3} B={4,3} | x=800 A={5,3} R={6,3} G={7,3} B={8,3} | x=1300 A={9,3}" -f $y, $c1.A, $c1.R, $c1.G, $c1.B, $c2.A, $c2.R, $c2.G, $c2.B, $c3.A)
}

$bmp.Dispose()
