const CACHE_NAME = 'mediconnect-v2';

const STATIC_ASSETS = [
    '/',
    '/assets/css/main.css',
    '/assets/css/auth.css',
    '/assets/css/dashboard.css',
    '/assets/css/consultation.css',
    '/assets/css/responsive.css',
    '/assets/js/main.js',
    '/assets/js/notifications.js',
    '/assets/js/consultation.js',
    '/assets/img/icons/icon-192.png',
    '/assets/img/icons/icon-512.png',
];

self.addEventListener('install', function(event) {
    event.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_ASSETS);
        })
    );
});

self.addEventListener('activate', function(event) {
    event.waitUntil(
        caches.keys().then(function(cacheNames) {
            return Promise.all(
                cacheNames.map(function(name) {
                    if (name !== CACHE_NAME) {
                        return caches.delete(name);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', function(event) {
    event.respondWith(
        caches.match(event.request).then(function(response) {
            if (response) {
                return response;
            }
            return fetch(event.request).catch(function() {
                // Return offline page if available
                return caches.match('/');
            });
        })
    );
});
