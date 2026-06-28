<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/env.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/api/bootstrap.php';

$pdo = getDbConnection();
$counts = ['open' => 0, 'limited' => 0, 'closed' => 0, 'unavailable' => 0];
$total = 0;

$ids = $pdo->query('SELECT id FROM hospitals WHERE is_active = true')->fetchAll(PDO::FETCH_COLUMN);

foreach ($ids as $id) {
    foreach (fetchHospitalServices($pdo, (int) $id) as $s) {
        $total++;
        $a = $s['availability'];
        if (isset($counts[$a])) {
            $counts[$a]++;
        }
    }
}

echo "Availability mix (all hospitals, now=" . date('l H:i') . ")\n";
echo str_repeat('-', 40) . "\n";
foreach ($counts as $k => $v) {
    $pct = round($v / max(1, $total) * 100, 1);
    echo sprintf("  %-12s %5d  (%s%%)\n", $k, $v, $pct);
}
echo "  TOTAL        {$total}\n";
