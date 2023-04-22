<?php

function createMandelbrotImage($width, $height, $max_iterations, $x_min, $x_max, $y_min, $y_max)
{
    // Create a blank image of the specified size
    $image = imagecreatetruecolor($width, $height);

    // Loop through each pixel in the image
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            // Calculate the complex number corresponding to this pixel
            $c2 = [
                $x_min + ($x / ($width - 1)) * ($x_max - $x_min),
                $y_min + ($y / ($height - 1)) * ($y_max - $y_min)
            ];

            // Calculate the number of iterations before the sequence diverges
            $z2 = [0, 0];

            $iterations = 0;
            while ($z2[0] * $z2[0] + $z2[1] * $z2[1] < 4 && $iterations < $max_iterations) {
                $z2 = [
                    $z2[0] * $z2[0] - $z2[1] * $z2[1] + $c2[0],
                    2 * $z2[0] * $z2[1] + $c2[1]
                ];
                $iterations++;
            }

            // Calculate the color of this pixel based on the number of iterations
            if ($iterations == $max_iterations) {
                $color = imagecolorallocate($image, 0, 0, 0);
            } else {
                $color = imagecolorallocate($image, $iterations * 255 / $max_iterations, 0, 0);
            }

            // Set the color of this pixel in the image
            imagesetpixel($image, $x, $y, $color);
        }
    }

    return $image;
}


// Define the image dimensions and Mandelbrot set parameters
$width = 800;
$height = 800;
$max_iterations = 100;
$x_min = -2;
$x_max = 1;
$y_min = -1.5;
$y_max = 1.5;

// Call the createMandelbrotImage function to generate the image
$image = createMandelbrotImage($width, $height, $max_iterations, $x_min, $x_max, $y_min, $y_max);

// Display the image in the browser
imagepng($image, __DIR__ . '/brot.png');
imagedestroy($image);
