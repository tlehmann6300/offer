<?php
// Create a simple placeholder logo (1x1 transparent PNG)
// This should be replaced with the actual IBC logo
$img = imagecreatetruecolor(200, 60);
imagesavealpha($img, true);
$transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
imagefill($img, 0, 0, $transparent);

$white = imagecolorallocate($img, 255, 255, 255);
$font = 5; // Built-in font
imagestring($img, $font, 70, 25, 'IBC', $white);

imagepng($img, 'ibc_logo_original_navbar.png');
imagedestroy($img);

echo "Placeholder logo created: ibc_logo_original_navbar.png\n";
echo "Please replace this with the actual IBC logo.\n";
