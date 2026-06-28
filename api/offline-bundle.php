<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

try {
    $pdo = getDbConnection();

    $regions = $pdo->query("
        SELECT r.id, r.name, r.name_sw,
            (SELECT COUNT(*) FROM hospitals h
             JOIN districts d ON d.id = h.district_id
             WHERE d.region_id = r.id AND h.is_active = true) AS hospital_count
        FROM regions r ORDER BY r.name
    ")->fetchAll();

    foreach ($regions as &$region) {
        $dStmt = $pdo->prepare('SELECT id, name, name_sw FROM districts WHERE region_id = :rid ORDER BY name');
        $dStmt->execute(['rid' => $region['id']]);
        $region['districts'] = $dStmt->fetchAll();
    }

    $serviceTypes = $pdo->query('SELECT id, code, name, name_sw, icon, category FROM service_types ORDER BY category, name')->fetchAll();

    [$sql, $params] = buildHospitalQuery(null, null, null, null);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll();

    foreach ($hospitals as &$h) {
        $h['services'] = fetchHospitalServices($pdo, (int) $h['id']);
        $h['open_services_count'] = count(array_filter($h['services'], fn($s) => $s['availability'] === 'open'));
        $h['total_services_count'] = count($h['services']);
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'regions' => $regions,
            'service_types' => $serviceTypes,
            'hospitals' => $hospitals,
        ],
        'meta' => [
            'timestamp' => date('c'),
            'driver' => getActiveDbDriver(),
            'count' => count($hospitals),
            'offline_ready' => true,
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $config['debug'] ? $e->getMessage() : 'Bundle unavailable',
    ], 500);
}
