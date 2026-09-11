// PHOTOBOOTH PRO Service Worker
const CACHE_NAME = 'photobooth-pro-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    // Biarkan request non-GET (POST, PUT, dll) mengalir normal tanpa diintersep
    if (event.request.method !== 'GET') return;

    // Abaikan request ke chrome-extension atau non-http(s)
    const url = new URL(event.request.url);
    if (!url.protocol.startsWith('http')) return;

    event.respondWith(
        fetch(event.request).catch(async () => {
            // Coba ambil dari cache jika offline
            const cached = await caches.match(event.request);
            // caches.match() bisa return undefined jika tidak ada cache —
            // fallback ke Response 503 agar browser tidak error
            return cached || new Response('Offline — resource not cached', {
                status: 503,
                statusText: 'Service Unavailable',
                headers: { 'Content-Type': 'text/plain' },
            });
        })
    );
});
