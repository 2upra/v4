// sw.js
const CACHE_NAME = 'audio-cache-v1';

// Log helper
const swLog = (message) => {
    console.log(`[ServiceWorker] ${message}`);
};

self.addEventListener('install', event => {
    swLog('Installing...');
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    swLog('Activating...');
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
    if (!event.request.url.includes('/wp-json/1/v1/2')) {
        return;
    }

    swLog(`Interceptando petición: ${event.request.url}`);
    
    event.respondWith(
        caches.open(CACHE_NAME)
            .then(cache => {
                return cache.match(event.request)
                    .then(cachedResponse => {
                        if (cachedResponse) {
                            swLog('Retornando respuesta cacheada');
                            return cachedResponse;
                        }

                        swLog('Haciendo petición a la red');
                        return fetch(event.request)
                            .then(networkResponse => {
                                swLog('Cacheando nueva respuesta');
                                cache.put(event.request, networkResponse.clone());
                                return networkResponse;
                            })
                            .catch(error => {
                                swLog(`Error en fetch: ${error}`);
                                throw error;
                            });
                    });
            })
    );
});