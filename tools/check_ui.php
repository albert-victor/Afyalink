<?php
declare(strict_types=1);

$base = getenv('CHECK_BASE') ?: 'http://localhost:8000';

$paths = [
    '/index.php',
    '/assets/css/app.css?v=9',
    '/assets/vendor/fontawesome/css/all.min.css',
    '/assets/vendor/fonts/app.css',
    '/assets/js/app.js?v=9',
];

echo "Base: {$base}\n\n";

foreach ($paths as $path) {
    $url = rtrim($base, '/') . $path;
    $ctx = stream_context_create(['http' => ['timeout' => 5]]);
    $headers = @get_headers($url, true, $ctx);
    $code = is_array($headers) ? (int) preg_replace('/\D/', '', (string) ($headers[0] ?? '0')) : 0;
    echo "{$code} {$path}\n";
}

echo "\n--- DB ---\n";
require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';

try {
    $pdo = getDbConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $count = (int) $pdo->query('SELECT COUNT(*) FROM hospitals')->fetchColumn();
    echo "OK driver={$driver} hospitals={$count}\n";
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . "\n";
}

echo "\n--- index.php head snippet ---\n";
$html = @file_get_contents(rtrim($base, '/') . '/index.php', false, $ctx);
if ($html === false) {
    echo "Could not fetch index.php\n";
} elseif (str_contains($html, '<link rel="stylesheet"')) {
    preg_match_all('/<link[^>]+stylesheet[^>]+>/', $html, $m);
    foreach ($m[0] as $link) {
        echo $link . "\n";
    }
} else {
    echo substr($html, 0, 500) . "\n";
}
