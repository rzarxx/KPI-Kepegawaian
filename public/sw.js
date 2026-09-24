const STATIC_CACHE = 'kpi-static-v4';
const STATIC_DESTINATIONS = new Set(['font', 'image', 'script', 'style']);
const CORE_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/icons/kpi-mark.svg',
    '/icons/kpi-64.png',
    '/icons/kpi-192.png',
    '/icons/kpi-512.png',
    '/icons/kpi-maskable-512.png',
    '/icons/apple-touch-icon.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(CORE_ASSETS)));
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys
                .filter((key) => key.startsWith('kpi-static-') && key !== STATIC_CACHE)
                .map((key) => caches.delete(key)),
        )),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match('/offline.html')));
        return;
    }

    const isVersionedBuildAsset = url.pathname.startsWith('/build/');
    const isPublicIcon = url.pathname.startsWith('/icons/') || url.pathname === '/favicon.ico';

    if (!STATIC_DESTINATIONS.has(request.destination) || (!isVersionedBuildAsset && !isPublicIcon)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
                return cachedResponse;
            }

            return fetch(request).then((response) => {
                if (response.ok) {
                    void caches.open(STATIC_CACHE).then((cache) => cache.put(request, response.clone()));
                }

                return response;
            });
        }),
    );
});
