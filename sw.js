// En el Service Worker (sw.js)
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
                    try {
                        // Intentar obtener desde caché
                        const cachedResponse = await cache.match(event.request);
                        if (cachedResponse) {
                            return cachedResponse;
                        }

                        // Si no está en caché, hacer la petición
                        const networkResponse = await fetch(event.request.clone(), {
                            credentials: 'same-origin',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8'
                            }
                        });

                        if (networkResponse.ok) {
                            // Verificar que sea audio
                            const contentType = networkResponse.headers.get('content-type');
                            if (contentType && contentType.includes('audio')) {
                                // Clonar y cachear
                                cache.put(event.request, networkResponse.clone());
                                return networkResponse;
                            }
                        }
                        
                        throw new Error('Invalid audio response');
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