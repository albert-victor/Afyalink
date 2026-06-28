<?php
/**
 * Enrich AfyaLink data with realistic status logs, wait times, phones & notes.
 * Status mix target: ~88% open, ~10% limited, ~1% unavailable (schedule handles rare closed hours)
 *
 * Data layers:
 *   - Hospitals: MOH HFR Portal (real)
 *   - Service types per hospital: HFR mapping + defaults (semi-real)
 *   - Schedules & live status/wait: MVP simulated (until staff API)
 *
 * Run after import: php tools/enrich_realism.php
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';

echo "AfyaLink Realism Enricher\n" . str_repeat('=', 50) . "\n";

$pdo = getDbConnection();

$knownFacilities = [
    'MUHIMBILI' => [
        'phone' => '+255 22 215 1367',
        'emergency_phone' => '114',
        'name_sw' => 'Hospitali ya Taifa Muhimbili',
    ],
    'AMANA' => [
        'phone' => '+255 22 286 0510',
        'emergency_phone' => '+255 22 286 0511',
        'name_sw' => 'Hospitali ya Rufaa ya Mkoa Amana',
    ],
    'MWANANYAMALA' => [
        'phone' => '+255 22 272 6555',
        'emergency_phone' => '+255 22 272 6556',
        'name_sw' => 'Hospitali ya Rufaa ya Mkoa Mwananyamala',
    ],
    'TEMEKE' => [
        'phone' => '+255 22 286 2345',
        'emergency_phone' => '+255 22 286 2346',
        'name_sw' => 'Hospitali ya Rufaa ya Mkoa Temeke',
    ],
    'MOROGORO' => [
        'phone' => '+255 23 261 2336',
        'emergency_phone' => '+255 23 261 2400',
        'name_sw' => 'Hospitali ya Rufaa ya Mkoa Morogoro',
    ],
    'TUMBI' => [
        'phone' => '+255 23 293 4255',
        'emergency_phone' => '+255 23 293 4256',
        'name_sw' => 'Hospitali ya Wilaya Tumbi',
    ],
    'BUGURUNI' => [
        'phone' => '+255 22 286 1122',
        'emergency_phone' => '114',
        'name_sw' => 'Kituo cha Afya Buguruni',
    ],
    'IRINGA' => [
        'phone' => '+255 26 270 2891',
        'emergency_phone' => '+255 26 270 2892',
        'name_sw' => 'Hospitali ya Rufaa ya Mkoa Iringa',
    ],
];

$updatedPhones = 0;
foreach ($knownFacilities as $keyword => $data) {
    $stmt = $pdo->prepare("
        UPDATE hospitals SET
            phone = COALESCE(NULLIF(:phone, ''), phone),
            emergency_phone = COALESCE(NULLIF(:emergency, ''), emergency_phone),
            name_sw = COALESCE(NULLIF(:name_sw, ''), name_sw)
        WHERE UPPER(name) LIKE :kw OR UPPER(facility_code) LIKE :kw2
    ");
    $stmt->execute([
        'phone' => $data['phone'],
        'emergency' => $data['emergency_phone'],
        'name_sw' => $data['name_sw'] ?? '',
        'kw' => '%' . $keyword . '%',
        'kw2' => '%' . $keyword . '%',
    ]);
    $updatedPhones += $stmt->rowCount();
}
echo "Updated {$updatedPhones} facility contact records.\n";

$waitProfiles = [
    'emergency'    => ['national' => [5, 25],  'regional' => [10, 45],  'district' => [15, 60],  'default' => [20, 90]],
    'maternity'    => ['national' => [15, 40],  'regional' => [20, 55],  'district' => [25, 70],  'default' => [30, 90]],
    'laboratory'   => ['national' => [10, 30],  'regional' => [15, 45],  'district' => [20, 60],  'default' => [25, 75]],
    'xray'         => ['national' => [20, 50],  'regional' => [30, 70],  'district' => [40, 90],  'default' => [45, 120]],
    'pharmacy'     => ['national' => [5, 15],   'regional' => [8, 20],   'district' => [10, 30],  'default' => [12, 35]],
    'opd'          => ['national' => [30, 90],  'regional' => [45, 120], 'district' => [60, 150], 'default' => [45, 120]],
    'pediatrics'   => ['national' => [20, 50],  'regional' => [25, 65],  'district' => [30, 80],  'default' => [30, 75]],
    'surgery'      => ['national' => [60, 180], 'regional' => [90, 240], 'district' => [120, 300],'default' => [90, 240]],
    'default'      => ['national' => [15, 45],  'regional' => [20, 60],  'district' => [25, 75],  'default' => [20, 60]],
];

$notesSw = [
    'emergency'  => 'Wagonjwa wa dharura wanashughulikiwa kwanza. Kwa ajali kubwa piga 114.',
    'maternity'  => 'Huduma za uzazi zinapatikana. Beba kitabu cha kliniki ukiwa na mjamzito.',
    'laboratory' => 'Matokeo ya vipimo vya haraka yanapatikana ndani ya masaa 2–4.',
    'pharmacy'   => 'Dawa za msingi za NHIF na zile za kulipia zinapatikana.',
    'opd'        => 'Fika mapema – foleni huwa kubwa kuanzia saa 3 asubuhi.',
    'xray'       => 'Picha za X-ray za kawaida zinachukua dakika 15–30.',
];

$notesEn = [
    'emergency'  => 'Emergency patients triaged first. For major accidents call 114.',
    'maternity'  => 'Maternity services available. Bring your ANC card if pregnant.',
    'laboratory' => 'Routine lab results typically within 2–4 hours.',
    'pharmacy'   => 'NHIF and private medications available.',
    'opd'        => 'Arrive early – queues build from 9 AM.',
    'xray'       => 'Standard X-rays take 15–30 minutes.',
];

// Reset availability – mark ~1% as genuinely unavailable (equipment/staff gap)
$pdo->exec('UPDATE hospital_services SET is_available = true');

$services = $pdo->query("
    SELECT hs.id AS hs_id, st.code, h.facility_type, h.is_24_7
    FROM hospital_services hs
    JOIN service_types st ON st.id = hs.service_type_id
    JOIN hospitals h ON h.id = hs.hospital_id
")->fetchAll();

$pdo->exec('DELETE FROM service_status_log');

$statusCount = 0;
$unavailableCount = 0;
$limitedCount = 0;
$availableCount = 0;
$hour = (int) date('G');
$isPeakHours = $hour >= 9 && $hour <= 15;

foreach ($services as $svc) {
    $hsId = (int) $svc['hs_id'];
    $code = $svc['code'];
    $ftype = $svc['facility_type'] ?: 'default';

    // ~1% unavailable – rare specialist gaps (never core services)
    $markUnavailable = ($hsId % 97 === 0) && !in_array($code, ['emergency', 'opd', 'pharmacy', 'maternity', 'laboratory'], true);
    if ($markUnavailable) {
        $pdo->prepare('UPDATE hospital_services SET is_available = false WHERE id = :id')
            ->execute(['id' => $hsId]);
        $unavailableCount++;
        continue;
    }

    $profile = $waitProfiles[$code] ?? $waitProfiles['default'];
    $range = $profile[$ftype] ?? $profile['default'];
    $waitMin = random_int($range[0], $range[1]);
    if ($isPeakHours) {
        $waitMin = (int) round($waitMin * 1.2);
    }

    // Live status mix: mostly open, few busy queues
    $roll = random_int(1, 100);
    if ($roll <= 88) {
        $status = 'available';
        $availableCount++;
    } elseif ($roll <= 97) {
        $status = 'limited';
        $limitedCount++;
        $waitMin = (int) round($waitMin * 1.3);
    } else {
        $status = 'busy';
        $limitedCount++;
        $waitMin = (int) round($waitMin * 1.5);
    }

    $noteSw = $notesSw[$code] ?? null;
    $noteEn = $notesEn[$code] ?? null;
    if ($noteSw) {
        $pdo->prepare('UPDATE hospital_services SET notes = :en, notes_sw = :sw WHERE id = :id')
            ->execute(['en' => $noteEn, 'sw' => $noteSw, 'id' => $hsId]);
    }

    $stmt = $pdo->prepare("
        INSERT INTO service_status_log (hospital_service_id, status, wait_minutes, updated_by)
        VALUES (:hs, :status, :wait, 'AfyaLink Seed')
    ");
    $stmt->execute([
        'hs' => $hsId,
        'status' => $status,
        'wait' => $waitMin > 0 ? $waitMin : null,
    ]);
    $statusCount++;
}

echo "Seeded {$statusCount} status entries ({$availableCount} available, {$limitedCount} limited/busy).\n";
echo "Marked {$unavailableCount} services as unavailable (~1%).\n";
echo "Tip: run php tools/reseed_schedules.php if schedules look wrong.\n";
echo "Done.\n";
