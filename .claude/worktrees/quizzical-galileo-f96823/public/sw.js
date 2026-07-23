/**
 * MagDyn HRMS Service Worker
 * Provides: offline shell caching, background sync, push notifications
 */

var CACHE_NAME = 'hrms-v2';
var STATIC_ASSETS = [
    '/',
    '/assets/css/magdyn-base.css',
    '/assets/js/shortcuts.js',
    '/offline.html',
];

// ── Install ──────────────────────────────────────────────────────
self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(STATIC_ASSETS);
        }).then(function () {
            return self.skipWaiting();
        })
    );
});

// ── Activate ─────────────────────────────────────────────────────
self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(
                keys.filter(function (key) { return key !== CACHE_NAME; })
                    .map(function (key) { return caches.delete(key); })
            );
        }).then(function () {
            return self.clients.claim();
        })
    );
});

// ── Fetch — network-first for API, cache-first for static ────────
self.addEventListener('fetch', function (e) {
    var url = new URL(e.request.url);

    // Skip non-GET and cross-origin
    if (e.request.method !== 'GET') return;
    if (url.origin !== location.origin) return;

    // API / dynamic routes → network first, fall back to offline page
    var isPage = e.request.headers.get('Accept') &&
                 e.request.headers.get('Accept').indexOf('text/html') !== -1;

    if (isPage) {
        e.respondWith(
            fetch(e.request).catch(function () {
                return caches.match('/offline.html');
            })
        );
        return;
    }

    // Static assets → cache first
    e.respondWith(
        caches.match(e.request).then(function (cached) {
            return cached || fetch(e.request).then(function (response) {
                if (response && response.status === 200) {
                    var clone = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(e.request, clone);
                    });
                }
                return response;
            });
        })
    );
});

// ── Push Notifications ───────────────────────────────────────────
self.addEventListener('push', function (e) {
    var data = {};
    try { data = e.data ? e.data.json() : {}; } catch (err) {}

    var title   = data.title   || 'HRMS Notification';
    var body    = data.body    || 'You have a new notification.';
    var icon    = data.icon    || '/assets/img/icon-192.png';
    var badge   = data.badge   || '/assets/img/icon-192.png';
    var url     = data.url     || '/';
    var tag     = data.tag     || 'hrms-notif';

    e.waitUntil(
        self.registration.showNotification(title, {
            body:    body,
            icon:    icon,
            badge:   badge,
            tag:     tag,
            data:    { url: url },
            actions: [
                { action: 'open',    title: 'Open' },
                { action: 'dismiss', title: 'Dismiss' }
            ]
        })
    );
});

// ── Notification click ───────────────────────────────────────────
self.addEventListener('notificationclick', function (e) {
    e.notification.close();

    if (e.action === 'dismiss') return;

    var targetUrl = (e.notification.data && e.notification.data.url) || '/';

    e.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (list) {
            for (var i = 0; i < list.length; i++) {
                if (list[i].url === targetUrl && 'focus' in list[i]) {
                    return list[i].focus();
                }
            }
            return clients.openWindow(targetUrl);
        })
    );
});
