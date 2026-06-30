<?php
/**
 * Download Font Awesome, Google Fonts, and other CDN assets for offline use.
 *
 * Usage: php tools/download_offline_assets.php
 */
declare(strict_types=1);

const FA_VERSION = '6.5.1';
const FA_CDN = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . FA_VERSION;

const FONT_SETS = [
    'app' => 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap',
    'login' => 'https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap',
    'admin' => 'https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&display=swap',
];

$root = dirname(__DIR__);
$vendor = $root . '/assets/vendor';

echo "AfyaLink Offline Asset Downloader\n";
echo str_repeat('=', 50) . "\n\n";

$downloaded = downloadFontAwesome($vendor);
$downloaded = array_merge($downloaded, downloadGoogleFonts($vendor));
$manifestPath = writeManifest($vendor, $downloaded);

echo "\n" . str_repeat('=', 50) . "\n";
echo "Done! " . count($downloaded) . " files saved under assets/vendor/\n";
echo "Manifest: {$manifestPath}\n";
echo "Update HTML to use local vendor CSS (see config/vendor_assets.php).\n";

function ensureDir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function downloadFile(string $url, string $dest, array $headers = []): bool
{
    ensureDir(dirname($dest));

    if (is_file($dest) && filesize($dest) > 0) {
        echo "  skip (exists) " . basename($dest) . "\n";
        return true;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code >= 400) {
        echo "  FAIL HTTP {$code}: {$url}\n";
        return false;
    }

    file_put_contents($dest, $body);
    $kb = round(strlen($body) / 1024, 1);
    echo "  saved {$kb} KB → " . str_replace('\\', '/', $dest) . "\n";
    return true;
}

function downloadFontAwesome(string $vendor): array
{
    echo "Font Awesome " . FA_VERSION . "\n";

    $base = $vendor . '/fontawesome';
    $cssUrl = FA_CDN . '/css/all.min.css';
    $cssPath = $base . '/css/all.min.css';
    $files = [];

    if (!downloadFile($cssUrl, $cssPath)) {
        return $files;
    }
    $files[] = 'assets/vendor/fontawesome/css/all.min.css';

    $css = file_get_contents($cssPath);
    preg_match_all('#url\(\.\./webfonts/([^)]+)\)#', $css, $matches);

    $fontFiles = array_unique($matches[1] ?? []);
    if (!$fontFiles) {
        // Fallback: standard FA6 webfonts
        $fontFiles = [
            'fa-solid-900.woff2',
            'fa-regular-400.woff2',
            'fa-brands-400.woff2',
            'fa-v4compatibility.woff2',
        ];
    }

    foreach ($fontFiles as $font) {
        $url = FA_CDN . '/webfonts/' . $font;
        $dest = $base . '/webfonts/' . $font;
        if (downloadFile($url, $dest)) {
            $files[] = 'assets/vendor/fontawesome/webfonts/' . $font;
        }
    }

    return $files;
}

function downloadGoogleFonts(string $vendor): array
{
    echo "\nGoogle Fonts\n";
    $files = [];
    $fontDir = $vendor . '/fonts/files';
    ensureDir($fontDir);

    foreach (FONT_SETS as $name => $cssUrl) {
        echo "  set: {$name}\n";

        $css = fetchGoogleCss($cssUrl);
        if ($css === '') {
            echo "  FAIL: empty CSS for {$name}\n";
            continue;
        }

        $localCss = $css;
        preg_match_all('/url\((https:\/\/fonts\.gstatic\.com\/[^)]+)\)/', $css, $urlMatches);

        foreach (array_unique($urlMatches[1] ?? []) as $fontUrl) {
            $filename = basename(parse_url($fontUrl, PHP_URL_PATH) ?: 'font.woff2');
            $dest = $fontDir . '/' . $filename;

            if (downloadFile($fontUrl, $dest)) {
                $localPath = '../files/' . $filename;
                $localCss = str_replace($fontUrl, $localPath, $localCss);
                $rel = 'assets/vendor/fonts/files/' . $filename;
                if (!in_array($rel, $files, true)) {
                    $files[] = $rel;
                }
            }
        }

        $outCss = $vendor . '/fonts/' . $name . '.css';
        file_put_contents($outCss, $localCss);
        $files[] = 'assets/vendor/fonts/' . $name . '.css';
        echo "  wrote fonts/{$name}.css\n";
    }

    return $files;
}

function fetchGoogleCss(string $cssUrl): string
{
    $ch = curl_init($cssUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return is_string($body) ? $body : '';
}

function writeManifest(string $vendor, array $files): string
{
    $manifest = [
        'generated_at' => date('c'),
        'fontawesome_version' => FA_VERSION,
        'files' => array_values(array_unique($files)),
    ];

    $path = $vendor . '/manifest.json';
    file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return str_replace('\\', '/', $path);
}
