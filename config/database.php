<?php
declare(strict_types=1);

function getSqlitePath(): string
{
    $path = getenv('SQLITE_PATH') ?: dirname(__DIR__) . '/database/afyalink_fallback.sqlite';
    if (!str_starts_with($path, DIRECTORY_SEPARATOR) && !preg_match('#^[A-Za-z]:\\\\#', $path)) {
        $path = dirname(__DIR__) . '/' . ltrim($path, '/');
    }
    return $path;
}

function getDbDriverPreference(): string
{
    return strtolower(getenv('DB_DRIVER') ?: 'auto');
}

function connectPostgres(): ?PDO
{
    $host     = getenv('DB_HOST') ?: 'localhost';
    $port     = getenv('DB_PORT') ?: '5432';
    $dbname   = getenv('DB_NAME') ?: 'afyalink';
    $user     = getenv('DB_USER') ?: 'postgres';
    $password = getenv('DB_PASS') ?: 'postgres';

    try {
        $pdo = new PDO("pgsql:host={$host};port={$port};dbname={$dbname}", $user, $password, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->query('SELECT 1');
        return $pdo;
    } catch (Throwable) {
        return null;
    }
}

function connectSqlite(bool $createIfMissing = true): ?PDO
{
    if (!extension_loaded('pdo_sqlite')) {
        return null;
    }

    $path = getSqlitePath();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $isNew = !file_exists($path);
    if ($isNew && !$createIfMissing) {
        return null;
    }

    try {
        $pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');

        if ($isNew) {
            initSqliteSchema($pdo);
        }

        return $pdo;
    } catch (Throwable) {
        return null;
    }
}

function initSqliteSchema(PDO $pdo): void
{
    $schema = file_get_contents(dirname(__DIR__) . '/database/schema.sqlite.sql');
    $pdo->exec($schema);
    $seed = file_get_contents(dirname(__DIR__) . '/database/seed.sql');
    // SQLite: strip PostgreSQL-only syntax
    $seed = preg_replace('/ON CONFLICT \(name\) DO NOTHING/', 'OR IGNORE', $seed);
    $seed = preg_replace('/ON CONFLICT \(code\) DO NOTHING/', 'OR IGNORE', $seed);
    $pdo->exec($seed);
}

function getDbConnection(): PDO
{
    static $pdo = null;
    static $driver = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pref = getDbDriverPreference();

    if ($pref === 'sqlite') {
        $pdo = connectSqlite(true);
        if (!$pdo) {
            throw new RuntimeException('SQLite connection failed');
        }
        $driver = 'sqlite';
        return $pdo;
    }

    if ($pref === 'pgsql') {
        $pdo = connectPostgres();
        if (!$pdo) {
            throw new RuntimeException('PostgreSQL connection failed');
        }
        $driver = 'pgsql';
        return $pdo;
    }

    // auto: PostgreSQL first, SQLite fallback
    $pdo = connectPostgres();
    if ($pdo) {
        $driver = 'pgsql';
        return $pdo;
    }

    $pdo = connectSqlite(true);
    if ($pdo) {
        $driver = 'sqlite';
        error_log('AfyaLink: PostgreSQL unavailable – using SQLite fallback');
        return $pdo;
    }

    throw new RuntimeException('No database available (PostgreSQL or SQLite)');
}

function getActiveDbDriver(): string
{
    static $driver = null;
    if ($driver !== null) {
        return $driver;
    }
    $pdo = getDbConnection();
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    return $driver;
}

function isPostgres(): bool
{
    static $cached = null;
    if ($cached !== null) return $cached;

    try {
        $pdo = getDbConnection();
        $cached = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';
    } catch (Throwable) {
        $cached = false;
    }
    return $cached;
}

/** Case-insensitive LIKE for cross-DB search */
function sqlLike(string $column, string $param = ':search'): string
{
    if (isPostgres()) {
        return "{$column} ILIKE {$param}";
    }
    return "LOWER({$column}) LIKE LOWER({$param})";
}

function boolParam(bool $value): mixed
{
    return isPostgres() ? $value : ($value ? 1 : 0);
}
