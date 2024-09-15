// Variables globales
let imagenUrl, imagenId, audioUrl, audioId, archivoUrl, archivoId;
let subidaAudioEnProgreso = false;
let subidaImagenEnProgreso = false;
let subidaArchivoEnProgreso = false;
// Logs
let enablelogRS = true;
const logRS = enablelogRS ? console.log : function () {};

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

/*
No registra bien los checkbox, siempre se envian en 0 aunque marque uno igual es cero

<div class="bloque flex-row"" id="opciones" style="display: none">
    <p>Opciones de post</p>
    <div class="flex flex-row gap-2">
        <label class="custom-checkbox">
            <input type="checkbox" id="descarga" name="descarga" value="1">
            <span class="checkmark"></span>
            <?php echo $GLOBALS['descargaicono']; ?>
        </label>
        <label class="custom-checkbox">
            <input type="checkbox" id="exclusivo" name="exclusivo" value="1">
            <span class="checkmark"></span>
            <?php echo $GLOBALS['estrella']; ?>
        </label>
        <label class="custom-checkbox">
            <input type="checkbox" id="colab" name="colab" value="1">
            <span class="checkmark"></span>
            <?php echo $GLOBALS['iconocolab']; ?>
        </label>
*/

async function envioRs() {
    const button = document.getElementById('enviarRs');

    button.addEventListener('click', async (event) => {
        button.disabled = true;
        
        const verificarCampos = verificarCamposRs();
        const valid = verificarCampos();
        if (!valid) {
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

        console.log(
            'Valores finales:',
            'descarga:', descarga, 
            'exclusivo:', exclusivo, 
            'colab:', colab, 
            'tags:', tags,
            'textoNormal:', textoNormal,
        );

        //codigo de envio omitido
        const data = {
            imagenUrl: typeof imagenUrl !== 'undefined' ? imagenUrl : null,
            imagenId: typeof imagenId !== 'undefined' ? imagenId : null,
            audioUrl: typeof audioUrl !== 'undefined' ? audioUrl : null,
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

    const subidaAudio = async file => {
        subidaAudioEnProgreso = true;
        try {
            alert(`Audio subido: ${file.name}`);
            previewAudio.style.display = 'block';
            opciones.style.display = 'flex';
            const progressBarId = waveAudio(file);
            const {fileUrl, fileId} = await subidaRsBackend(file, progressBarId);
            audioUrl = fileUrl;
            audioId = fileId;
            subidaAudioEnProgreso = false;
        } catch (error) {
            alert('Hubo un problema al cargar el Audio. Inténtalo de nuevo.');
            subidaAudioEnProgreso = false;
        }
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
        imagenSelecionada = file;
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

    const waveAudio = file => {
        const reader = new FileReader(),
            audioContainerId = `waveform-container-${Date.now()}`,
            progressBarId = `progress-${Date.now()}`;
        reader.onload = e => {
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
