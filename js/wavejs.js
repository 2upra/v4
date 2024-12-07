function inicializarWaveforms() {
    let currentlyPlayingAudio = null;

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                if (entry.isIntersecting) {
                    if (!container.dataset.loadTimeoutSet) {
                        container.dataset.loadTimeout = setTimeout(() => {
                            if (!container.dataset.audioLoaded) {
                                loadAudio(postId, audioUrl, container, false);
                            }
                        }, 1500);
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
        { threshold: 0.5 }
    );

    document.querySelectorAll('.waveform-container').forEach(container => {
        if (!container.dataset.initialized) {
            const postId = container.getAttribute('postIDWave');
            const audioUrl = container.getAttribute('data-audio-url');

            if (postId && audioUrl) {
                container.dataset.initialized = 'true';
                observer.observe(container);
            }
        }

        if (!container.dataset.clickListenerAdded) {
            container.addEventListener('click', () => {
                handleWaveformClick(container);
            });
            container.dataset.clickListenerAdded = 'true';
        }
    });

    document.querySelectorAll('.POST-sampleList').forEach(post => {
        if (!post.dataset.clickListenerAdded) {
            post.addEventListener('click', event => {
                const waveformContainer = post.querySelector('.waveform-container');
                const clickedElement = event.target;

                if (clickedElement.closest('.tags-container') || clickedElement.closest('.QSORIW')) {
                    return;
                }

                if (waveformContainer) {
                    handleWaveformClick(waveformContainer);
                }
            });
            post.dataset.clickListenerAdded = 'true';
        }
    });

    function handleWaveformClick(container) {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');

        if (!postId) return;

        if (currentlyPlayingAudio && (currentlyPlayingAudio !== window.wavesurfers[postId])) {
                currentlyPlayingAudio.pause();
                currentlyPlayingAudio = null;
        }

        
        if (!container.dataset.audioLoaded) {
            if(currentlyPlayingAudio){
                currentlyPlayingAudio.pause();
                currentlyPlayingAudio = null;
            }
            loadAudio(postId, audioUrl, container, true);
        } else {
            const wavesurfer = window.wavesurfers[postId];
            if (wavesurfer) {
                if (wavesurfer.isPlaying()) {
                    wavesurfer.pause();
                    currentlyPlayingAudio = null;
                } else {
                    if(currentlyPlayingAudio){
                        currentlyPlayingAudio.pause();
                        currentlyPlayingAudio = null;
                    }
                    wavesurfer.play();
                    currentlyPlayingAudio = wavesurfer;
                }
            }
        }
    }

    window.stopAllWaveSurferPlayers = function () {
        if (currentlyPlayingAudio) {
            currentlyPlayingAudio.pause();
            currentlyPlayingAudio = null;
        }
        for (const postId in window.wavesurfers) {
            if (window.wavesurfers[postId].isPlaying()) {
                window.wavesurfers[postId].pause();
            }
        }
    };
}

function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId) {
        return;
    }
    if (!container.dataset.audioLoaded) {
        window.we(postId, audioUrl, container, playOnLoad);
        container.dataset.audioLoaded = 'true';
    }
}

window.we = function (postId, audioUrl, container, playOnLoad = false) {
    if (!window.wavesurfers) {
        window.wavesurfers = {};
    }
    const MAX_RETRIES = 3;
    const loadAndPlayAudioStream = (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

        window.audioLoading = true;

        fetch(audioUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': audioSettings.nonce,
                'X-Requested-With': 'XMLHttpRequest'
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
                    if (playOnLoad) {
                        wavesurfer.play();
                        currentlyPlayingAudio = wavesurfer;
                    }
                });

                wavesurfer.on('error', () => {
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                });
            })
            .catch(error => {
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            });
    };

    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    // Verifica si el contenedor o alguno de sus elementos padre tiene la clase 'LISTWAVESAMPLE'
    const isListWaveSample = container.classList.contains('LISTWAVESAMPLE') || container.parentElement.classList.contains('LISTWAVESAMPLE');

    const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : isListWaveSample ? 40 : 90;

    const ctx = document.createElement('canvas').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 500);
    const progressGradient = ctx.createLinearGradient(0, 0, 0, 500);

    // Configuración de los colores del gradiente
    gradient.addColorStop(0, '#848484');
    gradient.addColorStop(0.55, '#848484');
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
