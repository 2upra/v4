window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    if (!container || !audioSrc) return;

    const cacheKey = `waveform_buffer_${audioSrc}`;
    const cachedBuffer = localStorage.getItem(cacheKey);

    const options = {
        container: container,
        waveColor: '#d9dcff',
        progressColor: '#4353ff',
        backend: 'WebAudio',
        height: 60,
        barWidth: 2,
        responsive: true
    };

    let wavesurfer = WaveSurfer.create(options);

    // Función para decodificar el audio y cargarlo en WaveSurfer
    const loadAudioBuffer = (audioData) => {
        const audioContext = wavesurfer.backend.getAudioContext();
        audioContext.decodeAudioData(audioData, function(buffer) {
            if (buffer) {
                console.log('Buffer decodificado correctamente.');
                wavesurfer.loadDecodedBuffer(buffer); // Carga el buffer en WaveSurfer
            } else {
                console.error('No se pudo decodificar el buffer de audio.');
                wavesurfer.load(audioSrc); // Si falla, cargar el archivo de audio original
            }
        }, function(error) {
            console.error('Error al decodificar el buffer de audio:', error);
            wavesurfer.load(audioSrc); // Cargar el archivo de audio si falla el buffer
        });
    };

    if (cachedBuffer) {
        console.log('Buffer encontrado en caché.');
        // Si el buffer está en caché, cárgalo
        const decodedData = new Uint8Array(JSON.parse(cachedBuffer)).buffer;
        loadAudioBuffer(decodedData);
    } else {
        console.log('No se encontró buffer en caché, cargando audio desde la fuente.');
        // Si no está en caché, carga el audio y guarda el buffer
        wavesurfer.load(audioSrc);

        wavesurfer.on('ready', function () {
            console.log('WaveSurfer está listo.');
            const buffer = wavesurfer.backend.buffer;

            if (buffer) {
                console.log('El buffer está listo.');
                // Convierte el buffer a Uint8Array para almacenarlo en localStorage
                const rawData = buffer.getChannelData(0); // Obtener el canal 0
                const uintArray = new Uint8Array(rawData.buffer);

                // Guardar el buffer en caché
                localStorage.setItem(cacheKey, JSON.stringify(Array.from(uintArray)));
                console.log('Buffer guardado en caché.');
            } else {
                console.error('El buffer no está disponible o es undefined.');
            }
        });

        // Manejo de errores durante la carga del audio
        wavesurfer.on('error', function (error) {
            console.error('Error al cargar el archivo de audio:', error);
        });
    }

    // Reproduce o pausa el audio al hacer clic en el contenedor
    container.addEventListener('click', function () {
        wavesurfer.playPause();
    });
};