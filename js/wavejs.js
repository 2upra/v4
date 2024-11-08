function inicializarWaveforms() {
    //console.log('Inicializando waveforms...');

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                //console.log(`Observando contenedor: postId=${postId}, isIntersecting=${entry.isIntersecting}`);

                if (entry.isIntersecting) {
                    if (!container.dataset.loadTimeoutSet) {
                        //console.log(`Estableciendo timeout para cargar audio: postId=${postId}`);

                        const loadTimeout = setTimeout(() => {
                            if (!container.dataset.audioLoaded) {
                                //console.log(`Timeout alcanzado, cargando audio: postId=${postId}`);
                                loadAudio(postId, audioUrl, container, false); // No reproducir automáticamente
                            }
                        }, 1500);

                        container.dataset.loadTimeout = loadTimeout;
                        container.dataset.loadTimeoutSet = 'true';
                    }
                } else {
                    if (container.dataset.loadTimeoutSet) {
                        //console.log(`Despejando timeout para postId=${postId} porque ya no está visible`);
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                }
            });
        },
        {threshold: 0.5}
    );

    // Inicializar wavesurfers observando cada contenedor
    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');

        if (postId && audioUrl) {
            if (!container.dataset.initialized) {
                //console.log(`Observando contenedor por primera vez: postId=${postId}`);
                container.dataset.initialized = 'true';
                observer.observe(container);
            } else {
                //console.log(`Contenedor ya estaba inicializado: postId=${postId}`);
            }
        } else {
            //console.error(`Contenedor con postId=${postId} no tiene atributos completos`);
        }
    });

    // Agregar manejador de clic para los elementos POST-sampleList
    document.querySelectorAll('.POST-sampleList').forEach(post => {
        if (!post.dataset.clickListenerAdded) {
            post.addEventListener('click', event => {
                const waveformContainer = post.querySelector('.waveform-container');

                const clickedElement = event.target;
                if (clickedElement.closest('.tags-container') || clickedElement.closest('.QSORIW') || clickedElement.closest('.post-image-container') || clickedElement.closest('.CONTENTLISTSAMPLE')) {
                    //console.log('Clic ignorado por estar dentro de un contenedor excluido.');
                    return;
                }

                if (waveformContainer) {
                    const postId = waveformContainer.getAttribute('postIDWave');
                    const audioUrl = waveformContainer.getAttribute('data-audio-url');

                    if (!postId) {
                        //console.error('postIDWave no está definido para el contenedor de onda.');
                        return;
                    }

                    //console.log(`Clic en postId=${postId}. Verificando si el audio ya está cargado...`);

                    if (!waveformContainer.dataset.audioLoaded) {
                        //console.log(`Audio no cargado aún para postId=${postId}. Cargando ahora...`);
                        loadAudio(postId, audioUrl, waveformContainer, true); // Cargar y reproducir
                    } else {
                        //console.log(`Audio ya cargado para postId=${postId}. Reproduciendo/Pausando...`);
                        const wavesurfer = window.wavesurfers[postId];
                        if (wavesurfer) {
                            if (wavesurfer.isPlaying()) {
                                wavesurfer.pause();
                                //console.log(`Audio pausado para postId=${postId}`);
                            } else {
                                wavesurfer.play();
                                //console.log(`Audio reproduciendo para postId=${postId}`);
                            }
                        } else {
                            //console.error(`No se encontró wavesurfer para postId=${postId}`);
                        }
                    }
                }
            });
            post.dataset.clickListenerAdded = 'true';
            //console.log(`Manejador de clic añadido a postId=${post.getAttribute('postIDWave')}`);
        }
    });

    // Agregar manejador de clic para los elementos waveform-container
    document.querySelectorAll('.waveform-container').forEach(container => {
        if (!container.dataset.clickListenerAdded) {
            container.addEventListener('click', () => {
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                if (!postId) {
                    //console.error('postIDWave no está definido para el contenedor de onda.');
                    return;
                }

                //console.log(`Clic en waveform-container con postId=${postId}. Verificando si el audio ya está cargado...`);

                if (!container.dataset.audioLoaded) {
                    //console.log(`Audio no cargado aún para postId=${postId}. Cargando ahora...`);
                    loadAudio(postId, audioUrl, container, true); // Cargar y reproducir
                } else {
                    //console.log(`Audio ya cargado para postId=${postId}. Reproduciendo/Pausando...`);
                    const wavesurfer = window.wavesurfers[postId];
                    if (wavesurfer) {
                        if (wavesurfer.isPlaying()) {
                            wavesurfer.pause();
                            //console.log(`Audio pausado para postId=${postId}`);
                        } else {
                            wavesurfer.play();
                            //console.log(`Audio reproduciendo para postId=${postId}`);
                        }
                    } else {
                        //console.error(`No se encontró wavesurfer para postId=${postId}`);
                    }
                }
            });
            container.dataset.clickListenerAdded = 'true';
            //console.log(`Manejador de clic añadido a waveform-container con postId=${postId}`);
        }
    });
}

async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            console.log('Intentando registrar Service Worker...');
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/',
                updateViaCache: 'none'
            });
            console.log('Service Worker registrado:', registration);

            registration.addEventListener('statechange', e => {
                console.log('Service Worker state changed:', e.target.state);
            });

            return registration;
        } catch (error) {
            console.error('Error registrando Service Worker:', error);
            return null;
        }
    }
    return null;
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    registerServiceWorker();
});

/*
tengo este problema
Service Worker registrado: 
ServiceWorkerRegistration { installing: null, waiting: null, active: ServiceWorker, navigationPreload: NavigationPreloadManager, scope: "https://2upra.com/", updateViaCache: "none", onupdatefound: null, pushManager: PushManager }
wavejs.js:153:21
Cargando audio: 
Object { postId: "301085", audioUrl: "https://2upra.com/wp-json/1/v1/2?token=MzAxMDkyfDE5MjQ5MDU2MDB8Y2FjaGVkfDQ4NGI1NzljNjc5NzhhM2YwYjIzMzkwMmJlYmQ1MTJjfDk5OTk5OXw2M2ViOTg1ZGJiZmU4OTQ2YjEwNDNhMDhlNThjMzFmMzIxN2ZjNWFmNjFhMmQ2YzgxNzNiYWE0MGZkZjJjZTRl&_wpnonce=15d7d107c6&ts=1731039933&sig=61eeffd397a52481edc5ee0d5ab88fa3fb16f64d7f604a8d9d9b3376201d8aa3" }
wavejs.js:177:13
Usando Service Worker para cargar audio wavejs.js:182:25
Verificando configuración de audio: 
Object { nonce: "Presente", url: "https://2upra.com/sample/mellow-autumnal-melody/", origin: "https://2upra.com" }
wavejs.js:198:13
Iniciando carga de audio - PostID: 301085 wavejs.js:231:13
Uncaught (in promise) DOMException: The buffer passed to decodeAudioData contains an unknown content type. 
*/
function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId || container.dataset.audioLoaded) return;

    console.log('Cargando audio:', {postId, audioUrl});

    const loadWithServiceWorker = async () => {
        try {
            if (navigator.serviceWorker.controller) {
                console.log('Usando Service Worker para cargar audio');
                await window.we(postId, audioUrl, container, playOnLoad);
            } else {
                console.log('Service Worker no disponible, usando carga normal');
                await window.we(postId, audioUrl, container, playOnLoad);
            }
            container.dataset.audioLoaded = 'true';
        } catch (error) {
            console.error('Error cargando audio:', error);
        }
    };

    loadWithServiceWorker();
}

function verifyAudioSettings() {
    console.log('Verificando configuración de audio:', {
        nonce: audioSettings?.nonce ? 'Presente' : 'Ausente',
        url: window.location.href,
        origin: window.location.origin
    });
}

function showError(container, message) {
    const loadingEl = container.querySelector('.waveform-loading');
    const messageEl = container.querySelector('.waveform-message');

    if (loadingEl) loadingEl.style.display = 'none';
    if (messageEl) {
        messageEl.style.display = 'block';
        messageEl.textContent = message;
    }
}

function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId || container.dataset.audioLoaded) return;

    console.log('Cargando audio:', {postId, audioUrl});

    const loadWithServiceWorker = async () => {
        try {
            if (navigator.serviceWorker.controller) {
                console.log('Usando Service Worker para cargar audio');
                await window.we(postId, audioUrl, container, playOnLoad);
            } else {
                console.log('Service Worker no disponible, usando carga normal');
                await window.we(postId, audioUrl, container, playOnLoad);
            }
            container.dataset.audioLoaded = 'true';
        } catch (error) {
            console.error('Error cargando audio:', error);
        }
    };

    loadWithServiceWorker();
}

window.we = function (postId, audioUrl, container, playOnLoad = false) {
    // Verificaciones iniciales
    verifyAudioSettings();

    if (!audioSettings || !audioSettings.nonce) {
        console.error('audioSettings no está configurado correctamente');
        showError(container, 'Error de configuración');
        return;
    }

    if (!window.wavesurfers) {
        window.wavesurfers = {};
    }

    const MAX_RETRIES = 3;
    console.log(`Iniciando carga de audio - PostID: ${postId}`);

    async function loadAndPlayAudioStream(retryCount = 0) {
        try {
            window.audioLoading = true;
    
            const urlObj = new URL(audioUrl);
            if (!urlObj.searchParams.has('_wpnonce')) {
                urlObj.searchParams.append('_wpnonce', audioSettings.nonce);
            }
            const finalAudioUrl = urlObj.toString();
    
            const response = await fetch(finalAudioUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': audioSettings.nonce,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'audio/mpeg',
                    'Range': 'bytes=0-'
                }
            });
    
            if (!response.ok && response.status !== 206) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
    
            // Obtener el contenido como blob directamente
            const blob = await response.blob();
            let audioBlob = blob;
    
            // Si hay encriptación, manejarla
            const iv = response.headers.get('X-Encryption-IV');
            if (iv && audioSettings.key) {
                try {
                    const arrayBuffer = await blob.arrayBuffer();
                    const keyMaterial = await crypto.subtle.importKey(
                        'raw',
                        new TextEncoder().encode(audioSettings.key),
                        { name: 'AES-CBC', length: 256 },
                        false,
                        ['decrypt']
                    );
    
                    const ivArray = new Uint8Array(
                        atob(iv)
                            .split('')
                            .map(c => c.charCodeAt(0))
                    );
    
                    const decryptedData = await crypto.subtle.decrypt(
                        {
                            name: 'AES-CBC',
                            iv: ivArray
                        },
                        keyMaterial,
                        arrayBuffer
                    );
    
                    audioBlob = new Blob([decryptedData], { type: 'audio/mpeg' });
                } catch (decryptError) {
                    console.error('Error en la desencriptación:', decryptError);
                    // Si falla la desencriptación, intentamos usar el blob original
                    audioBlob = blob;
                }
            }
    
            const blobUrl = URL.createObjectURL(audioBlob);
    
            // Inicializar wavesurfer con configuración optimizada
            const wavesurfer = WaveSurfer.create({
                container: container,
                waveColor: '#D1D6DA',
                progressColor: '#2D5BFF',
                cursorColor: '#2D5BFF',
                barWidth: 2,
                barRadius: 3,
                cursorWidth: 1,
                height: 100,
                barGap: 3,
                normalize: true,
                responsive: true,
                fillParent: true,
                backend: 'MediaElement',
                mediaControls: true,
                xhr: {
                    cache: true,
                    mode: 'cors',
                    credentials: 'same-origin',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            });
    
            window.wavesurfers[postId] = wavesurfer;
    
            // Manejar eventos de wavesurfer
            wavesurfer.on('error', error => {
                console.error('WaveSurfer error:', error);
                if (retryCount < MAX_RETRIES) {
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                }
            });
    
            // Cargar el audio
            wavesurfer.load(blobUrl);
    
            wavesurfer.on('ready', () => {
                window.audioLoading = false;
                container.dataset.audioLoaded = 'true';
                
                const loadingElement = container.querySelector('.waveform-loading');
                if (loadingElement) {
                    loadingElement.style.display = 'none';
                }
    
                const waveformBackground = container.querySelector('.waveform-background');
                if (waveformBackground) {
                    waveformBackground.style.display = 'none';
                }
    
                if (playOnLoad) {
                    wavesurfer.play();
                }
            });
    
            // Limpiar recursos cuando se destruya
            wavesurfer.on('destroy', () => {
                URL.revokeObjectURL(blobUrl);
            });
    
        } catch (error) {
            console.error('Load error:', error);
            if (retryCount < MAX_RETRIES) {
                console.log(`Reintentando (${retryCount + 1}/${MAX_RETRIES})...`);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            } else {
                showError(container, 'Error al cargar el audio.');
            }
        }
    }

    // Iniciar la carga
    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    const isListWaveSample = container.classList.contains('LISTWAVESAMPLE') || container.parentElement.classList.contains('LISTWAVESAMPLE');

    const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : isListWaveSample ? 45 : 102;

    const ctx = document.createElement('canvas').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 500);
    const progressGradient = ctx.createLinearGradient(0, 0, 0, 500);

    // Configuración de los colores del gradiente
    gradient.addColorStop(0, '#FFFFFF');
    gradient.addColorStop(0.55, '#FFFFFF');
    gradient.addColorStop(0.551, '#d43333');
    gradient.addColorStop(1, '#d43333');

    progressGradient.addColorStop(0, '#d43333');
    progressGradient.addColorStop(1, '#d43333');

    return WaveSurfer.create({
        container: container,
        waveColor: gradient,
        progressColor: progressGradient,
        backend: 'MediaElement',
        interact: true,
        barWidth: 2,
        height: containerHeight,
        partialRender: true
    });
}

// Función para generar la imagen de la forma de onda
function generateWaveformImage(wavesurfer) {
    const canvas = wavesurfer.getWrapper().querySelector('canvas');
    return canvas.toDataURL('image/png');
}

// Función para enviar la imagen generada al servidor
async function sendImageToServer(imageData, postId) {
    if (imageData.length < 100) {
        return;
    }

    // Convertir la cadena base64 a Blob
    const byteString = atob(imageData.split(',')[1]);
    const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    const blob = new Blob([ab], {type: mimeString});

    const formData = new FormData();
    formData.append('action', 'save_waveform_image');
    formData.append('image', blob, 'waveform.png');
    formData.append('post_id', postId);

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            //console.error('Error al guardar la imagen:', data.message);
        }
    } catch (error) {
        //console.error('Error en la solicitud:', error);
    }
}

// Inicializa los reproductores de audio cuando el DOM está completamente cargado
document.addEventListener('DOMContentLoaded', inicializarWaveforms);
