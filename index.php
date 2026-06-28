<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

$config = require __DIR__ . '/config/config.php';
date_default_timezone_set($config['timezone']);

$pageTitle = $config['app_name'] . ' – ' . $config['app_tagline'];
$disclaimerSw = $config['disclaimer']['sw'];
$disclaimerEn = $config['disclaimer']['en'];
?>
<!DOCTYPE html>
<html lang="sw" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="AfyaLink – Pata hospitali na huduma zinazopatikana sasa hivi Tanzania. Si badala ya daktari.">
    <meta name="theme-color" content="#1a6b5c">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/svg+xml" href="assets/icons/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;1,9..40,400&family=Fraunces:opsz,wght@9..144,600;9..144,700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
    <link rel="stylesheet" href="assets/css/app.css?v=8">
    <script>
        (function () {
            var t = localStorage.getItem('afyalink_theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
</head>
<body>
    <div id="offline-banner" class="offline-banner hidden" role="alert">
        <i class="fas fa-wifi-slash" aria-hidden="true"></i>
        <span data-i18n="offline_mode">Hali ya nje ya mtandao – data iliyohifadhiwa inaonyeshwa</span>
    </div>

    <header class="app-header">
        <div class="header-inner">
            <div class="brand">
                <div class="brand-icon" aria-hidden="true">
                    <i class="fas fa-heart-pulse"></i>
                </div>
                <div>
                    <h1 class="brand-name">AfyaLink</h1>
                    <p class="brand-tagline" data-i18n="tagline">Pata hospitali na huduma zinazopatikana sasa hivi</p>
                </div>
            </div>

            <nav class="desktop-nav" aria-label="Main navigation">
                <span class="nav-underline" id="nav-indicator" aria-hidden="true"></span>
                <a href="#stats-section" class="nav-link" data-nav="stats-section">
                    <span class="nav-link-icon"><i class="fas fa-chart-column" aria-hidden="true"></i></span>
                    <span class="nav-link-text" data-i18n="nav_stats">Takwimu</span>
                </a>
                <a href="#results-section" class="nav-link" data-nav="results-section">
                    <span class="nav-link-icon"><i class="fas fa-hospital" aria-hidden="true"></i></span>
                    <span class="nav-link-text" data-i18n="nav_hospitals">Hospitali</span>
                </a>
                <a href="login.php" class="nav-link nav-link--login">
                    <span class="nav-link-icon"><i class="fas fa-right-to-bracket" aria-hidden="true"></i></span>
                    <span class="nav-link-text" data-i18n="nav_login">Ingia</span>
                </a>
            </nav>

            <div class="header-actions">
                <button id="theme-toggle" class="btn-icon" aria-label="Toggle theme" title="Badilisha mandhari">
                    <i class="fas fa-moon theme-icon-dark" aria-hidden="true"></i>
                    <i class="fas fa-sun theme-icon-light" aria-hidden="true"></i>
                </button>
                <button id="lang-toggle" class="btn-icon btn-lang" aria-label="Switch language" title="Badilisha lugha">
                    <i class="fas fa-language" aria-hidden="true"></i>
                    <span>EN</span>
                </button>
                <button id="sync-btn" class="btn-icon" aria-label="Sync data" title="Sasisha data">
                    <i class="fas fa-arrows-rotate" aria-hidden="true"></i>
                </button>
                <button id="menu-toggle" class="btn-icon btn-hamburger" aria-label="Mipangilio" aria-expanded="false" aria-controls="mobile-drawer" title="Mipangilio">
                    <i class="fas fa-gear" aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile drawer — settings only (nav is in tab bar) -->
    <div id="drawer-overlay" class="drawer-overlay" aria-hidden="true"></div>
    <aside id="mobile-drawer" class="mobile-drawer" aria-label="Mipangilio" aria-hidden="true">
        <div class="drawer-header">
            <h2 class="drawer-title" data-i18n="nav_settings">Mipangilio</h2>
            <button id="drawer-close" class="btn-close" aria-label="Funga">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <nav class="drawer-nav">
            <button type="button" class="drawer-link drawer-action" id="drawer-theme">
                <i class="fas fa-moon theme-icon-dark" aria-hidden="true"></i>
                <i class="fas fa-sun theme-icon-light" aria-hidden="true"></i>
                <span data-i18n="nav_theme">Mandhari</span>
            </button>
            <button type="button" class="drawer-link drawer-action" id="drawer-lang">
                <i class="fas fa-language" aria-hidden="true"></i>
                <span data-i18n="nav_language">Lugha</span>
            </button>
            <button type="button" class="drawer-link drawer-action" id="drawer-sync">
                <i class="fas fa-arrows-rotate" aria-hidden="true"></i>
                <span data-i18n="nav_sync">Sasisha data</span>
            </button>
        </nav>
        <div class="drawer-footer">
            <p data-i18n="footer_note">Dar es Salaam · Pwani · Morogoro</p>
        </div>
    </aside>

    <a href="tel:114" class="emergency-pill" title="Dharura – piga 114">
        <i class="fas fa-phone-volume" aria-hidden="true"></i>
        <span>114</span>
    </a>

    <section class="disclaimer-strip reveal-left" role="note">
        <i class="fas fa-circle-info disclaimer-strip-icon" aria-hidden="true"></i>
        <p id="disclaimer-text"><?= htmlspecialchars($disclaimerSw) ?></p>
    </section>

    <main class="app-main">
        <section class="finder-hero reveal-up" id="search-section">
            <div class="finder-hero-bg" aria-hidden="true">
                <span class="finder-blob finder-blob-a"></span>
                <span class="finder-blob finder-blob-b"></span>
            </div>
            <div class="finder-hero-inner">
                <p class="finder-eyebrow" data-i18n="disclaimer_tag">Taarifa muhimu</p>
                <h2 class="finder-title" data-i18n="finder_title">Tafuta huduma karibu nawe</h2>
                <div class="search-box">
                    <i class="fas fa-magnifying-glass search-icon" aria-hidden="true"></i>
                    <input type="search" id="search-input" placeholder="Tafuta hospitali au huduma..." data-i18n-placeholder="search_placeholder" autocomplete="off" aria-autocomplete="list" aria-controls="search-suggestions" aria-expanded="false">
                    <div id="search-suggestions" class="search-suggestions hidden" role="listbox" aria-label="Mapendekezo ya utafutaji"></div>
                </div>
                <div class="filters">
                    <label class="filter-group">
                        <i class="fas fa-map" aria-hidden="true"></i>
                        <select id="region-filter" aria-label="Chagua mkoa">
                            <option value="" data-i18n="all_regions">Mikoa yote</option>
                        </select>
                    </label>
                    <label class="filter-group">
                        <i class="fas fa-location-dot" aria-hidden="true"></i>
                        <select id="district-filter" aria-label="Chagua wilaya" disabled>
                            <option value="" data-i18n="all_districts">Wilaya zote</option>
                        </select>
                    </label>
                    <label class="filter-group">
                        <i class="fas fa-stethoscope" aria-hidden="true"></i>
                        <select id="service-filter" aria-label="Chagua huduma">
                            <option value="" data-i18n="all_services">Huduma zote</option>
                        </select>
                    </label>
                </div>
                <div class="filter-secondary">
                    <label class="filter-group">
                        <i class="fas fa-landmark" aria-hidden="true"></i>
                        <select id="ownership-filter" aria-label="Chagua umiliki">
                            <option value="" data-i18n="all_ownership">Umiliki wote</option>
                            <option value="public" data-i18n="ownership_public">Umma</option>
                            <option value="private" data-i18n="ownership_private">Binafsi</option>
                        </select>
                    </label>
                    <label class="filter-toggle">
                        <input type="checkbox" id="open-now-filter" value="1">
                        <span class="filter-toggle-pill">
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            <span data-i18n="filter_open_now">Wazi sasa</span>
                        </span>
                    </label>
                </div>
            </div>
        </section>

        <section class="hero-stats stats-ribbon reveal-left" id="stats-section" aria-label="Takwimu">
            <div class="stat-card highlight reveal-up">
                <div class="stat-icon"><i class="fas fa-door-open" aria-hidden="true"></i></div>
                <span class="stat-value" id="stat-open" data-count="0">0</span>
                <span class="stat-label" data-i18n="stat_open">Huduma wazi sasa</span>
            </div>
            <div class="stat-card reveal-left">
                <div class="stat-icon"><i class="fas fa-hospital" aria-hidden="true"></i></div>
                <span class="stat-value" id="stat-hospitals" data-count="0">0</span>
                <span class="stat-label" data-i18n="stat_hospitals">Hospitali</span>
            </div>
            <div class="stat-card reveal-right">
                <div class="stat-icon"><i class="fas fa-map-location-dot" aria-hidden="true"></i></div>
                <span class="stat-value" id="stat-regions" data-count="3">0</span>
                <span class="stat-label" data-i18n="stat_regions">Mikoa</span>
            </div>
        </section>

        <section class="results-section reveal-left" id="results-section">
            <div class="results-header">
                <h2><i class="fas fa-hospital-user" aria-hidden="true"></i> <span data-i18n="results_title">Hospitali</span></h2>
                <span class="results-count" id="results-count">0</span>
            </div>
            <div id="hospitals-list" class="hospitals-grid" role="list"></div>
            <div id="empty-state" class="empty-state hidden">
                <i class="fas fa-building-circle-xmark" aria-hidden="true"></i>
                <p data-i18n="no_results">Hakuna hospitali iliyopatikana. Jaribu vichujio vingine.</p>
            </div>
            <div id="loading-state" class="loading-state">
                <div class="spinner"></div>
                <p data-i18n="loading">Inapakia...</p>
            </div>
        </section>
    </main>

    <!-- Hospital Detail Modal -->
    <div id="modal-overlay" class="modal-overlay" aria-hidden="true"></div>
    <dialog id="hospital-modal" class="modal" aria-labelledby="modal-name">
        <div class="modal-content">
            <div class="modal-hero" id="modal-hero">
                <div class="modal-hero-bg" aria-hidden="true"></div>
                <button class="btn-close modal-close-btn" id="modal-close" aria-label="Funga">
                    <i class="fas fa-xmark" aria-hidden="true"></i>
                </button>
                <div class="modal-hero-content">
                    <span class="facility-badge" id="modal-type"></span>
                    <h2 id="modal-name"></h2>
                    <p id="modal-location" class="modal-location"></p>
                </div>
                <div class="modal-quick-stats" id="modal-quick-stats"></div>
            </div>
            <div class="modal-body">
                <div class="modal-info-grid" id="modal-info-grid"></div>
                <div class="modal-contacts" id="modal-contacts"></div>
                <h3 class="modal-section-title">
                    <i class="fas fa-list-check" aria-hidden="true"></i>
                    <span data-i18n="services_now">Huduma zinazopatikana sasa</span>
                </h3>
                <div id="modal-services" class="services-grid"></div>
            </div>
        </div>
    </dialog>

    <!-- AI Chat FAB -->
    <button id="ai-fab" class="ai-fab" aria-label="Msaidizi wa AI">
        <span class="ai-fab-pulse" aria-hidden="true"></span>
        <span class="ai-fab-icon"><i class="fas fa-robot" aria-hidden="true"></i></span>
        <span class="ai-fab-label">
            <span id="ai-fab-text"></span><span class="fab-cursor" aria-hidden="true"></span>
        </span>
    </button>

    <!-- AI Chat Panel -->
    <aside id="ai-panel" class="ai-panel hidden" aria-label="AI Assistant">
        <header class="ai-header">
            <div>
                <h3><i class="fas fa-robot" aria-hidden="true"></i> AfyaLink AI</h3>
                <p data-i18n="ai_subtitle">Msaidizi wa kupata hospitali – si daktari</p>
            </div>
            <button id="ai-close" class="btn-close" aria-label="Funga">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </header>
        <div class="ai-disclaimer">
            <small id="ai-disclaimer-text"><?= htmlspecialchars($disclaimerSw) ?></small>
        </div>
        <div id="ai-messages" class="ai-messages"></div>
        <form id="ai-form" class="ai-form">
            <input type="text" id="ai-input" placeholder="Uliza kuhusu hospitali na huduma..." data-i18n-placeholder="ai_placeholder" autocomplete="off">
            <button type="submit" aria-label="Tuma">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </form>
    </aside>

    <nav class="mobile-tab-bar" aria-label="Mobile navigation">
        <a href="#stats-section" class="tab-link" data-nav="stats-section">
            <i class="fas fa-chart-simple" aria-hidden="true"></i>
            <span data-i18n="nav_stats">Takwimu</span>
        </a>
        <a href="#results-section" class="tab-link" data-nav="results-section">
            <i class="fas fa-hospital" aria-hidden="true"></i>
            <span data-i18n="nav_hospitals">Hospitali</span>
        </a>
        <a href="login.php" class="tab-link tab-link--login">
            <i class="fas fa-right-to-bracket" aria-hidden="true"></i>
            <span data-i18n="nav_login">Ingia</span>
        </a>
    </nav>

    <footer class="app-footer reveal-up">
        <p><i class="fas fa-heart-pulse" aria-hidden="true"></i> AfyaLink MVP &copy; 2026 – MUHAS Local AI Buildathon</p>
        <p class="footer-note" data-i18n="footer_note">Dar es Salaam · Pwani · Morogoro · Iringa</p>
        <p class="footer-note">
            <i class="fas fa-database" aria-hidden="true"></i>
            Data: <a href="https://hfrportal.moh.go.tz/web/index.php" target="_blank" rel="noopener">MOH HFR</a>
            · <a href="login.php"><i class="fas fa-right-to-bracket"></i> Ingia / Admin</a>
        </p>
    </footer>

    <script src="assets/js/offline.js" defer></script>
    <script src="assets/js/app.js?v=7" defer></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('sw.js').catch(console.warn);
            });
        }
    </script>
</body>
</html>
