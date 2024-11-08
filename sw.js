const CACHE_NAME = 'audio-cache-v1';

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(clients.claim());
});

self.addEventListener('fetch', (event) => {
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(
            caches.open(CACHE_NAME)
                .then(async (cache) => {
                    // Intentar obtener desde caché
                    const cachedResponse = await cache.match(event.request);
                    if (cachedResponse) {
                        return cachedResponse;
                    }

                    // Si no está en caché, hacer la petición
                    try {
                        const networkResponse = await fetch(event.request);
                        if (networkResponse.ok) {
                            // Clonar la respuesta antes de cachear
                            cache.put(event.request, networkResponse.clone());
                            return networkResponse;
                        }
                        throw new Error('Network response was not ok');
                    } catch (error) {
                        console.error('Fetch error:', error);
                        return new Response('Error loading audio', {
                            status: 500,
                            headers: { 'Content-Type': 'text/plain' }
                        });
                    }
                })
        );
    }
});