<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

$vendorOk = file_exists(dirname(__DIR__) . '/assets/vendor/fontawesome/css/all.min.css')
    && file_exists(dirname(__DIR__) . '/assets/css/app.css');

jsonResponse([
    'success' => true,
    'data' => [
        'driver' => getActiveDbDriver(),
        'sqlite_path' => getSqlitePath(),
        'sqlite_exists' => file_exists(getSqlitePath()),
        'postgres_available' => connectPostgres() !== null,
        'vendor_assets_ok' => $vendorOk,
        'hospitals' => (int) getDbConnection()->query('SELECT COUNT(*) FROM hospitals')->fetchColumn(),
        'offline_layers' => [
            'browser_indexeddb' => 'API responses cached in IndexedDB (assets/js/offline.js)',
            'service_worker' => 'Static files + API network-first cache (sw.js)',
            'sqlite_fallback' => 'Server-side copy when PostgreSQL is down',
        ],
        'load_sources' => [
            'online' => 'PHP API → PostgreSQL (primary)',
            'postgres_down' => 'PHP API → SQLite fallback file',
            'browser_offline' => 'IndexedDB + Service Worker cache',
        ],
    ],
    'meta' => ['timestamp' => date('c')],
]);
