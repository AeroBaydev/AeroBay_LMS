<?php
header("Content-type: text/css; charset: UTF-8");

// Retrieve dynamic values from the database or plugin settings
$colorPrimary = get_config('theme_yourtheme', 'color_primary');
$colorSecondary = get_config('theme_yourtheme', 'color_secondary');
$textColor = get_config('theme_yourtheme', 'text_color');

echo <<<CSS
:root {
    --color-primary: $colorPrimary;
    --color-secondary: $colorSecondary;
    --text-color: $textColor;
}
CSS;
?>
