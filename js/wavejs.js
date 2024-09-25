function inicializarWaveforms() {
    const maxRetryAttempts = 5; // Máximo número de reintentos para cargar el audio

    const loadAndPlayAudio = (container, wavesurfer, src, retries = 0) => {
        if (retries > maxRetryAttempts) {
            console.error(`Error: No se pudo cargar el audio tras ${maxRetryAttempts} intentos.`);
            container.querySelector('.waveform-message').style.display = 'block';
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

            wavesurfer.setPlaybackRate(1.2);

            if (wavesurfer.pitchShift) {
                wavesurfer.pitchShift.setPitch(3);
            }

            if (!container.dataset.waveCargada) {
                requestAnimationFrame(() => {
                    const image = generateWaveformImage(wavesurfer);
                    const postId = container.getAttribute('postIDWave');
                    sendImageToServer(image, postId);
                });
            }
        });

        wavesurfer.on('error', () => {
            setTimeout(() => {
                loadAndPlayAudio(container, wavesurfer, src, retries + 1);
            }, 3000); // Retraso de 3 segundos antes del reintento
        });
    };

    function generateWaveformImage(wavesurfer) {
        const canvas = wavesurfer.getWrapper().querySelector('canvas');
        return canvas.toDataURL('image/png');
    }

    async function sendImageToServer(imageData, postId) {
        if (imageData.length < 100) return;

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

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
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
                            }, 5000); // Retraso de 5 segundos solo si no está cargado
                        }

                        container.addEventListener('click', () => {
                            if (container.dataset.audioLoaded === 'false') {
                                loadAndPlayAudio(container, wavesurfer, audioSrc);
                            } else {
                                wavesurfer.playPause();
                            }
                        }, { once: true });

                        container.dataset.initialized = 'true';
                        observer.unobserve(container);
                    }
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

    const initWavesurfer = (container) => {
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
}
