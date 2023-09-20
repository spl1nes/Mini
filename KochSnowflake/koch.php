<?php

function draw_koch_snowflake($image, $size, $depth) {
    // Define the starting points of the triangle
    $x1 = $size / 2;
    $y1 = 0;
    $x2 = $size;
    $y2 = $size * sin(deg2rad(60));
    $x3 = 0;
    $y3 = $size * sin(deg2rad(60));

    // Recursively draw the snowflake
    draw_koch($image, $x1, $y1, $x2, $y2, $depth);
    draw_koch($image, $x2, $y2, $x3, $y3, $depth);
    draw_koch($image, $x3, $y3, $x1, $y1, $depth);
}

function draw_koch($image, $x1, $y1, $x2, $y2, $depth) {
    if ($depth == 0) {
        // Define the color of the line
        $color = imagecolorallocate($image, 255, 0, 0);
        imageline($image, $x1, $y1, $x2, $y2, $color);
    } else {
        // Calculate the new points
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $x3 = $x1 + $dx / 3;
        $y3 = $y1 + $dy / 3;
        $x4 = $x2 - $dx / 3;
        $y4 = $y2 - $dy / 3;
        $x5 = ($x1 + $x2) / 2 - $dy / (2 * sqrt(3));
        $y5 = ($y1 + $y2) / 2 + $dx / (2 * sqrt(3));

        // Recursively draw the four line segments
        draw_koch($image, $x1, $y1, $x3, $y3, $depth - 1);
        draw_koch($image, $x3, $y3, $x5, $y5, $depth - 1);
        draw_koch($image, $x5, $y5, $x4, $y4, $depth - 1);
        draw_koch($image, $x4, $y4, $x2, $y2, $depth - 1);
    }
}

// Set up the image
$image_size = 1024;
$image = imagecreatetruecolor($image_size, $image_size);
$bg_color = imagecolorallocate($image, 0, 0, 0);
imagefill($image, 0, 0, $bg_color);

// Draw the Koch Snowflake
draw_koch_snowflake($image, $image_size, 5);

// Display the image in the browser
imagepng($image, __DIR__ . '/koch.png');
imagedestroy($image);
