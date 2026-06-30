<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once dirname(__DIR__) . '/config/vendor_assets.php';

if (!isAdminLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$config = require dirname(__DIR__) . '/config/config.php';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – AfyaLink</title>
    <link rel="icon" type="image/svg+xml" href="../assets/icons/icon.svg">
    <?php renderVendorStyles('../', 'admin'); ?>
    <link rel="stylesheet" href="../assets/css/app.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
    <header class="app-header">
        <div class="header-inner">
            <div class="brand">
                <div class="brand-icon" aria-hidden="true">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div>
                    <h1 class="brand-name">AfyaLink Admin</h1>
                    <p class="brand-tagline">Sasisha huduma za hospitali yako</p>
                </div>
            </div>
            <div class="header-actions">
                <a href="../index.php" class="btn-link"><i class="fas fa-home"></i> App</a>
                <a href="../login.php" class="btn-link" id="logout-link"><i class="fas fa-right-from-bracket"></i> Toka</a>
            </div>
        </div>
    </header>

    <main class="admin-main">
        <section id="dashboard-section">
            <div class="admin-card">
                <div class="admin-dashboard-header">
                    <div>
                        <h2 id="dash-hospital-name"><i class="fas fa-spinner fa-spin"></i> Inapakia...</h2>
                        <p id="dash-facility-code" class="admin-meta"></p>
                    </div>
                </div>
                <p class="admin-disclaimer">
                    <i class="fas fa-circle-info"></i>
                    Unasasisha tu <strong>upatikanaji wa huduma</strong> – si utibu wa wagonjwa.
                </p>
            </div>
            <div id="services-admin-list" class="admin-services"></div>
        </section>
    </main>

    <script src="admin.js"></script>
    <script>
        document.getElementById('logout-link')?.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('../api/admin/logout.php', { method: 'POST', credentials: 'same-origin' });
            window.location.href = '../login.php';
        });
    </script>
</body>
</html>
