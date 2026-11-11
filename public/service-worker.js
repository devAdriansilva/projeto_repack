/**
 * Projeto Repack - Service Worker PWA Offline-First
 * Baseado estritamente em docs/FSD.md e AGENTS.md
 */

const CACHE_NAME = 'repack-pwa-v1.37';
const STATIC_ASSETS = [
    '/',
    '/operador',
    '/operador/avaria/passo1',
    '/operador/avaria/passo2',
    '/operador/avaria/resumo',
    '/operador/cronometro',
    '/operador/cronometro/resumo',
    '/assets/css/bootstrap.min.css',
    '/assets/css/urna.css',
    '/assets/js/bootstrap.bundle.min.js',
    '/assets/js/touch-handler.js',
    '/assets/js/app-offline.js',
    '/manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS).catch((err) => {
                console.warn('Alguns assets falharam no pré-cache:', err);
            });
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys.map((key) => {
                    if (key !== CACHE_NAME) {
                        return caches.delete(key);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Não intercepta chamadas de API com cache estático
    if (url.pathname.startsWith('/api/')) {
        return;
    }

    event.respondWith(
        fetch(event.request)
            .then((networkResponse) => {
                if (networkResponse && networkResponse.status === 200 && event.request.method === 'GET') {
                    const responseClone = networkResponse.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(event.request, responseClone);
                    });
                }
                return networkResponse;
            })
            .catch(() => {
                return caches.match(event.request).then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    if (event.request.mode === 'navigate') {
                        return caches.match('/operador');
                    }
                });
            })
    );
});
