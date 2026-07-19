const CACHE_NAME = 'homewatt-v1.0.0';
const OFFLINE_URL = '/offline';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/favicon.png',
    '/manifest.json',
    '/icons/icon-192.png',
    '/icons/icon-512.png'
];

// Sự kiện install: Lưu các asset cơ bản vào cache
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[Service Worker] Đang tải các tài nguyên thiết yếu vào cache');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => self.skipWaiting())
    );
});

// Sự kiện activate: Xóa sạch cache cũ để tránh xung đột phiên bản
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('[Service Worker] Đang xóa cache cũ:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Sự kiện fetch: Quản lý request và chiến lược tải cache
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Handle share target POST requests — redirect to GET for SPA handling
    if (event.request.method === 'POST' && url.pathname === '/expenses/create') {
        event.respondWith(
            (async () => {
                const formData = await event.request.formData();
                const title = formData.get('description') || '';
                const text = formData.get('notes') || '';
                const files = formData.getAll('receipts');

                // Build redirect URL with shared data as query params
                const redirectUrl = new URL('/expenses/create', self.location.origin);
                redirectUrl.searchParams.set('source', 'share');
                if (title) redirectUrl.searchParams.set('description', title);
                if (text) redirectUrl.searchParams.set('notes', text);
                if (files.length > 0) redirectUrl.searchParams.set('shared_files', files.length.toString());

                // Redirect to GET so the normal navigation route handles it
                return Response.redirect(redirectUrl.toString(), 303);
            })()
        );
        return;
    }

    // Chỉ xử lý các request GET
    if (event.request.method !== 'GET') {
        return;
    }

    // 1. Đối với tài nguyên điều hướng (HTML pages) -> Chiến lược Network-First
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    // Nếu phản hồi tốt, lưu một bản copy vào cache
                    if (networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    // Khi offline: Thử lấy trang từ cache
                    return caches.match(event.request)
                        .then((cachedResponse) => {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            // Nếu trang chưa được cache, trả về trang offline fallback tuyệt đẹp
                            return caches.match(OFFLINE_URL);
                        });
                })
        );
        return;
    }

    // 2. Đối với Static Assets (Vite CSS/JS, Fonts, Icons) -> Chiến lược Stale-While-Revalidate hoặc Cache-First
    const isStaticAsset = 
        url.pathname.startsWith('/build/') || 
        url.pathname.startsWith('/icons/') || 
        url.pathname.startsWith('/images/') || 
        url.hostname.includes('fonts.bunny.net') ||
        url.pathname.endsWith('manifest.json') ||
        url.pathname.endsWith('favicon.png') ||
        url.pathname.endsWith('favicon.ico');

    if (isStaticAsset) {
        event.respondWith(
            caches.match(event.request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        // Trả về ngay lập tức, nhưng ngầm cập nhật cache từ network
                        fetch(event.request).then((networkResponse) => {
                            if (networkResponse.status === 200) {
                                caches.open(CACHE_NAME).then((cache) => {
                                    cache.put(event.request, networkResponse);
                                });
                            }
                        }).catch(() => { /* Bỏ qua lỗi kết nối ngầm */ });
                        
                        return cachedResponse;
                    }

                    // Nếu chưa có trong cache, tải từ mạng và lưu vào cache
                    return fetch(event.request).then((networkResponse) => {
                        if (networkResponse.status === 200) {
                            const responseClone = networkResponse.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(event.request, responseClone);
                            });
                        }
                        return networkResponse;
                    });
                })
        );
        return;
    }

    // 3. Các request khác (API, tải dữ liệu động): Chạy trực tiếp từ network
    event.respondWith(
        fetch(event.request).catch(() => {
            // Khi mất mạng, thử phục vụ từ cache nếu có sẵn
            return caches.match(event.request);
        })
    );
});
