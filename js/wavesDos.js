window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    if (!container || !audioSrc) return;

    const cacheKey = `waveform_buffer_${audioSrc}`; // Clave de caché basada solo en el audioSrc
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
        audioContext.decodeAudioData(audioData, (buffer) => {
            if (buffer) {
                console.log('Buffer decodificado correctamente.');
                wavesurfer.loadDecodedBuffer(buffer); // Carga el buffer en WaveSurfer
            } else {
                console.error('No se pudo decodificar el buffer de audio.');
                wavesurfer.load(audioSrc); // Si falla, cargar el archivo de audio original
            }
        }, (error) => {
            console.error('Error al decodificar el buffer de audio:', error);
            wavesurfer.load(audioSrc); // Cargar el archivo de audio si falla el buffer
        }).catch(err => {
            console.error('Error inesperado al decodificar el buffer de audio:', err);
            wavesurfer.load(audioSrc);
        });
    };

    if (cachedBuffer) {
        try {
            console.log('Buffer encontrado en caché.');
            // Convertir el caché en un ArrayBuffer
            const audioData = base64ToArrayBuffer(cachedBuffer);
            loadAudioBuffer(audioData);
        } catch (error) {
            console.error('Error al cargar el buffer desde caché. Recargando desde fuente:', error);
            wavesurfer.load(audioSrc);
        }
    } else {
        console.log('No se encontró buffer en caché, cargando audio desde la fuente.');
        wavesurfer.load(audioSrc);

        wavesurfer.on('ready', () => {
            console.log('WaveSurfer está listo.');

            try {
                if (wavesurfer.backend && wavesurfer.backend.buffer) {
                    const buffer = wavesurfer.backend.buffer;
                    console.log('El buffer está listo.');

                    // Convertir el buffer a un ArrayBuffer
                    const arrayBuffer = bufferToArrayBuffer(buffer);

                    // Guardar el buffer en caché como base64
                    localStorage.setItem(cacheKey, arrayBufferToBase64(arrayBuffer));
                    console.log('Buffer guardado en caché.');
                } else {
                    console.error('El backend o el buffer no están disponibles.');
                }
            } catch (error) {
                console.error('Error al intentar acceder al buffer:', error);
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

    // Función para convertir un AudioBuffer a ArrayBuffer
    function bufferToArrayBuffer(buffer) {
        const numberOfChannels = buffer.numberOfChannels;
        const length = buffer.length * numberOfChannels * Float32Array.BYTES_PER_ELEMENT;
        const result = new ArrayBuffer(length);
        const view = new DataView(result);

        let offset = 0;

        for (let channel = 0; channel < numberOfChannels; channel++) {
            const channelData = buffer.getChannelData(channel);
            for (let i = 0; i < channelData.length; i++) {
                view.setFloat32(offset, channelData[i], true);
                offset += Float32Array.BYTES_PER_ELEMENT;
            }
        }

        return result;
    }

    // Función para convertir ArrayBuffer a base64
    function arrayBufferToBase64(buffer) {
        let binary = '';
        const bytes = new Uint8Array(buffer);
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    // Función para convertir base64 a ArrayBuffer
    function base64ToArrayBuffer(base64) {
        const binaryString = window.atob(base64);
        const len = binaryString.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binaryString.charCodeAt(i);
        }
        return bytes.buffer;
    }
};