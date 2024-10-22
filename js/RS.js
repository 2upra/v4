// Variables globales
let imagenUrl, imagenId, audioUrl, audioId, archivoUrl, archivoId;
let subidaAudioEnProgreso = false;
let subidaImagenEnProgreso = false;
let subidaArchivoEnProgreso = false;
let audiosData = [];
// Logs
let enablelogRS = true;
const logRS = enablelogRS ? console.log : function () {};
let waveSurferInstances = {};

function iniciarRS() {
    logRS('comienzoFormRS fue llamado');
    if (document.getElementById('formRs')) {
        logRS('formRs existe');
        imagenUrl = null;
        imagenId = null;
        audioUrl = null;
        audioId = null;
        archivoUrl = null;
        archivoId = null;

        audiosData = [];

        subidaAudioEnProgreso = false;
        subidaImagenEnProgreso = false;
        subidaArchivoEnProgreso = false;
        cantidadAudio = 2;
        subidaRs();
        envioRs();
        placeholderRs();
        TagEnTexto();
    } else {
        logRS('formRs no existe');
    }
}

function verificarCamposRs() {
    const textoRsDiv = document.getElementById('textoRs');
    textoRsDiv.setAttribute('placeholder', 'Puedes agregar tags agregando un #');

    // Variables que representan si hay subidas en progreso
    const subidaAudioEnProgreso = window.subidaAudioEnProgreso || false;
    const subidaImagenEnProgreso = window.subidaImagenEnProgreso || false;
    const subidaArchivoEnProgreso = window.subidaArchivoEnProgreso || false;

    function verificarCampos() {
        // Verificar si hay alguna subida en progreso
        if (subidaAudioEnProgreso) {
            alert('Espera que se suba tu archivo de audio.');
            return false;
        }
        if (subidaImagenEnProgreso) {
            alert('Espera que se suba tu imagen.');
            return false;
        }
        if (subidaArchivoEnProgreso) {
            alert('Espera que se suba tu archivo.');
            return false;
        }

        // Verificación de texto y tags
        const tags = window.Tags || [];
        const textoNormal = window.NormalText || '';

        if (textoNormal.length < 3) {
            alert('El texto debe tener al menos 3 caracteres');
            return false;
        }
        if (textoNormal.length > 800) {
            alert('El texto no puede exceder los 800 caracteres');
            textoRsDiv.innerText = textoNormal.substring(0, 800);
            return false;
        }
        if (tags.length === 0) {
            alert('Debe incluir al menos un tag');
            return false;
        }
        if (tags.some(tag => tag.length < 3)) {
            alert('Cada tag debe tener al menos 3 caracteres');
            return false;
        }

        return true;
    }

    return verificarCampos;
}

function selectorformtipo() {
    document.addEventListener('change', function (event) {
        if (event.target.matches('.custom-checkbox input[type="checkbox"]')) {
            const label = event.target.closest('label');
            if (event.target.checked) {
                label.style.color = '#ffffff';
                label.style.background = '#131313';
            } else {
                label.style.color = '#6b6b6b';
                label.style.background = '';
            }
        }
    });
}

async function envioRs() {
    const button = document.getElementById('enviarRs');

    button.addEventListener('click', async event => {
        // Almacenar el texto original del botón
        const originalText = button.innerText || button.textContent;

        // Cambiar el texto del botón a "Procesando" y deshabilitarlo
        button.innerText = 'Procesando';
        button.disabled = true;

        const verificarCampos = verificarCamposRs();
        const valid = verificarCampos();
        if (!valid) {
            // Restaurar el texto original y reactivar el botón si la validación falla
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        const tags = window.Tags || [];
        const textoNormal = window.NormalText || '';

        const descargaCheckbox = document.getElementById('descargacheck');
        const exclusivoCheckbox = document.getElementById('exclusivocheck');
        const colabCheckbox = document.getElementById('colabcheck');
        const descarga = descargaCheckbox.checked ? descargaCheckbox.value : 0;
        const exclusivo = exclusivoCheckbox.checked ? exclusivoCheckbox.value : 0;
        const colab = colabCheckbox.checked ? colabCheckbox.value : 0;

        console.log('Valores finales:', 'descarga:', descarga, 'exclusivo:', exclusivo, 'colab:', colab, 'tags:', tags, 'textoNormal:', textoNormal);

        const data = {
            imagenUrl1: typeof imagenUrl !== 'undefined' ? imagenUrl : null,
            imagenId: typeof imagenId !== 'undefined' ? imagenId : null,
            audioUrl1: typeof audioUrl !== 'undefined' ? audioUrl : null,
            audioId: typeof audioId !== 'undefined' ? audioId : null,
            archivoUrl: typeof archivoUrl !== 'undefined' ? archivoUrl : null,
            archivoId: typeof archivoId !== 'undefined' ? archivoId : null,
            tags,
            textoNormal,
            descarga,
            exclusivo,
            colab
        };

        console.table(data); // Verificar la tabla de datos antes de enviar

        try {
            const response = await enviarAjax('subidaRs', data);
            if (response?.success) {
                alert('Publicación realizada con éxito');
                limpiarCamposRs();
            } else {
                alert(`Error al publicar post: ${response?.message || 'Desconocido'}`);
            }
        } catch (error) {
            console.error('Error al enviar los datos:', error);
            alert('Ocurrió un error durante la publicación. Por favor, inténtelo de nuevo.');
        } finally {
            // Restaurar el texto original y reactivar el botón
            button.innerText = originalText;
            button.disabled = false;
        }
    });
}

function subidaRs() {
    const ids = ['formRs', 'botonAudio', 'botonImagen', 'previewAudio', 'previewArchivo', 'opciones', 'botonArchivo', 'previewImagen', 'enviarRs'];
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

    const {formRs, botonAudio, botonImagen, previewAudio, previewArchivo, opciones, botonArchivo, previewImagen, enviarRs} = elements;

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (!file) return;
        if (file.size > 50 * 1024 * 1024) return alert('El archivo no puede superar los 50 MB.');

        file.type.startsWith('audio/') ? subidaAudio(file) : file.type.startsWith('image/') ? subidaImagen(file) : subidaArchivo(file);
    };

    //tambien quiero que entienda que por ejemplo si el usuario borra todos los waveAudio con sus fileUrl, fileId correspondiente oculte previewAudio.style.display

    const subidaAudio = async file => {
        subidaAudioEnProgreso = true;
        try {
            alert(`Audio subido: ${file.name}`);
            previewAudio.style.display = 'block';
            opciones.style.display = 'flex';

            // Crear un ID temporal para el archivo
            const tempId = `temp-${Date.now()}`;
            const progressBarId = waveAudio(file, tempId);

            // Agregamos el objeto temporalmente a audiosData
            audiosData.push({tempId, fileUrl: null, fileId: null});

            const {fileUrl, fileId} = await subidaRsBackend(file, progressBarId);

            // Actualizar el audio en audiosData con los valores reales cuando lleguen del backend
            const index = audiosData.findIndex(audio => audio.tempId === tempId);
            if (index !== -1) {
                audiosData[index].fileUrl = fileUrl;
                audiosData[index].fileId = fileId;

                // Actualizar el atributo data-audio-url en el contenedor de la waveform con el verdadero fileUrl
                const waveformContainer = document.querySelector(`[data-temp-id="${tempId}"]`);
                if (waveformContainer) {
                    waveformContainer.setAttribute('data-audio-url', fileUrl);
                }
            }

            // Verificamos si ya hay 30 audios subidos
            if (audiosData.length > 30) {
                alert('Ya has subido el límite máximo de 30 audios.');
            }

            subidaAudioEnProgreso = false;
        } catch (error) {
            alert('Hubo un problema al cargar el Audio. Inténtalo de nuevo.');
            subidaAudioEnProgreso = false;
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
                </div>`;

            previewAudio.appendChild(newWaveform);
            inicializarWaveform(audioContainerId, e.target.result);

            const deleteButton = newWaveform.querySelector('.delete-waveform');
            deleteButton.addEventListener('click', () => eliminarWaveform(audioContainerId, tempId));
        };

        reader.readAsDataURL(file);
        return progressBarId;
    };

    const eliminarWaveform = (containerId, tempId) => {
        const wrapper = document.getElementById(containerId);

        if (wrapper) {
            // Detener la reproducción y destruir la instancia de WaveSurfer si existe
            if (waveSurferInstances[containerId]) {
                if (waveSurferInstances[containerId].isPlaying()) {
                    waveSurferInstances[containerId].stop(); // Detener si estaba reproduciendo
                }
                waveSurferInstances[containerId].destroy(); // Destruir la instancia
                delete waveSurferInstances[containerId]; // Eliminar la instancia después de destruirla
            }

            // Eliminar el contenedor del DOM
            wrapper.parentNode.removeChild(wrapper);
        }

        // Eliminar el audio de audiosData usando tempId
        const index = audiosData.findIndex(audio => audio.tempId === tempId);
        if (index !== -1) {
            audiosData.splice(index, 1);
        }

        // Si audiosData está vacío, ocultar previewAudio
        if (audiosData.length === 0) {
            previewAudio.style.display = 'none';
        }

        // Verificar si la instancia sigue reproduciendo después de 1 segundo y detenerla si es necesario
        setTimeout(() => {
            if (waveSurferInstances[containerId]) {
                if (waveSurferInstances[containerId].isPlaying()) {
                    waveSurferInstances[containerId].stop(); // Detener si sigue reproduciendo
                }
            }
        }, 500); 
    };

    const subidaArchivo = async file => {
        subidaArchivoEnProgreso = true;
        previewArchivo.style.display = 'block';
        previewArchivo.innerHTML = `<div class="file-name">${file.name}</div><div id="barraProgresoFile" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>`;
        try {
            alert(`Archivo subido: ${file.name}`);
            const {fileUrl, fileId} = await subidaRsBackend(file, 'barraProgresoFile');
            archivoUrl = fileUrl;
            archivoId = fileId;
            subidaArchivoEnProgreso = false;
        } catch {
            alert('Hubo un problema al cargar el Archivo. Inténtalo de nuevo.');
            subidaArchivoEnProgreso = false;
        }
    };

    const subidaImagen = async file => {
        subidaImagenEnProgreso = true;
        opciones.style.display = 'flex';
        updatePreviewImagen(file);
        try {
            alert(`Imagen subida: ${file.name}`);
            const {fileUrl, fileId} = await subidaRsBackend(file, 'barraProgresoImagen');
            imagenUrl = fileUrl;
            imagenId = fileId;
            subidaImagenEnProgreso = false;
        } catch {
            alert('Hubo un problema al cargar la Imagen. Inténtalo de nuevo.');
            subidaImagenEnProgreso = false;
        }
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
        const clickedElement = event.target.closest('.previewAudio, .previewImagen');
        clickedElement && abrirSelectorArchivos(clickedElement.classList.contains('previewAudio') ? 'audio/*' : 'image/*');
    });

    const abrirSelectorArchivos = tipoArchivo => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = tipoArchivo;
        input.onchange = inicialSubida;
        input.click();
    };

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
    logRS('Iniciando subida de archivo', {fileName: file.name, fileSize: file.size});

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
            reject(new Error('Error en la conexión con el servidor'));
        };

        try {
            logRS('Enviando solicitud AJAX', {formData});
            xhr.send(formData);
        } catch (error) {
            logRS('Error al enviar la solicitud AJAX', {errorMessage: error.message});
            reject(new Error('Error al enviar la solicitud AJAX'));
        }
    });
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

function limpiarCamposRs() {
    // Limpiar variables globales
    imagenUrl = null;
    imagenId = null;
    audioUrl = null;
    audioId = null;
    archivoUrl = null;
    archivoId = null;

    // Ocultar y limpiar los contenidos de las áreas de previsualización
    const previewAudio = document.getElementById('previewAudio');
    if (previewAudio) {
        previewAudio.style.display = 'none';
        const labelAudio = previewAudio.querySelector('label');
        if (labelAudio) labelAudio.textContent = '';
    }

    const opciones = document.getElementById('opciones');
    if (opciones) {
        opciones.style.display = 'none';
    }

    const previewArchivo = document.getElementById('previewArchivo');
    if (previewArchivo) {
        previewArchivo.style.display = 'none';
        const labelArchivo = previewArchivo.querySelector('label');
        if (labelArchivo) labelArchivo.textContent = 'Archivo adicional para colab (flp, zip, rar, midi, etc)';
    }

    const previewImagen = document.getElementById('previewImagen');
    if (previewImagen) {
        previewImagen.style.display = 'none';
        const labelImagen = previewImagen.querySelector('label');
        if (labelImagen) labelImagen.textContent = '';
    }

    // Limpiar el texto de la sección de tags
    const textoRs = document.getElementById('textoRs');
    if (textoRs) {
        textoRs.textContent = '';
    }

    // Restablecer los valores de window (si es necesario)
    window.Tags = [];
    window.NormalText = '';

    // Desmarcar los checkboxes
    const descargaCheckbox = document.getElementById('descarga');
    if (descargaCheckbox) descargaCheckbox.checked = false;

    const exclusivoCheckbox = document.getElementById('exclusivo');
    if (exclusivoCheckbox) exclusivoCheckbox.checked = false;

    const colabCheckbox = document.getElementById('colab');
    if (colabCheckbox) colabCheckbox.checked = false;
}

/*
cuando se borra un audio, debería detener la reproduccion, pero no es lo que hace, en cambio cuando borro, se empieza a reproducir aunque no estaba reproduciendose

let waveSurferInstances = {};
    const subidaAudio = async file => {
        subidaAudioEnProgreso = true;
        try {
            alert(`Audio subido: ${file.name}`);
            previewAudio.style.display = 'block';
            opciones.style.display = 'flex';

            // Crear un ID temporal para el archivo
            const tempId = `temp-${Date.now()}`;
            const progressBarId = waveAudio(file, tempId);

            // Agregamos el objeto temporalmente a audiosData
            audiosData.push({tempId, fileUrl: null, fileId: null});

            const {fileUrl, fileId} = await subidaRsBackend(file, progressBarId);

            // Actualizar el audio en audiosData con los valores reales cuando lleguen del backend
            const index = audiosData.findIndex(audio => audio.tempId === tempId);
            if (index !== -1) {
                audiosData[index].fileUrl = fileUrl;
                audiosData[index].fileId = fileId;

                // Actualizar el atributo data-audio-url en el contenedor de la waveform con el verdadero fileUrl
                const waveformContainer = document.querySelector(`[data-temp-id="${tempId}"]`);
                if (waveformContainer) {
                    waveformContainer.setAttribute('data-audio-url', fileUrl);
                }
            }

            // Verificamos si ya hay 30 audios subidos
            if (audiosData.length > 30) {
                alert('Ya has subido el límite máximo de 30 audios.');
            }

            subidaAudioEnProgreso = false;
        } catch (error) {
            alert('Hubo un problema al cargar el Audio. Inténtalo de nuevo.');
            subidaAudioEnProgreso = false;
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
                </div>`;

            previewAudio.appendChild(newWaveform);
            inicializarWaveform(audioContainerId, e.target.result);

            const deleteButton = newWaveform.querySelector('.delete-waveform');
            deleteButton.addEventListener('click', () => eliminarWaveform(audioContainerId, tempId));
        };

        reader.readAsDataURL(file);
        return progressBarId;
    };

    const eliminarWaveform = (containerId, tempId) => {
        const wrapper = document.getElementById(containerId);
    
        if (wrapper) {
            // Detener y destruir la instancia de WaveSurfer si existe
            if (waveSurferInstances[containerId]) {
                waveSurferInstances[containerId].destroy();
                delete waveSurferInstances[containerId]; // Eliminar la instancia después de destruirla
            }
    
            // Eliminar el contenedor del DOM
            wrapper.parentNode.removeChild(wrapper);
        }
    
        // Eliminar el audio de audiosData usando tempId
        const index = audiosData.findIndex(audio => audio.tempId === tempId);
        if (index !== -1) {
            audiosData.splice(index, 1);
        }
    
        // Si audiosData está vacío, ocultar previewAudio
        if (audiosData.length === 0) {
            previewAudio.style.display = 'none';
        }
    };
*/

window.inicializarWaveform = function (containerId, audioSrc) {
    const container = document.getElementById(containerId);

    if (container && audioSrc) {
        const options = {
            container: container,
            waveColor: '#d9dcff',
            progressColor: '#4353ff',
            backend: 'WebAudio',
            height: 60,
            barWidth: 2,
            responsive: true
        };

        // Crear instancia de WaveSurfer
        let wavesurfer = WaveSurfer.create(options);

        // Almacenar la instancia en waveSurferInstances
        waveSurferInstances[containerId] = wavesurfer;

        wavesurfer.load(audioSrc);
        console.log(`Cargando el archivo de audio: ${audioSrc}`);

        wavesurfer.on('ready', function () {
            console.log('Audio listo. Forma de onda generada.');
        });

        container.addEventListener('click', function () {
            console.log('Toggling play/pause');
            wavesurfer.playPause();
        });
    } else {
        console.log('Error: No se encontró el contenedor o el audioSrc no es válido.');
    }
};
