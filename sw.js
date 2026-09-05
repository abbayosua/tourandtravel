// Service worker ringan: cache shell (halaman statis & aset)
const CACHE = 'tat-shell-v1';
const ASSETS = ['/', '/assets/css/style.css', '/assets/css/design-tokens.css'];
self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)).then(() => self.skipWaiting()));
});
self.addEventListener('activate', e => e.waitUntil(clients.claim()));
self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    e.respondWith(
        fetch(e.request).then(r => {
            const copy = r.clone();
            caches.open(CACHE).then(c => c.put(e.request, copy)).catch(() => {});
            return r;
        }).catch(() => caches.match(e.request))
    );
});
