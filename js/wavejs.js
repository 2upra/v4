function initializeAllAudioPlayers() {
    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl && !container.dataset.initialized) {
            container.dataset.initialized = 'true';
            observeWaveform(container, postId, audioUrl);
        }
    });
}

function observeWaveform(container, postId, audioUrl) {
    let loadTimeout = null;

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                loadTimeout = setTimeout(() => {
                    if (!container.dataset.audioLoaded) {
                        loadAudio(postId, audioUrl, container);
                    }
                }, 10000); 
            } else {

                clearTimeout(loadTimeout);
            }
        });
    }, { threshold: 0.5 }); 


    observer.observe(container);

    container.addEventListener('click', () => {
        if (!container.dataset.audioLoaded) {
            clearTimeout(loadTimeout); 
            loadAudio(postId, audioUrl, container);
        }
    });
}


function loadAudio(postId, audioUrl, container) {
    if (!container.dataset.audioLoaded) {
        window.we(postId, audioUrl); 
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
            credentials: 'include', // Incluye las cookies de sesión en la solicitud
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
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
            //cuando ya esta cargada
            .then(stream => new Response(stream))
            .then(response => response.blob())
            .then((blob) => {
                container.querySelector('.waveform-loading').style.display = 'block';
                container.querySelector('.waveform-message').style.display = 'none';
                const audioBlobUrl = URL.createObjectURL(blob);

                wavesurfer = initWavesurfer(container);
                wavesurfer.load(audioBlobUrl);
                wavesurfer.on('ready', () => {
                    const waveformBackground = container.querySelector('.waveform-background');
                    if (waveformBackground) {
                        waveformBackground.style.display = 'none';
                    }
                    window.audioLoading = false;
                    container.dataset.audioLoaded = 'true';
                    container.querySelector('.waveform-loading').style.display = 'none';
                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

                    if (!waveCargada) {
                        setTimeout(() => {
                            const image = generateWaveformImage(wavesurfer);
                            const postId = container.getAttribute('postIDWave');
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
    gradient.addColorStop(0.551, '#d43333'); // Cambia aquí si prefieres otros colores
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

// Observador para cargar el audio cuando el contenedor de la forma de onda está en el viewport
function inicializarWaveforms() {
    initializeAllAudioPlayers();
    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && entry.target.dataset.initialized !== 'true') {
                    const container = entry.target;
                    const audioSrc = container.getAttribute('data-audio-url');
                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

                    if (audioSrc && !window.audioLoading) {
                        container.dataset.audioLoaded = 'false';

                        if (waveCargada) {
                            loadAudio(container.getAttribute('postIDWave'), audioSrc);
                        } else {
                            const loadTimer = setTimeout(() => {
                                if (container.dataset.audioLoaded === 'false') {
                                    loadAudio(container.getAttribute('postIDWave'), audioSrc);
                                }
                            }, 5000);
                        }
                    }

                    container.dataset.initialized = 'true';
                    observer.unobserve(container);
                }
            });
        },
        { rootMargin: '0px', threshold: 0.1 }
    );

    document.querySelectorAll('div[id^="waveform-"]').forEach((container) => {
        if (container.dataset.initialized !== 'true') {
            observer.observe(container);
        }
    });
}