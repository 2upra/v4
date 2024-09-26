function loadAudio(postId, audioUrl) {
    fetch(audioUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.blob(); // Convierto el audio a un Blob
        })
        .then(blob => {
            const audioUrl = URL.createObjectURL(blob); // Creo una URL de objeto para el Blob
            const container = document.getElementById(`waveform-${postId}`);
            const wavesurfer = WaveSurfer.create({
                container: container,
                waveColor: '#D9DCFF',
                progressColor: '#4353FF',
                backend: 'MediaElement',
                barWidth: 3,
                height: 128,
            });
            wavesurfer.load(audioUrl); // Cargar la URL del Blob en WaveSurfer
        })
        .catch(error => console.error('Error al cargar el audio:', error));
}


function inicializarWaveforms() {
    const MAX_RETRIES = 3; // Límite de reintentos

    const loadAndPlayAudio = (container, wavesurfer, src, retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            console.error('No se pudo cargar el audio después de varios intentos');
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

        window.audioLoading = true;
        container.querySelector('.waveform-loading').style.display = 'block';
        container.querySelector('.waveform-message').style.display = 'none';
        const waveformBackground = container.querySelector('.waveform-background');
        if (waveformBackground) {
            waveformBackground.style.display = 'none';
        }

        wavesurfer.load(src);

        wavesurfer.on('ready', () => {
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
        });

        wavesurfer.on('error', () => {
            console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`);
            setTimeout(() => loadAndPlayAudio(container, wavesurfer, src, retryCount + 1), 3000);
        });
    };

    function generateWaveformImage(wavesurfer) {
        const canvas = wavesurfer.getWrapper().querySelector('canvas');
        return canvas.toDataURL('image/png');
    }

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

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting && entry.target.dataset.initialized !== 'true') {
                    const container = entry.target;
                    const audioSrc = container.getAttribute('data-audio-url');
                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

                    let wavesurfer;

                    const initWavesurfer = () => {
                        const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : 102;
                        const ctx = document.createElement('canvas').getContext('2d');
                        const gradient = ctx.createLinearGradient(0, 0, 0, 500);
                        const progressGradient = ctx.createLinearGradient(0, 0, 0, 500);
                        [gradient, progressGradient].forEach(g => {
                            ['0', '0.55', '0.551', '0.552', '0.553', '1'].forEach(stop => {
                                g.addColorStop(parseFloat(stop), g === gradient ? '#FFFFFF' : '#d43333');
                            });
                        });

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
                    };

                    if (audioSrc && !window.audioLoading) {
                        wavesurfer = initWavesurfer();
                        container.dataset.audioLoaded = 'false';

                        if (waveCargada) {
                            wavesurfer.load(audioSrc);
                        } else {
                            const loadTimer = setTimeout(() => {
                                if (container.dataset.audioLoaded === 'false') {
                                    loadAndPlayAudio(container, wavesurfer, audioSrc);
                                }
                            }, 5000);
                        }
                    }

                    if (wavesurfer) {
                        container.addEventListener('click', () => {
                            if (container.dataset.audioLoaded === 'false') {
                                loadAndPlayAudio(container, wavesurfer, audioSrc);
                            } else {
                                wavesurfer.playPause();
                            }
                        });
                    }

                    container.dataset.initialized = 'true';
                    observer.unobserve(container);
                }
            });
        },
        { rootMargin: '0px', threshold: 0.1 }
    );

    document.querySelectorAll('div[id^="waveform-"]').forEach(container => {
        if (container.dataset.initialized !== 'true') {
            observer.observe(container);
        }
    });
}