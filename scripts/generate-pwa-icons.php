<?php

declare(strict_types=1);

if (! extension_loaded('gd')) {
    fwrite(STDERR, "Ekstensi GD diperlukan untuk membuat ikon PWA.\n");
    exit(1);
}

$target = dirname(__DIR__).DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'icons';

/**
 * Generate a local raster equivalent of public/icons/kpi-mark.svg.
 */
function generateIcon(string $path, int $size, bool $maskable = false): void
{
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $transparent);
    imagealphablending($image, true);

    $green = imagecolorallocate($image, 22, 163, 74);
    $white = imagecolorallocate($image, 255, 255, 255);
    if ($maskable) {
        imagefilledrectangle($image, 0, 0, $size, $size, $green);
    } else {
        $radius = (int) round($size * 0.21875);
        imagefilledrectangle($image, $radius, 0, $size - $radius, $size, $green);
        imagefilledrectangle($image, 0, $radius, $size, $size - $radius, $green);
        imagefilledellipse($image, $radius, $radius, $radius * 2, $radius * 2, $green);
        imagefilledellipse($image, $size - $radius, $radius, $radius * 2, $radius * 2, $green);
        imagefilledellipse($image, $radius, $size - $radius, $radius * 2, $radius * 2, $green);
        imagefilledellipse($image, $size - $radius, $size - $radius, $radius * 2, $radius * 2, $green);
    }

    $scale = $size / 512;
    $points = [
        144, 128, 216, 128, 216, 240, 314, 128, 405, 128, 284, 257,
        415, 384, 318, 384, 216, 280, 216, 384, 144, 384,
    ];
    $scaled = array_map(static fn (int $point): int => (int) round($point * $scale), $points);
    imagefilledpolygon($image, $scaled, $white);

    imagepng($image, $path, 9);
    imagedestroy($image);
}

generateIcon($target.DIRECTORY_SEPARATOR.'kpi-64.png', 64);
generateIcon($target.DIRECTORY_SEPARATOR.'kpi-192.png', 192);
generateIcon($target.DIRECTORY_SEPARATOR.'kpi-512.png', 512);
generateIcon($target.DIRECTORY_SEPARATOR.'kpi-maskable-512.png', 512, true);
generateIcon($target.DIRECTORY_SEPARATOR.'apple-touch-icon.png', 180);
