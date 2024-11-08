// sw.js
const CACHE_NAME = 'audio-cache-v1';

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
    // Intercepta solo las solicitudes de audio específicas
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(async function() {
            const cache = await caches.open(CACHE_NAME);
            const cachedResponse = await cache.match(event.request);

            // Si existe en cache y está autorizado, úsalo
            if (cachedResponse && isValidRequest(event.request)) {
                return cachedResponse;
            }

            // Si no está en cache o no es válida, intenta fetch
            try {
                const networkResponse = await fetch(event.request.clone(), {
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-WP-Nonce': event.request.headers.get('X-WP-Nonce')
                    }
                });

                if (networkResponse.ok && isValidResponse(networkResponse)) {
                    const responseClone = networkResponse.clone();
                    cache.put(event.request, responseClone);
                    return networkResponse;
                } else {
                    throw new Error('Respuesta de red no válida');
                }
            } catch (error) {
                // Manejar el error, volver a cache si existe una respuesta válida
                if (cachedResponse) return cachedResponse;
                throw error;
            }
        }());
    }
});
