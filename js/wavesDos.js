window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    if (!container || !audioSrc) return;

    const cacheKey = `waveform_buffer_${audioSrc}`;
    const cachedBuffer = localStorage.getItem(cacheKey);

    const options = {
        container: container,
        waveColor: '#d9dcff',
        progressColor: '#4353ff',
        backend: 'WebAudio', // Necesario para usar buffers
        height: 60,
        barWidth: 2,
        responsive: true
    };

    let wavesurfer = WaveSurfer.create(options);

    const loadAudioBuffer = (audioData) => {
        const audioContext = wavesurfer.backend.getAudioContext();

        // Decodificar el buffer de audio
        audioContext.decodeAudioData(audioData, function(buffer) {
            if (buffer) {
                wavesurfer.loadDecodedBuffer(buffer); // Carga el buffer decodificado en WaveSurfer
            } else {
                console.error('No se pudo decodificar el buffer de audio.');
                wavesurfer.load(audioSrc); // Si falla, cargar el archivo de audio original
            }
        }, function(error) {
            console.error('Error al decodificar el buffer de audio:', error);
            wavesurfer.load(audioSrc); // Si hay un error, cargar el archivo de audio original
        });
    };

    if (cachedBuffer) {
        // Si el buffer de audio está en caché, cárgalo
        const decodedData = new Uint8Array(JSON.parse(cachedBuffer)).buffer;
        loadAudioBuffer(decodedData);
    } else {
        // Si no está en caché, carga el audio y guarda el buffer
        wavesurfer.load(audioSrc);
        wavesurfer.on('ready', function () {
            if (wavesurfer.backend.buffer) {
                const buffer = wavesurfer.backend.buffer;
                const rawData = buffer.getChannelData(0); // Obtener datos de un canal (esto es un Float32Array)
                const uintArray = new Uint8Array(rawData.buffer); // Convertir a Uint8Array para guardar en localStorage

                localStorage.setItem(cacheKey, JSON.stringify(Array.from(uintArray)));
            } else {
                console.error('El buffer no está listo o es undefined.');
            }
        });
    }

    container.addEventListener('click', function () {
        wavesurfer.playPause();
    });
};
