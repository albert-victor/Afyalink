<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

try {
    $pdo = getDbConnection();

    $regions = (int) $pdo->query('SELECT COUNT(*) FROM regions')->fetchColumn();
    $hospitals = (int) $pdo->query('SELECT COUNT(*) FROM hospitals WHERE is_active = true')->fetchColumn();
    $services = (int) $pdo->query('SELECT COUNT(*) FROM service_types')->fetchColumn();

    $openNow = 0;
    $hStmt = $pdo->query('SELECT id FROM hospitals WHERE is_active = true');
    while ($row = $hStmt->fetch()) {
        $svcs = fetchHospitalServices($pdo, (int) $row['id']);
        $openNow += count(array_filter($svcs, fn($s) => $s['availability'] === 'open'));
    }

    jsonResponse([
        'success' => true,
        'data' => [
            'app' => $config['app_name'],
            'version' => $config['app_version'],
            'regions' => $regions,
            'hospitals' => $hospitals,
            'service_types' => $services,
            'services_open_now' => $openNow,
            'mvp_regions' => $config['regions_mvp'],
            'ai_configured' => $config['openrouter']['api_key'] !== '',
            'disclaimer' => $config['disclaimer'],
        ],
        'meta' => [
            'timestamp' => date('c'),
            'timezone' => $config['timezone'],
            'day_of_week' => getCurrentDayOfWeek(),
            'current_time' => getCurrentTime(),
            'db_driver' => getActiveDbDriver(),
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $config['debug'] ? $e->getMessage() : 'Stats unavailable',
    ], 500);
}
