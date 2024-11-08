// sw.js
const CACHE_NAME = 'audio-cache-v1';
const API_URL = 'https://2upra.com/wp-json/1/v1/2';

// Función para validar token
async function validateToken(token, nonce) {
    try {
        const response = await fetch('https://2upra.com/wp-json/1/v1/validate-token', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': nonce,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ token })
        });
        return response.ok;
    } catch (error) {
        console.error('Error validando token:', error);
        return false;
    }
}

self.addEventListener('install', (event) => {
    self.skipWaiting();
    event.waitUntil(
        caches.open(CACHE_NAME)
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});

self.addEventListener('fetch', (event) => {
    if (event.request.url.includes('/wp-json/1/v1/2')) {
        event.respondWith(
            (async () => {
                try {
                    const cache = await caches.open(CACHE_NAME);
                    const cachedResponse = await cache.match(event.request);

                    if (cachedResponse) {
                        const url = new URL(event.request.url);
                        const token = url.searchParams.get('token');
                        const nonce = url.searchParams.get('_wpnonce');

                        if (token && nonce) {
                            const isValid = await validateToken(token, nonce);
                            if (isValid) {
                                return cachedResponse;
                            }
                        }
                    }

                    const networkResponse = await fetch(event.request);
                    if (networkResponse.ok) {
                        await cache.put(event.request, networkResponse.clone());
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
            })()
        );
    }
});