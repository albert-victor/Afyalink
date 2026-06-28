<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

$regionId    = isset($_GET['region_id']) ? (int) $_GET['region_id'] : null;
$districtId  = isset($_GET['district_id']) ? (int) $_GET['district_id'] : null;
$search      = isset($_GET['q']) ? trim((string) $_GET['q']) : null;
$serviceCode = isset($_GET['service']) ? trim((string) $_GET['service']) : null;
$ownership   = isset($_GET['ownership']) ? trim((string) $_GET['ownership']) : null;
$openNow     = isset($_GET['open_now']) && in_array((string) $_GET['open_now'], ['1', 'true', 'yes'], true);
$hospitalId  = isset($_GET['id']) ? (int) $_GET['id'] : null;
$includeServices = !isset($_GET['summary']) || $_GET['summary'] !== '1';

try {
    $pdo = getDbConnection();

    if ($hospitalId) {
        $stmt = $pdo->prepare("
            SELECT h.id, h.facility_code, h.name, h.name_sw, h.facility_type, h.address, h.phone,
                h.emergency_phone, h.latitude, h.longitude, h.is_24_7,
                h.ownership, h.council, h.hfr_facility_type, h.operating_status,
                d.name AS district, d.name_sw AS district_sw,
                r.id AS region_id, r.name AS region, r.name_sw AS region_sw
            FROM hospitals h
            JOIN districts d ON d.id = h.district_id
            JOIN regions r ON r.id = d.region_id
            WHERE h.id = :id AND h.is_active = true
        ");
        $stmt->execute(['id' => $hospitalId]);
        $hospital = $stmt->fetch();

        if (!$hospital) {
            jsonResponse(['success' => false, 'error' => 'Hospital not found'], 404);
        }

        $hospital['services'] = fetchHospitalServices($pdo, $hospitalId);
        $openCount = count(array_filter($hospital['services'], fn($s) => $s['availability'] === 'open'));
        $hospital['open_services_count'] = $openCount;
        $hospital['total_services_count'] = count($hospital['services']);

        jsonResponse([
            'success' => true,
            'data' => $hospital,
            'meta' => [
                'timestamp' => date('c'),
                'day_of_week' => getCurrentDayOfWeek(),
                'current_time' => getCurrentTime(),
            ],
        ]);
    }

    [$sql, $params] = buildHospitalQuery(
        $regionId ?: null,
        $districtId ?: null,
        $search ?: null,
        $serviceCode ?: null,
        $ownership ?: null
    );

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll();

    $hospitalIds = array_column($hospitals, 'id');
    $servicesByHospital = $includeServices && $hospitalIds
        ? fetchHospitalServicesBatch($pdo, $hospitalIds)
        : [];

    foreach ($hospitals as &$hospital) {
        if ($includeServices) {
            $hospital['services'] = $servicesByHospital[(int) $hospital['id']] ?? [];
            $hospital['open_services_count'] = count(array_filter(
                $hospital['services'],
                fn($s) => $s['availability'] === 'open'
            ));
            $hospital['total_services_count'] = count($hospital['services']);
        } else {
            $countStmt = $pdo->prepare("
                SELECT COUNT(*) FROM hospital_services hs
                WHERE hs.hospital_id = :id AND hs.is_available = true
            ");
            $countStmt->execute(['id' => $hospital['id']]);
            $hospital['total_services_count'] = (int) $countStmt->fetchColumn();
        }
    }
    unset($hospital);

    if ($openNow) {
        $hospitals = array_values(array_filter(
            $hospitals,
            fn($h) => ($h['open_services_count'] ?? 0) > 0
        ));
    }

    jsonResponse([
        'success' => true,
        'data' => $hospitals,
        'count' => count($hospitals),
        'meta' => [
            'timestamp' => date('c'),
            'filters' => array_filter([
                'region_id' => $regionId,
                'district_id' => $districtId,
                'q' => $search,
                'service' => $serviceCode,
                'ownership' => $ownership,
                'open_now' => $openNow ?: null,
            ]),
            'day_of_week' => getCurrentDayOfWeek(),
            'current_time' => getCurrentTime(),
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $config['debug'] ? $e->getMessage() : 'Failed to load hospitals',
    ], 500);
}
