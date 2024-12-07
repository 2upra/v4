/*
tengo esto en cada POST-sampleList
<div class="botonesRep" >
<div class="reproducirSL" id-post="<? echo $postId; ?>"><? echo $GLOBALS['play'];?></div>
<div class="pausaSL" id-post="<? echo $postId; ?>"><? echo $GLOBALS['pause'];?></div>
</div>

necesito
cada vez que se reproduce un audio y mientras que se este reproduciendo, pausaSL debe ser visible (el que correspond segun la id) si existe
si se pone el mouse sobre POST-sampleList y no se esta reproduciendo debe mostrar reproducirSL
si se pone el mouse sobre POST-sampleList y se esta reproduciendo. debe mostrar pausaSL
si se esta reproduciendo, debe mostrar pausaSL
si se pauso, se oculta pausaSL

dame la parte del codigo que tengo que modifcar
*/

let currentlyPlayingAudio = null;
let audioPlayingStatus = false;

function inicializarWaveforms() {
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
        {threshold: 0.5}
    );

    function setupWaveformContainer(container) {
        if (!container.dataset.initialized) {
            const postId = container.getAttribute('postIDWave');
            const audioUrl = container.getAttribute('data-audio-url');

            if (postId && audioUrl) {
                container.dataset.initialized = 'true';
                observer.observe(container);
            }
        }

        if (!container.dataset.clickListenerAdded) {
            container.addEventListener('click', () => handleWaveformClick(container));
            container.dataset.clickListenerAdded = 'true';
        }
    }

    document.querySelectorAll('.waveform-container').forEach(setupWaveformContainer);

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.POST-sampleList').forEach(post => {
            const reproducirSL = post.querySelector('.reproducirSL');
            const pausaSL = post.querySelector('.pausaSL');
            const postId = reproducirSL.getAttribute('id-post');

            // Function to hide all buttons
            const hideAllButtons = currentPost => {
                document.querySelectorAll('.POST-sampleList').forEach(p => {
                    const rSL = p.querySelector('.reproducirSL');
                    const pSL = p.querySelector('.pausaSL');
                    if (rSL) rSL.style.display = 'none';
                    if (pSL) pSL.style.display = 'none';
                });
            };

            // Function to show the play button
            const showPlayButton = () => {
                hideAllButtons();
                if (reproducirSL) reproducirSL.style.display = 'flex';
            };

            // Function to show the pause button
            const showPauseButton = () => {
                hideAllButtons();
                if (pausaSL) pausaSL.style.display = 'flex';
            };

            // Function to handle waveform click (simplified for clarity)
            const handleWaveformClick = container => {
                const postId = container.getAttribute('postIDWave');
                if (!postId) return;

                const wavesurfer = window.wavesurfers[postId];

                if (currentlyPlayingAudio && currentlyPlayingAudio !== wavesurfer) {
                    currentlyPlayingAudio.pause();
                }

                if (!container.dataset.audioLoaded) {
                    const audioUrl = container.getAttribute('data-audio-url');
                    loadAudio(postId, audioUrl, container, true);
                } else {
                    if (wavesurfer) {
                        if (wavesurfer.isPlaying()) {
                            wavesurfer.pause();
                        } else {
                            wavesurfer.play();
                        }
                    }
                }
            };

            // Event listener for post clicks
            if (!post.dataset.clickListenerAdded) {
                post.addEventListener('click', event => {
                    const waveformContainer = post.querySelector('.waveform-container');
                    if (!event.target.closest('.tags-container') && !event.target.closest('.QSORIW') && waveformContainer) {
                        handleWaveformClick(waveformContainer);
                    }
                });
                post.dataset.clickListenerAdded = 'true';
            }

            // Mouseenter event for showing play/pause buttons
            post.addEventListener('mouseenter', () => {
                if (window.wavesurfers && window.wavesurfers[postId]) {
                    if (window.wavesurfers[postId].isPlaying()) {
                        showPauseButton();
                    } else {
                        showPlayButton();
                    }
                }
            });

            // Mouseleave event for hiding buttons
            post.addEventListener('mouseleave', () => {
                if (window.wavesurfers && window.wavesurfers[postId] && !window.wavesurfers[postId].isPlaying()) {
                    hideAllButtons();
                }
            });

            // Wavesurfer event listeners
            if (window.wavesurfers && window.wavesurfers[postId]) {
                window.wavesurfers[postId].on('play', () => {
                    currentlyPlayingAudio = window.wavesurfers[postId];
                    showPauseButton();
                });

                window.wavesurfers[postId].on('pause', () => {
                    if (currentlyPlayingAudio === window.wavesurfers[postId]) {
                        currentlyPlayingAudio = null;
                    }
                    showPlayButton();
                });

                window.wavesurfers[postId].on('finish', () => {
                    showPlayButton(); // Show play button after audio finishes
                    currentlyPlayingAudio = null; // Reset currently playing audio
                });
            }
        });
    });

    function handleWaveformClick(container) {
        const postId = container.getAttribute('postIDWave');
        if (!postId) return;

        if (audioPlayingStatus && currentlyPlayingAudio !== window.wavesurfers[postId]) {
            if (currentlyPlayingAudio) {
                currentlyPlayingAudio.pause();
            }
            audioPlayingStatus = false;
            currentlyPlayingAudio = null;
        }

        if (!container.dataset.audioLoaded) {
            const audioUrl = container.getAttribute('data-audio-url');
            loadAudio(postId, audioUrl, container, true);
        } else {
            const wavesurfer = window.wavesurfers[postId];
            if (wavesurfer) {
                if (wavesurfer.isPlaying()) {
                    wavesurfer.pause();
                    audioPlayingStatus = false;
                    currentlyPlayingAudio = null;
                } else {
                    wavesurfer.play();
                    audioPlayingStatus = true;
                    currentlyPlayingAudio = wavesurfer;
                }
            }
        }
    }

    window.stopAllWaveSurferPlayers = function () {
        if (currentlyPlayingAudio) {
            currentlyPlayingAudio.pause();
        }
        audioPlayingStatus = false;
        currentlyPlayingAudio = null;
        for (const postId in window.wavesurfers) {
            if (window.wavesurfers[postId].isPlaying()) {
                window.wavesurfers[postId].pause();
            }
        }
    };
}

function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId || container.dataset.audioLoaded) return;
    window.we(postId, audioUrl, container, playOnLoad);
    container.dataset.audioLoaded = 'true';
}

window.we = function (postId, audioUrl, container, playOnLoad = false) {
    if (!window.wavesurfers) window.wavesurfers = {};
    const MAX_RETRIES = 3;

    const loadAndPlayAudioStream = (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

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
                return response.blob();
            })
            .then(blob => {
                const audioBlobUrl = URL.createObjectURL(blob);
                const wavesurfer = initWavesurfer(container);
                window.wavesurfers[postId] = wavesurfer;
                wavesurfer.load(audioBlobUrl);

                const waveformBackground = container.querySelector('.waveform-background');
                if (waveformBackground) waveformBackground.style.display = 'none';

                wavesurfer.on('ready', () => {
                    container.dataset.audioLoaded = 'true';
                    container.querySelector('.waveform-loading').style.display = 'none';
                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';
                    const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

                    if (!waveCargada && !isMobile && !container.closest('.LISTWAVESAMPLE')) {
                        setTimeout(() => {
                            const image = generateWaveformImage(wavesurfer);
                            sendImageToServer(image, postId);
                        }, 1);
                    }
                    if (playOnLoad) {
                        if (audioPlayingStatus) {
                            currentlyPlayingAudio.pause();
                        }
                        wavesurfer.play();
                        audioPlayingStatus = true;
                        currentlyPlayingAudio = wavesurfer;
                    }
                });

                wavesurfer.on('error', () => {
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                });

                wavesurfer.on('finish', () => {
                    audioPlayingStatus = false;
                    currentlyPlayingAudio = null;
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
