<?php
/**
 * Re-apply realistic schedules to all hospital services (no HFR re-import).
 * Usage: php tools/reseed_schedules.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/schedule_helpers.php';

echo "AfyaLink Schedule Reseed\n" . str_repeat('=', 50) . "\n";

$pdo = getDbConnection();

$rows = $pdo->query("
    SELECT hs.id AS hs_id, st.code, h.facility_type
    FROM hospital_services hs
    JOIN service_types st ON st.id = hs.service_type_id
    JOIN hospitals h ON h.id = hs.hospital_id
    WHERE hs.is_available = true
")->fetchAll();

$count = 0;
foreach ($rows as $row) {
    seedSchedule($pdo, (int) $row['hs_id'], $row['code'], $row['facility_type']);
    $count++;
}

echo "Reseeded schedules for {$count} services.\n";
echo "Run: php tools/enrich_realism.php  (to refresh status mix)\n";
