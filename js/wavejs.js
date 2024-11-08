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

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker
            .register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registrado:', registration);
            })
            .catch(error => {
                console.error('Error al registrar ServiceWorker:', error);
            });
    });
}

// Modificar la función loadAudio
function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId) return;

    if (!container.dataset.audioLoaded) {
        // Verificar si el Service Worker está activo
        if (navigator.serviceWorker.controller) {
            window.we(postId, audioUrl, container, playOnLoad);
            container.dataset.audioLoaded = 'true';
        } else {
            // Fallback si el Service Worker no está disponible
            window.we(postId, audioUrl, container, playOnLoad);
        }
    }
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

    const loadAndPlayAudioStream = async (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            console.error(`Máximo de intentos alcanzado para postId=${postId}`);
            showError(container, 'Error al cargar el audio después de varios intentos.');
            return;
        }

        try {
            window.audioLoading = true;

            // Preparar URL con nonce
            const urlObj = new URL(audioUrl);
            urlObj.searchParams.append('_wpnonce', audioSettings.nonce);
            const finalAudioUrl = urlObj.toString();

            // Usar fetch con las mismas opciones
            const response = await fetch(finalAudioUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': audioSettings.nonce,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8',
                    Referer: 'https://2upra.com/',
                    Origin: 'https://2upra.com'
                }
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Error de respuesta:', {
                    status: response.status,
                    statusText: response.statusText,
                    errorText: errorText,
                    headers: Object.fromEntries(response.headers.entries())
                });
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const iv = Uint8Array.from(
                atob(response.headers.get('X-Encryption-IV')), 
                c => c.charCodeAt(0)
            );

            const reader = response.body.getReader();
            const decryptedStream = new ReadableStream({
                async start(controller) {
                    const decoder = new TextDecoder();
                    const key = await crypto.subtle.importKey('raw', new TextEncoder().encode(audioSettings.encryptionKey), {name: 'AES-CBC'}, false, ['decrypt']);

                    while (true) {
                        const {done, value} = await reader.read();
                        if (done) break;

                        const decrypted = await crypto.subtle.decrypt({name: 'AES-CBC', iv}, key, value);

                        controller.enqueue(new Uint8Array(decrypted));
                    }

                    controller.close();
                }
            });

            const audioResponse = new Response(stream);
            const blob = await audioResponse.blob();
            const audioBlobUrl = URL.createObjectURL(blob);

            // Inicializar wavesurfer
            const wavesurfer = initWavesurfer(container);
            window.wavesurfers[postId] = wavesurfer;

            wavesurfer.load(audioBlobUrl);

            const waveformBackground = container.querySelector('.waveform-background');
            if (waveformBackground) {
                waveformBackground.style.display = 'none';
            }

            wavesurfer.on('ready', () => {
                window.audioLoading = false;
                container.dataset.audioLoaded = 'true';
                container.querySelector('.waveform-loading').style.display = 'none';
                console.log(`Audio cargado exitosamente - PostID: ${postId}`);

                const waveCargada = container.getAttribute('data-wave-cargada') === 'true';
                const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

                if (!waveCargada && !isMobile && !container.closest('.LISTWAVESAMPLE')) {
                    setTimeout(() => {
                        const image = generateWaveformImage(wavesurfer);
                        sendImageToServer(image, postId);
                        console.log(`Waveform generado - PostID: ${postId}`);
                    }, 1);
                }

                if (playOnLoad) {
                    wavesurfer.play();
                    console.log(`Reproducción iniciada - PostID: ${postId}`);
                }
            });

            wavesurfer.on('error', error => {
                console.error(`Error en wavesurfer - PostID: ${postId}`, error);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            });
        } catch (error) {
            console.error(`Error en la carga - PostID: ${postId}`, error);
            if (retryCount < MAX_RETRIES) {
                console.log(`Reintentando (${retryCount + 1}/${MAX_RETRIES})...`);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            } else {
                showError(container, 'Error al cargar el audio.');
            }
        }
    };

    // Iniciar la carga
    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    // Verifica si el contenedor o alguno de sus elementos padre tiene la clase 'LISTWAVESAMPLE'
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
        backend: 'WebAudio',
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
