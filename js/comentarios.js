let CimagenUrl, CimagenId, CaudioId, CaudioUrl, CpostId;
let subidaEnComentario = 0;

let enablelogCom = true;
const logcm = enablelogCom ? console.log : function () {};
let waveSurferInstancesCom = {};

let comIniciado = false;

/*
[x] background
[x] Cerrar modal 
[x] Eliminar imagen
[x] Ocultar comentario a enviar comentario
[x] Esperar que los elementos se carguen 
[ ] Ver comentarios anteriores 
[ ] Responder comentarios 
[ ] Notificacion de comentarios 
*/

function limpiarcamposCom() {
    document.getElementById('comentContent').value = '';
    CimagenUrl = null;
    CaudioUrl = null;
    CimagenId = null;
    CaudioId = null;
    CpostId = null;
    subidaEnComentario = 0;
    waveSurferInstancesCom = {};

    // Eliminar contenido de los divs
    const audioDiv = document.querySelector(".previewAreaArchivos.paudio#pcomentAudio");
    const imagenDiv = document.querySelector(".previewAreaArchivos.pimagen#pcomentImagen");

    if (audioDiv) {
        audioDiv.innerHTML = ''; // Elimina el contenido interno
        audioDiv.style.display = 'none'; // Oculta el div si aún no está oculto
    }

    if (imagenDiv) {
        imagenDiv.innerHTML = ''; // Elimina el contenido interno
        imagenDiv.style.display = 'none'; // Oculta el div si aún no está oculto
    }
    
    // Ocultar elementos adicionales (si ya no están ocultos por los pasos anteriores)
    const elementsToHide = ['pcomentImagen', 'pcomentAudio', 'previevsComent'];
    elementsToHide.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'none';
        }
    });
}
function iniciarcm() {
    ocultarColec();
    limpiarcamposCom();
    if (comIniciado) return;
    comIniciado = true;
    if (document.getElementById('rsComentario')) {
        logcm('IniciarCOM start');
        CimagenId = null;
        CimagenUrl = null;
        CaudioId = null;
        CaudioUrl = null;
        subidasEnProgreso = 0;
        enviarComentario();
        subidaComentario();
        abrirComentario();
    }
}

function verificarComentario() {
    function verificarCamposCom() {
        const textComent = document.getElementById('comentContent').value;

        if (!CpostId || isNaN(CpostId) || parseInt(CpostId) <= 0) {
            alert('Error: ID de publicación inválido.');
            return false;
        }

        if (textComent.length < 1) {
            alert('Ingresa un comentario para enviar');
            return false;
        }

        if (textComent.length > 500) {
            alert('Tu comentario no puede exceder los 500 caracteres');
            return false;
        }

        return true;
    }

    if (typeof subidaEnComentario !== 'undefined' && subidaEnComentario > 0) {
        alert('Por favor, espera a que se completen las subidas de los archivos adjuntos antes de enviar el comentario.');
        return false;
    }

    return verificarCamposCom();
}

async function enviarComentario() {
    const button = document.getElementById('enviarComent');

    button.addEventListener('click', async event => {
        const originalText = button.innerText;
        button.innerText = 'Procesando';
        button.disabled = true;

        const valid = verificarComentario();
        if (!valid) {
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        // Función para validar la URL
        const isValidUrl = url => {
            // Si la URL es null, la consideramos válida (ya que no es obligatoria)
            if (url === null) {
                return true;
            }
            const requiredPrefix = 'https://2upra.com/wp-content/uploads';
            return url.startsWith(requiredPrefix);
        };

        // Solo validamos las URLs si no son null
        if (CaudioUrl !== null && !isValidUrl(CaudioUrl)) {
            alert('La URL del audio no es válida.');
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        if (CimagenUrl !== null && !isValidUrl(CimagenUrl)) {
            alert('La URL de la imagen no es válida.');
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        const data = {
            comentario: document.getElementById('comentContent').value,
            imagenUrl: CimagenUrl, // Puede ser null
            audioUrl: CaudioUrl, // Puede ser null
            imagenId: CimagenId,
            audioId: CaudioId,
            postId: CpostId
        };

        try {
            const response = await enviarAjax('procesarComentario', data);
            if (response.success) {
                limpiarcamposCom();
                ocultarColec();
                alert('Comentario enviado con éxito');
            } else {
                alert('Error al enviar el comentario');
            }
        } catch (error) {
            console.error('Error al enviar el comentario:', error);
            alert('Error al enviar el comentario');
        } finally {
            button.innerText = originalText;
            button.disabled = false;
        }
    });
}

function subidaComentario() {
    const ids = ['imagenComent', 'audioComent', 'pcomentImagen', 'pcomentAudio', 'previevsComent', 'rsComentario'];

    const elements = ids.reduce((acc, id) => {
        const el = document.getElementById(id);
        if (!el) console.warn(`Elemento con id="${id}" no encontrado en el DOM.`);
        acc[id] = el;
        return acc;
    }, {});

    const missingElements = Object.entries(elements)
        .filter(([_, el]) => !el)
        .map(([id]) => id);
    if (missingElements.length) {
        return;
    }

    const {imagenComent, audioComent, pcomentImagen, pcomentAudio, previevsComent, rsComentario} = elements;

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (!file) return;
        if (file.size > 12 * 1024 * 1024) return alert('El archivo no puede superar los 12 MB.');

        if (file.type.startsWith('audio/')) {
            if (CaudioUrl) {
                alert('Solo se permite subir un audio.');
                return;
            }
            subidaAudio(file);
        } else if (file.type.startsWith('image/')) {
            subidaImagen(file);
        }
    };

    const subidaAudio = async file => {
        try {
            pcomentAudio.style.display = 'flex';
            previevsComent.style.display = 'flex';
            const tempId = `temp-${Date.now()}`;
            const progressBarId = waveAudio(file, tempId);
            const {fileUrl, fileId} = await subidaComBackend(file, progressBarId);
            CaudioUrl = fileUrl;
            CaudioId = fileId;
        } catch {
            alert('Hubo un problema al cargar el audio. Inténtalo de nuevo.');
        }
    };

    const waveAudio = (file, tempId) => {
        const reader = new FileReader(),
            audioContainerId = `waveform-container-${Date.now()}`,
            progressBarId = `progress-${Date.now()}`;

        reader.onload = e => {
            pcomentAudio.innerHTML = ''; // Limpiar el contenedor de audio
            const newWaveform = document.createElement('div');
            newWaveform.innerHTML = `
            <div id="${audioContainerId}" class="waveform-wrapper">
                <div class="waveform-container without-image" data-temp-id="${tempId}">
                    <div class="waveform-loading" style="display: none;">Cargando...</div>
                    <audio controls style="width: 100%;"><source src="${e.target.result}" type="${file.type}"></audio>
                    <div class="file-name">${file.name}</div>
                    <button class="delete-waveform">Eliminar</button>
                </div>
                <div class="progress-bar" style="width: 100%; height: 2px; background-color: #ddd; margin-top: 10px;">
                    <div id="${progressBarId}" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>
                </div>
                <!-- Campo de texto para el nombre del audio -->
                <input type="text" id="nombre-${tempId}" class="nombreAudioRs" placeholder="Titulo" style="display: none; margin-top: 5px; width: 100%;">
            </div>`;

            pcomentAudio.appendChild(newWaveform);
            inicializarWaveform(audioContainerId, e.target.result);
            const deleteButton = newWaveform.querySelector('.delete-waveform');
            deleteButton.addEventListener('click', event => {
                event.stopPropagation();
                eliminarWaveform(audioContainerId, tempId);
            });
        };

        reader.readAsDataURL(file);
        return progressBarId;
    };

    const eliminarWaveform = (containerId, tempId) => {
        const wrapper = document.getElementById(containerId);

        if (wrapper) {
            wrapper.parentNode.removeChild(wrapper);
            if (waveSurferInstancesCom[containerId]) {
                waveSurferInstancesCom[containerId].unAll();
                if (waveSurferInstancesCom[containerId].isPlaying()) {
                    waveSurferInstancesCom[containerId].stop();
                }
                waveSurferInstancesCom[containerId].destroy();
                delete waveSurferInstancesCom[containerId];
            }
        }

        CaudioUrl = null;
        CaudioId = null;

        pcomentAudio.style.display = 'none';
        if (!CimagenUrl) {
            previevsComent.style.display = 'none';
        }
    };

    const subidaImagen = async file => {
        try {
            const {fileUrl, fileId} = await subidaComBackend(file, 'barraProgresoImagen');
            previevsComent.style.display = 'flex';
            CimagenUrl = fileUrl;
            CimagenId = fileId;
            updatePreviewImagen(file);
        } catch {
            alert('Hubo un problema al cargar la imagen. Inténtalo de nuevo.');
        }
    };

    const updatePreviewImagen = file => {
        const reader = new FileReader();
        reader.onload = e => {
            pcomentImagen.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            pcomentImagen.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    };

    rsComentario.addEventListener('click', event => {
        const clickedElement = event.target.closest('.paudio, .pimagen');
        if (clickedElement) {
            if (clickedElement.classList.contains('paudio')) {
                if (!CaudioUrl) {
                    abrirSelectorArchivos('audio/*');
                } else {
                    alert('Ya se ha subido un audio.');
                }
            } else {
                abrirSelectorArchivos('image/*');
            }
        }
    });

    const abrirSelectorArchivos = tipoArchivo => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = tipoArchivo;
        input.onchange = inicialSubida;
        input.click();
    };

    audioComent.addEventListener('click', () => {
        if (!CaudioUrl) {
            abrirSelectorArchivos('audio/*');
        } else {
            alert('Ya se ha subido un audio.');
        }
    });
    imagenComent.addEventListener('click', () => abrirSelectorArchivos('image/*'));

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        rsComentario.addEventListener(eventName, e => {
            e.preventDefault();
            rsComentario.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            if (eventName === 'drop') {
                if (e.dataTransfer.files.length > 0) {
                    if (e.dataTransfer.files[0].type.startsWith('audio/')) {
                        if (!CaudioUrl) {
                            inicialSubida(e);
                        } else {
                            alert('Solo se permite subir un audio.');
                        }
                    } else {
                        inicialSubida(e);
                    }
                }
            }
        });
    });
}

async function subidaComBackend(file, progressBarId) {
    logcm('Iniciando subida de archivo', {fileName: file.name, fileSize: file.size});

    // Incrementar el contador de subidas en progreso
    subidaEnComentario++;

    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    formData.append('file_hash', await generateFileHash(file));

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        logcm('Preparando solicitud AJAX', {url: my_ajax_object.ajax_url});

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const progressBar = document.getElementById(progressBarId);
                const progressPercent = (e.loaded / e.total) * 100;
                if (progressBar) progressBar.style.width = `${progressPercent}%`;

                logcm('Actualizando barra de progreso', {loaded: e.loaded, total: e.total, progressPercent});
            }
        };

        xhr.onload = () => {
            logcm('Respuesta recibida', {status: xhr.status, response: xhr.responseText});

            // Decrementar el contador al finalizar la subida
            subidaEnComentario--;

            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        logcm('Archivo subido exitosamente', {data: result.data});
                        resolve(result.data);
                    } else {
                        logcm('Error en la respuesta del servidor (No éxito)', {response: result});
                        reject(new Error('Error en la respuesta del servidor'));
                    }
                } catch (error) {
                    logcm('Error al parsear la respuesta', {errorMessage: error.message, response: xhr.responseText});
                    reject(error);
                }
            } else {
                logcm('Error en la carga del archivo', {status: xhr.status, response: xhr.responseText});
                reject(new Error(`Error en la carga del archivo. Status: ${xhr.status}`));
            }
        };

        xhr.onerror = () => {
            logcm('Error en la conexión con el servidor', {status: xhr.status});
            subidaEnComentario--; // Decrementar el contador en caso de error
            reject(new Error('Error en la conexión con el servidor'));
        };

        try {
            logcm('Enviando solicitud AJAX', {formData});
            xhr.send(formData);
        } catch (error) {
            logcm('Error al enviar la solicitud AJAX', {errorMessage: error.message});
            subidaEnComentario--; // Decrementar el contador en caso de error
            reject(new Error('Error al enviar la solicitud AJAX'));
        }
    });
}

function ocultarColec() {
    const rsComentario = document.getElementById('rsComentario');
    rsComentario.style.display = 'none';
    removeComDarkBackground(); // Asegúrate de eliminar el fondo también
}

function abrirComentario() {
    const rsComentario = document.getElementById('rsComentario');
    if (!rsComentario) return;

    document.body.addEventListener('click', event => {
        const boton = event.target.closest('.WNLOFT');
        if (boton) {
            event.stopPropagation(); // Detiene la propagación aquí
            CpostId = boton.dataset.postId;
            rsComentario.style.display = 'flex';
            createComDarkBackground();

            // Evita que el clic se propague desde el comentario
            rsComentario.addEventListener('click', (event) => {
                event.stopPropagation();
            });
        }
    });
}

window.createComDarkBackground = function () {
    let darkBackground = document.getElementById('submenu-background5323');
    if (!darkBackground) {
        // Crear el fondo oscuro si no existe
        darkBackground = document.createElement('div');
        darkBackground.id = 'submenu-background5323';
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = 0;
        darkBackground.style.left = 0;
        darkBackground.style.width = '100%';
        darkBackground.style.height = '100%';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = 1000;
        darkBackground.style.display = 'none';
        // darkBackground.style.pointerEvents = 'none';  // No es necesario al inicio
        darkBackground.style.opacity = '0';
        darkBackground.style.transition = 'opacity 0.3s ease';
        document.body.appendChild(darkBackground);
    }

    // Se remueve el listener del fondo oscuro si ya existe para evitar duplicados.
    darkBackground.removeEventListener('click', ocultarColec);
    darkBackground.addEventListener('click', ocultarColec);

    darkBackground.style.display = 'block';
    setTimeout(() => {
        darkBackground.style.opacity = '1';
    }, 10);
    // darkBackground.style.pointerEvents = 'auto'; // No es necesario, se establece al hacer display block
};

// Eliminar el fondo oscuro
window.removeComDarkBackground = function () {
    const darkBackground = document.getElementById('submenu-background5323');
    if (darkBackground) {
        darkBackground.style.opacity = '0';
        setTimeout(() => {
            darkBackground.style.display = 'none';
            // darkBackground.style.pointerEvents = 'none'; // No es necesario, se elimina al hacer display none
        }, 300);
    }
};