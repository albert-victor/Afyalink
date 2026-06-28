<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/api/bootstrap.php';
require_once dirname(__DIR__) . '/admin/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$body = getRequestBody();
$facilityCode = trim((string) ($body['facility_code'] ?? ''));
$pin = trim((string) ($body['pin'] ?? ''));

if ($facilityCode === '' || $pin === '') {
    jsonResponse(['success' => false, 'error' => 'Facility code and PIN required'], 422);
}

try {
    $pdo = getDbConnection();
    if (adminLogin($pdo, $facilityCode, $pin)) {
        jsonResponse([
            'success' => true,
            'data' => [
                'hospital_id' => $_SESSION['admin_hospital_id'],
                'facility_code' => $_SESSION['admin_facility_code'],
                'hospital_name' => $_SESSION['admin_hospital_name'],
            ],
        ]);
    }
    jsonResponse(['success' => false, 'error' => 'Invalid facility code or PIN'], 401);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => $config['debug'] ? $e->getMessage() : 'Login failed'], 500);
}
