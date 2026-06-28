<?php
require __DIR__ . '/../config/env.php';
require __DIR__ . '/../config/database.php';
$p = getDbConnection();
echo "Hospitals: " . $p->query('SELECT COUNT(*) FROM hospitals')->fetchColumn() . "\n";
echo "Districts: " . $p->query('SELECT COUNT(*) FROM districts')->fetchColumn() . "\n";
echo "Service links: " . $p->query('SELECT COUNT(*) FROM hospital_services')->fetchColumn() . "\n";
foreach ($p->query('SELECT r.name, COUNT(h.id) c FROM hospitals h JOIN districts d ON d.id=h.district_id JOIN regions r ON r.id=d.region_id GROUP BY r.name ORDER BY r.name') as $row) {
    echo "  {$row['name']}: {$row['c']}\n";
}
