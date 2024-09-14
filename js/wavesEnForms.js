window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);
    if (container && audioSrc) {
        let loadingElement = container.querySelector('.waveform-loading');
        let messageElement = container.querySelector('.waveform-message');
        let backgroundElement = container.querySelector('.waveform-background');
        messageElement.style.display = 'none';
        loadingElement.style.display = 'block';
        backgroundElement.style.display = 'block';

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
        wavesurfer.load(audioSrc);
        wavesurfer.on('ready', function () {
            loadingElement.style.display = 'none';
            backgroundElement.style.display = 'none';
        });
        container.addEventListener('click', function () {
            wavesurfer.playPause();
        });
    }
}