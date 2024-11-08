function inicializarWaveforms() {
    //console.log('Inicializando waveforms...');

    const observer = new IntersectionObserver(
        entries => {
            entries.forEach(entry => {
                const container = entry.target;
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                //console.log(`Observando contenedor: postId=${postId}, isIntersecting=${entry.isIntersecting}`);

                if (entry.isIntersecting) {
                    if (!container.dataset.loadTimeoutSet) {
                        //console.log(`Estableciendo timeout para cargar audio: postId=${postId}`);

                        const loadTimeout = setTimeout(() => {
                            if (!container.dataset.audioLoaded) {
                                //console.log(`Timeout alcanzado, cargando audio: postId=${postId}`);
                                loadAudio(postId, audioUrl, container, false); // No reproducir automáticamente
                            }
                        }, 1500);

                        container.dataset.loadTimeout = loadTimeout;
                        container.dataset.loadTimeoutSet = 'true';
                    }
                } else {
                    if (container.dataset.loadTimeoutSet) {
                        //console.log(`Despejando timeout para postId=${postId} porque ya no está visible`);
                        clearTimeout(container.dataset.loadTimeout);
                        delete container.dataset.loadTimeout;
                        delete container.dataset.loadTimeoutSet;
                    }
                }
            });
        },
        {threshold: 0.5}
    );

    // Inicializar wavesurfers observando cada contenedor
    document.querySelectorAll('.waveform-container').forEach(container => {
        const postId = container.getAttribute('postIDWave');
        const audioUrl = container.getAttribute('data-audio-url');

        if (postId && audioUrl) {
            if (!container.dataset.initialized) {
                //console.log(`Observando contenedor por primera vez: postId=${postId}`);
                container.dataset.initialized = 'true';
                observer.observe(container);
            } else {
                //console.log(`Contenedor ya estaba inicializado: postId=${postId}`);
            }
        } else {
            //console.error(`Contenedor con postId=${postId} no tiene atributos completos`);
        }
    });

    // Agregar manejador de clic para los elementos POST-sampleList
    document.querySelectorAll('.POST-sampleList').forEach(post => {
        if (!post.dataset.clickListenerAdded) {
            post.addEventListener('click', event => {
                const waveformContainer = post.querySelector('.waveform-container');

                const clickedElement = event.target;
                if (clickedElement.closest('.tags-container') || clickedElement.closest('.QSORIW') || clickedElement.closest('.post-image-container') || clickedElement.closest('.CONTENTLISTSAMPLE')) {
                    //console.log('Clic ignorado por estar dentro de un contenedor excluido.');
                    return;
                }

                if (waveformContainer) {
                    const postId = waveformContainer.getAttribute('postIDWave');
                    const audioUrl = waveformContainer.getAttribute('data-audio-url');

                    if (!postId) {
                        //console.error('postIDWave no está definido para el contenedor de onda.');
                        return;
                    }

                    //console.log(`Clic en postId=${postId}. Verificando si el audio ya está cargado...`);

                    if (!waveformContainer.dataset.audioLoaded) {
                        //console.log(`Audio no cargado aún para postId=${postId}. Cargando ahora...`);
                        loadAudio(postId, audioUrl, waveformContainer, true); // Cargar y reproducir
                    } else {
                        //console.log(`Audio ya cargado para postId=${postId}. Reproduciendo/Pausando...`);
                        const wavesurfer = window.wavesurfers[postId];
                        if (wavesurfer) {
                            if (wavesurfer.isPlaying()) {
                                wavesurfer.pause();
                                //console.log(`Audio pausado para postId=${postId}`);
                            } else {
                                wavesurfer.play();
                                //console.log(`Audio reproduciendo para postId=${postId}`);
                            }
                        } else {
                            //console.error(`No se encontró wavesurfer para postId=${postId}`);
                        }
                    }
                }
            });
            post.dataset.clickListenerAdded = 'true';
            //console.log(`Manejador de clic añadido a postId=${post.getAttribute('postIDWave')}`);
        }
    });

    // Agregar manejador de clic para los elementos waveform-container
    document.querySelectorAll('.waveform-container').forEach(container => {
        if (!container.dataset.clickListenerAdded) {
            container.addEventListener('click', () => {
                const postId = container.getAttribute('postIDWave');
                const audioUrl = container.getAttribute('data-audio-url');

                if (!postId) {
                    //console.error('postIDWave no está definido para el contenedor de onda.');
                    return;
                }

                //console.log(`Clic en waveform-container con postId=${postId}. Verificando si el audio ya está cargado...`);

                if (!container.dataset.audioLoaded) {
                    //console.log(`Audio no cargado aún para postId=${postId}. Cargando ahora...`);
                    loadAudio(postId, audioUrl, container, true); // Cargar y reproducir
                } else {
                    //console.log(`Audio ya cargado para postId=${postId}. Reproduciendo/Pausando...`);
                    const wavesurfer = window.wavesurfers[postId];
                    if (wavesurfer) {
                        if (wavesurfer.isPlaying()) {
                            wavesurfer.pause();
                            //console.log(`Audio pausado para postId=${postId}`);
                        } else {
                            wavesurfer.play();
                            //console.log(`Audio reproduciendo para postId=${postId}`);
                        }
                    } else {
                        //console.error(`No se encontró wavesurfer para postId=${postId}`);
                    }
                }
            });
            container.dataset.clickListenerAdded = 'true';
            //console.log(`Manejador de clic añadido a waveform-container con postId=${postId}`);
        }
    });
}

async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            console.log('Intentando registrar Service Worker...');
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/',
                updateViaCache: 'none'
            });
            console.log('Service Worker registrado:', registration);

            registration.addEventListener('statechange', e => {
                console.log('Service Worker state changed:', e.target.state);
            });

            return registration;
        } catch (error) {
            console.error('Error registrando Service Worker:', error);
            return null;
        }
    }
    return null;
}

// Inicializar cuando el documento esté listo
document.addEventListener('DOMContentLoaded', () => {
    registerServiceWorker();
});

function verifyAudioSettings() {
    console.log('Verificando configuración de audio:', {
        nonce: audioSettings?.nonce ? 'Presente' : 'Ausente',
        url: window.location.href,
        origin: window.location.origin
    });
}

function showError(container, message) {
    const loadingEl = container.querySelector('.waveform-loading');
    const messageEl = container.querySelector('.waveform-message');

    if (loadingEl) loadingEl.style.display = 'none';
    if (messageEl) {
        messageEl.style.display = 'block';
        messageEl.textContent = message;
    }
}

function loadAudio(postId, audioUrl, container, playOnLoad) {
    if (!postId || container.dataset.audioLoaded) return;

    console.log('Cargando audio:', {postId, audioUrl});

    const loadWithServiceWorker = async () => {
        try {
            if (navigator.serviceWorker.controller) {
                console.log('Usando Service Worker para cargar audio');
                await window.we(postId, audioUrl, container, playOnLoad);
            } else {
                console.log('Service Worker no disponible, usando carga normal');
                await window.we(postId, audioUrl, container, playOnLoad);
            }
            container.dataset.audioLoaded = 'true';
        } catch (error) {
            console.error('Error cargando audio:', error);
        }
    };

    loadWithServiceWorker();
}

window.we = function (postId, audioUrl, container, playOnLoad = false) {
    // Verificaciones iniciales
    verifyAudioSettings();

    if (!audioSettings || !audioSettings.nonce) {
        console.error('audioSettings no está configurado correctamente');
        showError(container, 'Error de configuración');
        return;
    }

    if (!window.wavesurfers) {
        window.wavesurfers = {};
    }

    const MAX_RETRIES = 1;
    console.log(`Iniciando carga de audio - PostID: ${postId}`);

    async function loadAndPlayAudioStream(retryCount = 0) {
        try {
            window.audioLoading = true;
    
            // Construir la URL final del audio
            const finalAudioUrl = buildAudioUrl(audioUrl, audioSettings.nonce);
    
            const response = await fetch(finalAudioUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-WP-Nonce': audioSettings.nonce,
                    'X-Requested-With': 'XMLHttpRequest',
                    Accept: 'audio/mpeg',
                    Range: 'bytes=0-'
                }
            });
    
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
    
            // Obtener los datos encriptados
            const arrayBuffer = await response.arrayBuffer();
    
            // Verificar headers y obtener IV
            console.log('Headers recibidos:', Object.fromEntries(response.headers));
            const iv = response.headers.get('X-Encryption-IV');
            console.log('IV recibido:', iv);
    
            // Verificar si tenemos el IV necesario
            if (!iv && audioSettings.key) {
                console.error('Se esperaba IV para desencriptación pero no se recibió');
                throw new Error('No se recibió el IV en los headers');
            }
    
            // Procesar los datos
            let audioData = arrayBuffer;
            if (iv && audioSettings.key) {
                console.log('Iniciando proceso de desencriptación');
                audioData = await decryptAudioData(arrayBuffer, iv, audioSettings.key);
            }
    
            // Convertir los datos en blob
            const blobUrl = createAudioBlobUrl(audioData);
            await validateAudio(blobUrl);
    
            // Inicializar y cargar Wavesurfer
            const wavesurfer = initWavesurfer(container);
            window.wavesurfers[postId] = wavesurfer;
    
            wavesurfer.load(blobUrl);
    
            handleWaveSurferEvents(wavesurfer, container, postId, blobUrl);
        } catch (error) {
            console.error('Error en loadAndPlayAudioStream:', error);
            handleLoadError(error, retryCount, container);
        }
    }

    async function decryptAudioData(arrayBuffer, iv, key) {
        let ivArray, keyArray;
        
        try {
            console.log('Iniciando desencriptación con parámetros:', {
                arrayBufferLength: arrayBuffer.byteLength,
                ivBase64: iv,
                keyHex: key
            });
            
            // Convertir IV de base64 a ArrayBuffer
            ivArray = Uint8Array.from(atob(iv), c => c.charCodeAt(0));
            console.log('IV convertido:', {
                originalLength: iv.length,
                decodedLength: ivArray.length,
                ivArrayHex: Array.from(ivArray).map(b => b.toString(16).padStart(2, '0')).join('')
            });
            
            // Convertir key hex a ArrayBuffer
            keyArray = new Uint8Array(
                key.match(/.{1,2}/g).map(byte => parseInt(byte, 16))
            );
            
            // Importar la clave
            const cryptoKey = await crypto.subtle.importKey(
                'raw',
                keyArray.buffer,
                {
                    name: 'AES-CBC',
                    length: 256
                },
                false,
                ['decrypt']
            );
            
            // Verificar datos encriptados
            const encryptedBytes = new Uint8Array(arrayBuffer);
            console.log('Datos encriptados:', {
                length: arrayBuffer.byteLength,
                firstBytesHex: Array.from(encryptedBytes.slice(0, 16))
                    .map(b => b.toString(16).padStart(2, '0'))
                    .join('')
            });
            
            // Desencriptar
            try {
                const decryptedData = await crypto.subtle.decrypt(
                    {
                        name: 'AES-CBC',
                        iv: ivArray
                    },
                    cryptoKey,
                    arrayBuffer
                );
                
                // Remover padding PKCS7
                const decryptedArray = new Uint8Array(decryptedData);
                const paddingLength = decryptedArray[decryptedArray.length - 1];
                const unpaddedData = decryptedData.slice(0, decryptedData.byteLength - paddingLength);
                
                console.log('Desencriptación exitosa:', {
                    originalLength: decryptedData.byteLength,
                    unpaddedLength: unpaddedData.byteLength
                });
                
                return unpaddedData;
            } catch (decryptError) {
                console.error('Error específico en decrypt:', {
                    error: decryptError,
                    params: {
                        ivLength: ivArray.length,
                        keyType: cryptoKey.type,
                        dataLength: arrayBuffer.byteLength,
                        firstBytesHex: Array.from(encryptedBytes.slice(0, 16))
                            .map(b => b.toString(16).padStart(2, '0'))
                            .join('')
                    }
                });
                throw decryptError;
            }
        } catch (error) {
            console.error('Error detallado en desencriptación:', {
                errorName: error.name,
                errorMessage: error.message,
                ivDetails: {
                    originalIV: iv,
                    ivLength: iv?.length,
                    decodedIVLength: ivArray?.length
                },
                keyDetails: {
                    keyLength: key?.length,
                    decodedKeyLength: keyArray?.length
                },
                dataDetails: {
                    dataLength: arrayBuffer?.byteLength,
                    isArrayBuffer: arrayBuffer instanceof ArrayBuffer
                }
            });
            throw error;
        }
    }

    // Función para construir la URL de audio
    function buildAudioUrl(audioUrl, nonce) {
        const urlObj = new URL(audioUrl);
        if (!urlObj.searchParams.has('_wpnonce')) {
            urlObj.searchParams.append('_wpnonce', nonce);
        }
        return urlObj.toString();
    }

    function createAudioBlobUrl(audioData) {
        const audioBlob = new Blob([audioData], {type: 'audio/mpeg'});
        return URL.createObjectURL(audioBlob);
    }

    async function validateAudio(blobUrl) {
        const audio = new Audio();
        audio.src = blobUrl;

        await new Promise((resolve, reject) => {
            audio.addEventListener('canplaythrough', resolve);
            audio.addEventListener('error', reject);
            audio.load();
        });
    }

    // Función para manejar los eventos de Wavesurfer
    function handleWaveSurferEvents(wavesurfer, container, postId, blobUrl) {
        const waveformBackground = container.querySelector('.waveform-background');
        if (waveformBackground) {
            waveformBackground.style.display = 'none';
        }

        wavesurfer.on('ready', () => {
            window.audioLoading = false;
            container.dataset.audioLoaded = 'true';
            const loadingElement = container.querySelector('.waveform-loading');
            if (loadingElement) {
                loadingElement.style.display = 'none';
            }

            // Generar y enviar la imagen de la forma de onda
            handleWaveformGeneration(wavesurfer, container, postId);

            // Reproducir si es necesario
            if (playOnLoad) {
                wavesurfer.play();
            }
        });

        wavesurfer.on('destroy', () => {
            URL.revokeObjectURL(blobUrl);
        });

        wavesurfer.on('error', error => {
            console.error('WaveSurfer error:', error);
            if (retryCount < MAX_RETRIES) {
                setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
            }
        });
    }

    // Función para manejar errores de carga
    function handleLoadError(error, retryCount, container) {
        console.error('Load error:', error);
        if (retryCount < MAX_RETRIES) {
            setTimeout(() => loadAndPlayAudioStream(retryCount + 1), 3000);
        } else {
            showError(container, 'Error al cargar el audio.');
        }
    }

    // Función para manejar la generación de la forma de onda
    function handleWaveformGeneration(wavesurfer, container, postId) {
        const waveCargada = container.getAttribute('data-wave-cargada') === 'true';
        const isMobile = /Mobi|Android|iPhone|iPad|iPod/.test(navigator.userAgent);

        if (!waveCargada && !isMobile && !container.closest('.LISTWAVESAMPLE')) {
            setTimeout(() => {
                const image = generateWaveformImage(wavesurfer);
                sendImageToServer(image, postId);
            }, 1);
        }
    }

    // Iniciar la carga
    loadAndPlayAudioStream();
};

// La función que inicializa WaveSurfer con los estilos y configuraciones deseados
function initWavesurfer(container) {
    const isListWaveSample = container.classList.contains('LISTWAVESAMPLE') || container.parentElement.classList.contains('LISTWAVESAMPLE');

    const containerHeight = container.classList.contains('waveform-container-venta') ? 60 : isListWaveSample ? 45 : 102;

    const ctx = document.createElement('canvas').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 500);
    const progressGradient = ctx.createLinearGradient(0, 0, 0, 500);

    // Configuración de los colores del gradiente
    gradient.addColorStop(0, '#FFFFFF');
    gradient.addColorStop(0.55, '#FFFFFF');
    gradient.addColorStop(0.551, '#d43333');
    gradient.addColorStop(1, '#d43333');

    progressGradient.addColorStop(0, '#d43333');
    progressGradient.addColorStop(1, '#d43333');

    return WaveSurfer.create({
        container: container,
        waveColor: gradient,
        progressColor: progressGradient,
        backend: 'MediaElement',
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
