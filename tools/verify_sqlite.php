<?php
$db = new PDO('sqlite:' . __DIR__ . '/../database/afyalink_fallback.sqlite');
echo "Hospitals: " . $db->query('SELECT COUNT(*) FROM hospitals')->fetchColumn() . "\n";
echo "Regions: " . $db->query('SELECT COUNT(*) FROM regions')->fetchColumn() . "\n";
foreach ($db->query('SELECT r.name, COUNT(h.id) c FROM hospitals h JOIN districts d ON d.id=h.district_id JOIN regions r ON r.id=d.region_id GROUP BY r.name ORDER BY r.name') as $r) {
    echo "  {$r['name']}: {$r['c']}\n";
}
$ak = $db->query("SELECT name FROM hospitals WHERE facility_code='AKP-IRINGA-01'")->fetchColumn();
echo "Aga Khan: " . ($ak ?: 'MISSING') . "\n";
