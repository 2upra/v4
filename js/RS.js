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
let enablelogRS = false;
const logRS = enablelogRS ? console.log : function () {};
let waveSurferInstances = {};

let rsIniciado = false;
function iniciarRS() {
    if (rsIniciado) return;
    rsIniciado = true;
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
        selectorformtipo();
        selectorFanArtista();
        selectorTipoPost();
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

        const fanCheck = document.getElementById('fancheck');
        const artistaCheck = document.getElementById('artistacheck');
        const tiendaCheck = document.getElementById('tiendacheck');
        const musicCheck = document.getElementById('musiccheck');

        // Verificar condiciones de checkboxes
        if (!(musicCheck.checked || tiendaCheck.checked)) {
            if (!fanCheck.checked && !artistaCheck.checked) {
                alert('Debe seleccionar al menos una opción: Área de fans o Área de artistas.');
                return false;
            }
        }

        // Validar inputs de texto si musicCheck está marcado
        if (musicCheck.checked) {
            const nombreInputs = document.querySelectorAll('input.nombreAudioRs');
            for (const input of nombreInputs) {
                const value = input.value.trim();
                if (value.length < 3) {
                    alert('Todos los títulos deben tener al menos 3 caracteres.');
                    input.focus();
                    return false;
                }
                if (value.length > 100) {
                    alert('Todos los títulos deben tener como máximo 100 caracteres.');
                    input.focus();
                    return false;
                }
            }
        }

        // Validar inputs de número si tiendaCheck está marcado
        if (tiendaCheck.checked) {
            const precioInputs = document.querySelectorAll('input.precioAudioRs');
            for (const input of precioInputs) {
                const value = parseFloat(input.value);
                if (isNaN(value) || value < 5 || value > 100) {
                    alert('Todos los precios deben ser numéricos y estar entre 5 y 100 USD.');
                    input.focus();
                    return false;
                }
            }
        }

        // Verificación de texto y tags
        const tags = window.Tags || [];
        const textoNormal = window.NormalText || '';

        if (textoNormal.length < 3) {
            alert('El texto debe tener al menos 3 caracteres.');
            return false;
        }
        if (textoNormal.length > 800) {
            alert('El texto no puede exceder los 800 caracteres.');
            textoRsDiv.innerText = textoNormal.substring(0, 800);
            return false;
        }
        if (tags.length === 0) {
            alert('Debe incluir al menos un tag.');
            return false;
        }
        if (tags.some(tag => tag.length < 3)) {
            alert('Cada tag debe tener al menos 3 caracteres.');
            return false;
        }

        // Verificación de audiosData para múltiples posts
        if (audiosData.length > 1) {
            const individualPost = document.getElementById('individualPost');
            const multiplePost = document.getElementById('multiplePost');

            if (!individualPost.checked && !multiplePost.checked) {
                alert('Debe seleccionar al menos una opción: Post individual o múltiples, porque estás intentando subir varios audios :)');
                return false;
            }
        }

        return true;
    }

    return verificarCampos;
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
        const fancheck = document.getElementById('fancheck');
        const artistacheck = document.getElementById('artistacheck');
        const individualPost = document.getElementById('individualPost');
        const multiplePost = document.getElementById('multiplePost');
        const tiendacheck = document.getElementById('tiendacheck');

        const fan = fancheck.checked ? fancheck.value : 0;
        const artista = artistacheck.checked ? artistacheck.value : 0;
        const descarga = descargaCheckbox.checked ? descargaCheckbox.value : 0;
        const exclusivo = exclusivoCheckbox.checked ? exclusivoCheckbox.value : 0;
        const colab = colabCheckbox.checked ? colabCheckbox.value : 0;
        const music = musicCheckbox.checked ? musicCheckbox.value : 0;
        const individual = individualPost.checked ? individualPost.value : 0;
        const multiple = multiplePost.checked ? multiplePost.value : 0;
        const tienda = tiendacheck.checked ? tiendacheck.value : 0;

        const uniqueAudioUrls = new Set(); // Para almacenar URLs únicas
        const uniqueAudioIds = new Set();

        const isValidNombre = nombre => {
            return nombre && nombre.trim().length >= 3;
        };

        const isValidUrl = url => {
            const requiredPrefix = 'https://2upra.com/wp-content/uploads';
            return url.startsWith(requiredPrefix);
        };

        const nombreInputs = document.querySelectorAll('.nombreAudioRs');
        const precioInputs = document.querySelectorAll('.precioAudioRs');

        let nombreRolaData = {}; // Declaración de nombreRolaData como objeto
        let precioRolaData = {}; // Declaración de precioRolaData como objeto

        /*
        RS.js?ver=0.2.165:243  Uncaught (in promise) ReferenceError: precioRolaData is not defined
        at RS.js?ver=0.2.165:243:25
        at Array.forEach (<anonymous>)
        at HTMLButtonElement.<anonymous> (RS.js?ver=0.2.165:205:40)
        */

        audiosData.slice(0, maxAudios).forEach((audio, index) => {
            const audioNumber = index + 1;

            if (!isValidUrl(audio.audioUrl)) {
                console.warn(`URL inválida para el audio con ID ${audio.audioId}: ${audio.audioUrl}`);
                //return;
            }

            if (!uniqueAudioUrls.has(audio.audioUrl) && !uniqueAudioIds.has(audio.audioId)) {
                audioData[`audioUrl${audioNumber}`] = audio.audioUrl;
                audioData[`audioId${audioNumber}`] = audio.audioId;

                uniqueAudioUrls.add(audio.audioUrl);
                uniqueAudioIds.add(audio.audioId);

                const tempId = audio.tempId; // Asumiendo que cada audio tiene un tempId único
                const nombreInput = Array.from(nombreInputs).find(input => input.id === `nombre-${tempId}`);
                const precioInput = Array.from(precioInputs).find(input => input.id === `precio-${tempId}`);

                // Recoger nombre de la canción
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

                // Recoger precio del audio (si existe)
                if (precioInput) {
                    const precioRola = parseFloat(precioInput.value);
                    if (!isNaN(precioRola) && precioRola >= 5 && precioRola <= 100) {
                        precioRolaData[`precioRola${audioNumber}`] = precioRola;
                    } else {
                        console.warn(`El precio del audio con ID ${audio.audioId} es inválido o fuera de rango (debe ser entre 5 y 100 USD).`);
                        precioRolaData[`precioRola${audioNumber}`] = null; // O bien, maneja el caso como consideres.
                    }
                } else {
                    precioRolaData[`precioRola${audioNumber}`] = null;
                }
            }
        });

        const nombreLanzamientoInput = document.getElementById('nombreLanzamiento');

        // Construir el objeto final de datos a enviar
        const data = {
            imagenUrl1: typeof imagenUrl !== 'undefined' ? imagenUrl : null,
            imagenId1: typeof imagenId !== 'undefined' ? imagenId : null,
            archivoUrl1: typeof archivoUrl !== 'undefined' ? archivoUrl : null,
            archivoId1: typeof archivoId !== 'undefined' ? archivoId : null,
            ...audioData,
            ...nombreRolaData,
            ...precioRolaData,
            tags,
            textoNormal,
            descarga,
            fan,
            artista,
            exclusivo,
            colab,
            music,
            individual,
            multiple,
            tienda
        };

        if (music) {
            // Verificar la URL de la imagen
            if (!data.imagenUrl1 || !isValidUrl(data.imagenUrl1)) {
                alert('Cuando seleccionas "Music", es obligatorio incluir una imagen válida');
                button.innerText = originalText;
                button.disabled = false;
                return;
            }

            // Verificar si el campo nombreLanzamiento está visible y si su valor es válido
            if (nombreLanzamientoInput.style.display !== 'none') {
                if (!nombreLanzamientoInput.value || nombreLanzamientoInput.value.length < 3) {
                    alert('El título de lanzamiento debe tener al menos 3 caracteres.');
                    button.innerText = originalText;
                    button.disabled = false;
                    return;
                } else {
                    // Agregar el valor de nombreLanzamiento al objeto data
                    data.nombreLanzamiento = nombreLanzamientoInput.value;
                }
            }

            // Verificar que todos los nombres de audio tienen al menos 3 caracteres
            let nombresValidos = true;
            nombreInputs.forEach(input => {
                if (input.value && !isValidNombre(input.value)) {
                    nombresValidos = false;
                }
            });

            if (!nombresValidos) {
                alert('Cuando seleccionas "Music", todos los nombres de audio deben tener al menos 3 caracteres');
                button.innerText = originalText;
                button.disabled = false;
                return;
            }
        }

        if (tienda) {
            if (!data.imagenUrl1 || !isValidUrl(data.imagenUrl1)) {
                alert('Para vender un sample o beat es obligatorio incluir una imagen válida');
                button.innerText = originalText;
                button.disabled = false;
                return; // Asegúrate de detener el flujo si no es válido
            }
        }

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
    const ids = ['formRs', 'botonAudio', 'botonImagen', 'previewAudio', 'previewArchivo', 'opciones', 'botonArchivo', 'previewImagen', 'enviarRs', 'ppp3', 'multiplesAudios'];
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

    const {formRs, botonAudio, botonImagen, previewAudio, previewArchivo, opciones, botonArchivo, previewImagen, enviarRs, ppp3, multiplesAudios} = elements;

    const inicialSubida = event => {
        event.preventDefault();
        const files = event.dataTransfer?.files || event.target.files;

        if (!files || files.length === 0) return;

        for (let i = 0; i < files.length; i++) {
            const file = files[i];

            if (file.size > 50 * 1024 * 1024) {
                alert(`El archivo "${file.name}" no puede superar los 50 MB.`);
                continue; // Salta al siguiente archivo si el actual es demasiado grande
            }

            if (file.type.startsWith('audio/')) {
                subidaAudio(file);
            } else if (file.type.startsWith('image/')) {
                subidaImagen(file);
            } else {
                subidaArchivo(file);
            }
        }
    };

    const actualizarFlexDirection = () => {
        const previewsFormDiv = document.querySelector('.previewsForm.NGEESM.RS');

        if (previewsFormDiv) {
            if (audiosData.length > 1) {
                multiplesAudios.style.display = 'flex';
                previewsFormDiv.style.flexDirection = 'column';
            } else {
                previewsFormDiv.style.flexDirection = '';
            }
        }
    };

    // Obtenemos las referencias a los checkbox
    const musicCheckbox = document.getElementById('musiccheck');
    const tiendaCheckbox = document.getElementById('tiendacheck');
    const nombreLanzamiento = document.getElementById('nombreLanzamiento');

    const actualizarCamposNombre = () => {
        setTimeout(() => {
            const cantidadAudios = audiosData.length;
            const mostrarCamposNombre = musicCheckbox.checked && cantidadAudios > 0;

            audiosData.forEach(audio => {
                // Actualizar visibilidad de los campos de nombre
                const inputNombre = document.getElementById(`nombre-${audio.tempId}`);
                if (inputNombre) {
                    inputNombre.style.display = mostrarCamposNombre ? 'block' : 'none';
                    nombreLanzamiento.style.display = mostrarCamposNombre ? 'block' : 'none';
                }

                // Actualizar visibilidad de los campos de precio si tiendacheck está activo
                const mostrarCamposPrecio = tiendaCheckbox.checked;
                const inputPrecio = document.getElementById(`precio-${audio.tempId}`);
                if (inputPrecio) {
                    inputPrecio.style.display = mostrarCamposPrecio ? 'block' : 'none';
                }
            });
        }, 10);
    };

    // Añadimos el listener para el evento 'change' en ambos checkboxes
    musicCheckbox.addEventListener('change', actualizarCamposNombre);
    tiendaCheckbox.addEventListener('change', actualizarCamposNombre);

    const subidaAudio = async file => {
        try {
            alert(`Audio subido: ${file.name}`);
            previewAudio.style.display = 'block';
            ppp3.style.display = 'flex';
            opciones.style.display = 'flex';
            const tempId = `temp-${Date.now()}`;
            const progressBarId = waveAudio(file, tempId);
            audiosData.push({tempId, audioUrl: null, audioId: null});
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
            actualizarCamposNombre();
        } catch (error) {
            alert('Hubo un problema al cargar el Audio. Inténtalo de nuevo.');
        }
    };
    //como se hace para que el campo precio solo acepte valores numeros y que al final automaticamente aparezca el simbolo
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
                    <button class="delete-waveform"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" viewBox="0 0 16 16" width="16" style="color: currentcolor;"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.75 2.75C6.75 2.05964 7.30964 1.5 8 1.5C8.69036 1.5 9.25 2.05964 9.25 2.75V3H6.75V2.75ZM5.25 3V2.75C5.25 1.23122 6.48122 0 8 0C9.51878 0 10.75 1.23122 10.75 2.75V3H12.9201H14.25H15V4.5H14.25H13.8846L13.1776 13.6917C13.0774 14.9942 11.9913 16 10.6849 16H5.31508C4.00874 16 2.92263 14.9942 2.82244 13.6917L2.11538 4.5H1.75H1V3H1.75H3.07988H5.25ZM4.31802 13.5767L3.61982 4.5H12.3802L11.682 13.5767C11.6419 14.0977 11.2075 14.5 10.6849 14.5H5.31508C4.79254 14.5 4.3581 14.0977 4.31802 13.5767Z" fill="currentColor"></path></svg></button>
                </div>
                <div class="progress-bar" style="width: 100%; height: 2px; background-color: #ddd; margin-top: 10px;">
                    <div id="${progressBarId}" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>
                </div>
                <!-- Campo de texto para el nombre del audio -->
                <input type="text" id="nombre-${tempId}" class="nombreAudioRs" placeholder="Titulo" style="display: none; margin-top: 5px; width: 100%;">
                <!-- Campo de texto para el precio -->
                <input type="number" id="precio-${tempId}" class="precioAudioRs" placeholder="Precio en USD" style="display: none; margin-top: 5px; width: 100%;" min="5" max="100">
                <span class="usd-label" style="display: none;"> $ USD</span>
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

            // --- Código para manejar el input de precio ---
            const precioInput = newWaveform.querySelector(`.precioAudioRs`);
            precioInput.addEventListener('input', function () {
                let valor = parseFloat(this.value);

                if (isNaN(valor)) {
                    //valor = 5; // Podrías establecer un valor por defecto o dejarlo vacío.
                    return;
                }

                // Validar el rango
                if (valor < 5) {
                    valor = 5;
                    this.value = valor;
                } else if (valor > 100) {
                    valor = 100;
                    this.value = valor;
                }
                //El span ya se agrega en el HTML, por lo que no es necesario manipularlo mediante JS
            });
            // ------------------------------------------------
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
            multiplesAudios.style.display = 'none';
            previewAudio.style.display = 'none';
            ppp3.style.display = 'none';
        }
    };

    const subidaArchivo = async file => {
        subidaArchivoEnProgreso = true;
        previewArchivo.style.display = 'block';
        ppp3.style.display = 'flex';
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
        ppp3.style.display = 'flex';
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
        input.multiple = true; // Permite seleccionar múltiples archivos
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
    const ppp3 = document.getElementById('ppp3');
    if (previewAudio) {
        previewAudio.style.display = 'none';
        ppp3.style.display = 'none';
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
//cuando se desmarca musiccheck, no se pone en block fanLabel artistaLabel, siento que tal vez es por la propagacion de eventos porque musiccheck tambien hace algo adicional en otra parte del codigo
async function selectorformtipo() {
    const descargacheck = document.getElementById('descargacheck');
    const musiccheck = document.getElementById('musiccheck');
    const exclusivocheck = document.getElementById('exclusivocheck');
    const colabcheck = document.getElementById('colabcheck');
    const fancheck = document.getElementById('fancheck');
    const artistacheck = document.getElementById('artistacheck');
    const tiendacheck = document.getElementById('tiendacheck'); // Nuevo checkbox
    const individualPost = document.getElementById('individualPost');
    const multiplePost = document.getElementById('multiplePost');
    const fanartistchecks = document.getElementById('fanartistchecks');

    // Verifica si los elementos necesarios existen; si no, retorna
    if (!descargacheck || !musiccheck || !exclusivocheck || !colabcheck || !fancheck || !artistacheck || !tiendacheck || !individualPost || !multiplePost) return;

    descargacheck.checked = true;
    const label = descargacheck.closest('label');
    label.style.color = '#ffffff';
    label.style.background = '#131313';

    // Función específica para manejar la lógica de musiccheck en selectorformtipo
    function handleMusicCheckChange(isChecked) {
        if (!isChecked) {
            if (exclusivocheck.checked || colabcheck.checked || descargacheck.checked) {
                fanartistchecks.style.display = 'flex';
            }
        } else {
            descargacheck.checked = false;
            exclusivocheck.checked = false;
            colabcheck.checked = false;
            tiendacheck.checked = false;
            fanartistchecks.style.display = 'none';
            resetStyles();
        }
    }

    document.addEventListener('change', async function (event) {
        if (event.target.matches('.custom-checkbox input[type="checkbox"]')) {
            const checkedCheckboxes = document.querySelectorAll('.custom-checkbox input[type="checkbox"]:checked');

            // Incluye individualPost y multiplePost en la lista de checkboxes que no son fan ni artista
            const nonFanArtistChecked = Array.from(checkedCheckboxes).filter(checkbox => checkbox.id !== 'fancheck' && checkbox.id !== 'artistacheck' && checkbox.id !== 'tiendacheck' && checkbox.id !== 'artistaTipoCheck' && checkbox.id !== 'fanTipoCheck');

            if (nonFanArtistChecked.length > 2) {
                event.target.checked = false;
                alert('Solo puedes seleccionar un máximo de 2 opciones.');
                return;
            }

            // Si se marca 'tiendacheck', desmarca los demás checkboxes
            if (event.target.id === 'tiendacheck' && event.target.checked) {
                descargacheck.checked = false;
                musiccheck.checked = false;
                exclusivocheck.checked = false;
                colabcheck.checked = false;
                fanartistchecks.style.display = 'none';
                resetStyles();
            }

            // Si se desmarca 'tiendacheck', muestra fanLabel y artistaLabel si 'exclusivocheck' o 'colabcheck' están marcados,
            if (event.target.id === 'tiendacheck' && !event.target.checked) {
                if (exclusivocheck.checked || colabcheck.checked || descargacheck.checked) {
                    fanartistchecks.style.display = 'flex';
                }
            }

            // Si se marca o desmarca 'musiccheck', retrasa la ejecución de la lógica específica
            if (event.target.id === 'musiccheck') {
                setTimeout(() => {
                    handleMusicCheckChange(event.target.checked);
                }, 0);
            }

            // Si se marca 'exclusivocheck', desmarca 'colabcheck', 'musiccheck' y 'tiendacheck'
            if (event.target.id === 'exclusivocheck' && event.target.checked) {
                colabcheck.checked = false;
                musiccheck.checked = false;
                tiendacheck.checked = false;
                const colabLabel = colabcheck.closest('label');
                colabLabel.style.color = '#6b6b6b';
                colabLabel.style.background = '';
                fanartistchecks.style.display = 'flex';
                resetStyles();
            }

            // Si se marca 'colabcheck', desmarca 'exclusivocheck', 'musiccheck' y 'tiendacheck'
            if (event.target.id === 'colabcheck' && event.target.checked) {
                exclusivocheck.checked = false;
                musiccheck.checked = false;
                tiendacheck.checked = false;
                const exclusivocLabel = exclusivocheck.closest('label');
                exclusivocLabel.style.color = '#6b6b6b';
                exclusivocLabel.style.background = '';
                resetStyles();
            }

            // Si se marca 'descargacheck', desmarca 'musiccheck' y 'tiendacheck'
            if (event.target.id === 'descargacheck' && event.target.checked) {
                musiccheck.checked = false;
                tiendacheck.checked = false;
                resetStyles();
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

//
function selectorFanArtista() {
    const fancheck = document.getElementById('fancheck');
    const artistacheck = document.getElementById('artistacheck');
    const tiendacheck = document.getElementById('tiendacheck');
    const musiccheck = document.getElementById('musiccheck');
    const fanartistchecks = document.getElementById('fanartistchecks');

    // Verifica si los elementos necesarios existen; si no, retorna
    if (!fancheck || !artistacheck || !tiendacheck) return;

    // Función para actualizar estilos
    function updateStyles(checkbox) {
        const label = checkbox.closest('label');
        if (checkbox.checked) {
        } else {
            label.style.color = '#6b6b6b';
            label.style.background = '';
        }
    }

    // Función para deseleccionar otros checkboxes y manejar la visibilidad
    function uncheckOthers(currentCheckbox) {
        const checkboxes = [fancheck, artistacheck, tiendacheck];
        checkboxes.forEach(checkbox => {
            if (checkbox !== currentCheckbox) {
                checkbox.checked = false;
                updateStyles(checkbox);
            }
        });

        // Si se selecciona 'tiendacheck', oculta 'fancheck' y 'artistacheck'
        if (currentCheckbox === tiendacheck && tiendacheck.checked) {
            fanartistchecks.style.display = 'none';
        } else {
            // Si no se selecciona 'tiendacheck', muestra 'fancheck' y 'artistacheck'
            fanartistchecks.style.display = 'flex';
        }
    }

    // Listener para 'fancheck'
    fancheck.addEventListener('change', function () {
        if (this.checked) {
            uncheckOthers(this);
        }
        updateStyles(this);
    });

    // Listener para 'artistacheck'
    artistacheck.addEventListener('change', function () {
        if (this.checked) {
            uncheckOthers(this);
        }
        updateStyles(this);
    });

    // Listener para 'tiendacheck'
    tiendacheck.addEventListener('change', function () {
        if (this.checked) {
            uncheckOthers(this);
        } else {
            // Si 'tiendacheck' se desmarca, muestra 'fancheck' y 'artistacheck'
            fanartistchecks.style.display = 'flex';
        }
        updateStyles(this);
    });

    musiccheck.addEventListener('change', function () {
        if (this.checked) {
            uncheckOthers(this);
        } else {
            // Si 'tiendacheck' se desmarca, muestra 'fancheck' y 'artistacheck'
            fanartistchecks.style.display = 'flex';
        }
        updateStyles(this);
    });
}

function selectorTipoPost() {
    const individualPost = document.getElementById('individualPost');
    const multiplePost = document.getElementById('multiplePost');

    // Verifica si los elementos necesarios existen; si no, retorna
    if (!individualPost || !multiplePost) return;

    // Función para actualizar estilos (reutilizada de selectorFanArtista)
    function updateStyles(checkbox) {
        const label = checkbox.closest('label');
        if (checkbox.checked) {
            label.style.color = '#ffffff';
            label.style.background = '#131313';
        } else {
            label.style.color = '#6b6b6b';
            label.style.background = '';
        }
    }

    // Listener para 'individualPost'
    individualPost.addEventListener('change', function () {
        if (individualPost.checked) {
            multiplePost.checked = false;
            updateStyles(multiplePost);
        }
        updateStyles(individualPost);
    });

    // Listener para 'multiplePost'
    multiplePost.addEventListener('change', function () {
        if (multiplePost.checked) {
            individualPost.checked = false;
            updateStyles(individualPost);
        }
        updateStyles(multiplePost);
    });
}
