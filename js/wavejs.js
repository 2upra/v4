function observacionWave(containers) {
    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                if (entry.isIntersecting) {
                    if (!container.dataset.loadTimeoutSet) {
                        container.dataset.loadTimeout = setTimeout(() => {
                            if (!container.dataset.audioLoaded) {
                                loadAudio(postId, audioUrl, container, false);
                            }
                        }, 1500);
                        container.dataset.loadTimeoutSet = 'true';
                    }
                } else {
                    if (container.dataset.loadTimeoutSet) {
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                }
            });
        },
        {threshold: 0.5}
    );

    containers.forEach(container => {
        if (!container.dataset.initialized) {
            const postId = container.getAttribute('postIDWave');
            const audioUrl = container.getAttribute('data-audio-url');

            if (postId && audioUrl) {
                container.dataset.initialized = 'true';
                observer.observe(container);
            }
        }
    });
}


let currentlyPlayingAudio = null;

window.stopAllWaveSurferPlayers = function () {
    if (currentlyPlayingAudio) {
        currentlyPlayingAudio.pause();
    }
    for (const postId in window.wavesurfers) {
        if (window.wavesurfers[postId].isPlaying()) {
            window.wavesurfers[postId].pause();
        }
    }
    currentlyPlayingAudio = null;
    document.querySelectorAll('.POST-sampleList').forEach(post => {
        post.querySelector('.reproducirSL').style.display = 'none';
        post.querySelector('.pausaSL').style.display = 'none';
    });
};

function hoverWaves(posts) {
    posts.forEach(post => {
        const postId = post.getAttribute('id-post');
        const reproducirSL = post.querySelector('.reproducirSL');
        const pausaSL = post.querySelector('.pausaSL');

        if (!post.dataset.hoverListenerAdded) {
            post.addEventListener('mouseenter', () => {
                const wavesurfer = window.wavesurfers[postId];
                if (wavesurfer && wavesurfer.isPlaying()) {
                    pausaSL.style.display = 'flex';
                    reproducirSL.style.display = 'none';
                } else {
                    pausaSL.style.display = 'none';
                    reproducirSL.style.display = 'flex';
                }
            });

            post.addEventListener('mouseleave', () => {
                const wavesurfer = window.wavesurfers[postId];
                if (!wavesurfer || !wavesurfer.isPlaying()) {
                    reproducirSL.style.display = 'none';
                    pausaSL.style.display = 'none';
                }
            });
            post.dataset.hoverListenerAdded = 'true';
        }
    });
}

function inicializarWaveforms() {
    const waveformContainers = document.querySelectorAll('.waveform-container');
    const posts = document.querySelectorAll('.POST-sampleList');

    observacionWave(waveformContainers);
    agregarManejadorWave(waveformContainers);
    hoverWaves(posts);
    clickWaveContainer(posts);
    sampleListEscucha(posts);
}

function reproducirWave(contenedorElemento) {
    if (!(contenedorElemento instanceof Element)) {
      console.log("❌ [reproducirWave] ➡️ El contenedor no es un elemento válido.");
      return;
    }
  
    const idPublicacion = contenedorElemento.getAttribute('postidwave');
    const urlAudio = contenedorElemento.getAttribute('data-audio-url');
  
    if (!idPublicacion) {
      console.log("❌ [reproducirWave] ➡️ No se encontró el ID de la publicación.");
      return;
    }
  
    if (!contenedorElemento.dataset.audioCargado) {
      console.log("⏳ [reproducirWave] ➡️ Cargando audio por primera vez...", { idPublicacion, urlAudio });
      loadAudio(idPublicacion, urlAudio, contenedorElemento, true);
    } else {
      const wavesurferInstancia = window.wavesurfers[idPublicacion];
      console.log("✅ [reproducirWave] ➡️ Audio ya cargado.", { wavesurferInstancia });
      if (wavesurferInstancia) {
        if (wavesurferInstancia.isPlaying()) {
          console.log("⏸️ [reproducirWave] ➡️ Pausando audio.", { idPublicacion });
          wavesurferInstancia.pause();
        } else {
          if (window.currentlyPlayingAudio && window.currentlyPlayingAudio !== wavesurferInstancia) {
            console.log("⏸️ [reproducirWave] ➡️ Pausando audio anterior.", { audioAnterior: window.currentlyPlayingAudio });
            window.currentlyPlayingAudio.pause();
          }
          console.log("▶️ [reproducirWave] ➡️ Reproduciendo audio.", { idPublicacion });
          wavesurferInstancia.play();
        }
      }
    }
  }
  
  function agregarManejadorWave(contenedorElemento) {
    if (!contenedorElemento.dataset.clickListenerAgregado) {
      contenedorElemento.addEventListener('click', () => {
        console.log("🖱️ [agregarManejadorWave] ➡️ Click en contenedor de audio.", { contenedorElemento });
        reproducirWave(contenedorElemento);
      });
      contenedorElemento.dataset.clickListenerAgregado = 'true';
      console.log("✅ [agregarManejadorWave] ➡️ Manejador de click agregado.", { contenedorElemento });
    } else {
        console.log("✅ [agregarManejadorWave] ➡️ El manejador de click ya existe.", { contenedorElemento });
    }
  }
  
  function clickWaveContainer(publicaciones) {
    publicaciones.forEach(publicacionElemento => {
      const contenedorWave = publicacionElemento.querySelector('.waveform-container');
  
      if (!publicacionElemento.dataset.clickListenerAgregado) {
        publicacionElemento.addEventListener('click', evento => {
          const elementoClickeado = evento.target;
          if (elementoClickeado.closest('.tags-container') || elementoClickeado.closest('.QSORIW')) {
            console.log("🖱️ [clickWaveContainer] ➡️ Click en tags o QSORIW, ignorando.", { elementoClickeado });
            return;
          }
          if (contenedorWave) {
            console.log("🖱️ [clickWaveContainer] ➡️ Click en contenedor de post, agregando manejador a waveform.", { publicacionElemento, contenedorWave });
            agregarManejadorWave(contenedorWave);
          }
        });
        publicacionElemento.dataset.clickListenerAgregado = 'true';
        console.log("✅ [clickWaveContainer] ➡️ Manejador de click agregado a la publicación.", { publicacionElemento });
      } else {
        console.log("✅ [clickWaveContainer] ➡️ El manejador de click ya existe en la publicación.", { publicacionElemento });
      }
    });
  }

function sampleListEscucha(posts) {
    //console.log("🎧 sampleListEscucha: Inicializando...");
    posts.forEach(post => {
        const postId = post.getAttribute('id-post');
        const waveformContainer = post.querySelector('.waveform-container');

        if (waveformContainer && !waveformContainer.dataset.eventListenersAdded) {
            //console.log(`👂 sampleListEscucha: Agregando listeners para post ${postId}`);
            waveformContainer.addEventListener('ready', () => {
                const wavesurfer = window.wavesurfers[postId];
                if (wavesurfer) {
                    wavesurfer.on('play', () => {
                        //console.log(`▶️ sampleListEscucha: Evento 'play' en post ${postId}`);
                        playWave(wavesurfer, postId);
                    });

                    wavesurfer.on('pause', () => {
                        //console.log(`⏸️ sampleListEscucha: Evento 'pause' en post ${postId}`);
                        pausaWave(wavesurfer, post);
                    });

                    wavesurfer.on('finish', () => {
                        //console.log(`🏁 sampleListEscucha: Evento 'finish' en post ${postId}`);
                        finalWave(wavesurfer, post);
                    });
                }
            });
            waveformContainer.dataset.eventListenersAdded = 'true';
            //console.log(`✅ sampleListEscucha: Listeners agregados para post ${postId}`);
        }
    });
    //console.log("💯 sampleListEscucha: Finalizado.");
}



function playWave(wavesurfer, postId) {
    if (currentlyPlayingAudio && currentlyPlayingAudio !== wavesurfer) {
        currentlyPlayingAudio.pause();
        const previousPostId = currentlyPlayingAudio.params.container.getAttribute('postIDWave');
        const previousPost = document.querySelector(`.POST-sampleList[id-post="${previousPostId}"]`);
        if (previousPost) {
            const previousReproducirSL = previousPost.querySelector('.reproducirSL');
            const previousPausaSL = previousPost.querySelector('.pausaSL');
            previousReproducirSL.style.display = 'flex';
            previousPausaSL.style.display = 'none';
        }
    }
    currentlyPlayingAudio = wavesurfer;
    document.querySelectorAll('.POST-sampleList').forEach(otherPost => {
        const otherPostId = otherPost.getAttribute('id-post');
        const otherReproducirSL = otherPost.querySelector('.reproducirSL');
        const otherPausaSL = otherPost.querySelector('.pausaSL');
        if (otherPostId !== postId) {
            otherReproducirSL.style.display = 'none';
            otherPausaSL.style.display = 'none';
        } else {
            otherReproducirSL.style.display = 'none';
            otherPausaSL.style.display = 'flex';
        }
    });
}

function pausaWave(wavesurfer, post) {
    const thisReproducirSL = post.querySelector('.reproducirSL');
    const thisPausaSL = post.querySelector('.pausaSL');
    thisReproducirSL.style.display = 'flex';
    thisPausaSL.style.display = 'none';
    if (currentlyPlayingAudio === wavesurfer) {
        currentlyPlayingAudio = null;
    }
}

function finalWave(wavesurfer, post) {
    const thisReproducirSL = post.querySelector('.reproducirSL');
    const thisPausaSL = post.querySelector('.pausaSL');
    thisReproducirSL.style.display = 'flex';
    thisPausaSL.style.display = 'none';
    if (currentlyPlayingAudio === wavesurfer) {
        currentlyPlayingAudio = null;
    }
}






function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId) {
        return;
    }
    if (!container.dataset.audioLoaded) {
        window.we(postId, audioUrl, container, playOnLoad);
        container.dataset.audioLoaded = 'true';
    }
}

window.we = function (postId, audioUrl, container, playOnLoad = false) {
    if (!window.wavesurfers) {
        window.wavesurfers = {};
    }

    const MAX_RETRIES = 3;
    //console.log(`Intentando cargar audio para postId=${postId}, URL=${audioUrl}`);

    const loadAndPlayAudioStream = (retryCount = 0) => {
        if (retryCount >= MAX_RETRIES) {
            //console.error(`No se pudo cargar el audio para postId=${postId} después de varios intentos`);
            container.querySelector('.waveform-loading').style.display = 'none';
            container.querySelector('.waveform-message').style.display = 'block';
            container.querySelector('.waveform-message').textContent = 'Error al cargar el audio.';
            return;
        }

        window.audioLoading = true;

        fetch(audioUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-WP-Nonce': audioSettings.nonce,
                'X-Requested-With': 'XMLHttpRequest'
                //Accept: 'audio/mpeg,audio/*;q=0.9,*/*;q=0.8'
            }
        })
            .then(response => {
                if (!response.ok) throw new Error('Respuesta de red no satisfactoria');
                return response;
            })
            .then(response => {
                const reader = response.body.getReader();
                return new ReadableStream({
                    start(controller) {
                        return pump();
                        function pump() {
                            return reader.read().then(({done, value}) => {
                                if (done) {
                                    controller.close();
                                    return;
                                }
                                controller.enqueue(value);
                                return pump();
                            });
                        }
                    }
                });
            })
            .then(stream => new Response(stream))
            .then(response => response.blob())
            .then(blob => {
                const audioBlobUrl = URL.createObjectURL(blob);

                // Inicializar wavesurfer y guardarlo en el objeto global
                const wavesurfer = initWavesurfer(container);
                window.wavesurfers[postId] = wavesurfer;

                wavesurfer.load(audioBlobUrl);

                const waveformBackground = container.querySelector('.waveform-background');
                if (waveformBackground) {
                    waveformBackground.style.display = 'none';
                }

                wavesurfer.on('ready', () => {
                    window.audioLoading = false;
                    container.dataset.audioLoaded = 'true';
                    container.querySelector('.waveform-loading').style.display = 'none';
                    //console.log(`Audio listo para postId=${postId}`);

                    const waveCargada = container.getAttribute('data-wave-cargada') === 'true';
                    const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

                    if (!waveCargada && !isMobile) {
                        if (!container.closest('.LISTWAVESAMPLE')) {
                            setTimeout(() => {
                                const image = generateWaveformImage(wavesurfer);
                                sendImageToServer(image, postId);
                                //console.log(`Imagen de waveform enviada para postId=${postId}`);
                            }, 1);
                        }
                    }

                    // Reproducir solo si fue cargado por un clic
                    if (playOnLoad) {
                        wavesurfer.play();
                        //console.log(`Audio reproduciendo automáticamente para postId=${postId}`);
                    } else {
                        //console.log(`Audio cargado pero no reproducido para postId=${postId}, esperando interacción del usuario.`);
                    }
                });

                wavesurfer.on('error', () => {
                    //console.error(`Error al cargar el audio para postId=${postId}. Intento ${retryCount + 1} de ${MAX_RETRIES}`);
                    setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
                });
            })
            .catch(error => {
                //console.error(`Error al cargar el audio para postId=${postId}. Intento ${retryCount + 1} de ${MAX_RETRIES}`, error);
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            });
    };

    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    // Verifica si el contenedor o alguno de sus elementos padre tiene la clase 'LISTWAVESAMPLE'
    const isListWaveSample = container.classList.contains('LISTWAVESAMPLE') || container.parentElement.classList.contains('LISTWAVESAMPLE');

    const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : isListWaveSample ? 40 : 90;

    const ctx = document.createElement('canvas').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 500);
    const progressGradient = ctx.createLinearGradient(0, 0, 0, 500);

    // Configuración de los colores del gradiente
    gradient.addColorStop(0, '#848484');
    gradient.addColorStop(0.55, '#848484');
    gradient.addColorStop(0.551, '#d43333');
    gradient.addColorStop(1, '#d43333');

    progressGradient.addColorStop(0, '#d43333');
    progressGradient.addColorStop(1, '#d43333');

    return WaveSurfer.create({
        container: container,
        waveColor: gradient,
        progressColor: progressGradient,
        backend: 'WebAudio',
        interact: true,
        barWidth: 2,
        height: containerHeight,
        partialRender: true
    });
}

// Función para generar la imagen de la forma de onda
function generateWaveformImage(wavesurfer) {
    const canvas = wavesurfer.getWrapper().querySelector('canvas');
    return canvas.toDataURL('image/png');
}

// Función para enviar la imagen generada al servidor
async function sendImageToServer(imageData, postId) {
    if (imageData.length < 100) {
        return;
    }

    // Convertir la cadena base64 a Blob
    const byteString = atob(imageData.split(',')[1]);
    const mimeString = imageData.split(',')[0].split(':')[1].split(';')[0];
    const ab = new ArrayBuffer(byteString.length);
    const ia = new Uint8Array(ab);
    for (let i = 0; i < byteString.length; i++) {
        ia[i] = byteString.charCodeAt(i);
    }
    const blob = new Blob([ab], {type: mimeString});

    const formData = new FormData();
    formData.append('action', 'save_waveform_image');
    formData.append('image', blob, 'waveform.png');
    formData.append('post_id', postId);

    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            //console.error('Error al guardar la imagen:', data.message);
        }
    } catch (error) {
        //console.error('Error en la solicitud:', error);
    }
}

// Inicializa los reproductores de audio cuando el DOM está completamente cargado
document.addEventListener('DOMContentLoaded', inicializarWaveforms);
