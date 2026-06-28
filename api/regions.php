<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

try {
    $pdo = getDbConnection();
    $stmt = $pdo->query("
        SELECT r.id, r.name, r.name_sw,
            (SELECT COUNT(*) FROM hospitals h
             JOIN districts d ON d.id = h.district_id
             WHERE d.region_id = r.id AND h.is_active = true) AS hospital_count
        FROM regions r
        ORDER BY r.name
    ");
    $regions = $stmt->fetchAll();

    foreach ($regions as &$region) {
        $dStmt = $pdo->prepare("
            SELECT id, name, name_sw FROM districts
            WHERE region_id = :region_id ORDER BY name
        ");
        $dStmt->execute(['region_id' => $region['id']]);
        $region['districts'] = $dStmt->fetchAll();
    }

    jsonResponse([
        'success' => true,
        'data' => $regions,
        'meta' => [
            'timestamp' => date('c'),
            'timezone' => $config['timezone'],
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $config['debug'] ? $e->getMessage() : 'Database connection failed',
    ], 500);
}
