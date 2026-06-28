<?php
/**
 * AfyaLink Database Setup Script
 * Run: php setup.php
 */
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

$host     = getenv('DB_HOST') ?: 'localhost';
$port     = getenv('DB_PORT') ?: '5432';
$dbname   = getenv('DB_NAME') ?: 'afyalink';
$user     = getenv('DB_USER') ?: 'postgres';
$password = getenv('DB_PASS') ?: 'postgres';

echo "AfyaLink Database Setup\n";
echo "========================\n\n";

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname=postgres", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $exists = $pdo->query("SELECT 1 FROM pg_database WHERE datname = '{$dbname}'")->fetch();
    if (!$exists) {
        echo "Creating database '{$dbname}'...\n";
        $pdo->exec("CREATE DATABASE {$dbname}");
        echo "✓ Database created\n";
    } else {
        echo "✓ Database '{$dbname}' already exists\n";
    }
} catch (PDOException $e) {
    die("✗ Cannot connect to PostgreSQL: " . $e->getMessage() . "\n");
}

try {
    $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    echo "Running schema.sql...\n";
    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    $pdo->exec($schema);
    echo "✓ Schema applied\n";

    echo "Running seed.sql...\n";
    $seed = file_get_contents(__DIR__ . '/database/seed.sql');
    $pdo->exec($seed);
    echo "✓ Seed data loaded\n";

    $hospitals = $pdo->query('SELECT COUNT(*) FROM hospitals')->fetchColumn();
    $services  = $pdo->query('SELECT COUNT(*) FROM hospital_services')->fetchColumn();
    $regions   = $pdo->query('SELECT COUNT(*) FROM regions')->fetchColumn();

    echo "\n========================\n";
    echo "Setup complete!\n";
    echo "  Regions:   {$regions}\n";
    echo "  Hospitals: {$hospitals}\n";
    echo "  Services:  {$services}\n";
    echo "\nOpen: http://localhost:8080/\n";
    echo "Admin: http://localhost:8080/admin/\n";

    if (extension_loaded('pdo_sqlite')) {
        echo "\nSyncing SQLite fallback...\n";
        passthru('php ' . escapeshellarg(__DIR__ . '/tools/sync_sqlite.php'));
    }
} catch (PDOException $e) {
    die("✗ Setup failed: " . $e->getMessage() . "\n");
}
