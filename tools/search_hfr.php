<?php
declare(strict_types=1);
/**
 * Search HFR portal by region and optional name keyword.
 * Usage: php tools/search_hfr.php --region=TZ.SH.IG --q=aga
 */
require_once dirname(__DIR__) . '/config/env.php';
require_once __DIR__ . '/import_hfr.php';

$opts = getopt('', ['region:', 'q:']);
$region = $opts['region'] ?? 'TZ.SH.IG';
$q = strtolower($opts['q'] ?? '');

$all = fetchAllFacilities($region);
echo "Region {$region}: " . count($all) . " facilities\n";

if ($q !== '') {
    $all = array_filter($all, fn($f) => str_contains(strtolower($f['name']), $q)
        || str_contains(strtolower($f['facility_code'] ?? ''), $q));
    echo "Matches for '{$q}': " . count($all) . "\n";
}

foreach ($all as $m) {
    echo "{$m['facility_code']} | {$m['name']} | {$m['ownership']} | {$m['hfr_facility_type']}\n";
}
