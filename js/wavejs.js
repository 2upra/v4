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
                    }, 5000); // Reduce el tiempo de espera a 5 segundos

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
    }, { threshold: 0.1 }); // Reduce el umbral a 10%

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
                } else {
                    // Si el audio ya está cargado, simplemente reproducir o pausar
                    const wavesurfer = container.wavesurferInstance;
                    if (wavesurfer) {
                        if (wavesurfer.isPlaying()) {
                            wavesurfer.pause();
                        } else {
                            wavesurfer.play();
                        }
                    }
                }
            });
        }
    });
}

function loadAudio(postId, audioUrl, container) {
    if (!container.dataset.audioLoaded) {
        window.we(postId, audioUrl, container);
    }
}

window.we = function (postId, audioUrl, container) {
    const containerId = `waveform-${postId}`;
    const waveformElement = document.getElementById(containerId);

    if (!waveformElement) {
        console.error(`No se encontró el contenedor con ID ${containerId}`);
        return;
    }

    let wavesurfer = container.wavesurferInstance;

    if (wavesurfer) {
        // Si ya existe una instancia de WaveSurfer, no crear otra
        return;
    }

    wavesurfer = initWavesurfer(container);

    // Guardar la instancia de WaveSurfer en el contenedor para futuras referencias
    container.wavesurferInstance = wavesurfer;

    wavesurfer.on('ready', () => {
        container.dataset.audioLoaded = 'true';
        const loadingEl = container.querySelector('.waveform-loading');
        if (loadingEl) loadingEl.style.display = 'none';

        const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

        if (!waveCargada) {
            setTimeout(() => {
                // Generar y enviar la imagen de la forma de onda si es necesario
                const image = generateWaveformImage(wavesurfer);
                sendImageToServer(image, postId);
            }, 1);
        }

        // Opcional: Puedes reproducir automáticamente después de cargar
        // wavesurfer.play();
    });

    wavesurfer.on('error', (e) => {
        console.error('Error en WaveSurfer:', e);
        const loadingEl = container.querySelector('.waveform-loading');
        if (loadingEl) loadingEl.style.display = 'none';
        const messageEl = container.querySelector('.waveform-message');
        if (messageEl) {
            messageEl.style.display = 'block';
            messageEl.textContent = 'Error al cargar el audio.';
        }
    });

    wavesurfer.load(audioUrl);
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

    const wavesurfer = WaveSurfer.create({
        container: container,
        waveColor: gradient,
        progressColor: progressGradient,
        backend: 'MediaElement', // Usar el backend MediaElement
        interact: true,
        barWidth: 2,
        height: containerHeight,
        partialRender: true,
        responsive: true, // Hace que el waveform sea responsivo
    });

    // Opcional: Manejar eventos adicionales si es necesario
    // Por ejemplo, actualizar la interfaz cuando se pausa o se reproduce

    return wavesurfer;
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