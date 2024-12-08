let CimagenUrl, CimagenId, CaudioId, CaudioUrl, CpostId;
let subidasEnProgreso = 0;

let enablelogCom = true;
const logCOM = enableLogCom ? console.log : function () {};
let waveSurferInstancesCom = {};

let comIniciado = false;

function iniciarCOM() {
    if (comIniciado) return;
    comIniciado = true;
    if (document.getElementById('rsComentario')) {
        CimagenId = null;
        CimagenUrl = null;
        CaudioId = null;
        CaudioUrl = null;
        subidasEnProgreso = 0;
        verificarComentario();
        enviarComentario();
        subidaComentario();
    }
}

function verificarComentario() {
    function verificarCamposCom() {
        const textComent = document.getElementById('comentContent').value;

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

    return verificarCamposCom();
}

async function enviarComentario() {
    const button = document.getElementById('enviarComent');

    button.addEventListener('click', async event => {
        const originalText = button.innerText || button.textContent;
        button.innerText = 'Procesando';
        button.disabled = true;

        const valid = verificarComentario();
        if (!valid) {
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        const isValidUrl = url => {
            const requiredPrefix = 'https://2upra.com/wp-content/uploads';
            return url.startsWith(requiredPrefix);
        };

        if (!isValidUrl(CaudioUrl)) {
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        if (!isValidUrl(CimagenUrl)) {
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        const data = {
            comentario: document.getElementById('comentContent').value,
            imagenUrl: CimagenUrl,
            audioUrl: CaudioUrl,
            imagenId: CimagenId,
            audioId: CaudioId,
            postId: CpostId
        };

        try {
            const response = await enviarAjax('procesarComentario', data);
            if (response.success) {
                document.getElementById('comentContent').value = '';
                CimagenUrl = null;
                CaudioUrl = null;
                CimagenId = null;
                CaudioId = null;
                subidasEnProgreso = 0;
                alert('Comentario enviado con exito');
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
    
        file.type.startsWith('audio/') ? subidaAudio(file) : file.type.startsWith('image/') ? subidaImagen(file) : null;
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

        const index = audiosData.findIndex(audio => audio.tempId === tempId);
        if (index !== -1) {
            audiosData.splice(index, 1);
        }

        if (audiosData.length === 0) {
            pcomentImagen.style.display = 'none';
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
        clickedElement && abrirSelectorArchivos(clickedElement.classList.contains('paudio') ? 'audio/*' : 'image/*');
    });

    const abrirSelectorArchivos = tipoArchivo => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = tipoArchivo;
        input.onchange = inicialSubida;
        input.click();
    };

    audioComent.addEventListener('click', () => abrirSelectorArchivos('audio/*'));
    imagenComent.addEventListener('click', () => abrirSelectorArchivos('image/*'));

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        rsComentario.addEventListener(eventName, e => {
            e.preventDefault();
            rsComentario.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            eventName === 'drop' && inicialSubida(e);
        });
    });
}

async function subidaComBackend(file, progressBarId) {
    logRS('Iniciando subida de archivo', {fileName: file.name, fileSize: file.size});

    // Incrementar el contador de subidas en progreso
    uploadInProgressCount++;

    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    formData.append('file_hash', await generateFileHash(file));

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        logRS('Preparando solicitud AJAX', {url: my_ajax_object.ajax_url});

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const progressBar = document.getElementById(progressBarId);
                const progressPercent = (e.loaded / e.total) * 100;
                if (progressBar) progressBar.style.width = `${progressPercent}%`;

                logRS('Actualizando barra de progreso', {loaded: e.loaded, total: e.total, progressPercent});
            }
        };

        xhr.onload = () => {
            logRS('Respuesta recibida', {status: xhr.status, response: xhr.responseText});

            // Decrementar el contador al finalizar la subida
            uploadInProgressCount--;

            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        logRS('Archivo subido exitosamente', {data: result.data});
                        resolve(result.data);
                    } else {
                        logRS('Error en la respuesta del servidor (No éxito)', {response: result});
                        reject(new Error('Error en la respuesta del servidor'));
                    }
                } catch (error) {
                    logRS('Error al parsear la respuesta', {errorMessage: error.message, response: xhr.responseText});
                    reject(error);
                }
            } else {
                logRS('Error en la carga del archivo', {status: xhr.status, response: xhr.responseText});
                reject(new Error(`Error en la carga del archivo. Status: ${xhr.status}`));
            }
        };

        xhr.onerror = () => {
            logRS('Error en la conexión con el servidor', {status: xhr.status});
            uploadInProgressCount--; // Decrementar el contador en caso de error
            reject(new Error('Error en la conexión con el servidor'));
        };

        try {
            logRS('Enviando solicitud AJAX', {formData});
            xhr.send(formData);
        } catch (error) {
            logRS('Error al enviar la solicitud AJAX', {errorMessage: error.message});
            uploadInProgressCount--; // Decrementar el contador en caso de error
            reject(new Error('Error al enviar la solicitud AJAX'));
        }
    });
}
