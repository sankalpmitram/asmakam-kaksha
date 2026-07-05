/**
 * service-worker.js
 * Provides offline caching for the app shell (static assets) so the
 * app loads instantly and can be installed on the home screen.
 * API requests (data) are always fetched fresh from the network since
 * they read/write live JSON data on the server.
 */

const CACHE_NAME = 'asmakam-kaksha-v1';
const APP_SHELL = [
    'assets/css/style.css',
    'assets/js/app.js',
    'assets/js/login.js',
    'assets/js/dashboard.js',
    'assets/js/classes.js',
    'assets/js/students.js',
    'assets/js/attendance.js',
    'assets/js/attendance_history.js',
    'assets/js/reports.js',
    'assets/js/settings.js',
    'assets/images/student-placeholder.svg',
    'assets/images/school-placeholder.svg',
    'assets/icons/icon-192.png',
    'assets/icons/icon-512.png',
    'manifest.json',
];

self.addEventListener('install', function (event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(APP_SHELL);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    event.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (event) {
    const url = new URL(event.request.url);

    // Never cache API calls, PHP pages, or non-GET requests -- always fetch live data.
    if (event.request.method !== 'GET' || url.pathname.indexOf('/api/') !== -1 || url.pathname.endsWith('.php')) {
        event.respondWith(
            fetch(event.request).catch(function () {
                return new Response(
                    JSON.stringify({ success: false, message: 'आप ऑफलाइन हैं। कृपया इंटरनेट से जुड़ें।', data: null }),
                    { headers: { 'Content-Type': 'application/json' } }
                );
            })
        );
        return;
    }

    // Cache-first strategy for static app shell assets.
    event.respondWith(
        caches.match(event.request).then(function (cached) {
            return cached || fetch(event.request).then(function (response) {
                if (response && response.status === 200) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) { cache.put(event.request, clone); });
                }
                return response;
            }).catch(function () { return cached; });
        })
    );
});
