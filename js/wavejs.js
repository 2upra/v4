/*
wavejs.js?ver=2.0.12.1483168427:99  No se encontró wavesurfer para postId: 273470

sucede cuando doy click a .POST-sampleList pero no reproduce el audio al menso que de click en la wave

te muestro como se ve la estructura del html

<li class="POST-sampleList EDYQHV" filtro="sampleList" id-post="273470" autor="44" data-registrado="true">



                    <div class="LISTSAMPLE">
                <div class="KLYJBY">
                                    </div>
                    <div class="post-image-container ">
        <img src="https://2upra.com/wp-content/uploads/2024/11/Pinterest_Download-50-576x1024.jpg" alt="Post Image">
    </div>
                <div class="INFOLISTSAMPLE">
                    <p class="CONTENTLISTSAMPLE">
                        </p><p>Punchy 808 Sample</p>
                    <p></p>
                    <div class="TAGSLISTSAMPLE">

                    </div>
                </div>
                <div class="ZQHOQY LISTWAVESAMPLE">
                    <div id="waveform-273470" class="waveform-container without-image" postidwave="273470" data-wave-cargada="true" data-audio-url="https://2upra.com/wp-json/1/v1/audio-pro/273514" data-initialized="true" data-load-timeout="11" data-load-timeout-set="true" data-audio-loaded="true">
                        <div class="waveform-background" style="background-image: url(&quot;https://2upra.com/wp-content/uploads/2024/11/273470_waveform.png&quot;); display: none;"></div>
                        <div class="waveform-message"></div>
                        <div class="waveform-loading" style="display: none;">Cargando...</div>
                    <div></div></div>
                </div>
                    <div class="QSORIW">


*/

function inicializarWaveforms() {
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

    // Inicializar wavesurfers observando cada contenedor
    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl && !container.dataset.initialized) {
            container.dataset.initialized = 'true';
            observer.observe(container);
        }
    });

    // Agregar manejador de clic para los elementos POST-sampleList
    document.querySelectorAll('.POST-sampleList').forEach(post => {
        if (!post.dataset.clickListenerAdded) {
            post.addEventListener('click', () => {
                const waveformContainer = post.querySelector('.waveform-container');
                if (waveformContainer) {
                    const postId = waveformContainer.getAttribute('postIDWave');
                    const audioUrl = waveformContainer.getAttribute('data-audio-url');

                    if (!postId) {
                        console.error('postIDWave no está definido para el contenedor de onda.');
                        return;
                    }

                    if (!waveformContainer.dataset.audioLoaded) {
                        loadAudio(postId, audioUrl, waveformContainer);
                    } else {
                        const wavesurfer = window.wavesurfers[postId];
                        if (wavesurfer) {
                            if (wavesurfer.isPlaying()) {
                                wavesurfer.pause();
                            } else {
                                wavesurfer.play();
                            }
                        }
                    }
                }
            });
            post.dataset.clickListenerAdded = 'true'; // Marcar que el listener ya fue añadido
        }
    });
}

function loadAudio(postId, audioUrl, container) {
    if (!postId) {
        console.error('postId no está definido en loadAudio.');
        return;
    }
    if (!container.dataset.audioLoaded) {
        window.we(postId, audioUrl);
        container.dataset.audioLoaded = 'true';
    }
}

window.we = function (postId, audioUrl) {
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

                // Inicializar wavesurfer y guardarlo en el objeto global
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

                    // Reproducir automáticamente cuando esté listo
                    wavesurfer.play();
                });

                wavesurfer.on('error', () => {
                    console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`);
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                });
            })
            .catch(error => {
                console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`, error);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            });
    };

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
            console.error('Error al guardar la imagen:', data.message);
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
    }
}

// Inicializa los reproductores de audio cuando el DOM está completamente cargado
document.addEventListener('DOMContentLoaded', inicializarWaveforms);
