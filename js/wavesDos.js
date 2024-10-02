window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    if (!container || !audioSrc) return;

    const cacheKey = `waveform_buffer_${audioSrc}`;
    const cachedBuffer = localStorage.getItem(cacheKey);

    const options = {
        container: container,
        waveColor: '#d9dcff',
        progressColor: '#4353ff',
        backend: 'WebAudio', // Usamos WebAudio para poder manejar buffers
        height: 60,
        barWidth: 2,
        responsive: true
    };

    let wavesurfer = WaveSurfer.create(options);

    if (cachedBuffer) {
        // Si el buffer de audio está en caché, cárgalo
        const audioContext = wavesurfer.backend.getAudioContext();
        const decodedData = new Uint8Array(JSON.parse(cachedBuffer));
        
        audioContext.decodeAudioData(decodedData.buffer, function(buffer) {
            wavesurfer.loadDecodedBuffer(buffer);
        }, function(error) {
            console.error('Error al decodificar el buffer de audio', error);
            wavesurfer.load(audioSrc); // Cargar el archivo de audio si hay un fallo en la caché
        });
    } else {
        // Si no está en caché, carga el audio y guarda el buffer
        wavesurfer.load(audioSrc);
        wavesurfer.on('ready', function () {
            // Almacena el buffer de audio en caché
            const buffer = wavesurfer.backend.buffer;
            const rawData = buffer.getChannelData(0); // Obtener los datos de un canal
            const uintArray = new Uint8Array(rawData.buffer); // Convertir a Uint8Array para guardarlo

            localStorage.setItem(cacheKey, JSON.stringify(Array.from(uintArray)));
        });
    }

    container.addEventListener('click', function () {
        wavesurfer.playPause();
    });
};
