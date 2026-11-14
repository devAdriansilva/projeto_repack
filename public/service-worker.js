/**
 * Projeto Repack - Service Worker PWA Offline-First
 * Baseado estritamente em docs/FSD.md e AGENTS.md
 */

const CACHE_NAME = 'repack-pwa-v1.74';

// Determina dinamicamente o caminho base a partir do escopo registrado do Repack
const SCOPE_URL = new URL(self.registration.scope);
const BASE_PATH = SCOPE_URL.pathname.replace(/\/$/, '');
const getAssetUrl = (path) => BASE_PATH + path;

const STATIC_ASSETS = [
    getAssetUrl(''),
    getAssetUrl('/operador'),
    getAssetUrl('/operador/avaria/passo1'),
    getAssetUrl('/operador/avaria/passo2'),
    getAssetUrl('/operador/avaria/resumo'),
    getAssetUrl('/operador/cronometro'),
    getAssetUrl('/operador/cronometro/resumo'),
    getAssetUrl('/operador/baixa'),
    getAssetUrl('/assets/css/bootstrap.min.css'),
    getAssetUrl('/assets/css/urna.css'),
    getAssetUrl('/assets/js/bootstrap.bundle.min.js'),
    getAssetUrl('/assets/js/touch-handler.js'),
    getAssetUrl('/assets/js/app-offline.js'),
    getAssetUrl('/assets/js/xlsx.mini.min.js'),
    getAssetUrl('/assets/img/icon-192.png'),
    getAssetUrl('/assets/img/icon-512.png'),
    getAssetUrl('/assets/img/apple-touch-icon.png'),
    getAssetUrl('/assets/img/favicon.png'),
    getAssetUrl('/manifest.json')
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

    // Não intercepta chamadas de API ou requisições fora do escopo do Repack
    if (url.pathname.includes('/api/') || !url.pathname.startsWith(BASE_PATH)) {
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
                        return caches.match(getAssetUrl('/operador'));
                    }
                });
            })
    );
});
