Add-Type -AssemblyName System.Drawing

$file = "c:\Users\USER\dev\isaale-eko\.figma\renders\1180-71383.png"
$bmp = [System.Drawing.Bitmap]::FromFile($file)

Write-Output "=== 1. HERO BANNER SPECS ==="
# Banner top and bottom
$bannerTop = 64
$bannerBottom = 564
$bannerH = $bannerBottom - $bannerTop # 500px in 2x, 250px in 1x
Write-Output "Hero Banner Height (2x): $bannerH px -> (1x): $($bannerH/2) px (e.g. 250px or ~h-[250px] or h-[260px])"

# Banner left and right:
$bLeft = 0; for ($x = 0; $x -lt 500; $x++) { if ($bmp.GetPixel($x, 200).A -gt 200) { $bLeft = $x; break } }
$bRight = $bmp.Width - 1; for ($x = $bmp.Width - 1; $x -gt 2000; $x--) { if ($bmp.GetPixel($x, 200).A -gt 200) { $bRight = $x; break } }
$bWidth = $bRight - $bLeft + 1
Write-Output "Hero Banner Width (2x): $bWidth px -> (1x): $($bWidth/2) px"
Write-Output "Left margin: $bLeft (1x: $($bLeft/2))"

# Check Corner radius of Hero Banner
# Top-left corner: find the first pixel at y=$bannerTop
$rX = 0; for ($x = $bLeft; $x -lt $bLeft + 100; $x++) { if ($bmp.GetPixel($x, $bannerTop).A -gt 200) { $rX = $x; break } }
$radius2x = $rX - $bLeft
Write-Output "Hero Banner Corner Radius approx: $($radius2x/2) px (Tailwind rounded-2xl is 16px, rounded-3xl is 24px)"

# Hero text position & color
# Sample headline text color (let's scan across y=200 for dark pixels)
$minLum = 255; $textC = $null
for ($x = 100; $x -lt 800; $x++) {
    $c = $bmp.GetPixel($x, 200)
    $lum = ($c.R + $c.G + $c.B)/3
    if ($lum -lt $minLum) { $minLum = $lum; $textC = $c }
}
if ($textC) {
    Write-Output ("Hero Text Dark Color: #{0:X2}{1:X2}{2:X2}" -f $textC.R, $textC.G, $textC.B)
}

Write-Output ""
Write-Output "=== 2. FILTER & SEARCH BAR SPECS ==="
# Scan y between 600 and 800 to find the search bar / filter buttons
$barTop = 0; $barBottom = 0
for ($y = 600; $y -lt 800; $y++) {
    for ($x = 100; $x -lt 2500; $x += 10) {
        $c = $bmp.GetPixel($x, $y)
        if ($c.A -gt 200) {
            if ($barTop -eq 0) { $barTop = $y }
            $barBottom = $y
            break
        }
    }
}
$barH = $barBottom - $barTop + 1
Write-Output "Filter/Search Row Top: $barTop, Bottom: $barBottom, Height (2x): $barH -> (1x): $($barH/2) px"
Write-Output "Distance from Banner Bottom to Filter Row (2x): $($barTop - $bannerBottom) -> (1x): $(($barTop - $bannerBottom)/2) px"

# Let's inspect the Search bar on the right
# Scan at y = ($barTop + $barBottom)/2 from right to left
$midY = [int](($barTop + $barBottom)/2)
$searchRight = 0; $searchLeft = 0
for ($x = $bmp.Width - 1; $x -gt 1500; $x--) {
    $c = $bmp.GetPixel($x, $midY)
    if ($c.A -gt 200 -and $searchRight -eq 0) { $searchRight = $x }
    if ($searchRight -ne 0 -and $c.A -eq 0) { $searchLeft = $x + 1; break }
}
Write-Output "Search Bar Right: $searchRight, Left: $searchLeft, Width (2x): $($searchRight - $searchLeft + 1) -> (1x): $(($searchRight - $searchLeft + 1)/2) px"

Write-Output ""
Write-Output "=== 3. RECIPE CARDS SPECS ==="
# Row 1 cards: top and bottom
$row1Top = 0
for ($y = $barBottom + 10; $y -lt 1000; $y++) {
    for ($x = 100; $x -lt 2500; $x += 50) {
        if ($bmp.GetPixel($x, $y).A -gt 200) { $row1Top = $y; break }
    }
    if ($row1Top -ne 0) { break }
}

$row1Bottom = 0
for ($y = $row1Top + 200; $y -lt 1500; $y++) {
    $hasPx = $false
    for ($x = 100; $x -lt 2500; $x += 50) {
        if ($bmp.GetPixel($x, $y).A -gt 200) { $hasPx = $true; break }
    }
    if (-not $hasPx) { $row1Bottom = $y - 1; break }
}
$row1H = $row1Bottom - $row1Top + 1
Write-Output "Row 1 Top: $row1Top, Bottom: $row1Bottom, Height (2x): $row1H -> (1x): $($row1H/2) px"
Write-Output "Distance from Filter Row to Cards (2x): $($row1Top - $barBottom) -> (1x): $(($row1Top - $barBottom)/2) px"

# Find horizontal card columns at y = row1Top + 100
$inCard = $false
$cards = @()
$cStart = 0
for ($x = 0; $x -lt $bmp.Width; $x++) {
    $c = $bmp.GetPixel($x, $row1Top + 100)
    if (-not $inCard -and $c.A -gt 200) {
        $inCard = $true
        $cStart = $x
    } elseif ($inCard -and $c.A -eq 0) {
        $inCard = $false
        $cards += @{ Start = $cStart; End = $x - 1; Width = ($x - $cStart) }
    }
}
if ($inCard) { $cards += @{ Start = $cStart; End = $bmp.Width - 1; Width = ($bmp.Width - $cStart) } }

Write-Output "Found $($cards.Count) cards across the row:"
for ($i = 0; $i -lt $cards.Count; $i++) {
    $card = $cards[$i]
    Write-Output " Card $($i+1): Start=$($card.Start), End=$($card.End), Width (2x): $($card.Width) px -> (1x): $($card.Width/2) px"
    if ($i -gt 0) {
        $gap = $card.Start - $cards[$i-1].End - 1
        Write-Output "   Gap (2x): $gap px -> (1x): $($gap/2) px"
    }
}

# Footer of Card 1
# Scan upwards from row1Bottom
$c1MidX = [int](($cards[0].Start + $cards[0].End)/2)
$footerBottom = $row1Bottom
$footerTop = 0
for ($y = $footerBottom; $y -gt $row1Top; $y--) {
    $c = $bmp.GetPixel($c1MidX, $y)
    # Card footer is light gray/white (#F6F6F6)
    # Check when color becomes dark (vignette image)
    if ($c.R -lt 200 -or $c.G -lt 200 -or $c.B -lt 200) {
        $footerTop = $y + 1
        break
    }
}
$footerH = $footerBottom - $footerTop + 1
Write-Output "Card Footer Top: $footerTop, Bottom: $footerBottom, Height (2x): $footerH px -> (1x): $($footerH/2) px"
Write-Output "Card Image Height (2x): $($footerTop - $row1Top) px -> (1x): $(($footerTop - $row1Top)/2) px"

# Row 2 cards:
$row2Top = 0
for ($y = $row1Bottom + 1; $y -lt $bmp.Height; $y++) {
    for ($x = 100; $x -lt 2500; $x += 50) {
        if ($bmp.GetPixel($x, $y).A -gt 200) { $row2Top = $y; break }
    }
    if ($row2Top -ne 0) { break }
}
Write-Output "Row 2 Top: $row2Top. Gap between Row 1 and Row 2 (2x): $($row2Top - $row1Bottom) -> (1x): $(($row2Top - $row1Bottom)/2) px"

$bmp.Dispose()
