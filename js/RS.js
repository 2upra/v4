// Variables globales
let imagenUrl, imagenId, audioUrl, audioId, archivoUrl, archivoId;
let subidaAudioEnProgreso = false;
let subidaImagenEnProgreso = false;
let subidaArchivoEnProgreso = false;

let audiosData = [];
let maxAudios = 30;
let audioData = {};
const nombreRolaData = {};

let uploadInProgressCount = 0;
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

        audioData = {};
        uploadInProgressCount = 0;
        audiosData = [];
        nombreRolaData;

        subidaAudioEnProgreso = false;
        subidaImagenEnProgreso = false;
        subidaArchivoEnProgreso = false;
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

    function verificarCampos() {
        // Verificar si hay alguna subida en progreso
        if (uploadInProgressCount > 0) {
            alert('Espera a que se completen las subidas de archivos.');
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

async function selectorformtipo() {
    // Al cargar la página, se activa el checkbox 'descargacheck' por defecto
    const descargacheck = document.getElementById('descargacheck');
    descargacheck.checked = true;
    const label = descargacheck.closest('label');
    label.style.color = '#ffffff';
    label.style.background = '#131313';

    const musiccheck = document.getElementById('musiccheck');
    const exclusivocheck = document.getElementById('exclusivocheck');
    const colabcheck = document.getElementById('colabcheck');

    document.addEventListener('change', async function (event) {
        if (event.target.matches('.custom-checkbox input[type="checkbox"]')) {
            const checkedCheckboxes = document.querySelectorAll('.custom-checkbox input[type="checkbox"]:checked');

            // Si hay más de 2 checkboxes seleccionados, desmarca el que acaba de ser seleccionado
            if (checkedCheckboxes.length > 2) {
                event.target.checked = false;
                alert('Solo puedes seleccionar un máximo de 2 opciones.');
                return;
            }

            // Si se marca 'musiccheck', desmarca los demás checkboxes y pide confirmación
            if (event.target.id === 'musiccheck' && event.target.checked) {
                // Mostrar la alerta personalizada y esperar confirmación del usuario
                const confirmacion = await window.confirm("Vas a publicar música en nuestra plataforma y en otras plataformas de stream.");
                if (!confirmacion) {
                    // Si el usuario no confirma, desmarcar 'musiccheck'
                    event.target.checked = false;
                    return;
                }

                descargacheck.checked = false;
                exclusivocheck.checked = false;
                colabcheck.checked = false;
                resetStyles(); // Restablecer estilos de los otros checkboxes
            }

            // Si se marca 'exclusivocheck', desmarca 'colabcheck' y 'musiccheck'
            if (event.target.id === 'exclusivocheck' && event.target.checked) {
                colabcheck.checked = false; // Desactiva 'colabcheck'
                musiccheck.checked = false; // Desactiva 'musiccheck'
                const colabLabel = colabcheck.closest('label');
                colabLabel.style.color = '#6b6b6b';
                colabLabel.style.background = '';
                resetStyles(); // Restablecer estilos de los otros checkboxes
            }

            // Si se marca 'colabcheck', desmarca 'exclusivocheck' y 'musiccheck'
            if (event.target.id === 'colabcheck' && event.target.checked) {
                exclusivocheck.checked = false; // Desactiva 'exclusivocheck'
                musiccheck.checked = false; // Desactiva 'musiccheck'
                const exclusivocLabel = exclusivocheck.closest('label');
                exclusivocLabel.style.color = '#6b6b6b';
                exclusivocLabel.style.background = '';
                resetStyles(); // Restablecer estilos de los otros checkboxes
            }

            // Si se marca 'descargacheck', desmarca 'musiccheck'
            if (event.target.id === 'descargacheck' && event.target.checked) {
                musiccheck.checked = false; // Desactiva 'musiccheck'
                resetStyles(); // Restablecer estilos de los otros checkboxes
            }

            // Estilo al checkbox seleccionado
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

    // Función para restablecer estilos cuando se desmarcan checkboxes
    function resetStyles() {
        const checkboxes = document.querySelectorAll('.custom-checkbox input[type="checkbox"]');
        checkboxes.forEach(function (checkbox) {
            const label = checkbox.closest('label');
            if (!checkbox.checked) {
                label.style.color = '#6b6b6b';
                label.style.background = '';
            }
        });
    }
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
        const musicCheckbox = document.getElementById('musiccheck');
        const descarga = descargaCheckbox.checked ? descargaCheckbox.value : 0;
        const exclusivo = exclusivoCheckbox.checked ? exclusivoCheckbox.value : 0;
        const colab = colabCheckbox.checked ? colabCheckbox.value : 0;
        const music = musicCheckbox.checked ? musicCheckbox.value : 0;
        const uniqueAudioUrls = new Set(); // Para almacenar URLs únicas
        const uniqueAudioIds = new Set(); 

        // Función para validar la URL
        const isValidUrl = url => {
            const requiredPrefix = 'https://2upra.com/wp-content/uploads';
            return url.startsWith(requiredPrefix);
        };

        const nombreInputs = document.querySelectorAll('.nombreAudioRs');

        audiosData.slice(0, maxAudios).forEach((audio, index) => {
            const audioNumber = index + 1;

            if (!isValidUrl(audio.audioUrl)) {
                console.warn(`URL inválida para el audio con ID ${audio.audioId}: ${audio.audioUrl}`);
                return; 
            }

            if (!uniqueAudioUrls.has(audio.audioUrl) && !uniqueAudioIds.has(audio.audioId)) {
                audioData[`audioUrl${audioNumber}`] = audio.audioUrl;
                audioData[`audioId${audioNumber}`] = audio.audioId;


                uniqueAudioUrls.add(audio.audioUrl);
                uniqueAudioIds.add(audio.audioId);

                const tempId = audio.tempId; // Asumiendo que cada audio tiene un tempId único
                const nombreInput = Array.from(nombreInputs).find(input => input.id === `nombre-${tempId}`);

                if (nombreInput) {
                    let nombreRola = nombreInput.value.trim();

                    const MAX_NOMBRE_LENGTH = 100; 

                    if (nombreRola.length > MAX_NOMBRE_LENGTH) {
                        nombreRola = nombreRola.substring(0, MAX_NOMBRE_LENGTH);
                        console.warn(`El nombre de la canción para audio ID ${audio.audioId} excede el máximo de caracteres. Se truncó a ${MAX_NOMBRE_LENGTH} caracteres.`);
                    }
                    nombreRolaData[`nombreRola${audioNumber}`] = nombreRola;
                } else {
                    nombreRolaData[`nombreRola${audioNumber}`] = null;
                }
            }
        });

        // Construir el objeto final de datos a enviar
        const data = {
            imagenUrl1: typeof imagenUrl !== 'undefined' ? imagenUrl : null,
            imagenId1: typeof imagenId !== 'undefined' ? imagenId : null,
            archivoUrl1: typeof archivoUrl !== 'undefined' ? archivoUrl : null,
            archivoId1: typeof archivoId !== 'undefined' ? archivoId : null,
            ...audioData,
            ...nombreRolaData, 
            tags,
            textoNormal,
            descarga,
            exclusivo,
            colab,
            music
        };



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

    //esto no funciona, no agrega column a <div class="previewsForm NGEESM">
    const actualizarFlexDirection = () => {
        const previewsFormDiv = document.querySelector('.previewsForm.NGEESM.RS');

        if (previewsFormDiv) {
            if (audiosData.length > 1) {
                previewsFormDiv.style.flexDirection = 'column';
            } else {
                previewsFormDiv.style.flexDirection = ''; // Restablece al valor por defecto
            }
        }
    };

    // Obtén la referencia al checkbox de música
    const musicCheckbox = document.getElementById('musiccheck');

    // Función para actualizar la visibilidad de los campos de nombre
    const actualizarCamposNombre = () => {
        const cantidadAudios = audiosData.length;
        const mostrarCampos = musicCheckbox.checked && cantidadAudios > 1;

        audiosData.forEach(audio => {
            const inputNombre = document.getElementById(`nombre-${audio.tempId}`);
            if (inputNombre) {
                inputNombre.style.display = mostrarCampos ? 'block' : 'none';
            }
        });
    };

    // Agrega un listener para cambios en el checkbox
    musicCheckbox.addEventListener('change', actualizarCamposNombre);

    const subidaAudio = async file => {
        try {
            alert(`Audio subido: ${file.name}`);
            previewAudio.style.display = 'block';
            opciones.style.display = 'flex';
            const tempId = `temp-${Date.now()}`;
            const progressBarId = waveAudio(file, tempId);
            audiosData.push({tempId, audioUrl: null, audioId: null});

            // Actualiza la dirección de flexión después de agregar el audio
            actualizarFlexDirection();

            const {fileUrl, fileId} = await subidaRsBackend(file, progressBarId);
            const index = audiosData.findIndex(audio => audio.tempId === tempId);
            if (index !== -1) {
                audiosData[index].audioUrl = fileUrl;
                audiosData[index].audioId = fileId;
                const waveformContainer = document.querySelector(`[data-temp-id="${tempId}"]`);
                if (waveformContainer) {
                    waveformContainer.setAttribute('data-audio-url', fileUrl);
                }
            }
            if (audiosData.length > 30) {
                alert('Ya has subido el límite máximo de 30 audios.');
            }

            // Actualiza los campos de nombre después de subir el audio
            actualizarCamposNombre();
        } catch (error) {
            alert('Hubo un problema al cargar el Audio. Inténtalo de nuevo.');
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

            previewAudio.appendChild(newWaveform);
            inicializarWaveform(audioContainerId, e.target.result);
            const deleteButton = newWaveform.querySelector('.delete-waveform');
            deleteButton.addEventListener('click', event => {
                event.stopPropagation();
                eliminarWaveform(audioContainerId, tempId);
            });

            // Actualiza los campos de nombre después de agregar un nuevo audio
            actualizarCamposNombre();
        };

        reader.readAsDataURL(file);
        return progressBarId;
    };

    const eliminarWaveform = (containerId, tempId) => {
        const wrapper = document.getElementById(containerId);

        if (wrapper) {
            wrapper.parentNode.removeChild(wrapper);
            if (waveSurferInstances[containerId]) {
                waveSurferInstances[containerId].unAll();
                if (waveSurferInstances[containerId].isPlaying()) {
                    waveSurferInstances[containerId].stop();
                }
                waveSurferInstances[containerId].destroy();
                delete waveSurferInstances[containerId];
            }
        }

        const index = audiosData.findIndex(audio => audio.tempId === tempId);
        if (index !== -1) {
            audiosData.splice(index, 1);
        }

        if (audiosData.length === 0) {
            previewAudio.style.display = 'none';
        }

        actualizarCamposNombre();
        actualizarFlexDirection();
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
sigue sucediendo, tambien parece que como el boton eliminar esta dentro del wave, al dar click tambuen cuenta como una reproducion, y se reproduciendo aunque se borra del dom

    const eliminarWaveform = (containerId, tempId) => {
        const wrapper = document.getElementById(containerId);
    
        if (wrapper) {
            // Eliminar el contenedor del DOM primero
            wrapper.parentNode.removeChild(wrapper);
    
            // Detener la reproducción y destruir la instancia de WaveSurfer si existe
            if (waveSurferInstances[containerId]) {
                // Eliminar todos los eventos para evitar reproducción accidental
                waveSurferInstances[containerId].unAll();
    
                // Detener si estaba reproduciendo
                if (waveSurferInstances[containerId].isPlaying()) {
                    waveSurferInstances[containerId].stop();
                }
    
                // Destruir la instancia
                waveSurferInstances[containerId].destroy();
                delete waveSurferInstances[containerId];
            }
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
