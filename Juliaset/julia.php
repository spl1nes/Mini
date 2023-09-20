<?php
function generateJuliaSet($width, $height, $c_real, $c_imag, $max_iterations) {
    // Create an empty image
    $img = imagecreate($width, $height);

    // Set the color palette
    for ($i = 0; $i < 256; $i++) {
        $hue = $i / 255 * 360; // Map the value of $i to a hue angle (0-360 degrees)
        $color = hslToRgb($hue, 1, 0.5); // Convert the HSL color to RGB
        $colors[$i] = imagecolorallocate($img, $color[0], $color[1], $color[2]);
    }

    // Iterate over each pixel in the image
    for ($x = 0; $x < $width; $x++) {
        for ($y = 0; $y < $height; $y++) {
            // Map the pixel coordinates to the complex plane
            $z_real = $x / $width * 3 - 1.5;
            $z_imag = $y / $height * 3 - 1.5;

            // Iterate the complex function z = z^2 + c
            for ($i = 0; $i < $max_iterations; $i++) {
                $temp_real = $z_real * $z_real - $z_imag * $z_imag + $c_real;
                $temp_imag = 2 * $z_real * $z_imag + $c_imag;

                // Check if the sequence has escaped
                if ($temp_real * $temp_real + $temp_imag * $temp_imag > 4) {
                    break;
                }

                // Update the value of z
                $z_real = $temp_real;
                $z_imag = $temp_imag;
            }

            // Set the color of the pixel based on the number of iterations
            imagesetpixel($img, $x, $y, $colors[$i]);
        }
    }

    return $img;
}

function hslToRgb($hue, $saturation, $lightness) {
    $chroma = (1 - abs(2 * $lightness - 1)) * $saturation;
    $h_prime = $hue / 60;
    $x = $chroma * (1 - abs(fmod($h_prime, 2) - 1));
    $rgb1 = array(0, 0, 0);

    if ($h_prime >= 0 && $h_prime <= 1) {
        $rgb1 = array($chroma, $x, 0);
    } elseif ($h_prime >= 1 && $h_prime <= 2) {
        $rgb1 = array($x, $chroma, 0);
    } elseif ($h_prime >= 2 && $h_prime <= 3) {
        $rgb1 = array(0, $chroma, $x);
    } elseif ($h_prime >= 3 && $h_prime <= 4) {
        $rgb1 = array(0, $x, $chroma);
    } elseif ($h_prime >= 4 && $h_prime <= 5) {
        $rgb1 = array($x, 0, $chroma);
    } elseif ($h_prime >= 5 && $h_prime <= 6) {
        $rgb1 = array($chroma, 0, $x);
    }

    $m = $lightness - 0.5 * $chroma;
    $rgb2 = array($rgb1[0] + $m, $rgb1[1] + $m, $rgb1[2] + $m);

    $rgb = array(
        round($rgb2[0] * 255),
        round($rgb2[1] * 255),
        round($rgb2[2] * 255),
    );

    return $rgb;
}

$width = 800;
$height = 800;
$c_real = -0.70176;
$c_imag = -0.3842;
$max_iterations = 100;

$image = generateJuliaSet($width, $height, $c_real, $c_imag, $max_iterations);

// Display the image in the browser
imagepng($image, __DIR__ . '/julia.png');
imagedestroy($image);
