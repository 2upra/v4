// sw.js
const CACHE_NAME = 'audio-cache-v1';

self.addEventListener('install', event => {
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', event => {
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async cache => {
                // Verificar caché
                const cachedResponse = await cache.match(event.request);
                if (cachedResponse) {
                    return cachedResponse;
                }

                // Clonar la solicitud original
                const fetchRequest = event.request.clone();

                try {
                    const response = await fetch(fetchRequest, {
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-WP-Nonce': fetchRequest.headers.get('X-WP-Nonce'),
                            'Accept': 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8'
                        }
                    });

                    if (response.ok) {
                        cache.put(event.request, response.clone());
                        return response;
                    }
                    
                    throw new Error(`HTTP error! status: ${response.status}`);
                } catch (error) {
                    console.error('Fetch error:', error);
                    return new Response('Error loading audio', {
                        status: 500,
                        headers: { 'Content-Type': 'audio/mpeg' }
                    });
                }
            })
        );
    }
});