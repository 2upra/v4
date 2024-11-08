// sw.js
const CACHE_NAME = 'audio-cache-v1';

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

// sw.js
self.addEventListener('fetch', event => {
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async cache => {
                try {
                    const response = await fetch(event.request.clone(), {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Verificar que el tipo de contenido sea correcto
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('audio/')) {
                        throw new Error('Invalid content type');
                    }

                    // Clonar y cachear la respuesta
                    const responseToCache = response.clone();
                    cache.put(event.request, responseToCache);

                    return response;
                } catch (error) {
                    console.error('Fetch error:', error);
                    const cachedResponse = await cache.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    throw error;
                }
            })
        );
    }
});