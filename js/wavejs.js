function inicializarWaveforms() {
    const observer = new IntersectionObserver(
        entries =>
            entries.forEach(entry => {
                const container = entry.target;
                const {postIDWave: postId, dataset} = container;
                const audioUrl = container.getAttribute('data-audio-url');

                if (entry.isIntersecting && !dataset.loadTimeoutSet) {
                    dataset.loadTimeout = setTimeout(() => !dataset.audioLoaded && loadAudio(postId, audioUrl, container), 20000);
                    dataset.loadTimeoutSet = 'true';
                } else if (!entry.isIntersecting && dataset.loadTimeoutSet) {
                    clearTimeout(dataset.loadTimeout);
                    delete dataset.loadTimeout;
                    delete dataset.loadTimeoutSet;
                }
            }),
        {threshold: 0.5}
    );

    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');
        if (postId && audioUrl && !container.dataset.initialized) {
            container.dataset.initialized = 'true';
            observer.observe(container);
            container.addEventListener('click', () => {
                if (!container.dataset.audioLoaded) {
                    container.dataset.loadTimeoutSet && (clearTimeout(container.dataset.loadTimeout), delete container.dataset.loadTimeout, delete container.dataset.loadTimeoutSet);
                    loadAudio(postId, audioUrl, container);
                }
            });
        }
    });
}

const loadAudio = (postId, audioUrl, container) => !container.dataset.audioLoaded && (window.we(postId, audioUrl), (container.dataset.audioLoaded = 'true'));

window.we = (postId, audioUrl) => {
    const container = document.getElementById(`waveform-${postId}`);
    const MAX_RETRIES = 3;
    let wavesurfer;

    const loadAndPlayAudioStream = async (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            console.error('No se pudo cargar el audio después de varios intentos');
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

        try {
            window.audioLoading = true;
            const response = await fetch(audioUrl, {credentials: 'include'});
            if (!response.ok) throw new Error('Respuesta de red no satisfactoria');

            const blob = await response.blob();
            wavesurfer = initWavesurfer(container);
            wavesurfer.load(URL.createObjectURL(blob));

            const backgroundElement = container.querySelector('.waveform-background');
            if (backgroundElement) {
                backgroundElement.style.display = 'none';
            }

            wavesurfer.on('ready', () => {
                window.audioLoading = false;
                container.dataset.audioLoaded = 'true';
                container.querySelector('.waveform-loading').style.display = 'none';

                if (!container.getAttribute('data-wave-cargada') && !/Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent)) {
                    setTimeout(() => sendImageToServer(generateWaveformImage(wavesurfer), postId), 1);
                }

                container.addEventListener('click', () => wavesurfer[wavesurfer.isPlaying() ? 'pause' : 'play']());
            });

            wavesurfer.on('error', () => {
                console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            });
        } catch (error) {
            console.error(`Error al cargar el audio. Intento ${retryCount + 1} de ${MAX_RETRIES}`, error);
            setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
        }
    };

    loadAndPlayAudioStream();
};

const initWavesurfer = container => {
    const ctx = document.createElement('canvas').getContext('2d');
    const [gradient, progressGradient] = [ctx.createLinearGradient(0, 0, 0, 500), ctx.createLinearGradient(0, 0, 0, 500)];

    gradient.addColorStop(0, '#FFFFFF');
    gradient.addColorStop(0.55, '#FFFFFF');
    gradient.addColorStop(0.551, '#d43333');
    gradient.addColorStop(1, '#d43333');
    progressGradient.addColorStop(0, '#d43333');
    progressGradient.addColorStop(1, '#d43333');

    return WaveSurfer.create({
        container,
        waveColor: gradient,
        progressColor: progressGradient,
        backend: 'WebAudio',
        interact: true,
        barWidth: 2,
        height: container.classList.contains('waveform-container-venta') ? 60 : 102,
        partialRender: true
    });
};

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
