// sw.js
const CACHE_NAME = 'audio-cache-v1';

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Cache opened');
                return cache;
            })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        Promise.all([
            clients.claim(),
            // Limpiar caches antiguas
            caches.keys().then(cacheNames => {
                return Promise.all(
                    cacheNames.map(cacheName => {
                        if (cacheName !== CACHE_NAME) {
                            return caches.delete(cacheName);
                        }
                    })
                );
            })
        ])
    );
});

self.addEventListener('fetch', event => {
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async cache => {
                // Intentar obtener de caché primero
                const cachedResponse = await cache.match(event.request);
                if (cachedResponse) {
                    return cachedResponse;
                }

                try {
                    const response = await fetch(event.request.clone(), {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            //'Accept': 'audio/mpeg',
                            //'Range': 'bytes=0-',
                            'X-WP-Nonce': event.request.headers.get('X-WP-Nonce'),
                            'Cache-Control': 'max-age=86400'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Cachear solo respuestas completas
                    if (response.status === 200) {
                        await cache.put(event.request, response.clone());
                    }

                    return response;
                } catch (error) {
                    console.error('Fetch error:', error);
                    throw error;
                }
            })
        );
    }
});