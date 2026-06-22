const CACHE = 'homewatt-v1';
const ASSETS = ['/', '/dashboard', '/login', '/build/assets/'];

self.addEventListener('install', (e) => {
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    if (e.request.method !== 'GET') return;

    if (e.request.url.includes('/build/assets/') || e.request.url.includes('/icons/') || e.request.url.endsWith('manifest.json') || e.request.url.endsWith('favicon.ico')) {
        e.respondWith(caches.match(e.request).then((r) => r || fetch(e.request).then((res) => {
            if (res.ok) {
                const clone = res.clone();
                caches.open(CACHE).then((c) => c.put(e.request, clone));
            }
            return res;
        })));
        return;
    }

    e.respondWith(fetch(e.request));
});
