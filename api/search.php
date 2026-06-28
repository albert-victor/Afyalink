<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(['ok' => true]);
}

$search      = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$regionId    = isset($_GET['region_id']) ? (int) $_GET['region_id'] : null;
$districtId  = isset($_GET['district_id']) ? (int) $_GET['district_id'] : null;
$serviceCode = isset($_GET['service']) ? trim((string) $_GET['service']) : null;
$ownership   = isset($_GET['ownership']) ? trim((string) $_GET['ownership']) : null;
$openNow     = isset($_GET['open_now']) && in_array((string) $_GET['open_now'], ['1', 'true', 'yes'], true);
$limit       = min(15, max(1, (int) ($_GET['limit'] ?? 8)));

if ($search === '' && !$regionId && !$districtId && !$serviceCode && !$ownership && !$openNow) {
    jsonResponse(['success' => true, 'data' => [], 'count' => 0, 'shown' => 0]);
}

try {
    $pdo = getDbConnection();

    [$sql, $params] = buildHospitalQuery(
        $regionId ?: null,
        $districtId ?: null,
        $search ?: null,
        $serviceCode ?: null,
        $ownership ?: null
    );

    $countSql = preg_replace('/SELECT[\s\S]+?FROM hospitals h/i', 'SELECT COUNT(*) FROM hospitals h', $sql);
    $countSql = preg_replace('/\s+ORDER BY[\s\S]+$/i', '', $countSql);
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int) $countStmt->fetchColumn();

    $stmt = $pdo->prepare($sql . ' LIMIT ' . $limit);
    $stmt->execute($params);
    $hospitals = $stmt->fetchAll();

    $servicesByHospital = fetchHospitalServicesBatch($pdo, array_column($hospitals, 'id'));
    $suggestions = [];

    foreach ($hospitals as $hospital) {
        $id = (int) $hospital['id'];
        $services = $servicesByHospital[$id] ?? [];
        $openServices = array_values(array_filter($services, fn($s) => $s['availability'] === 'open'));

        if ($openNow && count($openServices) === 0) {
            continue;
        }

        $suggestions[] = [
            'id' => $id,
            'name' => $hospital['name'],
            'name_sw' => $hospital['name_sw'],
            'facility_type' => $hospital['facility_type'],
            'district' => $hospital['district'],
            'district_sw' => $hospital['district_sw'],
            'region' => $hospital['region'],
            'region_sw' => $hospital['region_sw'],
            'region_id' => (int) $hospital['region_id'],
            'address' => $hospital['address'],
            'phone' => $hospital['phone'],
            'is_24_7' => (bool) $hospital['is_24_7'],
            'ownership' => $hospital['ownership'],
            'open_services_count' => count($openServices),
            'total_services_count' => count($services),
            'open_services' => array_map(fn($s) => [
                'code' => $s['code'],
                'name' => $s['name'],
                'name_sw' => $s['name_sw'],
            ], array_slice($openServices, 0, 5)),
        ];
    }

    jsonResponse([
        'success' => true,
        'data' => $suggestions,
        'count' => $totalCount,
        'shown' => count($suggestions),
        'meta' => [
            'timestamp' => date('c'),
            'q' => $search ?: null,
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse([
        'success' => false,
        'error' => $config['debug'] ? $e->getMessage() : 'Search failed',
    ], 500);
}
