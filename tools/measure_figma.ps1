Add-Type -AssemblyName System.Drawing

function Analyze-Image($file) {
    Write-Output "=================================================="
    Write-Output "Analyzing $file"
    Write-Output "=================================================="
    $bmp = [System.Drawing.Bitmap]::FromFile($file)
    Write-Output "Dimensions: $($bmp.Width) x $($bmp.Height) (Scale ~2x -> $([Math]::Round($bmp.Width/2)) x $([Math]::Round($bmp.Height/2)))"
    
    # 1. Inspect Hero banner
    # Sample top-left corner of banner
    # Find banner bounding box
    $topY = 0
    for ($y = 0; $y -lt 200; $y++) {
        $c = $bmp.GetPixel(50, $y)
        if ($c.A -gt 200) { $topY = $y; break }
    }
    Write-Output "Banner top y: $topY"

    # Find banner bounds
    $leftX = 0
    for ($x = 0; $x -lt 200; $x++) {
        $c = $bmp.GetPixel($x, 100)
        if ($c.A -gt 200) { $leftX = $x; break }
    }
    
    $rightX = $bmp.Width - 1
    for ($x = $bmp.Width - 1; $x -gt $bmp.Width - 200; $x--) {
        $c = $bmp.GetPixel($x, 100)
        if ($c.A -gt 200) { $rightX = $x; break }
    }
    
    $bannerHeight = 0
    for ($y = $topY; $y -lt 1000; $y++) {
        $c = $bmp.GetPixel(100, $y)
        if ($c.A -eq 0) { $bannerHeight = $y - $topY; break }
    }
    
    Write-Output "Banner Bounds (2x): x=$leftX, y=$topY, w=$($rightX - $leftX + 1), h=$bannerHeight"
    Write-Output "Banner Bounds (1x): x=$([Math]::Round($leftX/2)), y=$([Math]::Round($topY/2)), w=$([Math]::Round(($rightX - $leftX + 1)/2)), h=$([Math]::Round($bannerHeight/2))"
    
    # Banner background colors: sample several spots
    $cTopLeft = $bmp.GetPixel($leftX + 20, $topY + 20)
    $cMid = $bmp.GetPixel($leftX + 200, $topY + 100)
    Write-Output ("Banner BG Color: #{0:X2}{1:X2}{2:X2}" -f $cMid.R, $cMid.G, $cMid.B)

    # Let's inspect the Search Bar & Filter area (between banner bottom and cards)
    # Find next non-empty area below banner
    $filterAreaTop = 0
    for ($y = ($topY + $bannerHeight + 10); $y -lt ($topY + $bannerHeight + 400); $y++) {
        for ($x = 100; $x -lt $bmp.Width - 100; $x += 20) {
            $c = $bmp.GetPixel($x, $y)
            if ($c.A -gt 50) {
                $filterAreaTop = $y
                break
            }
        }
        if ($filterAreaTop -ne 0) { break }
    }
    Write-Output "Filter/Search area starts approx at y=$filterAreaTop (1x: $([Math]::Round($filterAreaTop/2)))"
    
    # Let's find the cards!
    # Cards have images, which have rich colorful pixels
    # Let's scan y from filterAreaTop + 100 downward to find first row of cards
    $cardsTop = 0
    for ($y = $filterAreaTop + 50; $y -lt $bmp.Height; $y += 5) {
        # Check variance across x
        $hasCard = $false
        for ($x = 20; $x -lt 300; $x += 5) {
            $c = $bmp.GetPixel($x, $y)
            # Check if non-transparent and not white/grey
            if ($c.A -gt 240) {
                $hasCard = $true
                break
            }
        }
        if ($hasCard) {
            $cardsTop = $y
            break
        }
    }
    Write-Output "Cards top row starts approx at y=$cardsTop (1x: $([Math]::Round($cardsTop/2)))"

    # Find the cards in that row: let's scan horizontally at y = cardsTop + 100
    # To identify card boundaries (x1, x2 for each column)
    $scanY = $cardsTop + 100
    $inCard = $false
    $cardColumns = @()
    $cardStart = 0
    for ($x = 0; $x -lt $bmp.Width; $x++) {
        $c = $bmp.GetPixel($x, $scanY)
        $isCardPixel = ($c.A -gt 200)
        if (-not $inCard -and $isCardPixel) {
            $inCard = $true
            $cardStart = $x
        } elseif ($inCard -and -not $isCardPixel) {
            $inCard = $false
            $cardColumns += @{ Start = $cardStart; End = ($x - 1); Width = ($x - $cardStart) }
        }
    }
    if ($inCard) {
        $cardColumns += @{ Start = $cardStart; End = ($bmp.Width - 1); Width = ($bmp.Width - $cardStart) }
    }
    
    Write-Output "Found $($cardColumns.Count) card columns in first row:"
    for ($i = 0; $i -lt $cardColumns.Count; $i++) {
        $col = $cardColumns[$i]
        Write-Output " Card $($i+1): x=$($col.Start) to $($col.End), w=$($col.Width) (1x: w=$([Math]::Round($col.Width/2)))"
        if ($i -gt 0) {
            $gap = $col.Start - $cardColumns[$i-1].End - 1
            Write-Output "   Gap from previous: $gap (1x: $([Math]::Round($gap/2)))"
        }
    }
    
    # Find card 1 height and bottom
    if ($cardColumns.Count -gt 0) {
        $firstCardX = $cardColumns[0].Start + 50
        $cardBottom = 0
        for ($y = $cardsTop; $y -lt $bmp.Height; $y++) {
            $c = $bmp.GetPixel($firstCardX, $y)
            if ($c.A -eq 0) {
                $cardBottom = $y - 1
                break
            }
        }
        $cardH = $cardBottom - $cardsTop + 1
        Write-Output "Card 1 Height: $cardH (1x: $([Math]::Round($cardH/2)))"
        
        # Sample card footer (bottom of card)
        # Scan from cardBottom upwards to find where the footer background starts
        $footerTop = 0
        $footerColor = $bmp.GetPixel($firstCardX, $cardBottom - 10)
        Write-Output ("Card Footer BG: #{0:X2}{1:X2}{2:X2}" -f $footerColor.R, $footerColor.G, $footerColor.B)
        
        for ($y = $cardBottom; $y -gt $cardsTop; $y--) {
            $c = $bmp.GetPixel($firstCardX, $y)
            # check if color changes significantly from footer
            $diff = [Math]::Abs($c.R - $footerColor.R) + [Math]::Abs($c.G - $footerColor.G) + [Math]::Abs($c.B - $footerColor.B)
            if ($diff -gt 30) {
                $footerTop = $y + 1
                break
            }
        }
        $footerH = $cardBottom - $footerTop + 1
        Write-Output "Card Footer Height: $footerH (1x: $([Math]::Round($footerH/2)))"
        Write-Output "Card Image/Content Area Height: $($cardH - $footerH) (1x: $([Math]::Round(($cardH - $footerH)/2)))"
        
        # Check border between image and footer
        $borderY = $footerTop
        $borderC = $bmp.GetPixel($firstCardX, $borderY)
        Write-Output ("Card Footer Border Color: #{0:X2}{1:X2}{2:X2}" -f $borderC.R, $borderC.G, $borderC.B)
    }

    $bmp.Dispose()
}

Analyze-Image "c:\Users\USER\dev\isaale-eko\.figma\renders\1180-71383.png"
