<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/admin/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

$admin = requireAdminApi();

try {
    $pdo = getDbConnection();
    $services = fetchHospitalServices($pdo, $admin['hospital_id']);

    $hStmt = $pdo->prepare('
        SELECT h.id, h.name, h.facility_code, h.phone, h.address, h.facility_type,
            d.name AS district, r.name AS region
        FROM hospitals h
        JOIN districts d ON d.id = h.district_id
        JOIN regions r ON r.id = d.region_id
        WHERE h.id = :id
    ');
    $hStmt->execute(['id' => $admin['hospital_id']]);
    $hospital = $hStmt->fetch();

    jsonResponse([
        'success' => true,
        'data' => [
            'hospital' => $hospital,
            'services' => $services,
        ],
        'meta' => ['timestamp' => date('c')],
    ]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => $config['debug'] ? $e->getMessage() : 'Failed'], 500);
}
