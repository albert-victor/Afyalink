<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

jsonResponse([
    'success' => true,
    'data' => [
        'driver' => getActiveDbDriver(),
        'sqlite_path' => getSqlitePath(),
        'sqlite_exists' => file_exists(getSqlitePath()),
        'postgres_available' => connectPostgres() !== null,
        'offline_layers' => [
            'browser_indexeddb' => 'API responses cached 24h in IndexedDB (assets/js/offline.js)',
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
