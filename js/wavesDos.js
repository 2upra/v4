window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    if (!container || !audioSrc) return;

    const cacheKey = `waveform_${audioSrc}`;
    const cachedWaveform = localStorage.getItem(cacheKey);

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

    if (cachedWaveform) {
        // Si la waveform está en caché, cárgala directamente
        wavesurfer.loadBlob(new Blob([atob(cachedWaveform)], { type: 'audio/wav' }));
    } else {
        // Si no está en caché, carga el audio y guarda la waveform
        wavesurfer.load(audioSrc);
        wavesurfer.on('ready', function () {
            // Guarda la waveform en caché
            wavesurfer.exportPCM(1024, 10000, true).then(function(pcmData) {
                localStorage.setItem(cacheKey, btoa(pcmData));
            });
        });
    }

    wavesurfer.on('ready', function () {
        // Cualquier lógica adicional que quieras ejecutar cuando la waveform esté lista
    });

    container.addEventListener('click', function () {
        wavesurfer.playPause();
    });
}