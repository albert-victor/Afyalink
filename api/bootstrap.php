<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/config.php';

$config = require dirname(__DIR__) . '/config/config.php';
date_default_timezone_set($config['timezone']);

function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function getRequestBody(): array
{
    $raw = file_get_contents('php://input');
    if (!$raw) {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function getCurrentDayOfWeek(): int
{
    return (int) date('w');
}

function getCurrentTime(): string
{
    return date('H:i:s');
}

function isServiceOpenNow(array $schedule, int $dayOfWeek, string $currentTime): bool
{
    if (empty($schedule)) {
        return false;
    }

    foreach ($schedule as $slot) {
        if ((int) $slot['day_of_week'] !== $dayOfWeek) {
            continue;
        }
        if (!empty($slot['is_closed'])) {
            return false;
        }
        return $currentTime >= $slot['open_time'] && $currentTime <= $slot['close_time'];
    }

    return false;
}

function getAvailabilityStatus(bool $isAvailable, bool $isOpenNow, ?string $latestStatus): string
{
    if (!$isAvailable) {
        return 'unavailable';
    }
    if (!$isOpenNow) {
        return 'closed';
    }
    if (in_array($latestStatus, ['limited', 'busy'], true)) {
        return 'limited';
    }
    return 'open';
}

function buildHospitalQuery(
    ?int $regionId = null,
    ?int $districtId = null,
    ?string $search = null,
    ?string $serviceCode = null,
    ?string $ownership = null
): array {
    $params = [];
    $where = ['h.is_active = true'];

    if ($regionId) {
        $where[] = 'r.id = :region_id';
        $params['region_id'] = $regionId;
    }

    if ($districtId) {
        $where[] = 'd.id = :district_id';
        $params['district_id'] = $districtId;
    }

    if ($search) {
        $like = sqlLike('h.name');
        $likeSw = sqlLike('h.name_sw');
        $likeAddr = sqlLike('h.address');
        $likeDist = sqlLike('d.name');
        $likeDistSw = sqlLike('d.name_sw');
        $likeReg = sqlLike('r.name');
        $likeRegSw = sqlLike('r.name_sw');
        $where[] = "(
            {$like} OR {$likeSw} OR {$likeAddr} OR {$likeDist} OR {$likeDistSw}
            OR {$likeReg} OR {$likeRegSw}
            OR EXISTS (
                SELECT 1 FROM hospital_services hs_s
                JOIN service_types st_s ON st_s.id = hs_s.service_type_id
                WHERE hs_s.hospital_id = h.id AND hs_s.is_available = true
                AND (" . sqlLike('st_s.name') . " OR " . sqlLike('st_s.name_sw') . " OR " . sqlLike('st_s.code') . ")
            )
        )";
        $params['search'] = '%' . $search . '%';
    }

    if ($serviceCode) {
        $where[] = 'EXISTS (
            SELECT 1 FROM hospital_services hs2
            JOIN service_types st2 ON st2.id = hs2.service_type_id
            WHERE hs2.hospital_id = h.id AND st2.code = :service_code AND hs2.is_available = true
        )';
        $params['service_code'] = $serviceCode;
    }

    if ($ownership) {
        $normalized = strtolower($ownership);
        if (in_array($normalized, ['public', 'private'], true)) {
            $where[] = 'LOWER(h.ownership) = :ownership';
            $params['ownership'] = $normalized;
        }
    }

    $whereClause = implode(' AND ', $where);

    $sql = "
        SELECT
            h.id, h.facility_code, h.name, h.name_sw, h.facility_type, h.address, h.phone,
            h.emergency_phone, h.latitude, h.longitude, h.is_24_7,
            h.ownership, h.council, h.hfr_facility_type,
            d.name AS district, d.name_sw AS district_sw,
            r.id AS region_id, r.name AS region, r.name_sw AS region_sw
        FROM hospitals h
        JOIN districts d ON d.id = h.district_id
        JOIN regions r ON r.id = d.region_id
        WHERE {$whereClause}
        ORDER BY
            CASE h.facility_type
                WHEN 'national' THEN 1
                WHEN 'regional' THEN 2
                WHEN 'teaching' THEN 3
                ELSE 4
            END,
            h.name
    ";

    return [$sql, $params];
}

function fetchHospitalServices(PDO $pdo, int $hospitalId): array
{
    $dayOfWeek = getCurrentDayOfWeek();
    $currentTime = getCurrentTime();

    $stmt = $pdo->prepare("
        SELECT
            hs.id AS hospital_service_id,
            st.code, st.name, st.name_sw, st.icon, st.category,
            hs.is_available, hs.notes, hs.notes_sw,
            (
                SELECT ssl.status FROM service_status_log ssl
                WHERE ssl.hospital_service_id = hs.id
                ORDER BY ssl.created_at DESC LIMIT 1
            ) AS latest_status,
            (
                SELECT ssl.wait_minutes FROM service_status_log ssl
                WHERE ssl.hospital_service_id = hs.id
                ORDER BY ssl.created_at DESC LIMIT 1
            ) AS wait_minutes
        FROM hospital_services hs
        JOIN service_types st ON st.id = hs.service_type_id
        WHERE hs.hospital_id = :hospital_id
        ORDER BY st.category, st.name
    ");
    $stmt->execute(['hospital_id' => $hospitalId]);
    $services = $stmt->fetchAll();

    foreach ($services as &$service) {
        $schedStmt = $pdo->prepare("
            SELECT day_of_week, open_time, close_time, is_closed
            FROM service_schedules
            WHERE hospital_service_id = :hs_id
            ORDER BY day_of_week
        ");
        $schedStmt->execute(['hs_id' => $service['hospital_service_id']]);
        $schedule = $schedStmt->fetchAll();

        $isOpenNow = isServiceOpenNow($schedule, $dayOfWeek, $currentTime);
        $service['schedule'] = $schedule;
        $service['is_open_now'] = $isOpenNow;
        $service['availability'] = getAvailabilityStatus(
            (bool) $service['is_available'],
            $isOpenNow,
            $service['latest_status']
        );
        $service['checked_at'] = date('c');
    }

    return $services;
}

function fetchHospitalServicesBatch(PDO $pdo, array $hospitalIds): array
{
    if (!$hospitalIds) {
        return [];
    }

    $hospitalIds = array_values(array_unique(array_map('intval', $hospitalIds)));
    $idList = implode(',', $hospitalIds);
    $dayOfWeek = getCurrentDayOfWeek();
    $currentTime = getCurrentTime();

    $stmt = $pdo->query("
        SELECT
            hs.id AS hospital_service_id,
            hs.hospital_id,
            st.code, st.name, st.name_sw, st.icon, st.category,
            hs.is_available, hs.notes, hs.notes_sw,
            (
                SELECT ssl.status FROM service_status_log ssl
                WHERE ssl.hospital_service_id = hs.id
                ORDER BY ssl.created_at DESC LIMIT 1
            ) AS latest_status,
            (
                SELECT ssl.wait_minutes FROM service_status_log ssl
                WHERE ssl.hospital_service_id = hs.id
                ORDER BY ssl.created_at DESC LIMIT 1
            ) AS wait_minutes
        FROM hospital_services hs
        JOIN service_types st ON st.id = hs.service_type_id
        WHERE hs.hospital_id IN ({$idList})
        ORDER BY hs.hospital_id, st.category, st.name
    ");
    $services = $stmt->fetchAll();

    if (!$services) {
        return array_fill_keys($hospitalIds, []);
    }

    $hsIds = array_map('intval', array_column($services, 'hospital_service_id'));
    $hsList = implode(',', $hsIds);
    $schedRows = $pdo->query("
        SELECT hospital_service_id, day_of_week, open_time, close_time, is_closed
        FROM service_schedules
        WHERE hospital_service_id IN ({$hsList})
        ORDER BY hospital_service_id, day_of_week
    ")->fetchAll();

    $schedulesByHs = [];
    foreach ($schedRows as $row) {
        $schedulesByHs[(int) $row['hospital_service_id']][] = $row;
    }

    $byHospital = array_fill_keys($hospitalIds, []);
    foreach ($services as &$service) {
        $schedule = $schedulesByHs[(int) $service['hospital_service_id']] ?? [];
        $isOpenNow = isServiceOpenNow($schedule, $dayOfWeek, $currentTime);
        $service['schedule'] = $schedule;
        $service['is_open_now'] = $isOpenNow;
        $service['availability'] = getAvailabilityStatus(
            (bool) $service['is_available'],
            $isOpenNow,
            $service['latest_status']
        );
        $service['checked_at'] = date('c');
        $byHospital[(int) $service['hospital_id']][] = $service;
    }
    unset($service);

    return $byHospital;
}
