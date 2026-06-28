<?php
/**
 * Sync PostgreSQL → SQLite fallback database
 * Run after import or admin updates: php tools/sync_sqlite.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "AfyaLink SQLite Sync\n";
echo str_repeat('=', 40) . "\n";

$pg = connectPostgres();
if (!$pg) {
    die("✗ PostgreSQL not available – cannot sync.\n");
}

$sqlite = connectSqlite(true);
if (!$sqlite) {
    die("✗ SQLite not available – enable pdo_sqlite.\n");
}

$tables = [
    'regions',
    'districts',
    'service_types',
    'hospitals',
    'hospital_services',
    'service_schedules',
    'service_status_log',
];

$sqlite->exec('PRAGMA foreign_keys = OFF');

foreach ($tables as $table) {
    echo "Syncing {$table}... ";
    $sqlite->exec("DELETE FROM {$table}");

    $rows = $pg->query("SELECT * FROM {$table}")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "0 rows\n";
        continue;
    }

    $columns = array_keys($rows[0]);
    $colList = implode(', ', $columns);
    $placeholders = implode(', ', array_map(fn($c) => ':' . $c, $columns));
    $stmt = $sqlite->prepare("INSERT INTO {$table} ({$colList}) VALUES ({$placeholders})");

    foreach ($rows as $row) {
        foreach ($row as $key => $val) {
            if (is_bool($val)) {
                $row[$key] = $val ? 1 : 0;
            }
            if ($val === null) {
                $row[$key] = null;
            }
        }
        $stmt->execute($row);
    }

    echo count($rows) . " rows\n";
}

$sqlite->exec('PRAGMA foreign_keys = ON');

$path = getSqlitePath();
$size = filesize($path);
echo "\n✓ Sync complete → {$path}\n";
echo "  Size: " . round($size / 1024 / 1024, 2) . " MB\n";
