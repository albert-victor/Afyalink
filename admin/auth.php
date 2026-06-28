<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function adminPin(): string
{
    return getenv('ADMIN_PIN') ?: 'afyalink2026';
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_hospital_id']);
}

function requireAdminApi(): array
{
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    return [
        'hospital_id' => (int) $_SESSION['admin_hospital_id'],
        'facility_code' => (string) $_SESSION['admin_facility_code'],
        'hospital_name' => (string) ($_SESSION['admin_hospital_name'] ?? ''),
    ];
}

function adminLogin(PDO $pdo, string $facilityCode, string $pin): bool
{
    if ($pin !== adminPin()) {
        return false;
    }

    $stmt = $pdo->prepare('
        SELECT id, name, facility_code FROM hospitals
        WHERE facility_code = :code AND is_active = true
    ');
    $stmt->execute(['code' => trim($facilityCode)]);
    $hospital = $stmt->fetch();

    if (!$hospital) {
        return false;
    }

    $_SESSION['admin_hospital_id'] = (int) $hospital['id'];
    $_SESSION['admin_facility_code'] = $hospital['facility_code'];
    $_SESSION['admin_hospital_name'] = $hospital['name'];

    return true;
}

function adminLogout(): void
{
    unset($_SESSION['admin_hospital_id'], $_SESSION['admin_facility_code'], $_SESSION['admin_hospital_name']);
}
