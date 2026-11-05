<?php

$im = imagecreatefrompng('public/images/store/banners/figma-hero-banner-full.png');
$w = imagesx($im);
$h = imagesy($im);

$minX = 9999;
$maxX = 0;
$minY = 9999;
$maxY = 0;

for ($y = 0; $y < $h; $y++) {
    for ($x = 600; $x < 1300; $x++) {
        $rgb = imagecolorat($im, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;

        // Check for the yellow diamond
        if ($r > 200 && $g > 140 && $b < 60) {
            if ($x < $minX) {
                $minX = $x;
            }
            if ($x > $maxX) {
                $maxX = $x;
            }
            if ($y < $minY) {
                $minY = $y;
            }
            if ($y > $maxY) {
                $maxY = $y;
            }
        }
    }
}

echo "Diamond bounds at 2x: x={$minX}..{$maxX}, y={$minY}..{$maxY}\n";
echo 'Diamond bounds at 1x: x='.($minX / 2).'..'.($maxX / 2).', y='.($minY / 2).'..'.($maxY / 2)."\n";
echo 'Diamond width='.(($maxX - $minX + 1) / 2).'px, height='.(($maxY - $minY + 1) / 2)."px\n";
