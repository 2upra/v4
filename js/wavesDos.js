window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    
    if (container && audioSrc) {
        // Crear clave única para almacenar los picos en localStorage, usando el nombre del archivo
        const peaksKey = `waveform_peaks_${audioSrc}`;

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
        }

        // Crear instancia de WaveSurfer
        let wavesurfer = WaveSurfer.create(options);

        // Cargar el archivo de audio
        wavesurfer.load(audioSrc);

        // Una vez que la forma de onda esté lista
        wavesurfer.on('ready', function () {
            // Si no hay picos almacenados, exportarlos y guardarlos en localStorage
            if (!storedPeaks) {
                const peaks = wavesurfer.exportPCM();
                localStorage.setItem(peaksKey, JSON.stringify(peaks));
                console.log('Picos generados y almacenados:', peaks);
            }
        });

        // Al hacer clic en el contenedor, reproducir o pausar el audio
        container.addEventListener('click', function () {
            wavesurfer.playPause();
        });
    }
}