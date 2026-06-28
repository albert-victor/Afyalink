/**
 * AfyaLink MVP – Main Application
 */
(function () {
    'use strict';

    const API = 'api';
    let lang = localStorage.getItem('afyalink_lang') || 'sw';
    let regions = [];
    let services = [];
    let hospitals = [];
    let searchTimeout = null;

    const i18n = {
        sw: {
            tagline: 'Pata hospitali na huduma zinazopatikana sasa hivi',
            offline_mode: 'Hali ya nje ya mtandao – data iliyohifadhiwa inaonyeshwa',
            nav_stats: 'Takwimu',
            nav_search: 'Tafuta',
            nav_hospitals: 'Hospitali',
            nav_login: 'Ingia',
            nav_settings: 'Mipangilio',
            finder_title: 'Tafuta huduma karibu nawe',
            nav_theme: 'Mandhari',
            nav_language: 'Lugha',
            nav_sync: 'Sasisha data',
            stat_hospitals: 'Hospitali',
            stat_open: 'Huduma wazi sasa',
            stat_regions: 'Mikoa',
            search_placeholder: 'Tafuta hospitali au huduma...',
            all_regions: 'Mikoa yote',
            all_districts: 'Wilaya zote',
            all_services: 'Huduma zote',
            all_ownership: 'Umiliki wote',
            ownership_public: 'Umma',
            ownership_private: 'Binafsi',
            filter_open_now: 'Wazi sasa',
            results_title: 'Hospitali',
            no_results: 'Hakuna hospitali iliyopatikana. Jaribu vichujio vingine.',
            loading: 'Inapakia...',
            services_now: 'Huduma zinazopatikana sasa',
            ai_assistant: 'Msaidizi',
            ai_subtitle: 'Msaidizi wa kupata hospitali – si daktari',
            ai_placeholder: 'Uliza kuhusu hospitali na huduma...',
            footer_note: 'Dar es Salaam · Pwani · Morogoro',
            open: 'Wazi sasa',
            limited: 'Imepungua',
            closed: 'Imefungwa',
            unavailable: 'Haipatikani',
            services_open: 'huduma wazi',
            open_24_7: 'Saa 24/7',
            call: 'Piga',
            emergency: 'Dharura',
            ai_welcome: 'Habari! Mimi ni msaidizi wa AfyaLink. Naweza kukusaidia kupata hospitali na huduma zinazopatikana sasa hivi. Si daktari – kwa dharura piga 114.',
            ai_typing: 'Anaandika',
            ai_thinking: 'Inafikiria...',
            search_suggestions: 'Mapendekezo',
            search_no_suggestions: 'Hakuna hospitali iliyopatikana',
            search_view_all: 'Ona matokeo yote',
            search_instant: 'Inatafuta...',
            synced: 'Data imesasishwa',
            offline_cached: 'Data ya nje ya mtandao inaonyeshwa',
            disclaimer_tag: 'Taarifa muhimu',
            fab_ask: 'Niulize...',
            fab_talk: 'Zungumza na AfyaLink',
            fab_find: 'Pata hospitali sasa',
            fab_help: 'Msaidizi wa AI',
            wait_time: 'Muda wa kusubiri',
            label_ownership: 'Umiliki',
            label_council: 'Halmashauri',
            label_hfr: 'Nambari HFR',
            label_status: 'Hali',
            min_wait: 'dak',
        },
        en: {
            tagline: 'Find hospitals and services available right now',
            offline_mode: 'Offline mode – showing cached data',
            nav_stats: 'Statistics',
            nav_search: 'Search',
            nav_hospitals: 'Hospitals',
            nav_login: 'Login',
            nav_settings: 'Settings',
            finder_title: 'Find care near you',
            nav_theme: 'Theme',
            nav_language: 'Language',
            nav_sync: 'Sync data',
            stat_hospitals: 'Hospitals',
            stat_open: 'Services open now',
            stat_regions: 'Regions',
            search_placeholder: 'Search hospitals or services...',
            all_regions: 'All regions',
            all_districts: 'All districts',
            all_services: 'All services',
            all_ownership: 'All ownership',
            ownership_public: 'Public',
            ownership_private: 'Private',
            filter_open_now: 'Open now',
            results_title: 'Hospitals',
            no_results: 'No hospitals found. Try different filters.',
            loading: 'Loading...',
            services_now: 'Services available now',
            ai_assistant: 'Assistant',
            ai_subtitle: 'Hospital finder assistant – not a doctor',
            ai_placeholder: 'Ask about hospitals and services...',
            footer_note: 'Dar es Salaam · Pwani · Morogoro',
            open: 'Open now',
            limited: 'Limited',
            closed: 'Closed',
            unavailable: 'Unavailable',
            services_open: 'services open',
            open_24_7: '24/7',
            call: 'Call',
            emergency: 'Emergency',
            ai_welcome: 'Hello! I\'m AfyaLink assistant. I can help you find hospitals and services available right now. I\'m not a doctor – for emergencies call 114.',
            ai_typing: 'Typing',
            ai_thinking: 'Thinking...',
            search_suggestions: 'Suggestions',
            search_no_suggestions: 'No hospitals found',
            search_view_all: 'View all results',
            search_instant: 'Searching...',
            synced: 'Data synced',
            offline_cached: 'Showing offline cached data',
            disclaimer_tag: 'Important notice',
            fab_ask: 'Ask me...',
            fab_talk: 'Talk to AfyaLink',
            fab_find: 'Find hospitals now',
            fab_help: 'AI Assistant',
            wait_time: 'Wait time',
            label_ownership: 'Ownership',
            label_council: 'Council',
            label_hfr: 'HFR Code',
            label_status: 'Status',
            min_wait: 'min',
        },
    };

    const SERVICE_ICONS = {
        emergency: 'fa-truck-medical',
        maternity: 'fa-baby',
        laboratory: 'fa-flask',
        xray: 'fa-x-ray',
        pharmacy: 'fa-pills',
        surgery: 'fa-user-doctor',
        pediatrics: 'fa-child',
        cardiology: 'fa-heart-pulse',
        dental: 'fa-tooth',
        mental: 'fa-brain',
        blood_bank: 'fa-droplet',
        hiv: 'fa-ribbon',
        physiotherapy: 'fa-person-walking',
        optical: 'fa-eye',
        dialysis: 'fa-droplet',
        opd: 'fa-stethoscope',
        ipd: 'fa-bed',
        immunization: 'fa-syringe',
        tb: 'fa-lungs',
        malaria: 'fa-virus',
    };

    const FAB_PROMPTS = {
        sw: ['fab_ask', 'fab_talk', 'fab_find', 'fab_help'],
        en: ['fab_ask', 'fab_talk', 'fab_find', 'fab_help'],
    };

    const $ = (sel) => document.querySelector(sel);
    const t = (key) => i18n[lang][key] || key;

    /* ── Theme ── */
    function getTheme() {
        return document.documentElement.getAttribute('data-theme') || 'light';
    }

    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem('afyalink_theme', theme);
        const meta = document.querySelector('meta[name="theme-color"]');
        if (meta) meta.content = theme === 'dark' ? '#080809' : '#ea580c';
    }

    function toggleTheme() {
        setTheme(getTheme() === 'dark' ? 'light' : 'dark');
    }

    function serviceIcon(code) {
        return SERVICE_ICONS[code] || 'fa-kit-medical';
    }

    function facilityTypeClass(type) {
        if (type === 'national') return 'type-national';
        if (type === 'regional') return 'type-regional';
        return 'type-district';
    }

    let fabTypewriterTimer = null;

    /* ── FAB typewriter ── */
    function initFabTypewriter() {
        const textEl = $('#ai-fab-text');
        if (!textEl) return;

        if (fabTypewriterTimer) {
            clearTimeout(fabTypewriterTimer);
            fabTypewriterTimer = null;
        }

        const keys = FAB_PROMPTS[lang] || FAB_PROMPTS.en;
        let msgIdx = 0;
        let charIdx = 0;
        let deleting = false;

        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) {
            textEl.textContent = t(keys[0]);
            return;
        }

        function tick() {
            const panel = $('#ai-panel');
            if (panel && !panel.classList.contains('hidden')) {
                fabTypewriterTimer = setTimeout(tick, 600);
                return;
            }

            const full = t(keys[msgIdx]);

            if (!deleting) {
                textEl.textContent = full.slice(0, charIdx + 1);
                charIdx++;
                if (charIdx >= full.length) {
                    fabTypewriterTimer = setTimeout(() => { deleting = true; tick(); }, 2400);
                    return;
                }
                fabTypewriterTimer = setTimeout(tick, 42 + Math.random() * 40);
            } else {
                charIdx--;
                textEl.textContent = full.slice(0, charIdx);
                if (charIdx <= 0) {
                    deleting = false;
                    msgIdx = (msgIdx + 1) % keys.length;
                    fabTypewriterTimer = setTimeout(tick, 500);
                    return;
                }
                fabTypewriterTimer = setTimeout(tick, 24);
            }
        }

        textEl.textContent = '';
        tick();
    }

    /* ── Dynamic navbar ── */
    function initDynamicNav() {
        const header = $('.app-header');
        const nav = $('.desktop-nav');
        const indicator = $('#nav-indicator');
        const tabBar = $('.mobile-tab-bar');

        const desktopLinks = nav ? nav.querySelectorAll('.nav-link[data-nav]') : [];
        const tabLinks = tabBar ? tabBar.querySelectorAll('.tab-link[data-nav]') : [];
        const allLinks = [...desktopLinks, ...tabLinks];

        const sections = ['stats-section', 'results-section']
            .map((id) => document.getElementById(id))
            .filter(Boolean);

        function moveIndicator(link) {
            if (!nav || !indicator || !link || !nav.contains(link)) return;
            indicator.style.width = `${link.offsetWidth}px`;
            indicator.style.transform = `translateX(${link.offsetLeft}px)`;
        }

        function setActive(id) {
            allLinks.forEach((l) => l.classList.toggle('active', l.dataset.nav === id));
            const active = nav ? nav.querySelector(`.nav-link[data-nav="${id}"]`) : null;
            moveIndicator(active);
        }

        window.addEventListener('scroll', () => {
            header.classList.toggle('header-scrolled', window.scrollY > 20);
        }, { passive: true });

        const observer = new IntersectionObserver(
            (entries) => {
                const visible = entries
                    .filter((e) => e.isIntersecting)
                    .sort((a, b) => b.intersectionRatio - a.intersectionRatio);
                if (visible.length) setActive(visible[0].target.id);
            },
            { rootMargin: '-42% 0px -42% 0px', threshold: [0, 0.15, 0.4] }
        );

        sections.forEach((sec) => observer.observe(sec));

        allLinks.forEach((link) => {
            link.addEventListener('click', () => {
                setActive(link.dataset.nav);
            });
        });

        window.addEventListener('resize', () => {
            const active = nav ? (nav.querySelector('.nav-link.active') || desktopLinks[0]) : null;
            moveIndicator(active);
        });

        if (desktopLinks.length) {
            desktopLinks[0].classList.add('active');
            requestAnimationFrame(() => moveIndicator(desktopLinks[0]));
        } else if (tabLinks.length) {
            tabLinks[0].classList.add('active');
        }
    }

    /* ── Modal ── */
    function openModalAnimated() {
        const modal = $('#hospital-modal');
        const overlay = $('#modal-overlay');
        overlay.classList.add('open');
        overlay.setAttribute('aria-hidden', 'false');
        modal.showModal();
        document.body.style.overflow = 'hidden';
    }

    function closeModalAnimated() {
        const modal = $('#hospital-modal');
        const overlay = $('#modal-overlay');
        overlay.classList.remove('open');
        overlay.setAttribute('aria-hidden', 'true');
        modal.classList.add('modal-closing');
        document.body.style.overflow = '';
        setTimeout(() => {
            modal.close();
            modal.classList.remove('modal-closing');
        }, 320);
    }

    function animateCounter(el, target, duration) {
        if (!el || isNaN(target)) return;
        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) {
            el.textContent = target;
            return;
        }

        const start = 0;
        const startTime = performance.now();

        function step(now) {
            const progress = Math.min((now - startTime) / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);
            const current = Math.round(start + (target - start) * eased);
            el.textContent = current;
            if (progress < 1) requestAnimationFrame(step);
        }

        requestAnimationFrame(step);
    }

    function animateStats(data) {
        if (!data) return;
        const map = {
            'stat-hospitals': data.hospitals,
            'stat-open': data.services_open_now,
            'stat-regions': data.regions,
        };
        Object.entries(map).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) {
                el.dataset.count = val;
                animateCounter(el, Number(val) || 0, 1200);
            }
        });
    }

    /* ── Scroll reveal ── */
    function initScrollReveal() {
        const els = document.querySelectorAll('.reveal-left, .reveal-right, .reveal-up');
        if (!els.length) return;

        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReduced) {
            els.forEach((el) => el.classList.add('revealed'));
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                        observer.unobserve(entry.target);
                    }
                });
            },
            { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
        );

        els.forEach((el) => observer.observe(el));
    }

    /* ── Mobile drawer ── */
    function openDrawer() {
        $('#mobile-drawer').classList.add('open');
        $('#drawer-overlay').classList.add('open');
        $('#menu-toggle').setAttribute('aria-expanded', 'true');
        $('#mobile-drawer').setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
        $('#mobile-drawer').classList.remove('open');
        $('#drawer-overlay').classList.remove('open');
        $('#menu-toggle').setAttribute('aria-expanded', 'false');
        $('#mobile-drawer').setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function initDrawer() {
        $('#menu-toggle').addEventListener('click', openDrawer);
        $('#drawer-close').addEventListener('click', closeDrawer);
        $('#drawer-overlay').addEventListener('click', closeDrawer);

        $('#drawer-theme').addEventListener('click', () => {
            toggleTheme();
            closeDrawer();
        });
        $('#drawer-lang').addEventListener('click', () => {
            switchLanguage();
            closeDrawer();
        });
        $('#drawer-sync').addEventListener('click', () => {
            syncData();
            closeDrawer();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDrawer();
        });
    }

    /* ── i18n ── */
    function applyI18n() {
        document.querySelectorAll('[data-i18n]').forEach((el) => {
            const key = el.dataset.i18n;
            if (!i18n[lang][key]) return;
            const textSpan = el.querySelector(':scope > span:not(.theme-icon-dark):not(.theme-icon-light)');
            if (textSpan) {
                textSpan.textContent = i18n[lang][key];
            } else if (el.childElementCount === 0 || el.querySelector('.theme-icon-dark, .theme-icon-light')) {
                const label = el.querySelector('span');
                if (label) label.textContent = i18n[lang][key];
                else el.textContent = i18n[lang][key];
            } else {
                el.textContent = i18n[lang][key];
            }
        });

        document.querySelectorAll('[data-i18n-placeholder]').forEach((el) => {
            const key = el.dataset.i18nPlaceholder;
            if (i18n[lang][key]) el.placeholder = i18n[lang][key];
        });

        $('#lang-toggle span').textContent = lang === 'sw' ? 'EN' : 'SW';
        document.documentElement.lang = lang;

        $('#disclaimer-text').textContent = lang === 'sw'
            ? 'AfyaLink SI badala ya daktari wala mtaalamu wa afya. Inaonyesha tu hospitali na huduma zinazopatikana kwa muda huu.'
            : 'AfyaLink is NOT a replacement for doctors or clinicians. It only shows which hospitals offer which services right now.';
        $('#ai-disclaimer-text').textContent = $('#disclaimer-text').textContent;

        window.dispatchEvent(new Event('resize'));
    }

    function switchLanguage() {
        lang = lang === 'sw' ? 'en' : 'sw';
        localStorage.setItem('afyalink_lang', lang);
        applyI18n();
        initFabTypewriter();
        loadRegions().then(loadServiceTypes).then(loadHospitals);
    }

    function statusLabel(status) {
        return t(status) || status;
    }

    function facilityLabel(type) {
        const labels = {
            national: lang === 'sw' ? 'Taifa' : 'National',
            regional: lang === 'sw' ? 'Mkoa' : 'Regional',
            district: lang === 'sw' ? 'Wilaya' : 'District',
            teaching: lang === 'sw' ? 'Mafunzo' : 'Teaching',
            private: lang === 'sw' ? 'Binafsi' : 'Private',
            health_center: lang === 'sw' ? 'Kituo cha Afya' : 'Health Center',
            hospital: lang === 'sw' ? 'Hospitali' : 'Hospital',
        };
        return labels[type] || type;
    }

    async function apiFetch(endpoint) {
        const url = `${API}/${endpoint}`;
        const result = await AfyaCache.fetchWithCache(url);
        updateOfflineBanner(result.fromCache);
        return result.data;
    }

    async function apiFetchLive(endpoint) {
        const url = `${API}/${endpoint}`;
        try {
            const res = await fetch(url);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const data = await res.json();
            AfyaCache.set(url, data).catch(() => {});
            updateOfflineBanner(false);
            return data;
        } catch (e) {
            const result = await AfyaCache.fetchWithCache(url);
            updateOfflineBanner(true);
            return result.data;
        }
    }

    function updateOfflineBanner(fromCache) {
        const banner = $('#offline-banner');
        if (!navigator.onLine || fromCache) {
            banner.classList.remove('hidden');
        } else {
            banner.classList.add('hidden');
        }
    }

    async function loadStats() {
        try {
            const res = await apiFetch('stats.php');
            if (res.success) {
                animateStats(res.data);
            }
        } catch (e) {
            console.warn('Stats unavailable offline');
        }
    }

    async function loadRegions() {
        const res = await apiFetch('regions.php');
        if (!res.success) return;
        regions = res.data;

        const select = $('#region-filter');
        select.innerHTML = `<option value="">${t('all_regions')}</option>`;
        regions.forEach((r) => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = lang === 'sw' ? r.name_sw : r.name;
            select.appendChild(opt);
        });
    }

    async function loadServiceTypes() {
        const res = await apiFetch('services.php');
        if (!res.success) return;
        services = res.data;

        const select = $('#service-filter');
        select.innerHTML = `<option value="">${t('all_services')}</option>`;
        services.forEach((s) => {
            const opt = document.createElement('option');
            opt.value = s.code;
            opt.textContent = lang === 'sw' ? s.name_sw : s.name;
            select.appendChild(opt);
        });
    }

    let suggestTimeout = null;
    let suggestAbort = null;
    let activeSuggestIndex = -1;

    function getSearchParams() {
        const params = new URLSearchParams();
        const regionId = $('#region-filter').value;
        const districtId = $('#district-filter').value;
        const service = $('#service-filter').value;
        const ownership = $('#ownership-filter').value;
        const openNow = $('#open-now-filter').checked;
        const q = $('#search-input').value.trim();

        if (regionId) params.set('region_id', regionId);
        if (districtId) params.set('district_id', districtId);
        if (service) params.set('service', service);
        if (ownership) params.set('ownership', ownership);
        if (openNow) params.set('open_now', '1');
        if (q) params.set('q', q);
        return params;
    }

    function hideSearchSuggestions() {
        const box = $('#search-suggestions');
        if (!box) return;
        box.classList.add('hidden');
        box.innerHTML = '';
        $('#search-input')?.setAttribute('aria-expanded', 'false');
        activeSuggestIndex = -1;
    }

    function renderSuggestionItem(h, index) {
        const name = lang === 'sw' && h.name_sw ? h.name_sw : h.name;
        const district = lang === 'sw' && h.district_sw ? h.district_sw : h.district;
        const region = lang === 'sw' && h.region_sw ? h.region_sw : h.region;
        const openCount = h.open_services_count || 0;
        const totalCount = h.total_services_count || 0;
        const chips = (h.open_services || []).slice(0, 2).map((s) => {
            const sname = lang === 'sw' && s.name_sw ? s.name_sw : s.name;
            return `<span class="suggest-chip">${escapeHtml(sname)}</span>`;
        }).join('');

        return `
            <button type="button" class="search-suggest-item" role="option" data-id="${h.id}" data-index="${index}" aria-selected="false">
                <span class="suggest-main">
                    <strong class="suggest-name">${escapeHtml(name)}</strong>
                    <span class="suggest-location">
                        <i class="fas fa-location-dot" aria-hidden="true"></i>
                        ${escapeHtml(district)}, ${escapeHtml(region)}
                    </span>
                </span>
                <span class="suggest-meta">
                    <span class="suggest-open ${openCount > 0 ? 'is-open' : ''}">
                        ${openCount}/${totalCount} ${t('services_open')}
                    </span>
                    ${chips ? `<span class="suggest-chips">${chips}</span>` : ''}
                </span>
            </button>`;
    }

    async function fetchSearchSuggestions() {
        const q = $('#search-input').value.trim();
        const params = getSearchParams();
        params.set('limit', '8');

        if (!q && !params.get('region_id') && !params.get('district_id') && !params.get('service') && !params.get('ownership') && !params.get('open_now')) {
            hideSearchSuggestions();
            return;
        }

        if (suggestAbort) suggestAbort.abort();
        suggestAbort = new AbortController();

        const box = $('#search-suggestions');
        box.classList.remove('hidden');
        box.innerHTML = `<div class="search-suggest-status"><div class="spinner spinner-sm"></div> ${escapeHtml(t('search_instant'))}</div>`;
        $('#search-input').setAttribute('aria-expanded', 'true');

        try {
            const res = await fetch(`${API}/search.php?${params}`, { signal: suggestAbort.signal });
            const data = await res.json();
            if (!data.success) {
                box.innerHTML = `<div class="search-suggest-empty">${escapeHtml(t('search_no_suggestions'))}</div>`;
                return;
            }

            if (!data.data.length) {
                box.innerHTML = `<div class="search-suggest-empty">${escapeHtml(t('search_no_suggestions'))}</div>`;
                return;
            }

            let html = `<div class="search-suggest-head">${escapeHtml(t('search_suggestions'))} (${data.count})</div>`;
            html += data.data.map((h, i) => renderSuggestionItem(h, i)).join('');

            if (data.count > data.shown) {
                html += `<button type="button" class="search-suggest-all" id="search-suggest-all">${escapeHtml(t('search_view_all'))} (${data.count})</button>`;
            }

            box.innerHTML = html;

            box.querySelectorAll('.search-suggest-item').forEach((btn) => {
                btn.addEventListener('click', () => {
                    hideSearchSuggestions();
                    openHospitalModal(parseInt(btn.dataset.id, 10));
                });
            });

            $('#search-suggest-all')?.addEventListener('click', () => {
                hideSearchSuggestions();
                loadHospitals();
                $('#results-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        } catch (e) {
            if (e.name !== 'AbortError') {
                box.innerHTML = `<div class="search-suggest-empty">${escapeHtml(t('search_no_suggestions'))}</div>`;
            }
        }
    }

    function initSearchSuggestions() {
        const input = $('#search-input');
        const box = $('#search-suggestions');
        if (!input || !box) return;

        input.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            clearTimeout(suggestTimeout);
            suggestTimeout = setTimeout(fetchSearchSuggestions, 180);
            searchTimeout = setTimeout(loadHospitals, 400);
        });

        input.addEventListener('focus', () => {
            if (input.value.trim() || $('#region-filter').value || $('#service-filter').value) {
                fetchSearchSuggestions();
            }
        });

        input.addEventListener('keydown', (e) => {
            const items = box.querySelectorAll('.search-suggest-item');
            if (!items.length || box.classList.contains('hidden')) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeSuggestIndex = Math.min(activeSuggestIndex + 1, items.length - 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeSuggestIndex = Math.max(activeSuggestIndex - 1, 0);
            } else if (e.key === 'Enter' && activeSuggestIndex >= 0) {
                e.preventDefault();
                items[activeSuggestIndex].click();
                return;
            } else if (e.key === 'Escape') {
                hideSearchSuggestions();
                return;
            } else {
                return;
            }

            items.forEach((el, i) => {
                el.classList.toggle('active', i === activeSuggestIndex);
                el.setAttribute('aria-selected', i === activeSuggestIndex ? 'true' : 'false');
            });
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-box')) hideSearchSuggestions();
        });
    }

    async function loadHospitals() {
        $('#loading-state').classList.remove('hidden');
        $('#empty-state').classList.add('hidden');
        $('#hospitals-list').innerHTML = '';

        const params = getSearchParams();

        try {
            const res = await apiFetchLive(`hospitals.php?${params}`);
            $('#loading-state').classList.add('hidden');

            if (!res.success) {
                $('#empty-state').classList.remove('hidden');
                return;
            }

            hospitals = res.data;
            animateCounter($('#results-count'), res.count, 600);

            if (hospitals.length === 0) {
                $('#empty-state').classList.remove('hidden');
                return;
            }

            renderHospitals(hospitals);
        } catch (e) {
            $('#loading-state').classList.add('hidden');
            $('#empty-state').classList.remove('hidden');
        }
    }

    function renderHospitals(list) {
        const container = $('#hospitals-list');
        container.innerHTML = list.map((h, i) => {
            const name = lang === 'sw' && h.name_sw ? h.name_sw : h.name;
            const district = lang === 'sw' ? h.district_sw : h.district;
            const region = lang === 'sw' ? h.region_sw : h.region;
            const openCount = h.open_services_count || 0;
            const totalCount = h.total_services_count || 0;

            const openServices = (h.services || []).filter((s) => s.availability === 'open').slice(0, 4);
            const chips = openServices.map((s) => {
                const sname = lang === 'sw' ? s.name_sw : s.name;
                return `<span class="chip open">${escapeHtml(sname)}</span>`;
            }).join('');

            const badgeClass = h.facility_type === 'national' ? 'national'
                : h.facility_type === 'regional' ? 'regional' : 'district';
            const typeClass = facilityTypeClass(h.facility_type);
            const pct = totalCount > 0 ? Math.round((openCount / totalCount) * 100) : 0;
            const delay = Math.min(i * 40, 400);

            return `
                <article class="hospital-card ${typeClass}" role="listitem" data-id="${h.id}" style="animation-delay:${delay}ms">
                    <div class="hospital-card-inner">
                        <div class="hospital-card-header">
                            <h3>${escapeHtml(name)}</h3>
                            <span class="facility-badge ${badgeClass}">${facilityLabel(h.facility_type)}</span>
                        </div>
                        <p class="hospital-location">
                            <i class="fas fa-location-dot" aria-hidden="true"></i>
                            ${escapeHtml(district)}, ${escapeHtml(region)}
                        </p>
                        <div class="hospital-meta">
                            ${h.is_24_7 ? `<span><i class="fas fa-clock" aria-hidden="true"></i> ${t('open_24_7')}</span>` : ''}
                            ${h.phone ? `<span><i class="fas fa-phone" aria-hidden="true"></i> ${escapeHtml(h.phone)}</span>` : ''}
                            ${h.ownership ? `<span><i class="fas fa-building" aria-hidden="true"></i> ${escapeHtml(h.ownership)}</span>` : ''}
                        </div>
                        <span class="open-badge ${openCount > 0 ? 'open' : 'closed'}">
                            <i class="fas fa-circle-check" aria-hidden="true"></i>
                            ${openCount}/${totalCount} ${t('services_open')}
                        </span>
                        ${chips ? `<div class="service-chips">${chips}</div>` : ''}
                        <div class="hospital-card-progress" aria-hidden="true">
                            <div class="hospital-card-progress-bar" style="width:${pct}%"></div>
                        </div>
                    </div>
                </article>
            `;
        }).join('');

        container.querySelectorAll('.hospital-card').forEach((card) => {
            card.addEventListener('click', () => openHospitalModal(parseInt(card.dataset.id, 10)));
        });
    }

    async function openHospitalModal(id) {
        const modal = $('#hospital-modal');
        const hero = $('#modal-hero');
        $('#modal-services').innerHTML = `<div class="loading-state"><div class="spinner"></div></div>`;
        openModalAnimated();

        try {
            const res = await apiFetch(`hospitals.php?id=${id}`);
            if (!res.success) return;

            const h = res.data;
            const name = lang === 'sw' && h.name_sw ? h.name_sw : h.name;
            const district = lang === 'sw' ? h.district_sw : h.district;
            const region = lang === 'sw' ? h.region_sw : h.region;
            const typeClass = facilityTypeClass(h.facility_type);

            hero.className = `modal-hero ${typeClass}`;
            $('#modal-name').textContent = name;
            $('#modal-type').textContent = facilityLabel(h.facility_type);
            $('#modal-type').className = `facility-badge ${h.facility_type === 'national' ? 'national' : h.facility_type === 'regional' ? 'regional' : 'district'}`;

            let location = `${district}, ${region}`;
            if (h.address) location += ` · ${h.address}`;
            $('#modal-location').textContent = location;

            const openCount = h.open_services_count || 0;
            const totalCount = h.total_services_count || 0;
            $('#modal-quick-stats').innerHTML = `
                <span class="modal-stat-pill"><i class="fas fa-door-open"></i> ${openCount}/${totalCount} ${t('services_open')}</span>
                ${h.is_24_7 ? `<span class="modal-stat-pill"><i class="fas fa-clock"></i> ${t('open_24_7')}</span>` : ''}
                ${h.facility_code ? `<span class="modal-stat-pill"><i class="fas fa-barcode"></i> ${escapeHtml(h.facility_code)}</span>` : ''}
            `;

            let infoHtml = '';
            if (h.ownership) infoHtml += `<div class="modal-info-item"><label>${t('label_ownership')}</label><span>${escapeHtml(h.ownership)}</span></div>`;
            if (h.council) infoHtml += `<div class="modal-info-item"><label>${t('label_council')}</label><span>${escapeHtml(h.council)}</span></div>`;
            if (h.hfr_facility_type) infoHtml += `<div class="modal-info-item"><label>${t('label_hfr')}</label><span>${escapeHtml(h.hfr_facility_type)}</span></div>`;
            if (h.operating_status) infoHtml += `<div class="modal-info-item"><label>${t('label_status')}</label><span>${escapeHtml(h.operating_status)}</span></div>`;
            $('#modal-info-grid').innerHTML = infoHtml;

            let contacts = '';
            if (h.phone) contacts += `<a href="tel:${h.phone.replace(/\s/g, '')}" class="modal-contact-btn phone"><i class="fas fa-phone"></i> ${t('call')}: ${escapeHtml(h.phone)}</a>`;
            if (h.emergency_phone) contacts += `<a href="tel:${h.emergency_phone.replace(/\s/g, '')}" class="modal-contact-btn emergency"><i class="fas fa-truck-medical"></i> ${t('emergency')}: ${escapeHtml(h.emergency_phone)}</a>`;
            $('#modal-contacts').innerHTML = contacts;

            const servicesHtml = (h.services || []).map((s, idx) => {
                const sname = lang === 'sw' ? s.name_sw : s.name;
                const note = lang === 'sw' ? (s.notes_sw || s.notes) : (s.notes || s.notes_sw);
                const icon = serviceIcon(s.code);
                const wait = s.wait_minutes ? `<span class="wait-badge"><i class="fas fa-hourglass-half"></i> ~${s.wait_minutes} ${t('min_wait')}</span>` : '';
                return `
                    <div class="service-item" style="animation-delay:${Math.min(idx * 35, 350)}ms">
                        <div class="service-item-left">
                            <div class="service-item-icon"><i class="fas ${icon}"></i></div>
                            <div>
                                <div class="service-item-name">${escapeHtml(sname)}</div>
                                ${note ? `<div class="service-item-note">${escapeHtml(note)}</div>` : ''}
                            </div>
                        </div>
                        <div class="service-item-right">
                            ${wait}
                            <span class="status ${s.availability}">${statusLabel(s.availability)}</span>
                        </div>
                    </div>
                `;
            }).join('');
            $('#modal-services').innerHTML = servicesHtml || `<p class="empty-state">${t('no_results')}</p>`;
        } catch (e) {
            $('#modal-services').innerHTML = `<p>${t('offline_cached')}</p>`;
        }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    async function syncData() {
        if (!navigator.onLine) return;
        await Promise.all([loadStats(), loadRegions(), loadServiceTypes(), loadHospitals()]);
        $('#offline-banner').classList.add('hidden');
    }

    /* AI Chat */
    let aiReplyInProgress = false;

    function prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function scrollAiMessages() {
        const el = $('#ai-messages');
        if (el) el.scrollTop = el.scrollHeight;
    }

    function formatAiInline(text) {
        return escapeHtml(text)
            .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
            .replace(/\*(.+?)\*/g, '<em>$1</em>')
            .replace(/(\+?\d[\d\s-]{7,}\d)/g, (m) => {
                const tel = m.replace(/\s/g, '');
                return `<a href="tel:${tel}" class="ai-phone">${m.trim()}</a>`;
            });
    }

    function parseAiReplyBlocks(raw) {
        const lines = String(raw || '').split('\n');
        const blocks = [];
        let listItems = [];
        let listOrdered = false;

        const flushList = () => {
            if (!listItems.length) return;
            blocks.push({
                type: listOrdered ? 'ol' : 'ul',
                items: [...listItems],
            });
            listItems = [];
        };

        lines.forEach((line) => {
            const trimmed = line.trim();
            if (!trimmed) {
                flushList();
                return;
            }

            const numbered = trimmed.match(/^(\d+)[.)]\s+(.+)/);
            const bullet = trimmed.match(/^[-*•]\s+(.+)/);

            if (numbered) {
                if (listItems.length && !listOrdered) flushList();
                listOrdered = true;
                listItems.push(numbered[2]);
                return;
            }

            if (bullet) {
                if (listItems.length && listOrdered) flushList();
                listOrdered = false;
                listItems.push(bullet[1]);
                return;
            }

            flushList();
            blocks.push({ type: 'p', text: trimmed });
        });

        flushList();
        return blocks.length ? blocks : [{ type: 'p', text: raw }];
    }

    function renderAiReplyHtml(raw) {
        return parseAiReplyBlocks(raw).map((block) => {
            if (block.type === 'p') {
                const cls = /(\b114\b|dharura|emergency)/i.test(block.text) ? ' class="ai-emergency-line"' : '';
                return `<p${cls}>${formatAiInline(block.text)}</p>`;
            }
            const tag = block.type;
            const items = block.items.map((item) => `<li>${formatAiInline(item)}</li>`).join('');
            return `<${tag} class="ai-list">${items}</${tag}>`;
        }).join('');
    }

    function sleep(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }

    function stripMarkdown(text) {
        return String(text || '')
            .replace(/\*\*(.+?)\*\*/g, '$1')
            .replace(/\*(.+?)\*/g, '$1');
    }

    async function typeTextBlock(el, text) {
        const plain = stripMarkdown(text);
        const tokens = plain.match(/\S+|\s+/g) || [];
        let current = '';

        for (const token of tokens) {
            current += token;
            el.innerHTML = `${escapeHtml(current)}<span class="ai-msg-cursor" aria-hidden="true"></span>`;
            scrollAiMessages();
            const delay = token.trim() ? 18 + Math.min(token.length * 2, 24) + Math.random() * 10 : 6;
            await sleep(delay);
        }

        el.innerHTML = formatAiInline(text);
    }

    async function typeBotReply(rawText) {
        const bubble = document.createElement('div');
        bubble.className = 'ai-msg bot';
        bubble.innerHTML = `
            <div class="ai-msg-avatar" aria-hidden="true"><i class="fas fa-robot"></i></div>
            <div class="ai-msg-body"><div class="ai-msg-content"></div></div>`;
        $('#ai-messages').appendChild(bubble);
        scrollAiMessages();

        const content = bubble.querySelector('.ai-msg-content');

        if (prefersReducedMotion()) {
            content.innerHTML = renderAiReplyHtml(rawText);
            scrollAiMessages();
            return bubble;
        }

        content.classList.add('is-typing');
        const blocks = parseAiReplyBlocks(rawText);

        for (const block of blocks) {
            if (block.type === 'p') {
                const p = document.createElement('p');
                if (/(\b114\b|dharura|emergency)/i.test(block.text)) {
                    p.className = 'ai-emergency-line';
                }
                content.appendChild(p);
                await typeTextBlock(p, block.text);
            } else if (block.type === 'ol' || block.type === 'ul') {
                const list = document.createElement(block.type);
                list.className = 'ai-list';
                content.appendChild(list);
                for (const item of block.items) {
                    const li = document.createElement('li');
                    list.appendChild(li);
                    await typeTextBlock(li, item);
                    scrollAiMessages();
                    await sleep(80);
                }
            }
            scrollAiMessages();
            await sleep(120);
        }

        content.classList.remove('is-typing');
        scrollAiMessages();
        return bubble;
    }

    function showTypingIndicator() {
        const div = document.createElement('div');
        div.className = 'ai-msg bot ai-typing';
        div.innerHTML = `
            <div class="ai-msg-avatar" aria-hidden="true"><i class="fas fa-robot"></i></div>
            <div class="ai-msg-body">
                <div class="ai-typing-bubble">
                    <span class="ai-typing-label">${escapeHtml(t('ai_typing'))}</span>
                    <span class="ai-typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                </div>
            </div>`;
        $('#ai-messages').appendChild(div);
        scrollAiMessages();
        return div;
    }

    function addAiMessage(text, role) {
        const div = document.createElement('div');
        div.className = `ai-msg ${role}`;

        if (role === 'bot') {
            div.innerHTML = `
                <div class="ai-msg-avatar" aria-hidden="true"><i class="fas fa-robot"></i></div>
                <div class="ai-msg-body"><div class="ai-msg-content"><p>${escapeHtml(text)}</p></div></div>`;
        } else {
            div.textContent = text;
        }

        $('#ai-messages').appendChild(div);
        scrollAiMessages();
        return div;
    }

    function initAiChat() {
        const panel = $('#ai-panel');
        const fab = $('#ai-fab');
        const messages = $('#ai-messages');
        const form = $('#ai-form');
        const input = $('#ai-input');

        fab.addEventListener('click', async () => {
            panel.classList.remove('hidden');
            fab.classList.add('hidden');
            if (messages.children.length === 0) {
                await typeBotReply(t('ai_welcome'));
            }
        });

        $('#ai-close').addEventListener('click', () => {
            panel.classList.add('hidden');
            fab.classList.remove('hidden');
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const msg = input.value.trim();
            if (!msg || aiReplyInProgress) return;

            aiReplyInProgress = true;
            input.disabled = true;
            form.querySelector('button').disabled = true;

            addAiMessage(msg, 'user');
            input.value = '';
            const typing = showTypingIndicator();

            try {
                const res = await fetch(`${API}/ai-chat.php`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: msg,
                        lang,
                        context: {
                            region_id: $('#region-filter').value || null,
                            service: $('#service-filter').value || null,
                            ownership: $('#ownership-filter').value || null,
                            open_now: $('#open-now-filter').checked || null,
                        },
                    }),
                });
                const data = await res.json();
                typing.remove();

                const reply = data.success
                    ? data.data.reply
                    : (data.fallback || data.error || 'Error');

                if (data.meta?.timings && typeof console !== 'undefined') {
                    console.debug('[AfyaLink AI]', data.meta.timings, data.data?.filters || '');
                }

                await typeBotReply(reply);
            } catch (err) {
                typing.remove();
                await typeBotReply(t('offline_cached'));
            } finally {
                aiReplyInProgress = false;
                input.disabled = false;
                form.querySelector('button').disabled = false;
                input.focus();
            }
        });
    }

    function onRegionChange() {
        const regionId = $('#region-filter').value;
        const districtSelect = $('#district-filter');
        districtSelect.innerHTML = `<option value="">${t('all_districts')}</option>`;

        if (!regionId) {
            districtSelect.disabled = true;
            loadHospitals();
            return;
        }

        const region = regions.find((r) => String(r.id) === regionId);
        if (region && region.districts) {
            districtSelect.disabled = false;
            region.districts.forEach((d) => {
                const opt = document.createElement('option');
                opt.value = d.id;
                opt.textContent = lang === 'sw' ? d.name_sw : d.name;
                districtSelect.appendChild(opt);
            });
        }
        loadHospitals();
    }

    function bindEvents() {
        $('#region-filter').addEventListener('change', () => {
            onRegionChange();
            fetchSearchSuggestions();
        });
        $('#district-filter').addEventListener('change', () => { loadHospitals(); fetchSearchSuggestions(); });
        $('#service-filter').addEventListener('change', () => { loadHospitals(); fetchSearchSuggestions(); });
        $('#ownership-filter').addEventListener('change', () => { loadHospitals(); fetchSearchSuggestions(); });
        $('#open-now-filter').addEventListener('change', () => { loadHospitals(); fetchSearchSuggestions(); });

        initSearchSuggestions();

        $('#modal-close').addEventListener('click', closeModalAnimated);
        $('#modal-overlay').addEventListener('click', closeModalAnimated);
        $('#hospital-modal').addEventListener('click', (e) => {
            if (e.target === $('#hospital-modal')) closeModalAnimated();
        });
        $('#hospital-modal').addEventListener('cancel', (e) => {
            e.preventDefault();
            closeModalAnimated();
        });

        $('#lang-toggle').addEventListener('click', switchLanguage);
        $('#theme-toggle').addEventListener('click', toggleTheme);
        $('#sync-btn').addEventListener('click', syncData);

        window.addEventListener('online', () => {
            loadHospitals();
            loadStats();
        });
        window.addEventListener('offline', () => updateOfflineBanner(true));
    }

    async function init() {
        applyI18n();
        bindEvents();
        initDrawer();
        initScrollReveal();
        initDynamicNav();
        initAiChat();
        initFabTypewriter();

        try {
            await AfyaCache.preloadBundle(API);
            await Promise.all([loadStats(), loadRegions(), loadServiceTypes()]);
            await loadHospitals();
        } catch (e) {
            $('#loading-state').classList.add('hidden');
            updateOfflineBanner(true);
        }
    }

    document.addEventListener('DOMContentLoaded', init);
})();
