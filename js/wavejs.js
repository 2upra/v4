/*

Sigue fallando, cuando doy click a reproducir un audio, a veces se reproduce otro distinto, y cuando vuelvo a llamar inicializarWaveforms porque hay un cambio ajax o algo, todos los auidos se reproducen automaticamente
*/

function inicializarWaveforms() {
    // Detener y destruir todos los wavesurfers existentes
    if (window.wavesurfers) {
        Object.values(window.wavesurfers).forEach(wavesurfer => {
            if (wavesurfer) {
                wavesurfer.pause();
                wavesurfer.destroy();
            }
        });
        window.wavesurfers = {};
    }

    let isInitializing = true;
    setTimeout(() => {
        isInitializing = false;
    }, 1000);

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                if (entry.isIntersecting) {
                    if (!container.dataset.loadTimeoutSet) {
                        const loadTimeout = setTimeout(() => {
                            if (!container.dataset.audioLoaded) {
                                loadAudio(postId, audioUrl, container);
                            }
                        }, 1500);

                        container.dataset.loadTimeout = loadTimeout;
                        container.dataset.loadTimeoutSet = 'true';
                    }
                } else {
                    if (container.dataset.loadTimeoutSet) {
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                }
            });
        },
        {threshold: 0.5}
    );

    // Remover listeners antiguos
    document.querySelectorAll('.POST-sampleList').forEach(post => {
        const oldPost = post.cloneNode(true);
        post.parentNode.replaceChild(oldPost, post);
    });

    // Inicializar wavesurfers observando cada contenedor
    document.querySelectorAll('.waveform-container').forEach(container => {
        container.dataset.audioLoaded = 'false';
        container.dataset.initialized = 'false';
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl) {
            container.dataset.initialized = 'true';
            observer.observe(container);
        }
    });

    // Agregar nuevos listeners
    document.querySelectorAll('.POST-sampleList').forEach(post => {
        post.addEventListener('click', async event => {
            if (isInitializing) return;

            const waveformContainer = post.querySelector('.waveform-container');
            if (!waveformContainer) return;

            // Evitar clicks en elementos específicos
            const clickedElement = event.target;
            if (
                clickedElement.closest('.tags-container') || 
                clickedElement.closest('.QSORIW') ||
                clickedElement.closest('.post-image-container') || 
                clickedElement.closest('.CONTENTLISTSAMPLE')
            ) {
                return;
            }

            // Prevenir múltiples clicks
            if (post.dataset.processing === 'true') return;
            post.dataset.processing = 'true';

            try {
                const postId = waveformContainer.getAttribute('postIDWave');
                const audioUrl = waveformContainer.getAttribute('data-audio-url');

                if (!postId) throw new Error('postIDWave no definido');

                if (!waveformContainer.dataset.audioLoaded) {
                    await loadAudio(postId, audioUrl, waveformContainer);
                }

                const wavesurfer = window.wavesurfers[postId];
                if (wavesurfer) {
                    // Pausar todos los otros audios
                    Object.entries(window.wavesurfers).forEach(([id, ws]) => {
                        if (id !== postId && ws && ws.isPlaying()) {
                            ws.pause();
                        }
                    });

                    if (wavesurfer.isPlaying()) {
                        wavesurfer.pause();
                    } else {
                        wavesurfer.play();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                post.dataset.processing = 'false';
            }
        });
    });
}

// Modificar loadAudio para que sea async
async function loadAudio(postId, audioUrl, container) {
    if (!postId) {
        console.error('postId no está definido en loadAudio.');
        return;
    }
    if (!container.dataset.audioLoaded) {
        await window.we(postId, audioUrl);
        container.dataset.audioLoaded = 'true';
    }
}

window.we = function(postId, audioUrl) {
    return new Promise((resolve, reject) => {
        if (!window.wavesurfers) {
            window.wavesurfers = {};
        }

        const container = document.getElementById(`waveform-${postId}`);
        const MAX_RETRIES = 3;
        console.log('Audio URL:', audioUrl);

        const loadAndPlayAudioStream = (retryCount = 0) => {
            if (retryCount >= MAX_RETRIES) {
                console.error('No se pudo cargar el audio después de varios intentos');
                container.querySelector('.waveform-loading').style.display = 'none';
                container.querySelector('.waveform-message').style.display = 'block';
                container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
                reject(new Error('Máximo número de intentos alcanzado'));
                return;
            }

            window.audioLoading = true;

            fetch(audioUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': audioSettings.nonce,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8'
                }
            })
                .then(response => {
                    if (!response.ok) throw new Error('Respuesta de red no satisfactoria');
                    return response;
                })
                .then(response => {
                    const reader = response.body.getReader();
                    return new ReadableStream({
                        start(controller) {
                            return pump();
                            function pump() {
                                return reader.read().then(({done, value}) => {
                                    if (done) {
                                        controller.close();
                                        return;
                                    }
                                    controller.enqueue(value);
                                    return pump();
                                });
                            }
                        }
                    });
                })
                .then(stream => new Response(stream))
                .then(response => response.blob())
                .then(blob => {
                    const audioBlobUrl = URL.createObjectURL(blob);

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
                        const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

                        const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

                        if (!waveCargada && !isMobile) {
                            if (!container.closest('.LISTWAVESAMPLE')) {
                                setTimeout(() => {
                                    const image = generateWaveformImage(wavesurfer);
                                    sendImageToServer(image, postId);
                                }, 1);
                            }
                        }

                        wavesurfer.play();
                        resolve(wavesurfer); // Resolvemos la promesa cuando el audio está listo
                    });

                    wavesurfer.on('error', (error) => {
                        console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`);
                        if (retryCount < MAX_RETRIES) {
                            setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                        } else {
                            reject(error);
                        }
                    });
                })
                .catch(error => {
                    console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`, error);
                    if (retryCount < MAX_RETRIES) {
                        setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                    } else {
                        reject(error);
                    }
                });
        };

        loadAndPlayAudioStream();
    });
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
            console.error('Error al guardar la imagen:', data.message);
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
    }
}
