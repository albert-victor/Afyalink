const CACHE_NAME = 'afyalink-v10';
const STATIC_ASSETS = [
    './',
    './index.php',
    './login.php',
    './sw.js',
    './assets/css/app.css',
    './assets/css/login.css',
    './assets/js/app.js',
    './assets/js/login.js',
    './assets/js/offline.js',
    './assets/icons/icon.svg',
    './assets/images/slide-1.svg',
    './assets/images/slide-2.svg',
    './assets/images/slide-3.svg',
    './assets/images/slide-4.svg',
    './assets/vendor/fontawesome/css/all.min.css',
    './assets/vendor/fontawesome/webfonts/fa-solid-900.woff2',
    './assets/vendor/fontawesome/webfonts/fa-regular-400.woff2',
    './assets/vendor/fontawesome/webfonts/fa-brands-400.woff2',
    './assets/vendor/fontawesome/webfonts/fa-v4compatibility.woff2',
    './assets/vendor/fonts/app.css',
    './assets/vendor/fonts/login.css',
    './assets/vendor/fonts/admin.css',
    './manifest.json',
    './assets/css/admin.css',
    './admin/admin.js',
];

// Font files referenced by local Google Fonts CSS (cached on first load via fetch handler)
const FONT_FILES_GLOB = './assets/vendor/fonts/files/';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(async (cache) => {
            await cache.addAll(STATIC_ASSETS);
            // Cache font woff2 files if manifest exists
            try {
                const res = await fetch('./assets/vendor/manifest.json');
                if (res.ok) {
                    const manifest = await res.json();
                    const fontFiles = (manifest.files || []).filter((f) => f.includes('/fonts/files/'));
                    await Promise.all(
                        fontFiles.map((f) => cache.add('./' + f.replace(/^\//, '')).catch(() => {}))
                    );
                }
            } catch (e) {
                /* manifest optional until download script runs */
            }
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Admin pages need live session – never serve from cache
    if (url.pathname.includes('/admin/')) {
        event.respondWith(fetch(event.request));
        return;
    }

    if (url.pathname.includes('/api/')) {
        event.respondWith(networkFirstApi(event.request));
        return;
    }

    event.respondWith(
        caches.match(event.request, { ignoreSearch: true }).then((cached) => {
            if (cached) {
                return cached;
            }
            return fetch(event.request)
                .then((response) => {
                    if (response.ok && event.request.method === 'GET') {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return response;
                })
                .catch(() => cached);
        })
    );
});

async function networkFirstApi(request) {
    try {
        const response = await fetch(request);
        if (response.ok) {
            const cache = await caches.open(CACHE_NAME);
            cache.put(request, response.clone());
        }
        return response;
    } catch (e) {
        const cached = await caches.match(request);
        if (cached) return cached;
        return new Response(
            JSON.stringify({ success: false, error: 'offline' }),
            { status: 503, headers: { 'Content-Type': 'application/json' } }
        );
    }
}
