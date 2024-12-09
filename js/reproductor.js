

function inicializarReproductorAudio() {
    const audio = document.querySelector('.GSJJHK');
    if (!audio) {
        console.log('Elemento de audio no encontrado');
        return;
    }

    function Cover(container) {
        const backgroundDiv = container.querySelector('.post-background');
        if (backgroundDiv) {
            const backgroundImage = backgroundDiv.style.backgroundImage;
            const match = backgroundImage.match(/url\((.*?)\)/);
            if (match) {
                const imageUrl = match[1].replace(/['\"]/g, '');
                const coverImage = document.querySelector('.LWXUER');
                if (coverImage) {
                    coverImage.src = imageUrl;
                    coverImage.style.display = 'block';
                }
            }
        }
    }

    function Info(container) {
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
    
            console.log('Updated title and author:', shortTitle, shortAuthor);
    
            // Envía la información a Android
            if (typeof Android !== 'undefined') {
                const audioSrc = container.querySelector('.audio-container audio')?.getAttribute('src');
                Android.sendAudioInfo(shortTitle, shortAuthor, imageUrl, audioSrc);
            }
        } else {
            console.log('Info div not found');
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
                console.log('Volume changed to:', this.value);
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
        console.log("inicializarEventosReproductor ejecutado");
        document.addEventListener('click', event => {
            const clickedElement = event.target;
            console.log("Elemento clickeado:", clickedElement);
    
            // Manejo del reproductor de audio
            const audioContainer = clickedElement.closest('.EDYQHV');
            if (audioContainer && !isExcludedElement(clickedElement)) {
                const index = Array.from(document.querySelectorAll('.EDYQHV')).indexOf(audioContainer);
                console.log("Reproduciendo audio desde el elemento, index:", index);
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
        document.querySelector('.TMLIWT').style.display = 'none';
        audio.pause();
    });

    function setupControls() {
        console.log("Configurando controles");
        document.querySelector('.next-btn')?.addEventListener('click', () => {
            console.log("Botón siguiente clickeado");
            playNextAudio();
        });
        document.querySelector('.prev-btn')?.addEventListener('click', () => {
            console.log("Botón anterior clickeado");
            playPreviousAudio();
        });
        const playButton = document.querySelector('.play-btn');
        const pauseButton = document.querySelector('.pause-btn');
    
        if (playButton) {
            playButton.addEventListener('click', () => {
                console.log("Botón play clickeado");
                togglePlayPause();
            });
        }
    
        if (pauseButton) {
            pauseButton.addEventListener('click', () => {
                console.log("Botón pause clickeado");
                togglePlayPause();
            });
        }
    }

    function setupProgressBar() {
        console.log("Configurando barra de progreso");
        const progressContainer = document.querySelector('.progress-container');
        progressContainer?.addEventListener('click', (e) => {
            console.log("Barra de progreso clickeada");
            updateProgress(e);
        });
        audio.addEventListener('timeupdate', () => {
            console.log("Evento timeupdate disparado");
            updateProgressBar();
        });
    }
    

    function updateProgress(e) {
        console.log("Actualizando progreso");
        const rect = e.currentTarget.getBoundingClientRect();
        const clickedPercentage = (e.clientX - rect.left) / rect.width;
        audio.currentTime = audio.duration * clickedPercentage;
        console.log("Nuevo currentTime:", audio.currentTime);
    }

    function updateProgressBar() {
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            const value = audio.currentTime > 0 ? (100 / audio.duration) * audio.currentTime : 0;
            progressBar.style.width = `${value}%`;
        }
    }

 
function updateProgressBar() {
    const progressBar = document.querySelector('.progress-bar');
    if (progressBar) {
        const value = audio.currentTime > 0 ? (100 / audio.duration) * audio.currentTime : 0;
        progressBar.style.width = `${value}%`;
        console.log("Progreso de la barra actualizado:", value);
    }
}

async function playAudioFromElement(element, index) {
    const audioContainer = element.querySelector('.audio-container');
    const audioSrc = audioContainer?.querySelector('audio')?.getAttribute('src');
    const postId = audioContainer?.getAttribute('data-post-id');
    const artistId = audioContainer?.getAttribute('artista-id');

    if (!audioSrc) {
        console.log("No se encontró audioSrc");
        return;
    }

    console.log("Mostrando reproductor");
    document.querySelector('.TMLIWT').style.display = 'block';

    if (audio.src === audioSrc) {
        console.log("Mismo audio, toggle play/pause");
        togglePlayPause();
    } else {
        try {
            console.log("Registrando reproducción y oyente");
            await registrarReproduccionYOyente(audioSrc, postId, artistId);

            console.log("Obteniendo audio desde:", audioSrc);
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

            const blob = await response.blob();
            const audioUrl = URL.createObjectURL(blob);

            audio.src = audioUrl;
            console.log("Reproduciendo audio");
            await audio.play();
            Cover(element);
            Info(element);
            currentAudioIndex = index;
        } catch (error) {
            console.error('Error al cargar el audio:', error);
        }
    }
}

    let isPlayingPromise = null;
    
    async function togglePlayPause() {
        console.log("togglePlayPause ejecutado, estado actual:", audio.paused ? "paused" : "playing");
        try {
            if (audio.paused) {
                console.log("Intentando reproducir");
                await audio.play();
                console.log("Reproducción iniciada");
            } else {
                console.log("Intentando pausar");
                await audio.pause();
                console.log("Reproducción pausada");
            }
        } catch (error) {
            console.error('Error en togglePlayPause:', error);
        } finally {
            updatePlayPauseButton();
        }
    }
    

    function updatePlayPauseButton() {
        console.log("Actualizando botones de play/pause");
        const playButton = document.querySelector('.play-btn');
        const pauseButton = document.querySelector('.pause-btn');
        if (playButton && pauseButton) {
            playButton.style.display = audio.paused ? 'block' : 'none';
            pauseButton.style.display = audio.paused ? 'none' : 'block';
            console.log("Botones actualizados, estado:", audio.paused ? "paused" : "playing");
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
                        console.log('Error al reproducir:', error);
                        updatePlayPauseButton();
                    });
            }
        }
    }

    audio.addEventListener('play', updatePlayPauseButton);
    audio.addEventListener('pause', updatePlayPauseButton);

    console.log('Initializing audio player events.');
    inicializarEventosReproductor();
    console.log('Audio player initialization complete.');
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
            console.log('Respuesta:', data);
        })
        .catch(error => {
            console.log('Error:', error);
        });
}
