(function () {
    'use strict';

    const API = '../api/admin';
    const $ = (s) => document.querySelector(s);

    async function loadDashboard() {
        try {
            const res = await fetch(`${API}/services.php`, { credentials: 'same-origin' });
            if (res.status === 401) {
                window.location.href = '../login.php';
                return;
            }
            const data = await res.json();
            if (data.success) showDashboard(data.data);
        } catch (e) {
            $('#dash-hospital-name').textContent = 'Hitilafu ya mtandao';
        }
    }

    function showDashboard(data) {
        $('#dash-hospital-name').textContent = data.hospital.name;
        $('#dash-facility-code').textContent =
            `HFR: ${data.hospital.facility_code} · ${data.hospital.district}, ${data.hospital.region}`;

        const container = $('#services-admin-list');
        container.innerHTML = data.services.map((s) => `
            <div class="admin-card admin-service-row" data-hsid="${s.hospital_service_id}">
                <div class="admin-service-info">
                    <strong><i class="fas fa-stethoscope"></i> ${s.name}</strong>
                    <span class="status ${s.availability}">${s.availability}</span>
                </div>
                <div class="admin-service-form">
                    <select class="status-select">
                        <option value="available" ${s.latest_status === 'available' ? 'selected' : ''}>Inapatikana</option>
                        <option value="limited" ${s.latest_status === 'limited' ? 'selected' : ''}>Imepungua</option>
                        <option value="busy" ${s.latest_status === 'busy' ? 'selected' : ''}>Msongamano</option>
                        <option value="unavailable" ${s.latest_status === 'unavailable' ? 'selected' : ''}>Haipatikani</option>
                    </select>
                    <input type="number" class="wait-input" placeholder="Dakika za kusubiri" min="0" max="480" value="${s.wait_minutes || ''}">
                    <label class="avail-check"><input type="checkbox" class="avail-input" ${s.is_available ? 'checked' : ''}> Huduma inaendelea</label>
                    <input type="text" class="notes-input" placeholder="Maelezo (hiari)" value="${s.notes || ''}">
                    <button type="button" class="btn-primary btn-save"><i class="fas fa-floppy-disk"></i> Hifadhi</button>
                </div>
            </div>
        `).join('');

        container.querySelectorAll('.btn-save').forEach((btn) => {
            btn.addEventListener('click', () => saveService(btn.closest('.admin-service-row')));
        });
    }

    async function saveService(row) {
        const hsid = row.dataset.hsid;
        const btn = row.querySelector('.btn-save');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inahifadhi...';

        try {
            const res = await fetch(`${API}/update-service.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    hospital_service_id: parseInt(hsid, 10),
                    status: row.querySelector('.status-select').value,
                    wait_minutes: parseInt(row.querySelector('.wait-input').value, 10) || null,
                    is_available: row.querySelector('.avail-input').checked,
                    notes: row.querySelector('.notes-input').value,
                }),
            });
            const data = await res.json();
            btn.innerHTML = data.success
                ? '<i class="fas fa-check"></i> Imesasishwa'
                : '<i class="fas fa-xmark"></i> Hitilafu';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-floppy-disk"></i> Hifadhi';
                btn.disabled = false;
            }, 2000);
        } catch (e) {
            btn.innerHTML = '<i class="fas fa-xmark"></i> Hitilafu';
            btn.disabled = false;
        }
    }

    loadDashboard();
})();
