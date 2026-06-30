<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/config/vendor_assets.php';
require_once __DIR__ . '/admin/auth.php';

if (isAdminLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit;
}

$photoCandidates = [
    'afya-pic.jpg',
    'Mloganzila1.jpg',
    'main image.jpeg',
    'bg-2.jpg',
    'bg-3.jpg',
    'bg-4.jpg',
    'image1024x768.jpg',
    '5-16-1024x683.jpg',
];

$bgImages = [];
foreach ($photoCandidates as $name) {
    if (is_file(__DIR__ . '/assets/images/' . $name)) {
        $bgImages[] = 'assets/images/' . implode('/', array_map('rawurlencode', explode('/', $name)));
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1a6b5c">
    <title data-i18n="login_title">Ingia – AfyaLink</title>
    <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg">
    <?php renderVendorStyles('', 'login'); ?>
    <link rel="stylesheet" href="assets/css/login.css?v=6">
</head>
<body class="login-page">
    <div class="login-bg" aria-hidden="true">
        <?php foreach ($bgImages as $i => $src): ?>
        <div class="login-bg-slide<?= $i === 0 ? ' active' : '' ?>">
            <img src="<?= htmlspecialchars($src) ?>" alt="" decoding="async"<?= $i === 0 ? ' fetchpriority="high"' : '' ?>>
            <div class="login-slide-overlay"></div>
        </div>
        <?php endforeach; ?>
        <div class="login-bg-mesh"></div>
    </div>

    <main class="login-main">
        <div class="login-card">
            <div class="login-card-glow" aria-hidden="true"></div>
            <div class="login-card-border" aria-hidden="true"></div>

            <div class="login-card-top">
                <a href="index.php" class="login-back" data-i18n-title="login_back_title">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span data-i18n="login_back">Rudi</span>
                </a>
                <button type="button" id="lang-toggle" class="login-lang" aria-label="Badilisha lugha">
                    <i class="fas fa-language" aria-hidden="true"></i>
                    <span>EN</span>
                </button>
            </div>

            <div class="login-card-body">
                <div class="login-brand">
                    <div class="login-brand-icon" aria-hidden="true">
                        <i class="fas fa-heart-pulse"></i>
                    </div>
                    <h1>AfyaLink</h1>
                    <p data-i18n="login_subtitle">Ingia kwa admin wa kituo</p>
                </div>

                <div class="login-panel">
                    <form id="login-form" class="login-form" novalidate>
                        <div class="login-fields-row">
                            <div class="login-field">
                                <label for="facility-code" data-i18n="login_facility_label">Nambari ya Kituo (HFR)</label>
                                <div class="login-input-wrap">
                                    <i class="fas fa-hospital-user" aria-hidden="true"></i>
                                    <input type="text" id="facility-code" name="facility_code"
                                           data-i18n-placeholder="login_facility_ph"
                                           placeholder="mf. 105905-4" required autocomplete="off">
                                </div>
                            </div>
                            <div class="login-field">
                                <label for="admin-pin" data-i18n="login_pin_label">PIN ya Kituo</label>
                                <div class="login-input-wrap">
                                    <i class="fas fa-lock" aria-hidden="true"></i>
                                    <input type="password" id="admin-pin" name="pin"
                                           data-i18n-placeholder="login_pin_ph"
                                           placeholder="Weka PIN" required autocomplete="current-password">
                                    <button type="button" class="login-pin-toggle" id="pin-toggle" aria-label="Onyesha PIN">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="login-btn" id="login-submit">
                            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
                            <span data-i18n="login_submit">Ingia</span>
                        </button>
                    </form>

                    <div id="login-error" class="login-error hidden" role="alert">
                        <i class="fas fa-circle-exclamation" aria-hidden="true"></i>
                        <span id="login-error-text"></span>
                    </div>

                    <a href="index.php" class="login-skip">
                        <span data-i18n="login_skip">Endelea bila kuingia</span>
                        <i class="fas fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/login.js?v=3"></script>
</body>
</html>
