// Variables globales
let imagenUrl, imagenId, audioUrl, audioId, archivoUrl, archivoId;
let subidaAudioEnProgreso = false;
let subidaImagenEnProgreso = false;
let subidaArchivoEnProgreso = false;

// Logs
const enableLogRS = true;
const logRS = enableLogRS ? console.log : () => {};

function iniciarRS() {
    logRS('iniciarRS fue llamado');
    const formRs = document.getElementById('formRs');
    if (formRs) {
        logRS('formRs existe');
        imagenUrl = imagenId = audioUrl = audioId = archivoUrl = archivoId = null;
        subidaAudioEnProgreso = subidaImagenEnProgreso = subidaArchivoEnProgreso = false;
        setupRs();
    } else {
        logRS('formRs no existe');
    }
}

function setupRs() {
    handleSubida();
    handleEnvio();
    setupPlaceholder();
    TagEnTexto();
}

function verificarCamposRs() {
    const textoRsDiv = document.getElementById('textoRs');
    textoRsDiv.setAttribute('placeholder', 'Puedes agregar tags agregando un #');
    const tags = window.Tags || [];
    const textoNormal = window.NormalText || '';

    return () => {
        if (subidaAudioEnProgreso || subidaImagenEnProgreso || subidaArchivoEnProgreso) {
            alert('Espera a que finalicen las subidas de archivos.');
            return false;
        }
        if (textoNormal.length < 3 || textoNormal.length > 800) {
            alert('El texto debe tener entre 3 y 800 caracteres.');
            textoRsDiv.innerText = textoNormal.substring(0, 800);
            return false;
        }
        if (tags.length === 0 || tags.some(tag => tag.length < 3)) {
            alert('Debe incluir al menos un tag, y cada tag debe tener al menos 3 caracteres.');
            return false;
        }
        return true;
    };
}

function selectorFormTipo() {
    document.addEventListener('change', event => {
        if (event.target.matches('.custom-checkbox input[type="checkbox"]')) {
            const label = event.target.closest('label');
            label.style.color = event.target.checked ? '#ffffff' : '#6b6b6b';
            label.style.background = event.target.checked ? '#131313' : '';
        }
    });
}

async function handleEnvio() {
    const button = document.getElementById('enviarRs');
    button.addEventListener('click', async () => {
        button.disabled = true;
        const verificarCampos = verificarCamposRs();
        if (!verificarCampos()) {
            button.disabled = false;
            return;
        }

        const tags = window.Tags || [];
        const textoNormal = window.NormalText || '';

        const descarga = document.getElementById('descargacheck').checked ? 1 : 0;
        const exclusivo = document.getElementById('exclusivocheck').checked ? 1 : 0;
        const colab = document.getElementById('colabcheck').checked ? 1 : 0;

        const data = {
            imagenUrl, imagenId, audioUrl, audioId, archivoUrl, archivoId,
            tags, textoNormal, descarga, exclusivo, colab
        };

        console.table(data);

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

function handleSubida() {
    const elements = ['formRs', 'botonAudio', 'botonImagen', 'previewAudio', 'previewArchivo', 'opciones', 'botonArchivo', 'previewImagen', 'enviarRs'].reduce((acc, id) => {
        acc[id] = document.getElementById(id);
        return acc;
    }, {});

    const { formRs, botonAudio, botonImagen, previewAudio, previewArchivo, opciones, botonArchivo, previewImagen } = elements;

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (!file || file.size > 50 * 1024 * 1024) {
            alert('El archivo no puede superar los 50 MB.');
            return;
        }
        if (file.type.startsWith('audio/')) subidaArchivo(file, 'audio');
        else if (file.type.startsWith('image/')) subidaArchivo(file, 'imagen');
        else subidaArchivo(file, 'archivo');
    };

    const subidaArchivo = async (file, tipo) => {
        const progressBarId = `barraProgreso${tipo.charAt(0).toUpperCase() + tipo.slice(1)}`;
        opciones.style.display = 'flex';
        const preview = tipo === 'audio' ? previewAudio : tipo === 'imagen' ? previewImagen : previewArchivo;
        preview.style.display = 'block';

        if (tipo === 'audio') {
            previewAudio.innerHTML = generarWaveform(file, progressBarId);
            inicializarWaveform(`waveform-container-${Date.now()}`, URL.createObjectURL(file));
        } else if (tipo === 'imagen') {
            previewImagen.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
        } else {
            previewArchivo.innerHTML = `<div class="file-name">${file.name}</div><div id="${progressBarId}" class="progress"></div>`;
        }

        try {
            alert(`${tipo.charAt(0).toUpperCase() + tipo.slice(1)} subido: ${file.name}`);
            const { fileUrl, fileId } = await subirArchivoBackend(file, progressBarId);
            if (tipo === 'audio') [audioUrl, audioId] = [fileUrl, fileId];
            else if (tipo === 'imagen') [imagenUrl, imagenId] = [fileUrl, fileId];
            else [archivoUrl, archivoId] = [fileUrl, fileId];
        } catch {
            alert(`Hubo un problema al cargar el ${tipo}. Inténtalo de nuevo.`);
        }
    };

    const generarWaveform = (file, progressBarId) => `
        <div id="waveform-container-${Date.now()}" class="waveform-container" data-audio-url="${URL.createObjectURL(file)}">
            <audio controls style="width: 100%;">
                <source src="${URL.createObjectURL(file)}" type="${file.type}">
            </audio>
            <div class="file-name">${file.name}</div>
        </div>
        <div class="progress-bar">
            <div id="${progressBarId}" class="progress"></div>
        </div>`;

    formRs.addEventListener('click', event => {
        const targetClass = event.target.closest('.previewAudio') ? 'audio/*' : event.target.closest('.previewImagen') ? 'image/*' : null;
        if (targetClass) abrirSelectorArchivos(targetClass);
    });

    const abrirSelectorArchivos = tipo => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = tipo;
        input.onchange = inicialSubida;
        input.click();
    };

    botonArchivo.addEventListener('click', () => abrirSelectorArchivos('*'));
    botonAudio.addEventListener('click', () => abrirSelectorArchivos('audio/*'));
    botonImagen.addEventListener('click', () => abrirSelectorArchivos('image/*'));

    formRs.addEventListener('dragover', e => {
        e.preventDefault();
        formRs.style.backgroundColor = '#e9e9e9';
    });
    formRs.addEventListener('dragleave', e => {
        e.preventDefault();
        formRs.style.backgroundColor = '';
    });
    formRs.addEventListener('drop', inicialSubida);
}

async function subirArchivoBackend(file, progressBarId) {
    logRS('Iniciando subida de archivo', { fileName: file.name, fileSize: file.size });
    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    formData.append('file_hash', await generateFileHash(file));

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const progressBar = document.getElementById(progressBarId);
                if (progressBar) progressBar.style.width = `${(e.loaded / e.total) * 100}%`;
            }
        };

        xhr.onload = () => {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        resolve(result.data);
                    } else {
                        reject(new Error('Error en la respuesta del servidor'));
                    }
                } catch (error) {
                    reject(error);
                }
            } else {
                reject(new Error(`Error en la carga del archivo. Status: ${xhr.status}`));
            }
        };

        xhr.onerror = () => reject(new Error('Error en la conexión con el servidor'));

        xhr.send(formData);
    });
}

function setupPlaceholder() {
    const div = document.getElementById('textoRs');
    div.addEventListener('focus', () => {
        if (div.innerHTML === '') div.innerHTML = '';
    });
    div.addEventListener('blur', () => {
        if (div.innerHTML === '') div.innerHTML = '';
    });
}

function limpiarCamposRs() {
    imagenUrl = imagenId = audioUrl = audioId = archivoUrl = archivoId = null;
    ['previewAudio', 'previewArchivo', 'previewImagen'].forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'none';
            const label = element.querySelector('label');
            if (label) label.textContent = '';
        }
    });

    const opciones = document.getElementById('opciones');
    if (opciones) opciones.style.display = 'none';

    const textoRs = document.getElementById('textoRs');
    if (textoRs) textoRs.textContent = '';

    window.Tags = [];
    window.NormalText = '';

    ['descarga', 'exclusivo', 'colab'].forEach(id => {
        const checkbox = document.getElementById(id);
        if (checkbox) checkbox.checked = false;
    });
}