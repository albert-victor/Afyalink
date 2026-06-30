<?php
declare(strict_types=1);

/**
 * Local vendor asset paths for offline-first UI.
 *
 * @param string $base Path prefix: '' for app root, '../' for admin/
 * @param 'app'|'login'|'admin' $fontSet Which Google Fonts bundle to load
 */
function renderVendorStyles(string $base = '', string $fontSet = 'app'): void
{
    $v = (int) (@filemtime(__DIR__ . '/../assets/vendor/fontawesome/css/all.min.css') ?: time());
    $fv = (int) (@filemtime(__DIR__ . "/../assets/vendor/fonts/{$fontSet}.css") ?: time());

    $fa = htmlspecialchars($base . 'assets/vendor/fontawesome/css/all.min.css?v=' . $v, ENT_QUOTES, 'UTF-8');
    $fonts = htmlspecialchars($base . "assets/vendor/fonts/{$fontSet}.css?v=" . $fv, ENT_QUOTES, 'UTF-8');

    echo "<link rel=\"stylesheet\" href=\"{$fa}\">\n";
    echo "    <link rel=\"stylesheet\" href=\"{$fonts}\">\n";
}
