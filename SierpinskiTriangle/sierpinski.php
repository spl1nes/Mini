<?php

function draw_sierpinski_triangle($image, $size, $depth) {
    // Define the starting point of the triangle
    $x1 = $size / 2;
    $y1 = 0;
    $x2 = 0;
    $y2 = $size;
    $x3 = $size;
    $y3 = $size;

    // Recursively draw the triangles
    draw_sierpinski($image, $x1, $y1, $x2, $y2, $x3, $y3, $depth);
}

function draw_sierpinski($image, $x1, $y1, $x2, $y2, $x3, $y3, $depth) {
    // Draw the current triangle
    if ($depth == 0) {
        // Define the color of the triangle
        $color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
        imagefilledpolygon($image, array($x1, $y1, $x2, $y2, $x3, $y3), 3, $color);
    } else {
        // Calculate the midpoints of the sides
        $x12 = ($x1 + $x2) / 2;
        $y12 = ($y1 + $y2) / 2;
        $x23 = ($x2 + $x3) / 2;
        $y23 = ($y2 + $y3) / 2;
        $x31 = ($x3 + $x1) / 2;
        $y31 = ($y3 + $y1) / 2;

        // Recursively draw the three smaller triangles
        draw_sierpinski($image, $x1, $y1, $x12, $y12, $x31, $y31, $depth - 1);
        draw_sierpinski($image, $x12, $y12, $x2, $y2, $x23, $y23, $depth - 1);
        draw_sierpinski($image, $x31, $y31, $x23, $y23, $x3, $y3, $depth - 1);
    }
}

// Set up the image
$image_size = 1024;
$image = imagecreatetruecolor($image_size, $image_size);
$bg_color = imagecolorallocate($image, 0, 0, 0);
imagefill($image, 0, 0, $bg_color);

// Draw the Sierpinski Triangle
draw_sierpinski_triangle($image, $image_size, 7);

// Display the image in the browser
imagepng($image, __DIR__ . '/sierpinski.png');
imagedestroy($image);
