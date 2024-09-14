// Variables globales
let audioUrl, audioId, archivoUrl, archivoId, imagenSelecionada;
// Logs
const enableLogs = true;
const logRS = enableLogs ? console.log : function () {};

function iniciarRS() {
    logRS('comienzoFormRS fue llamado');
    if (document.getElementById('formRs')) {
        logRS('formRs existe');
        audioUrl = null;
        audioId = {};
        archivoUrl = null;
        archivoId = {};
        imagenSelecionada = null;
        subidaRs();
        placeholderRs();
    } else {
        logRS('formRs no existe');
    }
}
//Esto debe capturar todo lo que esta dentro del elemento "id="formRs", los tags y el normaltext en window.Tags, window.NormalText

function envioRs() {
    const button = document.getElementById('enviarRS');
    button.addEventListener('click', () => {});
}

//Auxilair
function elementosPorID(ids) {
    return ids.reduce((acc, id) => {
        acc[id] = document.getElementById(id);
        return acc;
    }, {});
}

function subidaRs() {
    const elementos = ['formRs', 'botonAudio', 'botonImagen', 'previewAudio', 'previewArchivo', 'opciones', 'botonArchivo', 'previewImagen'];
    const elementosEncontrados = elementos.reduce((acc, id) => {
        const elemento = document.getElementById(id);
        if (!elemento) {
            console.warn(`Elemento con id="${id}" no encontrado en el DOM.`);
        }
        acc[id] = elemento;
        return acc;
    }, {});
    logRS('Elementos detectados:', elementosEncontrados);
    const elementosFaltantes = Object.entries(elementosEncontrados)
        .filter(([id, el]) => !el)
        .map(([id]) => id);
    if (elementosFaltantes.length > 0) {
        console.error(`No se encontraron los siguientes elementos en el DOM: ${elementosFaltantes.join(', ')}`);
        return;
    }
    const {formRs, botonAudio, botonImagen, previewAudio, previewArchivo, opciones, botonArchivo, previewImagen} = elementosEncontrados;

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (!file) return;
        if (file.size > 200 * 1024 * 1024) return alert('El archivo no puede superar los 200 MB.');
        file.type.startsWith('audio/') ? subidaAudio(file) : file.type.startsWith('image/') ? subidaImagen(file) : subidaArchivo(file);
    };

    const subidaAudio = async file => {
        alert(`Audio subido: ${file.name}`);
        previewAudio.style.display = 'block';
        opciones.style.display = 'flex';
        const progressBarId = waveAudio(file);
        try {
            const subidaAudioRecibida = await subidaRsBackend(file, progressBarId);
            audioUrl = subidaAudioRecibida.fileUrl;
            audioId = subidaAudioRecibida.fileId;
        } catch {
            alert('Hubo un problema al cargar el Audio. Inténtalo de nuevo.');
        }
    };

    const subidaArchivo = async file => {
        alert(`Archivo subido: ${file.name}`);
        previewArchivo.style.display = 'block';
        previewArchivo.innerHTML = `<div class="file-name">${file.name}</div><div id="barraProgresoFile" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>`;
        try {
            const subidaArchivoRecibida = await subidaRsBackend(file, 'barraProgresoFile');
            archivoUrl = subidaArchivoRecibida.fileUrl;
            archivoId = subidaArchivoRecibida.fileId;
        } catch {
            alert('Hubo un problema al cargar el Archivo. Inténtalo de nuevo.');
        }
    };

    const subidaImagen = file => {
        alert(`Imagen subida: ${file.name}`);
        opciones.style.display = 'flex';
        updatePreviewImagen(file);
        imagenSelecionada = file;
    };

    const waveAudio = file => {
        const reader = new FileReader(),
            audioContainerId = `waveform-container-${Date.now()}`,
            progressBarId = `progress-${Date.now()}`;
        reader.onload = function (e) {
            previewAudio.innerHTML = `
                <div id="${audioContainerId}" class="waveform-container without-image" data-audio-url="${e.target.result}">
                  <div class="waveform-background"></div>
                  <div class="waveform-message"></div>
                  <div class="waveform-loading" style="display: none;">Cargando...</div>
                  <audio controls style="width: 100%;"><source src="${e.target.result}" type="${file.type}"></audio>
                  <div class="file-name">${file.name}</div>
                </div>
                <div class="progress-bar" style="width: 100%; height: 2px; background-color: #ddd; margin-top: 10px;">
                  <div id="${progressBarId}" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>
                </div>`;
            inicializarWaveform(audioContainerId, e.target.result);
        };
        reader.readAsDataURL(file);
        return progressBarId;
    };

    const updatePreviewImagen = file => {
        const reader = new FileReader();
        reader.onload = e => {
            previewImagen.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            previewImagen.style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    formRs.addEventListener('click', event => {
        const clickedElement = event.target;
        clickedElement.closest.previewAudio ? abrirSelectorArchivos('audio/*') : clickedElement.closest.previewImagen && abrirSelectorArchivos('image/*');
    });

    function abrirSelectorArchivos(tipoArchivo) {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = tipoArchivo;
        input.onchange = e => inicialSubida(e);
        input.click();
    }

    botonArchivo.addEventListener('click', () => abrirSelectorArchivos('*'));
    botonAudio.addEventListener('click', () => abrirSelectorArchivos('audio/*'));
    botonImagen.addEventListener('click', () => abrirSelectorArchivos('image/*'));

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        formRs.addEventListener(eventName, e => {
            e.preventDefault();
            formRs.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            eventName === 'drop' && inicialSubida(e);
        });
    });
}

async function subidaRsBackend(file, progressBarId) {
    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    formData.append('file_hash', await generateFileHash(file));

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const progressBar = document.getElementById(progressBarId);
                if (progressBar) progressBar.style.width = `${(e.loaded / e.total) * 100}%`;
            }
        };
        xhr.onload = () => {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    result.success ? resolve(result.data) : reject(new Error('Error en la respuesta del servidor'));
                } catch (error) {
                    reject(error);
                }
            } else {
                reject(new Error('Error en la carga del archivo'));
            }
        };
        xhr.onerror = () => reject(new Error('Error en la conexión con el servidor'));
        xhr.send(formData);
    });
}

function verificarCamposPost() {
    const textoRsDiv = document.getElementById('textoRs');
    textoRsDiv.setAttribute('placeholder', 'Puedes agregar tags agregando un #');
    textoRsDiv.addEventListener('input', verificarCampos);

    function verificarCampos() {
        const tags = Array.isArray(window.Tags) ? window.Tags : [];
        const normalText = typeof window.NormalText === 'string' ? window.NormalText : '';
        if (normalText.length < 3) {
            alert('El texto debe tener al menos 3 caracteres');
            return;
        }
        if (normalText.length > 800) {
            alert('El texto no puede exceder los 800 caracteres');
            textoRsDiv.innerText = normalText.substring(0, 800);
            return;
        }
        if (tags.length === 0) {
            alert('Debe incluir al menos un tag');
            return;
        }
        if (tags.some(tag => tag.length < 3)) {
            alert('Cada tag debe tener al menos 3 caracteres');
            return;
        }
    }
    verificarCampos();
}

//Función auxiliar para el placeholder
function placeholderRs() {
    var div = document.getElementById('textoRs');
    div.addEventListener('focus', function () {
        if (this.innerHTML === '') {
            this.innerHTML = '';
        }
    });
    div.addEventListener('blur', function () {
        if (this.innerHTML === '') {
            this.innerHTML = '';
        }
    });
}

function inicializarWaveform(containerId, audioSrc) {
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
