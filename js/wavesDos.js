window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    
    if (container && audioSrc) {
        // Crear clave única para almacenar los picos en localStorage, usando el nombre del archivo
        const peaksKey = `waveform_peaks_${audioSrc}`;
        console.log(`Clave de los picos: ${peaksKey}`);

        // Configurar opciones básicas de WaveSurfer
        const options = {
            container: container,
            waveColor: '#d9dcff',
            progressColor: '#4353ff',
            backend: 'WebAudio',
            height: 60,
            barWidth: 2,
            responsive: true
        };

        // Verificar si ya tenemos picos almacenados en localStorage
        const storedPeaks = localStorage.getItem(peaksKey);
        if (storedPeaks) {
            // Si encontramos picos almacenados, convertimos el string a un array y lo pasamos a WaveSurfer
            options.peaks = JSON.parse(storedPeaks);
            console.log('Picos cargados desde localStorage:', options.peaks);
        } else {
            console.log('No se encontraron picos almacenados. Se generarán nuevos picos.');
        }

        // Crear instancia de WaveSurfer
        let wavesurfer = WaveSurfer.create(options);

        // Cargar el archivo de audio
        wavesurfer.load(audioSrc);
        console.log(`Cargando el archivo de audio: ${audioSrc}`);

        // Una vez que la forma de onda esté lista
        wavesurfer.on('ready', function () {
            console.log('Audio listo. Forma de onda generada.');
            
            // Si no hay picos almacenados, exportarlos y guardarlos en localStorage
            if (!storedPeaks) {
                // Usar exportPeaks() para obtener los picos
                const peaks = wavesurfer.exportPeaks({ channels: 2, maxLength: 8000, precision: 10000 });
                localStorage.setItem(peaksKey, JSON.stringify(peaks));
                console.log('Picos generados y almacenados en localStorage:', peaks);
            }
        });

        // Al hacer clic en el contenedor, reproducir o pausar el audio
        container.addEventListener('click', function () {
            console.log('Toggling play/pause');
            wavesurfer.playPause();
        });
    } else {
        console.log('Error: No se encontró el contenedor o el audioSrc no es válido.');
    }
}