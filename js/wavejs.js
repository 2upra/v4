const CONSTANTS = {
    LOAD_DELAY: 20000,
    RETRY_DELAY: 3000,
    MAX_RETRIES: 3,
    THRESHOLD: 0.5
};

class AudioCache {
    static cache = new Map();
    static set(key, audio) { this.cache.set(key, audio); }
    static get(key) { return this.cache.get(key); }
    static has(key) { return this.cache.has(key); }
}

class WaveformManager {
    constructor() {
        this.observer = new IntersectionObserver(
            entries => entries.forEach(entry => this.handleIntersection(entry)),
            { threshold: CONSTANTS.THRESHOLD }
        );
        this.loadQueue = new Map();
    }

    init() {
        document.querySelectorAll('.waveform-container').forEach(container => {
            if (!container.dataset.initialized) {
                this.initContainer(container);
            }
        });
    }

    initContainer(container) {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl) {
            container.dataset.initialized = 'true';
            this.observer.observe(container);
            container.addEventListener('click', () => this.handleClick(container, postId, audioUrl));
        }
    }

    handleIntersection(entry) {
        const container = entry.target;
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');

        if (entry.isIntersecting) {
            if (!container.dataset.loadTimeoutSet) {
                const timeout = setTimeout(() => {
                    if (!container.dataset.audioLoaded) {
                        this.loadAudio(postId, audioUrl, container);
                    }
                }, CONSTANTS.LOAD_DELAY);
                container.dataset.loadTimeout = timeout;
                container.dataset.loadTimeoutSet = 'true';
            }
        } else {
            this.clearLoadTimeout(container);
        }
    }

    handleClick(container, postId, audioUrl) {
        if (!container.dataset.audioLoaded) {
            this.clearLoadTimeout(container);
            this.loadAudio(postId, audioUrl, container);
        }
    }

    clearLoadTimeout(container) {
        if (container.dataset.loadTimeoutSet) {
            clearTimeout(parseInt(container.dataset.loadTimeout));
            delete container.dataset.loadTimeout;
            delete container.dataset.loadTimeoutSet;
        }
    }

    async loadAudio(postId, audioUrl, container) {
        if (container.dataset.audioLoaded) return;

        try {
            const wavesurfer = this.initWavesurfer(container);
            const audioBlob = await this.fetchAudio(audioUrl);
            const audioBlobUrl = URL.createObjectURL(audioBlob);

            wavesurfer.load(audioBlobUrl);
            this.setupWaveformEvents(wavesurfer, container, postId);
            container.dataset.audioLoaded = 'true';
        } catch (error) {
            console.error('Error loading audio:', error);
            this.showError(container);
        }
    }

    async fetchAudio(audioUrl, retryCount = 0) {
        try {
            const response = await fetch(audioUrl, { credentials: 'include' });
            if (!response.ok) throw new Error('Network response was not ok');
            return await response.blob();
        } catch (error) {
            if (retryCount < CONSTANTS.MAX_RETRIES) {
                await new Promise(resolve => setTimeout(resolve, CONSTANTS.RETRY_DELAY));
                return this.fetchAudio(audioUrl, retryCount + 1);
            }
            throw error;
        }
    }

    initWavesurfer(container) {
        const height = container.classList.contains('waveform-container-venta') ? 60 : 102;
        const gradients = this.createGradients();

        return WaveSurfer.create({
            container,
            waveColor: gradients.wave,
            progressColor: gradients.progress,
            backend: 'WebAudio',
            interact: true,
            barWidth: 2,
            height,
            partialRender: true
        });
    }

    createGradients() {
        const ctx = document.createElement('canvas').getContext('2d');
        const wave = ctx.createLinearGradient(0, 0, 0, 500);
        const progress = ctx.createLinearGradient(0, 0, 0, 500);

        wave.addColorStop(0, '#FFFFFF');
        wave.addColorStop(0.55, '#FFFFFF');
        wave.addColorStop(0.551, '#d43333');
        wave.addColorStop(1, '#d43333');

        progress.addColorStop(0, '#d43333');
        progress.addColorStop(1, '#d43333');

        return { wave, progress };
    }

    setupWaveformEvents(wavesurfer, container, postId) {
        wavesurfer.on('ready', () => {
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-background')?.style.display = 'none';

            if (!container.getAttribute('data-wave-cargada') && !this.isMobile()) {
                this.generateAndSendWaveform(wavesurfer, postId);
            }

            container.addEventListener('click', () => {
                wavesurfer.isPlaying() ? wavesurfer.pause() : wavesurfer.play();
            });
        });
    }

    isMobile() {
        return /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);
    }

    showError(container) {
        container.querySelector('.waveform-loading').style.display = 'none';
        const messageEl = container.querySelector('.waveform-message');
        messageEl.style.display = 'block';
        messageEl.textContent = 'Error al cargar el audio.';
    }
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