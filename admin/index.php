<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: ../login.php');
}
exit;
