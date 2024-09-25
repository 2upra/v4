function inicializarWaveforms() {
    const maxRetries = 3; // Número máximo de reintentos
    const retryDelay = 3000; // Retraso entre reintentos en milisegundos

    const loadAndPlayAudio = (container, wavesurfer, src, attempt = 1) => {
        if (attempt > maxRetries) {
            // Mostrar mensaje de error al usuario
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            console.error(`No se pudo cargar el audio después de ${maxRetries} intentos.`);
            return;
        }

        window.audioLoading = true;
        container.querySelector('.waveform-loading').style.display = 'block';
        container.querySelector('.waveform-message').style.display = 'none';

        // Ocultar el fondo de waveform
        const waveformBackground = container.querySelector('.waveform-background');
        if (waveformBackground) {
            waveformBackground.style.display = 'none';
        }

        wavesurfer.load(src);

        const onReady = () => {
            window.audioLoading = false;
            container.dataset.audioLoaded = 'true';
            container.querySelector('.waveform-loading').style.display = 'none';

            const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

            if (!waveCargada) {
                const image = generateWaveformImage(wavesurfer);
                if (image) {
                    const postId = container.getAttribute('postIDWave');
                    sendImageToServer(image, postId);
                } else {
                    console.error('No se pudo generar la imagen del waveform.');
                }
            }

            wavesurfer.un('ready', onReady);
            wavesurfer.un('error', onError);
        };

        const onError = (e) => {
            console.error(`Error al cargar el audio: ${e}`);
            wavesurfer.un('ready', onReady);
            wavesurfer.un('error', onError);

            // Intentar recargar después de un retraso
            setTimeout(() => {
                loadAndPlayAudio(container, wavesurfer, src, attempt + 1);
            }, retryDelay);
        };

        wavesurfer.on('ready', onReady);
        wavesurfer.on('error', onError);
    };

    const generateWaveformImage = (wavesurfer) => {
        if (wavesurfer.drawer && wavesurfer.drawer.canvases && wavesurfer.drawer.canvases[0]) {
            const canvas = wavesurfer.drawer.canvases[0].wave;
            return canvas.toDataURL('image/png');
        } else {
            console.error('Cannot generate waveform image. Canvases are not properly initialized.');
            return null;
        }
    };

    const sendImageToServer = async (imageData, postId) => {
        if (imageData.length < 100) return;

        // Convertir la cadena base64 a Blob
        const response = await fetch(imageData);
        const blob = await response.blob();

        const formData = new FormData();
        formData.append('action', 'save_waveform_image');
        formData.append('image', blob, 'waveform.png');
        formData.append('post_id', postId);

        try {
            const res = await fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            });

            const data = await res.json();

            if (!data.success) {
                console.error('Error al guardar la imagen:', data.message);
            }
        } catch (error) {
            console.error('Error en la solicitud:', error);
        }
    };

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && entry.target.dataset.initialized !== 'true') {
                    const container = entry.target;
                    const audioSrc = container.getAttribute('data-audio-url');
                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';

                    if (audioSrc && !window.audioLoading) {
                        const wavesurfer = initWavesurfer(container);
                        container.dataset.audioLoaded = 'false';

                        if (waveCargada) {
                            wavesurfer.load(audioSrc);
                        } else {
                            setTimeout(() => {
                                if (container.dataset.audioLoaded === 'false') {
                                    loadAndPlayAudio(container, wavesurfer, audioSrc);
                                }
                            }, 5000);
                        }

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

    const initWavesurfer = (container) => {
        const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : 102;
        const gradient = getGradient('#FFFFFF');
        const progressGradient = getGradient('#d43333');

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

    const getGradient = (color) => {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        const gradient = ctx.createLinearGradient(0, 0, 0, 500);

        gradient.addColorStop(0, color);
        gradient.addColorStop(1, color);

        return gradient;
    };

    document.querySelectorAll('div[id^="waveform-"]').forEach((container) => {
        if (container.dataset.initialized !== 'true') {
            observer.observe(container);
        }
    });
}