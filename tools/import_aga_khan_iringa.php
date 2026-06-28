<?php
/**
 * Import Aga Khan Polyclinic Iringa from official facility data (not in MOH HFR).
 * Source: https://www.aku.edu/ (Aga Khan Health Services Tanzania)
 *
 * Usage: php tools/import_aga_khan_iringa.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/schedule_helpers.php';

const FACILITY_CODE = 'AKP-IRINGA-01';
const OFFICIAL_NAME = 'Aga Khan Polyclinic, Iringa';
const OFFICIAL_NAME_SW = 'Polikliniki ya Aga Khan, Iringa';

echo "Aga Khan Iringa Manual Import\n";
echo str_repeat('=', 50) . "\n";

$pdo = getDbConnection();
$pdo->exec('SET search_path TO public');
$exists = $pdo->query("SELECT to_regclass('public.hospitals')")->fetchColumn();
if (!$exists) {
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
}
$pdo->exec(file_get_contents(dirname(__DIR__) . '/database/seed.sql'));

// Confirm not in HFR
require_once __DIR__ . '/import_hfr.php';
$hfrMatches = array_filter(
    fetchAllFacilities('TZ.SH.IG'),
    fn($f) => stripos($f['name'], 'aga khan') !== false
        || stripos($f['name'], 'aga khan polyclinic') !== false
);
if ($hfrMatches) {
    echo "SKIP: Found in HFR portal – use import_hfr.php instead:\n";
    foreach ($hfrMatches as $m) {
        echo "  {$m['facility_code']} | {$m['name']}\n";
    }
    exit(0);
}
echo "HFR check: Aga Khan Polyclinic NOT listed in Iringa HFR – importing manually.\n\n";

$regionId = getOrCreateRegion($pdo, 'Iringa');
$districtId = getOrCreateDistrict($pdo, $regionId, 'Iringa MC');

$serviceTypeIds = [];
foreach ($pdo->query('SELECT id, code FROM service_types') as $row) {
    $serviceTypeIds[$row['code']] = (int) $row['id'];
}

// Official services → AfyaLink service codes
$serviceCodes = [
    'opd',           // Consultation / General Medicine
    'hiv',           // CTC - Counselling and Testing Centre (HIV)
    'dental',
    'optical',       // Optometry
    'laboratory',    // Pathology
    'pharmacy',
    'physiotherapy',
    'xray',          // Radiology
    'maternity',     // RCH - Reproductive Child Health
    'pediatrics',    // RCH
    'immunization',  // RCH
];

$stmt = $pdo->prepare("
    INSERT INTO hospitals (
        facility_code, name, name_sw, district_id, facility_type,
        hfr_facility_type, council, ownership, operating_status,
        address, phone, is_24_7, is_active, data_source
    ) VALUES (
        :code, :name, :name_sw, :district_id, 'hospital',
        'Polyclinic', 'Iringa MC', 'Private', 'Operating',
        :address, :phone, false, true, 'Aga Khan Health Services (official website)'
    )
    ON CONFLICT (facility_code) DO UPDATE SET
        name = EXCLUDED.name,
        name_sw = EXCLUDED.name_sw,
        ownership = EXCLUDED.ownership,
        address = EXCLUDED.address,
        phone = EXCLUDED.phone,
        hfr_facility_type = EXCLUDED.hfr_facility_type,
        data_source = EXCLUDED.data_source,
        updated_at = NOW()
    RETURNING id
");
$stmt->execute([
    'code' => FACILITY_CODE,
    'name' => OFFICIAL_NAME,
    'name_sw' => OFFICIAL_NAME_SW,
    'district_id' => $districtId,
    'address' => 'Plot No 43/44, Block No A, Jamat Street, P.O. Box 119, Iringa',
    'phone' => '+255 686 312 490',
]);
$hospitalId = (int) $stmt->fetchColumn();

$pdo->prepare('DELETE FROM hospital_services WHERE hospital_id = :id')->execute(['id' => $hospitalId]);

$svcCount = 0;
foreach (array_unique($serviceCodes) as $code) {
    if (!isset($serviceTypeIds[$code])) {
        continue;
    }

    $ins = $pdo->prepare("
        INSERT INTO hospital_services (hospital_id, service_type_id, is_available)
        VALUES (:hid, :sid, true)
        RETURNING id
    ");
    $ins->execute(['hid' => $hospitalId, 'sid' => $serviceTypeIds[$code]]);
    $hsId = (int) $ins->fetchColumn();

    seedAgaKhanSchedule($pdo, $hsId, $code);
    $svcCount++;
    echo "  + {$code}\n";
}

// Status log – available
$pdo->prepare('DELETE FROM service_status_log WHERE hospital_service_id IN (SELECT id FROM hospital_services WHERE hospital_id = :hid)')
    ->execute(['hid' => $hospitalId]);
$statusStmt = $pdo->prepare("
    INSERT INTO service_status_log (hospital_service_id, status, wait_minutes, updated_by)
    SELECT id, 'available', 20, 'AfyaLink Manual Import'
    FROM hospital_services WHERE hospital_id = :hid
");
$statusStmt->execute(['hid' => $hospitalId]);

echo "\nImported: " . OFFICIAL_NAME . " ({$svcCount} services)\n";
echo "Ownership: Private | Code: " . FACILITY_CODE . "\n";
echo "Hours: Mon–Fri 08:00–20:00, Sat/Sun/Holidays 10:00–14:00\n";
echo "Done.\n";

function seedAgaKhanSchedule(PDO $pdo, int $hsId, string $serviceCode): void
{
    $pdo->prepare('DELETE FROM service_schedules WHERE hospital_service_id = :id')
        ->execute(['id' => $hsId]);

    // Mon–Fri 08:00–20:00
    for ($d = 1; $d <= 5; $d++) {
        insertSchedule($pdo, $hsId, $d, '08:00', '20:00', false);
    }
    // Sat/Sun/Public holidays 10:00–14:00
    insertSchedule($pdo, $hsId, 6, '10:00', '14:00', false);
    insertSchedule($pdo, $hsId, 0, '10:00', '14:00', false);
}
