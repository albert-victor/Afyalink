<?php
/**
 * Import operating health facilities from MOH HFR Portal
 * Source: https://hfrportal.moh.go.tz/web/index.php
 *
 * Usage: php tools/import_hfr.php [--limit=N] [--skip-services]
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once __DIR__ . '/schedule_helpers.php';

const HFR_BASE = 'https://hfrportal.moh.go.tz/web/index.php';

const MVP_REGIONS = [
    'TZ.ET.DS' => 'Dar es Salaam',
    'TZ.ET.MO' => 'Morogoro',
    'TZ.ET.PW' => 'Pwani',
    'TZ.SH.IG' => 'Iringa',
    'TZ.LK.MZ' => 'Mwanza',
    'TZ.SH.MB' => 'Mbeya',
    'TZ.CL.DO' => 'Dodoma',
    'TZ.NT.AS' => 'Arusha',
];

const HOSPITAL_FACILITY_TYPES = [26, 30, 32, 38, 39, 2]; // National, RRH, DH, Hosp regional/district, HC

const HFR_SERVICE_MAP = [
    'laboratory'    => ['Laboratory', 'Maabara'],
    'xray'          => ['X-Ray', 'Radiology Services', 'General radiography', 'CT Scan'],
    'pharmacy'      => ['Pharmacy', 'Dispensing Room'],
    'maternity'     => ['Antenatal Care', 'Postnatal Care', 'CEmOC', 'BEmOC', 'Family Planning'],
    'emergency'     => ['Basic Emergency Preparedness', 'Emergency Dental'],
    'pediatrics'    => ['IMCI', 'IMM-BASIC', 'Vaccination'],
    'hiv'           => ['HIV/AIDS Care and Treatment', 'VCT', 'PMTCT', 'ART'],
    'mental'        => ['Mental Health Services', 'Psychosocial Support'],
    'dental'        => ['Dental', 'Oral Health', 'Restoration', 'Scaling'],
    'physiotherapy' => ['Physiotherapy'],
    'optical'       => ['General Ophthalmology', 'Eye Care', 'Optometry'],
    'cardiology'    => ['ECG', 'ECHO', 'Cardiovascular'],
    'blood_bank'    => ['Hematology and Blood Transfusion'],
    'surgery'       => ['Major Surgical', 'Minor Surgical', 'Surgical Intervetion'],
    'opd'           => ['OPD - Outpatient'],
    'ipd'           => ['IPD - Inpatient'],
    'immunization'  => ['IMM-BASIC', 'Vaccination'],
    'tb'            => ['TB Diagnosis', 'Smear Microscopy', 'MDRTB'],
    'malaria'       => ['mRDT', 'Slide Microscopy', 'Malaria'],
    'dialysis'      => ['Dialysis'],
];

if (PHP_SAPI === 'cli' && realpath($argv[0] ?? '') === realpath(__FILE__)) {
    runHfrImport($argv);
}

function runHfrImport(array $argv): void
{
$options = getopt('', ['limit:', 'skip-services', 'region:']);
$limit = isset($options['limit']) ? (int) $options['limit'] : 0;
$skipServices = isset($options['skip-services']);
$onlyRegion = $options['region'] ?? null;

if (function_exists('ob_implicit_flush')) {
    ob_implicit_flush(true);
}
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', '0');

echo "AfyaLink HFR Importer\n";
echo "Source: " . HFR_BASE . "\n";
echo str_repeat('=', 50) . "\n\n";

$pdo = getDbConnection();
$pdo->exec('SET search_path TO public');

ensureSchema($pdo);
seedBaseData($pdo);

$serviceTypeIds = [];
foreach ($pdo->query('SELECT id, code FROM service_types') as $row) {
    $serviceTypeIds[$row['code']] = (int) $row['id'];
}

$regions = MVP_REGIONS;
if ($onlyRegion && isset(MVP_REGIONS[$onlyRegion])) {
    $regions = [$onlyRegion => MVP_REGIONS[$onlyRegion]];
}

$totalImported = 0;
$totalServices = 0;

foreach ($regions as $regionCode => $regionName) {
    echo "Region: {$regionName} ({$regionCode})\n";

    $regionId = getOrCreateRegion($pdo, $regionName);
    $facilities = fetchAllFacilities($regionCode);

    echo "  Found " . count($facilities) . " operating facilities\n";

    $count = 0;
    foreach ($facilities as $fac) {
        if ($limit > 0 && $totalImported >= $limit) {
            break 2;
        }

        if ($fac['operating_status'] !== 'Operating') {
            continue;
        }

        $districtId = getOrCreateDistrict($pdo, $regionId, $fac['council']);
        $detail = fetchFacilityDetail($fac['facility_code']);

        $hospitalId = upsertHospital($pdo, $fac, $detail, $districtId, $regionName);
        $svcCount = assignServices($pdo, $hospitalId, $fac, $detail, $serviceTypeIds, !$skipServices);
        $totalServices += $svcCount;

        $count++;
        $totalImported++;
        echo "  [{$count}] {$fac['name']} ({$fac['facility_code']}) – {$svcCount} services\n";
        if (function_exists('flush')) {
            flush();
        }

        usleep(150000); // 150ms – be polite to MOH servers
    }
    echo "\n";
}

echo str_repeat('=', 50) . "\n";
echo "Done! Imported {$totalImported} facilities, {$totalServices} service links.\n";
echo "Data source: MOH Health Facility Registry (HFR Portal)\n";
}

// --- Functions ---

function ensureSchema(PDO $pdo): void
{
    $exists = $pdo->query("SELECT to_regclass('public.hospitals')")->fetchColumn();
    if ($exists) {
        return;
    }
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/schema.sql'));
}

function seedBaseData(PDO $pdo): void
{
    $pdo->exec(file_get_contents(dirname(__DIR__) . '/database/seed.sql'));
}

function hfrGet(string $path, array $params = []): string
{
    $url = HFR_BASE . '?' . http_build_query(array_merge(['r' => $path], $params));

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTPHEADER     => [
            'X-Requested-With: XMLHttpRequest',
            'Referer: ' . HFR_BASE . '?r=portal/advanced-search',
            'User-Agent: AfyaLink-MVP/1.0 (MUHAS Hackathon; health facility directory)',
        ],
    ]);

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($body === false || $code >= 400) {
        throw new RuntimeException("HFR request failed: {$url} (HTTP {$code})");
    }

    return $body;
}

function fetchAllFacilities(string $regionCode): array
{
    $all = [];
    $page = 1;
    $maxPages = 100;

    $typeParams = [];
    foreach (HOSPITAL_FACILITY_TYPES as $i => $typeId) {
        $typeParams["facility_type[{$i}]"] = $typeId;
    }

    while ($page <= $maxPages) {
        $params = array_merge([
            'search_by'     => 'fac_name',
            'region[]'      => $regionCode,
            'submit_search' => 'search',
            'page'          => $page,
        ], $typeParams);

        $html = hfrGet('portal/health-facility-list', $params);
        $rows = parseFacilityListHtml($html);

        if (empty($rows)) {
            break;
        }

        $all = array_merge($all, $rows);

        if (!preg_match('/of <b>(\d+)<\/b>/', $html, $m)) {
            break;
        }
        $total = (int) $m[1];
        if (count($all) >= $total) {
            break;
        }

        $page++;
        usleep(200000);
    }

    return $all;
}

function parseFacilityListHtml(string $html): array
{
    $facilities = [];

    if (!preg_match_all(
        '/data-key="(\d+)".*?data-col-seq="1">([^<]+)<.*?data-col-seq="2">([^<]+)<.*?data-col-seq="3">([^<]+)<.*?data-col-seq="4">([^<]+)<.*?data-col-seq="5">([^<]+)<.*?data-col-seq="6">([^<]+)<.*?data-col-seq="9">([^<]+)<.*?facility_code=([^"&]+)/s',
        $html,
        $matches,
        PREG_SET_ORDER
    )) {
        return [];
    }

    foreach ($matches as $m) {
        $facilities[] = [
            'hfr_id'           => (int) $m[1],
            'facility_code'    => html_entity_decode(trim($m[2])),
            'name'             => html_entity_decode(trim($m[3])),
            'hfr_facility_type'=> html_entity_decode(trim($m[4])),
            'region'           => html_entity_decode(trim($m[5])),
            'council'          => html_entity_decode(trim($m[6])),
            'ownership'        => html_entity_decode(trim($m[7])),
            'operating_status' => html_entity_decode(trim($m[8])),
        ];
    }

    return $facilities;
}

function fetchFacilityDetail(string $facilityCode): array
{
    $html = hfrGet('portal/facility-detail', ['facility_code' => $facilityCode]);

    $detail = [
        'phone'   => extractLabelValue($html, 'Official Phone'),
        'address' => extractLabelValue($html, 'Address'),
        'status'  => extractLabelValue($html, 'Status'),
        'common_name' => extractLabelValue($html, 'Common name'),
        'services' => [],
    ];

    // Fetch services via facility-service endpoint (uses internal id from detail button)
    if (preg_match('/facility-service&amp;id=(\d+)/', $html, $m)) {
        $svcHtml = hfrGet('portal/facility-service', ['id' => $m[1]]);
        $detail['services'] = parseServicesHtml($svcHtml);
    }

    return $detail;
}

function extractLabelValue(string $html, string $label): string
{
    $pattern = '/<label>' . preg_quote($label, '/') . '<\/label>\s*<span[^>]*>([^<]*)<\/span>/s';
    return preg_match($pattern, $html, $m) ? trim(html_entity_decode($m[1])) : '';
}

function parseServicesHtml(string $html): array
{
    $services = [];
    if (preg_match_all('/data-col-seq="2">([^<]+)</', $html, $matches)) {
        foreach ($matches[1] as $desc) {
            $desc = trim(html_entity_decode($desc));
            if ($desc !== '' && $desc !== 'Service Description') {
                $services[] = $desc;
            }
        }
    }
    return array_unique($services);
}

function mapHfrTypeToLocal(string $hfrType): string
{
    $t = strtolower($hfrType);
    if (str_contains($t, 'national')) return 'national';
    if (str_contains($t, 'regional referral') || str_contains($t, 'regional level')) return 'regional';
    if (str_contains($t, 'district')) return 'district';
    if (str_contains($t, 'health center')) return 'health_center';
    return 'hospital';
}

function is24_7(string $hfrType): bool
{
    $t = strtolower($hfrType);
    return str_contains($t, 'national') || str_contains($t, 'regional referral');
}

function mapHfrServicesToCodes(array $hfrServices): array
{
    $codes = [];
    foreach ($hfrServices as $svc) {
        foreach (HFR_SERVICE_MAP as $code => $keywords) {
            foreach ($keywords as $kw) {
                if (stripos($svc, $kw) !== false) {
                    $codes[$code] = true;
                    break;
                }
            }
        }
    }
    return array_keys($codes);
}

function defaultServicesForType(string $facilityType): array
{
    return match ($facilityType) {
        'national', 'regional' => ['emergency', 'maternity', 'laboratory', 'xray', 'pharmacy', 'surgery', 'pediatrics', 'hiv', 'opd', 'ipd'],
        'district'             => ['emergency', 'maternity', 'laboratory', 'xray', 'pharmacy', 'pediatrics', 'hiv', 'opd'],
        'health_center'        => ['maternity', 'laboratory', 'pharmacy', 'pediatrics', 'malaria', 'immunization', 'opd'],
        default                => ['opd', 'pharmacy'],
    };
}

function getOrCreateRegion(PDO $pdo, string $name): int
{
    $stmt = $pdo->prepare('SELECT id FROM regions WHERE name = :name');
    $stmt->execute(['name' => $name]);
    $id = $stmt->fetchColumn();
    if ($id) return (int) $id;

    $stmt = $pdo->prepare('INSERT INTO regions (name, name_sw) VALUES (:name, :name) RETURNING id');
    $stmt->execute(['name' => $name]);
    return (int) $stmt->fetchColumn();
}

function getOrCreateDistrict(PDO $pdo, int $regionId, string $council): int
{
    $name = str_replace(' Region', '', $council);
    $stmt = $pdo->prepare('SELECT id FROM districts WHERE region_id = :rid AND name = :name');
    $stmt->execute(['rid' => $regionId, 'name' => $name]);
    $id = $stmt->fetchColumn();
    if ($id) return (int) $id;

    $stmt = $pdo->prepare('INSERT INTO districts (region_id, name, name_sw) VALUES (:rid, :name, :name) RETURNING id');
    $stmt->execute(['rid' => $regionId, 'name' => $name]);
    return (int) $stmt->fetchColumn();
}

function upsertHospital(PDO $pdo, array $fac, array $detail, int $districtId, string $regionName): int
{
    $localType = mapHfrTypeToLocal($fac['hfr_facility_type']);
    $phone = $detail['phone'] ?: null;
    $address = $detail['address'] ?: ($fac['council'] . ', ' . $regionName);
    $name = $detail['common_name'] ?: $fac['name'];

    $stmt = $pdo->prepare("
        INSERT INTO hospitals (
            facility_code, hfr_id, name, name_sw, district_id, facility_type,
            hfr_facility_type, council, ownership, operating_status,
            address, phone, is_24_7, is_active, data_source
        ) VALUES (
            :code, :hfr_id, :name, :name, :district_id, :ftype,
            :hfr_ftype, :council, :ownership, :status,
            :address, :phone, :is247, true, 'MOH HFR Portal'
        )
        ON CONFLICT (facility_code) DO UPDATE SET
            name = EXCLUDED.name,
            hfr_facility_type = EXCLUDED.hfr_facility_type,
            council = EXCLUDED.council,
            ownership = EXCLUDED.ownership,
            operating_status = EXCLUDED.operating_status,
            address = EXCLUDED.address,
            phone = EXCLUDED.phone,
            is_24_7 = EXCLUDED.is_24_7,
            updated_at = NOW()
        RETURNING id
    ");

    $is247 = is24_7($fac['hfr_facility_type']);

    $stmt->bindValue(':code', $fac['facility_code']);
    $stmt->bindValue(':hfr_id', $fac['hfr_id'], PDO::PARAM_INT);
    $stmt->bindValue(':name', $name);
    $stmt->bindValue(':district_id', $districtId, PDO::PARAM_INT);
    $stmt->bindValue(':ftype', $localType);
    $stmt->bindValue(':hfr_ftype', $fac['hfr_facility_type']);
    $stmt->bindValue(':council', $fac['council']);
    $stmt->bindValue(':ownership', $fac['ownership']);
    $stmt->bindValue(':status', $fac['operating_status']);
    $stmt->bindValue(':address', $address);
    $stmt->bindValue(':phone', $phone);
    $stmt->bindValue(':is247', $is247, PDO::PARAM_BOOL);
    $stmt->execute();

    return (int) $stmt->fetchColumn();
}

function assignServices(PDO $pdo, int $hospitalId, array $fac, array $detail, array $serviceTypeIds, bool $fetchFromHfr): int
{
    $localType = mapHfrTypeToLocal($fac['hfr_facility_type']);

    if ($fetchFromHfr && !empty($detail['services'])) {
        $codes = mapHfrServicesToCodes($detail['services']);
    } else {
        $codes = defaultServicesForType($localType);
    }

    if (empty($codes)) {
        $codes = defaultServicesForType($localType);
    }

    // Clear existing services for re-import
    $pdo->prepare('DELETE FROM hospital_services WHERE hospital_id = :id')->execute(['id' => $hospitalId]);

    $count = 0;
    foreach (array_unique($codes) as $code) {
        if (!isset($serviceTypeIds[$code])) continue;

        $stmt = $pdo->prepare("
            INSERT INTO hospital_services (hospital_id, service_type_id, is_available)
            VALUES (:hid, :sid, true)
            ON CONFLICT (hospital_id, service_type_id) DO UPDATE SET is_available = EXCLUDED.is_available
            RETURNING id
        ");
        $stmt->execute(['hid' => $hospitalId, 'sid' => $serviceTypeIds[$code]]);
        $hsId = $stmt->fetchColumn();
        if (!$hsId) continue;

        seedSchedule($pdo, (int) $hsId, $code, $localType);
        $count++;
    }

    return $count;
}
