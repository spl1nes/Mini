<?php

$fp = \fopen(__DIR__ . '/icons.csv', 'r');
while (($data = \fgetcsv($fp, 1024, ';')) !== false) {
    $curl = curl_init('https://www.iso.org' . $data[0]);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    $savePath = __DIR__ . '/iso_icons/' . $data[1] . '.png';
    $save = fopen($savePath, 'w');
    curl_setopt($curl, CURLOPT_FILE, fopen($savePath, 'w'));

    curl_exec($curl);
    curl_close($curl);

    fclose($save);
}

fclose($fp);

$files = \scandir(__DIR__ . '/iso_icons');
foreach ($files as $file) {
    if ($file === '.' || $file === '..' || \stripos($file, '_') !== false) {
        continue;
    }

    $in = \imagecreatefrompng(__DIR__ . '/iso_icons/' . $file);

    $white = \imagecolorallocate($in, 255, 255, 255);
    imagecolortransparent($in, $white);

    $width = \imagesx($in);
    $height = \imagesy($in);

    $outputImage = imagecreatetruecolor($width, $height);
    imagealphablending($outputImage, false);
    imagesavealpha($outputImage, true);
    $transparentColor = imagecolorallocatealpha($outputImage, 0, 0, 0, 127);
    imagefill($outputImage, 0, 0, $transparentColor);

    for ($i = 0; $i < $width; ++$i) {
        \imagesetpixel($in, $i, 0, $white);
        \imagesetpixel($in, $i, $height - 1, $white);

        \imagesetpixel($in, $i, 1, $white);
        \imagesetpixel($in, $i, $height - 2, $white);

        \imagesetpixel($in, $i, 2, $white);
        \imagesetpixel($in, $i, $height - 3, $white);
    }

    for ($i = 0; $i < $height; ++$i) {
        \imagesetpixel($in, 0, $i, $white);
        \imagesetpixel($in, $width - 1, $i, $white);

        \imagesetpixel($in, 1, $i, $white);
        \imagesetpixel($in, $width - 2, $i, $white);

        \imagesetpixel($in, 2, $i, $white);
        \imagesetpixel($in, $width - 3, $i, $white);
    }

    for ($i = 0; $i < $width; ++$i) {
        for ($j = 0; $j < $height; ++$j) {
            $color = imagecolorat($in, $i, $j);
            $rgb = imagecolorsforindex($in, $color);

            $gray = (int) round(($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3);

            $alpha = (int) (127 - round((255 - $gray) / 2));

            // for some reason most of the "black" is not really black. This fixes it.
            if (\abs(0 - $alpha) < 40) {
                $alpha = 0;
            }

            $colorWithAlpha = imagecolorallocatealpha($outputImage, 0, 0, 0, (int) \min(127, \max(0, $alpha)));
            imagesetpixel($outputImage, $i, $j, $colorWithAlpha);
        }
    }

    \imagepng($outputImage, __DIR__ . '/iso_icons2/' . $file);
    \imagedestroy($in);
    \imagedestroy($outputImage);
}
