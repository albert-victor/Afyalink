<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "AfyaLink diagnostics\n====================\n\n";

echo "PHP: " . PHP_VERSION . "\n";
echo "pdo_pgsql: " . (extension_loaded('pdo_pgsql') ? 'yes' : 'no') . "\n";
echo "pdo_sqlite: " . (extension_loaded('pdo_sqlite') ? 'yes' : 'no') . "\n";
echo "APP_URL: " . (getenv('APP_URL') ?: '(not set)') . "\n";
echo "DB_DRIVER: " . (getenv('DB_DRIVER') ?: 'auto') . "\n\n";

$pg = @connectPostgres();
echo 'PostgreSQL: ' . ($pg ? 'connected' : 'unavailable') . "\n";

$sqlitePath = getSqlitePath();
echo 'SQLite path: ' . $sqlitePath . "\n";
echo 'SQLite file: ' . (file_exists($sqlitePath) ? 'exists (' . round(filesize($sqlitePath) / 1024 / 1024, 2) . ' MB)' : 'missing') . "\n";

$sqlite = @connectSqlite(false);
echo 'SQLite open: ' . ($sqlite ? 'ok' : 'failed') . "\n\n";

try {
    $pdo = getDbConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $hospitals = (int) $pdo->query('SELECT COUNT(*) FROM hospitals')->fetchColumn();
    echo "Active DB: {$driver}\n";
    echo "Hospitals: {$hospitals}\n";
} catch (Throwable $e) {
    echo 'Active DB: FAILED – ' . $e->getMessage() . "\n";
}

echo "\nStatic assets:\n";
$files = [
    'assets/css/app.css',
    'assets/vendor/fontawesome/css/all.min.css',
    'assets/vendor/fonts/app.css',
    'assets/js/app.js',
    'config/vendor_assets.php',
];
foreach ($files as $f) {
    $p = dirname(__DIR__) . '/' . $f;
    echo (file_exists($p) ? '[OK] ' : '[MISSING] ') . $f . "\n";
}

echo "\nCSS is NOT loaded from the database.\n";
echo "If the page looks unstyled, check browser DevTools → Network for 404 on .css files.\n\n";
echo "Run server from project root:\n";
echo "  cd Afyalink && php -S localhost:8000\n";
echo "Open: http://localhost:8000/index.php\n";
