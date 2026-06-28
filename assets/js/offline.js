/**
 * AfyaLink Offline-First Cache Layer
 */
const AfyaCache = {
    DB_NAME: 'afyalink-offline',
    DB_VERSION: 2,
    STORE: 'api_cache',
    BUNDLE_KEY: 'offline-bundle',
    TTL_MS: 7 * 24 * 60 * 60 * 1000, // 7 days for offline bundle

    async openDB() {
        return new Promise((resolve, reject) => {
            const req = indexedDB.open(this.DB_NAME, this.DB_VERSION);
            req.onerror = () => reject(req.error);
            req.onsuccess = () => resolve(req.result);
            req.onupgradeneeded = (e) => {
                const db = e.target.result;
                if (!db.objectStoreNames.contains(this.STORE)) {
                    db.createObjectStore(this.STORE, { keyPath: 'key' });
                }
            };
        });
    },

    async set(key, data) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.STORE, 'readwrite');
            tx.objectStore(this.STORE).put({ key, data, cached_at: Date.now() });
            tx.oncomplete = () => resolve();
            tx.onerror = () => reject(tx.error);
        });
    },

    async get(key) {
        const db = await this.openDB();
        return new Promise((resolve, reject) => {
            const tx = db.transaction(this.STORE, 'readonly');
            const req = tx.objectStore(this.STORE).get(key);
            req.onsuccess = () => {
                const row = req.result;
                if (!row) return resolve(null);
                if (Date.now() - row.cached_at > this.TTL_MS) return resolve(null);
                resolve(row.data);
            };
            req.onerror = () => reject(req.error);
        });
    },

    async fetchWithCache(url, options = {}) {
        const cacheKey = url;

        if (navigator.onLine) {
            try {
                const res = await fetch(url, options);
                if (res.ok) {
                    const data = await res.json();
                    await this.set(cacheKey, data);
                    return { data, fromCache: false };
                }
            } catch (e) {
                console.warn('Network fetch failed, trying cache:', e);
            }
        }

        const cached = await this.get(cacheKey);
        if (cached) return { data: cached, fromCache: true };

        const bundle = await this.getBundle();
        if (bundle) {
            const fallback = this.resolveFromBundle(url, bundle);
            if (fallback) return { data: fallback, fromCache: true, fromBundle: true };
        }

        throw new Error('offline_no_cache');
    },

    async preloadBundle(baseUrl = 'api') {
        try {
            const res = await fetch(`${baseUrl}/offline-bundle.php`);
            if (!res.ok) return false;
            const data = await res.json();
            if (data.success) {
                await this.set(this.BUNDLE_KEY, data);
                await this.set(`${baseUrl}/offline-bundle.php`, data);
                console.info('[AfyaLink] Offline bundle cached:', data.meta?.count, 'hospitals');
                return true;
            }
        } catch (e) {
            console.warn('[AfyaLink] Bundle preload failed:', e);
        }
        return false;
    },

    async getBundle() {
        return this.get(this.BUNDLE_KEY);
    },

    resolveFromBundle(url, bundle) {
        if (!bundle?.data) return null;
        const u = url.replace(/^\.\.\//, '').replace(/^api\//, 'api/');

        if (u.includes('regions.php')) {
            return { success: true, data: bundle.data.regions, meta: bundle.meta };
        }
        if (u.includes('services.php')) {
            return { success: true, data: bundle.data.service_types, meta: bundle.meta };
        }
        if (u.includes('stats.php')) {
            const hospitals = bundle.data.hospitals || [];
            let open = 0;
            hospitals.forEach((h) => { open += h.open_services_count || 0; });
            return {
                success: true,
                data: {
                    hospitals: hospitals.length,
                    services_open_now: open,
                    regions: (bundle.data.regions || []).length,
                },
                meta: bundle.meta,
            };
        }
        if (u.includes('search.php') || u.includes('hospitals.php')) {
            let list = [...(bundle.data.hospitals || [])];
            const params = new URL(u, window.location.origin).searchParams;
            const regionId = params.get('region_id');
            const districtId = params.get('district_id');
            const service = params.get('service');
            const ownership = params.get('ownership');
            const openNow = params.get('open_now');
            const q = params.get('q')?.toLowerCase();
            const id = params.get('id');
            const limit = parseInt(params.get('limit') || '8', 10);

            if (id) {
                const h = list.find((x) => String(x.id) === id);
                return h ? { success: true, data: h, meta: bundle.meta } : null;
            }
            if (regionId) list = list.filter((h) => String(h.region_id) === regionId);
            if (districtId) list = list.filter((h) => String(h.district_id) === districtId);
            if (service) list = list.filter((h) => (h.services || []).some((s) => s.code === service && s.is_available));
            if (ownership) {
                const own = ownership.toLowerCase();
                list = list.filter((h) => (h.ownership || '').toLowerCase() === own);
            }
            if (openNow) list = list.filter((h) => (h.open_services_count || 0) > 0);
            if (q) {
                list = list.filter((h) => {
                    const hay = [
                        h.name, h.name_sw, h.district, h.district_sw, h.region, h.region_sw,
                        h.address, h.council,
                    ].filter(Boolean).join(' ').toLowerCase();
                    if (hay.includes(q)) return true;
                    return (h.services || []).some((s) => {
                        const svc = [s.name, s.name_sw, s.code].filter(Boolean).join(' ').toLowerCase();
                        return svc.includes(q);
                    });
                });
            }
            if (u.includes('search.php')) {
                const mapped = list.map((h) => ({
                    id: h.id,
                    name: h.name,
                    name_sw: h.name_sw,
                    facility_type: h.facility_type,
                    district: h.district,
                    district_sw: h.district_sw,
                    region: h.region,
                    region_sw: h.region_sw,
                    region_id: h.region_id,
                    address: h.address,
                    phone: h.phone,
                    is_24_7: h.is_24_7,
                    ownership: h.ownership,
                    open_services_count: h.open_services_count,
                    total_services_count: h.total_services_count,
                    open_services: (h.services || []).filter((s) => s.availability === 'open').slice(0, 5).map((s) => ({
                        code: s.code, name: s.name, name_sw: s.name_sw,
                    })),
                }));
                return {
                    success: true,
                    data: mapped.slice(0, limit),
                    count: mapped.length,
                    shown: Math.min(limit, mapped.length),
                    meta: bundle.meta,
                };
            }
            return { success: true, data: list, count: list.length, meta: bundle.meta };
        }
        return null;
    },

    isOnline() {
        return navigator.onLine;
    },
};

window.AfyaCache = AfyaCache;
