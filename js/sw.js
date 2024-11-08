// sw.js
const CACHE_NAME = 'audio-cache-v1';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME));
});

self.addEventListener('fetch', (event) => {
    // Solo interceptar peticiones de audio
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(
            caches.open(CACHE_NAME).then(async (cache) => {
                // Verificar si hay una respuesta en caché
                const cachedResponse = await cache.match(event.request);
                
                if (cachedResponse) {
                    // Verificar validez del token en la URL
                    const url = new URL(event.request.url);
                    const token = url.searchParams.get('token');
                    
                    try {
                        // Hacer una petición ligera para validar el token
                        const validationResponse = await fetch('/wp-json/1/v1/validate-token', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-WP-Nonce': url.searchParams.get('_wpnonce'),
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ token })
                        });

                        if (validationResponse.ok) {
                            return cachedResponse;
                        }
                    } catch (error) {
                        console.error('Error validando token:', error);
                    }
                }

                // Si no hay caché o el token no es válido, hacer nueva petición
                try {
                    const networkResponse = await fetch(event.request);
                    
                    if (networkResponse.ok) {
                        // Clonar la respuesta antes de cachear
                        cache.put(event.request, networkResponse.clone());
                        return networkResponse;
                    }
                    
                    throw new Error('Network response was not ok');
                } catch (error) {
                    console.error('Fetch failed:', error);
                    return new Response('Error loading audio', { status: 500 });
                }
            })
        );
    }
});