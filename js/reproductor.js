//////////////////////////////////////////////
//ACTIVAR O DESACTIVAR LOGS
const A06 = false; // Cambia a true para activar los logs
const log06 = A06 ? console.log : function () {};
//////////////////////////////////////////////

function inicializarReproductorAudio() {
    const audio = document.querySelector('.GSJJHK');
    if (!audio) {
        log06('Elemento de audio no encontrado');
        return;
    }
    //aqui esto relentiza demasiado la reproducción intentado cargar la imagen, no entiendo
    function Cover(container) {
        let imageUrl = null;
        // Si no se encontró la imagen en el background, busca el elemento img con clase 'imagenMusic'

        const imagenMusicElement = container.querySelector('.imagenMusic');
        if (imagenMusicElement) {
            imageUrl = imagenMusicElement.src;
        }

        // Si se encontró una URL de imagen, actualiza el elemento coverImage
        if (imageUrl) {
            const coverImage = document.querySelector('.LWXUER');
            if (coverImage) {
                coverImage.src = imageUrl;
                coverImage.style.display = 'block';
            }
        }
    }
    /*
    Dentro de CPQBEN hay un elemento con la clase de post-like-button, va a copiarlo, y a moverlo a SOMGMR que esta en el reproductor
    */

    function moveLikeButton() {
        const likeButtonContainer = document.querySelector('.CPQBEN .post-like-button'); // Busca el contenedor del botón de like
        const destinationContainer = document.querySelector('.SOMGMR'); // Busca el contenedor de destino

        if (likeButtonContainer && destinationContainer) {
            const likeButton = likeButtonContainer.cloneNode(true); // Clona el botón de like (incluyendo sus hijos y eventos)
            destinationContainer.appendChild(likeButton); // Mueve el botón clonado al contenedor de destino
            log06('Like button moved successfully.');
        } else {
            log06('Either like button or destination container not found.');
        }
    }

    function Info(container, postId = null) {
        const infoDiv = container.querySelector('.CPQBEN');
        if (infoDiv) {
            const author = infoDiv.querySelector('.CPQBAU').textContent.trim();
            const content = infoDiv.querySelector('.CPQBCO').textContent.trim();
            const imgElement = infoDiv.querySelector('img');
            const imageUrl = imgElement ? imgElement.getAttribute('src') : ''; // Extrae la URL de la imagen

            const shortAuthor = author.length > 40 ? author.slice(0, 40) + '...' : author;
            const shortTitle = content.length > 40 ? content.slice(0, 40) + '...' : content;
            const titleElement = document.querySelector('.XKPMGD .tituloR');
            const authorElement = document.querySelector('.XKPMGD .AutorR');

            if (titleElement) titleElement.textContent = shortTitle;
            if (authorElement) authorElement.textContent = shortAuthor;

            log06('Updated title and author:', shortTitle, shortAuthor);

            // Envía la información a Android a través de la interfaz correcta
            if (typeof AndroidAudioPlayer !== 'undefined') {
                const audioSrc = container.querySelector('.audio-container audio')?.getAttribute('src');
                AndroidAudioPlayer.sendAudioInfo(shortTitle, shortAuthor, imageUrl, audioSrc);
            }

            // Llama a la función para mover el botón de like después de actualizar la información
            moveLikeButton();
        } else {
            log06('Info div not found');
        }
    }
    //VOLUMEN
    const volumeControl = document.querySelector('.volume-control');
    const volumeButton = document.querySelector('.JMFCAI');
    const volumeContainer = document.querySelector('.TGXRDF');

    if (volumeControl) {
        volumeControl.addEventListener('input', function () {
            if (audio) {
                audio.volume = this.value;
                updateVolumeBackground(this.value);
                log06('Volume changed to:', this.value);
            }
        });
        updateVolumeBackground(volumeControl.value);
    }

    function updateVolumeBackground(value) {
        const percentage = value * 100;
        volumeControl.style.background = `linear-gradient(to right, #ffffff ${percentage}%, #9c9c9c ${percentage}%)`;
    }

    if (volumeButton && volumeContainer) {
        volumeButton.addEventListener('click', function () {
            if (volumeContainer.style.display === 'none' || !volumeContainer.style.display) {
                volumeContainer.style.display = 'block';
            } else {
                volumeContainer.style.display = 'none';
            }
        });
    }

    let audioList = [];
    let currentAudioIndex = -1;

    function inicializarEventosReproductor() {
        document.addEventListener('click', event => {
            const clickedElement = event.target;

            // Manejo del reproductor de audio
            const audioContainer = clickedElement.closest('.EDYQHV');
            if (audioContainer && !isExcludedElement(clickedElement)) {
                const index = Array.from(document.querySelectorAll('.EDYQHV')).indexOf(audioContainer);
                playAudioFromElement(audioContainer, index);
                event.stopPropagation();
                return;
            }
            // Manejo del cierre del modal
            if (clickedElement.classList.contains('modal-background')) {
                const modal = clickedElement.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
                event.stopPropagation();
                return;
            }

            // Manejo del "me gusta"
            if (clickedElement.classList.contains('post-like-button') || clickedElement.classList.contains('like-count') || clickedElement.classList.contains('TJKQGJ')) {
                event.stopPropagation();
                return;
            }
        });

        // Configuración adicional
        audioList = Array.from(document.querySelectorAll('.EDYQHV')).map(element => ({
            element: element,
            src: element.querySelector('.audio-container audio')?.getAttribute('src')
        }));

        setupControls();
        setupProgressBar();
    }

    function isExcludedElement(element) {
        const excludedClasses = ['TJKQGJ', 'HR695R7', 'post-like-button', 'A1806241', 'modal-background'];
        return excludedClasses.some(className => element.classList.contains(className) || element.closest(`.${className}`) !== null);
    }

    document.querySelector('.PCNLEZ').addEventListener('click', function () {
        //TMLIWT el productor
        document.querySelector('.TMLIWT').style.display = 'none';
        audio.pause();
    });

    function setupControls() {
        document.querySelector('.next-btn')?.addEventListener('click', playNextAudio);
        document.querySelector('.prev-btn')?.addEventListener('click', playPreviousAudio);
        document.querySelector('.play-btn')?.addEventListener('click', togglePlayPause);
        document.querySelector('.pause-btn')?.addEventListener('click', togglePlayPause);
    }

    function setupProgressBar() {
        const progressContainer = document.querySelector('.progress-container');
        progressContainer?.addEventListener('click', updateProgress);
        audio.addEventListener('timeupdate', updateProgressBar);
    }

    function updateProgress(e) {
        const rect = e.currentTarget.getBoundingClientRect();
        const clickedPercentage = (e.clientX - rect.left) / rect.width;
        audio.currentTime = audio.duration * clickedPercentage;
    }

    function updateProgressBar() {
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const value = audio.currentTime > 0 ? (100 / audio.duration) * audio.currentTime : 0;
            progressBar.style.width = `${value}%`;
        }
    }

    async function playAudioFromElement(element, index) {
        const audioContainer = element.querySelector('.audio-container');
        const audioSrc = audioContainer?.querySelector('audio')?.getAttribute('src');
        const postId = audioContainer?.getAttribute('data-post-id');
        const artistId = audioContainer?.getAttribute('artista-id');

        if (!audioSrc) return;

        document.querySelector('.TMLIWT').style.display = 'block';

        if (audio.src === audioSrc) {
            togglePlayPause();
        } else {
            try {
                // Primero registramos la reproducción
                await registrarReproduccionYOyente(audioSrc, postId, artistId);

                // Realizamos la solicitud fetch para obtener el audio
                const response = await fetch(audioSrc, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: {
                        'X-WP-Nonce': audioSettings.nonce,
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                // Creamos un blob del stream de audio
                const blob = await response.blob();
                const audioUrl = URL.createObjectURL(blob);

                // Actualizamos el source del audio y lo reproducimos
                audio.src = audioUrl;
                audio
                    .play()
                    .then(() => {
                        Cover(element);
                        Info(element, postId);
                        currentAudioIndex = index;
                    })
                    .catch(error => {
                        console.error('Error al reproducir el audio:', error);
                    });
            } catch (error) {
                console.error('Error al cargar el audio:', error);
            }
        }
    }

    let isPlayingPromise = null;

    async function togglePlayPause() {
        try {
            if (audio.paused) {
                await audio.play();
            } else {
                await audio.pause();
            }
            updatePlayPauseButton();
        } catch (error) {
            console.error('Error al toggle play/pause:', error);
            updatePlayPauseButton();
        }
    }

    /*
    aqui por ejemplo como uso esto
       if (typeof Android !== 'undefined') {
        Android.sendAudioInfo(title, author, imageUrl, audioUrl);
    }
    */

    function updatePlayPauseButton() {
        const playButton = document.querySelector('.play-btn');
        const pauseButton = document.querySelector('.pause-btn');
        if (playButton && pauseButton) {
            playButton.style.display = audio.paused ? 'block' : 'none';
            pauseButton.style.display = audio.paused ? 'none' : 'block';
        }
    }

    function playNextAudio() {
        if (currentAudioIndex < audioList.length - 1) {
            playAudioAtIndex(currentAudioIndex + 1);
        }
    }

    function playPreviousAudio() {
        if (currentAudioIndex > 0) {
            playAudioAtIndex(currentAudioIndex - 1);
        }
    }

    function playAudioAtIndex(index) {
        const audioInfo = audioList[index];
        if (audioInfo && audioInfo.src) {
            audio.src = audioInfo.src;
            isPlayingPromise = audio.play();
            if (isPlayingPromise !== undefined) {
                isPlayingPromise
                    .then(() => {
                        Cover(audioInfo.element);
                        Info(audioInfo.element);
                        currentAudioIndex = index;
                        updatePlayPauseButton();
                    })
                    .catch(error => {
                        log06('Error al reproducir:', error);
                        updatePlayPauseButton();
                    });
            }
        }
    }

    audio.addEventListener('play', updatePlayPauseButton);
    audio.addEventListener('pause', updatePlayPauseButton);

    log06('Initializing audio player events.');
    inicializarEventosReproductor();
    log06('Audio player initialization complete.');
}

function registrarReproduccionYOyente(audioSrc, postId, artist) {
    const formData = new FormData();
    formData.append('src', audioSrc);
    formData.append('post_id', postId);
    formData.append('artist', artist);

    // Obtener el usuario actual del DOM
    const userIdInput = document.getElementById('user_id');
    if (userIdInput) {
        formData.append('user_id', userIdInput.value || ''); // Agregar usuario actual si existe
    }

    fetch('/wp-json/miplugin/v1/reproducciones-y-oyentes/', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            log06('Respuesta:', data);
        })
        .catch(error => {
            log06('Error:', error);
        });
}
