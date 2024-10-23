function inicializarWaveforms() {
    const observer = new IntersectionObserver((entries) => {
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
                    }, 20000); // Carga el audio después de 20 segundos de estar en el viewport

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
    }, { threshold: 0.5 });

    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl && !container.dataset.initialized) {
            container.dataset.initialized = 'true';
            observer.observe(container);
            container.addEventListener('click', () => {
                if (!container.dataset.audioLoaded) {
                    if (container.dataset.loadTimeoutSet) {
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                    loadAudio(postId, audioUrl, container);
                }
            });
        }
    });
}

function loadAudio(postId, audioUrl, container) {
    if (!container.dataset.audioLoaded) {
        // Añadir nonce a la URL si es necesario
        const secureUrl = audioUrl + (audioUrl.includes('?') ? '&' : '?') + '_wpnonce=' + wpApiSettings.nonce;
        window.we(postId, secureUrl);
        container.dataset.audioLoaded = 'true';
    }
}


window.we = function (postId, audioUrl) {
    const container = document.getElementById(`waveform-${postId}`);
    const MAX_RETRIES = 3;
    let wavesurfer;

    const loadAndPlayAudioStream = (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            console.error('No se pudo cargar el audio después de varios intentos');
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

        window.audioLoading = true;

        fetch(audioUrl, {
            credentials: 'same-origin', // Importante para las cookies de sesión
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response;
            })
            .then((response) => {
                const reader = response.body.getReader();
                return new ReadableStream({
                    start(controller) {
                        return pump();
                        function pump() {
                            return reader.read().then(({ done, value }) => {
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
            .then((blob) => {
                const audioBlobUrl = URL.createObjectURL(blob);
            
                wavesurfer = initWavesurfer(container);
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
            
                    // Detectar si el usuario está en móvil
                    const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);
            
                    if (!waveCargada && !isMobile) {
                        setTimeout(() => {
                            const image = generateWaveformImage(wavesurfer);
                            sendImageToServer(image, postId);
                        }, 1);
                    }
            
                    container.addEventListener('click', () => {
                        if (wavesurfer.isPlaying()) {
                            wavesurfer.pause();
                        } else {
                            wavesurfer.play();
                        }
                    });
                });
            
                wavesurfer.on('error', () => {
                    console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`);
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                });
            })
            .catch((error) => {
                console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`, error);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            });
    };

    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : 102;
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
        partialRender: true,
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
    const blob = new Blob([ab], { type: mimeString });

    const formData = new FormData();
    formData.append('action', 'save_waveform_image');
    formData.append('image', blob, 'waveform.png');
    formData.append('post_id', postId);

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
        });

        const data = await response.json();

        if (!data.success) {
            console.error('Error al guardar la imagen:', data.message);
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
    }
}

// Inicializa los reproductores de audio cuando el DOM está completamente cargado
document.addEventListener('DOMContentLoaded', inicializarWaveforms);