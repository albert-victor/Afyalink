const CACHE_NAME = 'afyalink-v7';
const STATIC_ASSETS = [
    './',
    './index.php',
    './login.php',
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
    './manifest.json',
    './admin/dashboard.php',
    './admin/admin.js',
    './assets/css/admin.css',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
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

    if (url.pathname.includes('/api/')) {
        event.respondWith(networkFirstApi(event.request));
        return;
    }

    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
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
