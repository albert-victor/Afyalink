(function () {
    'use strict';

    const $ = (s) => document.querySelector(s);
    const $$ = (s) => document.querySelectorAll(s);

    let lang = localStorage.getItem('afyalink_lang') || 'sw';

    const i18n = {
        sw: {
            login_title: 'Ingia – AfyaLink',
            login_back: 'Rudi',
            login_back_title: 'Rudi kwenye app',
            login_subtitle: 'Ingia kwa admin wa kituo',
            login_facility_label: 'Nambari ya Kituo (HFR)',
            login_facility_ph: 'mf. 105905-4',
            login_pin_label: 'PIN ya Kituo',
            login_pin_ph: 'Weka PIN',
            login_submit: 'Ingia',
            login_skip: 'Endelea bila kuingia',
            login_signing_in: 'Inaingia...',
            login_error_invalid: 'Nambari ya kituo au PIN si sahihi',
            login_error_server: 'Server haipatikani. Jaribu tena.',
            login_pin_show: 'Onyesha PIN',
            login_pin_hide: 'Ficha PIN',
        },
        en: {
            login_title: 'Sign in – AfyaLink',
            login_back: 'Back',
            login_back_title: 'Back to app',
            login_subtitle: 'Facility admin sign in',
            login_facility_label: 'Facility Code (HFR)',
            login_facility_ph: 'e.g. 105905-4',
            login_pin_label: 'Facility PIN',
            login_pin_ph: 'Enter PIN',
            login_submit: 'Sign in',
            login_skip: 'Continue without signing in',
            login_signing_in: 'Signing in...',
            login_error_invalid: 'Invalid facility code or PIN',
            login_error_server: 'Server unavailable. Try again.',
            login_pin_show: 'Show PIN',
            login_pin_hide: 'Hide PIN',
        },
    };

    function t(key) {
        return i18n[lang][key] || key;
    }

    function applyI18n() {
        document.documentElement.lang = lang;
        document.title = t('login_title');

        $$('[data-i18n]').forEach((el) => {
            const key = el.dataset.i18n;
            if (i18n[lang][key]) el.textContent = i18n[lang][key];
        });

        $$('[data-i18n-placeholder]').forEach((el) => {
            const key = el.dataset.i18nPlaceholder;
            if (i18n[lang][key]) el.placeholder = i18n[lang][key];
        });

        $$('[data-i18n-title]').forEach((el) => {
            const key = el.dataset.i18nTitle;
            if (i18n[lang][key]) el.title = i18n[lang][key];
        });

        const langBtn = $('#lang-toggle span');
        if (langBtn) langBtn.textContent = lang === 'sw' ? 'EN' : 'SW';

        const pinToggle = $('#pin-toggle');
        if (pinToggle) {
            const hidden = $('#admin-pin')?.type === 'password';
            pinToggle.setAttribute('aria-label', t(hidden ? 'login_pin_show' : 'login_pin_hide'));
        }
    }

    function switchLanguage() {
        lang = lang === 'sw' ? 'en' : 'sw';
        localStorage.setItem('afyalink_lang', lang);
        applyI18n();
    }

    /* Background slideshow */
    const slides = $$('.login-bg-slide');
    let current = 0;
    let timer = null;

    function goToSlide(index) {
        if (!slides.length) return;
        current = (index + slides.length) % slides.length;
        slides.forEach((el, i) => el.classList.toggle('active', i === current));
    }

    function startSlideshow() {
        clearInterval(timer);
        if (slides.length > 1) {
            timer = setInterval(() => goToSlide(current + 1), 6000);
        }
    }

    /* PIN visibility toggle */
    const pinInput = $('#admin-pin');
    const pinToggle = $('#pin-toggle');
    if (pinInput && pinToggle) {
        pinToggle.addEventListener('click', () => {
            const show = pinInput.type === 'password';
            pinInput.type = show ? 'text' : 'password';
            pinToggle.querySelector('i').className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
            pinToggle.setAttribute('aria-label', t(show ? 'login_pin_hide' : 'login_pin_show'));
        });
    }

    /* Admin login */
    const form = $('#login-form');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const errBox = $('#login-error');
            const errText = $('#login-error-text');
            const btn = $('#login-submit');
            const btnLabel = btn.querySelector('span');
            errBox.classList.add('hidden');

            const facilityCode = $('#facility-code').value.trim();
            const pin = $('#admin-pin').value;

            btn.disabled = true;
            if (btnLabel) btnLabel.textContent = t('login_signing_in');
            btn.querySelector('i')?.classList.replace('fa-right-to-bracket', 'fa-spinner');
            btn.querySelector('i')?.classList.add('fa-spin');

            try {
                const res = await fetch('api/admin/login.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    credentials: 'same-origin',
                    body: JSON.stringify({ facility_code: facilityCode, pin }),
                });
                const data = await res.json();

                if (data.success) {
                    window.location.href = 'admin/dashboard.php';
                    return;
                }

                errText.textContent = data.error || t('login_error_invalid');
                errBox.classList.remove('hidden');
            } catch (ex) {
                errText.textContent = t('login_error_server');
                errBox.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                if (btnLabel) btnLabel.textContent = t('login_submit');
                const icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.remove('fa-spin', 'fa-spinner');
                    icon.classList.add('fa-right-to-bracket');
                }
            }
        });
    }

    $('#lang-toggle')?.addEventListener('click', switchLanguage);

    applyI18n();
    goToSlide(0);
    startSlideshow();
})();
