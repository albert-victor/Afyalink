<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("
        SELECT id, code, name, name_sw, icon, category
        FROM service_types ORDER BY category, name
    ");

    jsonResponse([
        'success' => true,
        'data' => $stmt->fetchAll(),
        'meta' => ['timestamp' => date('c')],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $config['debug'] ? $e->getMessage() : 'Failed to load services',
    ], 500);
}
