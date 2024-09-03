function inicializarWaveforms() {
    console.log('wave iniciado');
    const loadAndPlayAudio = (container, wavesurfer, src) => {
        console.log('Iniciando carga de audio...');
        window.audioLoading = true;
        container.querySelector('.waveform-loading').style.display = 'block';
        container.querySelector('.waveform-message').style.display = 'none';
        console.log('Mostrando indicador de carga y ocultando mensajes.');

        // Ocultar el fondo de waveform
        const waveformBackground = container.querySelector('.waveform-background');
        if (waveformBackground) {
            waveformBackground.style.display = 'none';
            console.log('Fondo de waveform ocultado.');
        }

        wavesurfer.load(src);
        console.log('Cargando audio desde la fuente:', src);

        wavesurfer.on('ready', () => {
            window.audioLoading = false;
            container.dataset.audioLoaded = 'true';
            container.querySelector('.waveform-loading').style.display = 'none';
            console.log('Audio cargado y listo para reproducir.');

            const waveCargada = container.getAttribute('data-wave-cargada') === 'true';
            console.log('¿Waveform ya cargada?:', waveCargada);

            if (!waveCargada) {
                // Retrasar la exportación de la imagen solo si no está ya guardada
                setTimeout(() => {
                    const image = generateWaveformImage(wavesurfer);
                    const postId = container.getAttribute('postIDWave');
                    console.log('Generando imagen de waveform y enviándola al servidor para el post:', postId);
                    sendImageToServer(image, postId);
                }, 1);
            }
        });

        wavesurfer.on('error', () => {
            console.error('Error al cargar el audio, reintentando...');
            setTimeout(() => loadAndPlayAudio(container, wavesurfer, src), 3000);
        });
    };

    function generateWaveformImage(wavesurfer) {
        const canvas = wavesurfer.getWrapper().querySelector('canvas');
        return canvas.toDataURL('image/png');
    }

    async function sendImageToServer(imageData, postId) {
        console.log('Longitud de los datos de la imagen:', imageData.length);
        if (imageData.length < 100) {
            console.error('Los datos de la imagen parecen ser demasiado cortos');
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

            if (data.success) {
                console.log('Imagen guardada exitosamente:', data.message);
            } else {
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
                            // Si la waveform ya está cargada, simplemente cargar el audio
                            wavesurfer.load(audioSrc);
                        } else {
                            // Si no está cargada, usar loadAndPlayAudio
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
        {rootMargin: '0px', threshold: 0.1}
    );

    document.querySelectorAll('div[id^="waveform-"]').forEach(container => {
        if (container.dataset.initialized !== 'true') {
            observer.observe(container);
        }
    });
}
