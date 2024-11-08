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
                            //'Accept': 'audio/mpeg',
                            //'Range': 'bytes=0-',
                            'X-WP-Nonce': event.request.headers.get('X-WP-Nonce')
                        }
                    });

                    if (!response.ok && response.status !== 206) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // No cachear respuestas parciales
                    if (response.status === 206) {
                        return response;
                    }

                    const clonedResponse = response.clone();
                    cache.put(event.request, clonedResponse);
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