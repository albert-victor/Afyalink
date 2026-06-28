<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';
require_once dirname(__DIR__, 2) . '/admin/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$admin = requireAdminApi();
$body = getRequestBody();

$hospitalServiceId = (int) ($body['hospital_service_id'] ?? 0);
$status = in_array($body['status'] ?? '', ['available', 'limited', 'busy', 'unavailable'], true)
    ? $body['status'] : 'available';
$waitMinutes = isset($body['wait_minutes']) ? (int) $body['wait_minutes'] : null;
$isAvailable = !isset($body['is_available']) || (bool) $body['is_available'];
$notes = trim((string) ($body['notes'] ?? ''));

if ($hospitalServiceId <= 0) {
    jsonResponse(['success' => false, 'error' => 'hospital_service_id required'], 422);
}

try {
    $pdo = getDbConnection();

    $check = $pdo->prepare('
        SELECT hs.id FROM hospital_services hs
        WHERE hs.id = :hsid AND hs.hospital_id = :hid
    ');
    $check->execute(['hsid' => $hospitalServiceId, 'hid' => $admin['hospital_id']]);
    if (!$check->fetch()) {
        jsonResponse(['success' => false, 'error' => 'Service not found for your facility'], 403);
    }

    $pdo->prepare('
        UPDATE hospital_services SET is_available = :avail, notes = :notes, notes_sw = :notes
        WHERE id = :id
    ')->execute([
        'avail' => boolParam($isAvailable),
        'notes' => $notes ?: null,
        'id' => $hospitalServiceId,
    ]);

    $pdo->prepare('
        INSERT INTO service_status_log (hospital_service_id, status, wait_minutes, updated_by)
        VALUES (:hsid, :status, :wait, :by)
    ')->execute([
        'hsid' => $hospitalServiceId,
        'status' => $status,
        'wait' => $waitMinutes,
        'by' => $admin['facility_code'] . ' (' . $admin['hospital_name'] . ')',
    ]);

    jsonResponse([
        'success' => true,
        'message' => 'Huduma imesasishwa',
        'meta' => ['timestamp' => date('c'), 'driver' => getActiveDbDriver()],
    ]);
} catch (Throwable $e) {
    jsonResponse(['success' => false, 'error' => $config['debug'] ? $e->getMessage() : 'Update failed'], 500);
}
