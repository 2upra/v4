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
                            'Accept': 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8',
                            'X-WP-Nonce': event.request.headers.get('X-WP-Nonce')
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Verificar y transformar la respuesta si es necesario
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('audio/')) {
                        // Intentar convertir la respuesta a un formato de audio válido
                        const blob = await response.blob();
                        const audioBlob = new Blob([blob], { type: 'audio/mpeg' });
                        const newResponse = new Response(audioBlob, {
                            status: 200,
                            headers: new Headers({
                                'Content-Type': 'audio/mpeg'
                            })
                        });
                        
                        cache.put(event.request, newResponse.clone());
                        return newResponse;
                    }

                    cache.put(event.request, response.clone());
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