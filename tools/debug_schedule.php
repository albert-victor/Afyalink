<?php
require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/api/bootstrap.php';

$pdo = getDbConnection();

$row = $pdo->query("
    SELECT hs.id, h.name, st.code
    FROM hospital_services hs
    JOIN hospitals h ON h.id = hs.hospital_id
    JOIN service_types st ON st.id = hs.service_type_id
    WHERE h.facility_type = 'health_center' AND st.code = 'opd'
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

$sched = $pdo->prepare('SELECT * FROM service_schedules WHERE hospital_service_id = ? ORDER BY day_of_week');
$sched->execute([$row['id']]);
$slots = $sched->fetchAll();

echo "Hospital: {$row['name']} service: {$row['code']}\n";
foreach ($slots as $s) {
    echo "  day={$s['day_of_week']} {$s['open_time']}-{$s['close_time']} closed=" . ($s['is_closed'] ? 'Y' : 'N') . "\n";
}

$open = isServiceOpenNow($slots, (int) date('w'), date('H:i:s'));
echo "isOpenNow: " . ($open ? 'YES' : 'NO') . "\n";

$svc = fetchHospitalServices($pdo, (int) $pdo->query("SELECT hospital_id FROM hospital_services WHERE id={$row['id']}")->fetchColumn());
foreach ($svc as $s) {
    if ($s['code'] === 'opd') {
        echo "availability: {$s['availability']} is_available: {$s['is_available']}\n";
    }
}
