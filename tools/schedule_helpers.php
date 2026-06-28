<?php
/**
 * Shared service schedule seeding — realistic TZ hospital hours.
 * Hospitals operate most days; only a few edge services have narrow windows.
 * Used by import_hfr.php and tools/reseed_schedules.php
 */
declare(strict_types=1);

function insertSchedule(PDO $pdo, int $hsId, int $day, string $open, string $close, bool $closed): void
{
    $stmt = $pdo->prepare("
        INSERT INTO service_schedules (hospital_service_id, day_of_week, open_time, close_time, is_closed)
        VALUES (:hs, :day, :open, :close, :closed)
        ON CONFLICT (hospital_service_id, day_of_week) DO UPDATE SET
            open_time = EXCLUDED.open_time,
            close_time = EXCLUDED.close_time,
            is_closed = EXCLUDED.is_closed
    ");
    $stmt->bindValue(':hs', $hsId, PDO::PARAM_INT);
    $stmt->bindValue(':day', $day, PDO::PARAM_INT);
    $stmt->bindValue(':open', $open);
    $stmt->bindValue(':close', $close);
    $stmt->bindValue(':closed', $closed, PDO::PARAM_BOOL);
    $stmt->execute();
}

/** Mon–Sun open slots (day 0 = Sunday). */
function seedAllWeek(PDO $pdo, int $hsId, string $weekdayOpen, string $weekdayClose, string $sundayOpen, string $sundayClose): void
{
    for ($d = 1; $d <= 6; $d++) {
        insertSchedule($pdo, $hsId, $d, $weekdayOpen, $weekdayClose, false);
    }
    insertSchedule($pdo, $hsId, 0, $sundayOpen, $sundayClose, false);
}

function seed24x7(PDO $pdo, int $hsId): void
{
    for ($d = 0; $d <= 6; $d++) {
        insertSchedule($pdo, $hsId, $d, '00:00', '23:59', false);
    }
}

function seedSchedule(PDO $pdo, int $hospitalServiceId, string $serviceCode, string $facilityType): void
{
    $pdo->prepare('DELETE FROM service_schedules WHERE hospital_service_id = :id')
        ->execute(['id' => $hospitalServiceId]);

    $isMajor = in_array($facilityType, ['national', 'regional', 'teaching'], true);
    $isDistrict = $facilityType === 'district';

    // Emergency — always reachable at hospitals
    if ($serviceCode === 'emergency') {
        if ($isMajor || $isDistrict) {
            seed24x7($pdo, $hospitalServiceId);
            return;
        }
        seedAllWeek($pdo, $hospitalServiceId, '07:00', '22:00', '08:00', '20:00');
        return;
    }

    // Pharmacy — extended hours, 7 days
    if ($serviceCode === 'pharmacy') {
        if ($isMajor) {
            seedAllWeek($pdo, $hospitalServiceId, '07:00', '21:00', '08:00', '20:00');
            return;
        }
        if ($isDistrict) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '20:00', '09:00', '20:00');
            return;
        }
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '18:00', '09:00', '20:00');
        return;
    }

    // Core clinical — open 7 days at all facility types
    $coreServices = ['opd', 'maternity', 'laboratory', 'pediatrics', 'immunization', 'malaria', 'hiv', 'tb'];
    if (in_array($serviceCode, $coreServices, true)) {
        if ($isMajor) {
            seedAllWeek($pdo, $hospitalServiceId, '07:30', '20:00', '08:30', '20:00');
            return;
        }
        if ($isDistrict) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '19:00', '09:00', '20:00');
            return;
        }
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '18:00', '09:00', '20:00');
        return;
    }

    // Diagnostics
    if (in_array($serviceCode, ['xray'], true)) {
        if ($isMajor) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '19:00', '09:00', '20:00');
            return;
        }
        if ($isDistrict) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '18:00', '09:00', '20:00');
            return;
        }
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '16:00', '09:00', '20:00');
        return;
    }

    // Mental health
    if ($serviceCode === 'mental') {
        if ($isMajor || $isDistrict) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '18:00', '09:00', '20:00');
            return;
        }
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '16:00', '09:00', '20:00');
        return;
    }

    // Specialist
    if (in_array($serviceCode, ['surgery', 'cardiology', 'dialysis', 'dental', 'optical', 'physiotherapy'], true)) {
        if ($isMajor) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '19:00', '09:00', '20:00');
            return;
        }
        if ($isDistrict) {
            seedAllWeek($pdo, $hospitalServiceId, '08:00', '18:00', '09:00', '20:00');
            return;
        }
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '17:00', '09:00', '20:00');
        return;
    }

    // Default — standard outpatient hours, 7 days
    if ($isMajor) {
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '19:00', '09:00', '20:00');
        return;
    }

    if ($isDistrict) {
        seedAllWeek($pdo, $hospitalServiceId, '08:00', '18:00', '09:00', '20:00');
        return;
    }

        seedAllWeek($pdo, $hospitalServiceId, '08:00', '17:00', '09:00', '20:00');
}
