//ACTIVAR O DESACTIVAR LOGS
const enableLogs = true; // Cambia a true para activar los logs
const log01 = enableLogs ? console.log : function () {};
//////////////////////////////////////////////
let rolaCount = 1;
let allRolas = [];
let deletedRolaIds = [];

window.formState = {
    sampleCampos: false,
    isAudioUploaded: false,
    isImageUploaded: false,
    cargaCompleta: false,
    uploadedFiles: [],
    uploadedFileUrls: {},
    ListaDeAudios: [],
    postCampos: false,
    camposRellenos: false,
    selectedImage: null,
    archivo: true
};
log01('Estado inicial del formulario:', window.formState);

function initializeCharacterLimits() {
    const limits = {
        postContent: 100,
        realName: 50,
        artisticName: 50,
        email: 100
    };

    Object.keys(limits).forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', function () {
                if (this.value.length > limits[id]) this.value = this.value.slice(0, limits[id]);
                checkAllFieldsFilled();
            });
        }
    });
}

function limitRolaTitleLength(elementId) {
    const nameRolaTextarea = document.getElementById(elementId);

    if (!nameRolaTextarea) {
        return;
    }

    nameRolaTextarea.addEventListener('input', function () {
        const maxLength = 60;
        if (this.value.length > maxLength) {
            this.value = this.value.slice(0, maxLength);
            alert('El título de la canción no puede superar los 60 caracteres.');
        }
        if (typeof checkAllFieldsFilled === 'function') {
            checkAllFieldsFilled();
        } else {
            console.warn('La función checkAllFieldsFilled no está definida.');
        }
    });
}

function checkAllFieldsFilled() {
    const requiredFields = ['postContent', 'realName', 'email', 'artisticName'];
    const allRequiredFieldsFilled = requiredFields.every(fieldId => {
        const field = document.getElementById(fieldId);
        const isFilled = field && field.value.trim().length > 0;
        return isFilled;
    });

    const rolaTextareas = Array.from(document.querySelectorAll('[id^="nameRola"]'));
    const allRolaNamesFilled = rolaTextareas.every(textarea => {
        const isFilled = textarea.value.trim().length > 0;
        return isFilled;
    });

    window.formState.camposRellenos = allRequiredFieldsFilled && allRolaNamesFilled;
}

function subidaRolaForm() {
    const postFormElement = document.getElementById('postFormRola');

    if (!postFormElement) {
        return;
    }

    rolaCount = 1;
    allRolas = [];
    deletedRolaIds = [];

    const elements = initializeElements();
    if (!elementsExist(elements)) return;

    initializeCharacterLimits();
    SubidaImagen();
    checkAllFieldsFilled();
    initializeRolaForm(elements);
}

function initializeElements() {
    return {
        otrarola: document.getElementById('otrarola'),
        rolasContainer: document.getElementById('rolasContainer'),
        previewAreaImagen: document.getElementById('previewAreaImagen'),
        postImage: document.getElementById('postImage'),
        artisticName: document.getElementById('artisticName')
    };
}

function initializeRolaForm(elements) {
    elements.otrarola.addEventListener('click', () => createRolaForm(elements, allRolas));
    createInitialRolaForm(elements);
}

function createInitialRolaForm(elements) {
    const initialRolaId = rolaCount++;
    arrastrar_archivo(initialRolaId);
    addEventListenersToRola(initialRolaId, elements, allRolas);
    allRolas.push('');
    updateAllRolasList(allRolas);

    limitRolaTitleLength(`nameRola${initialRolaId}`);
}

function createRolaForm(elements, allRolas) {
    if (rolaCount > 20) {
        alert('Has alcanzado el límite máximo de 20 rolas.');
        return;
    }
    let newRolaId;
    if (deletedRolaIds.length > 0) {
        newRolaId = deletedRolaIds.pop();
    } else {
        newRolaId = rolaCount++;
    }
    const newRolaForm = createRolaFormElement(newRolaId);
    elements.rolasContainer.appendChild(newRolaForm);
    arrastrar_archivo(newRolaId);
    addEventListenersToRola(newRolaId, elements, allRolas);
    allRolas[newRolaId - 1] = '';
    limitRolaTitleLength(`nameRola${newRolaId}`);
    addDeleteRolaListener(newRolaForm, newRolaId, allRolas);
}

function addDeleteRolaListener(newRolaForm, rolaId, allRolas) {
    newRolaForm.querySelector('.deleteRolaBtn').addEventListener('click', function () {
        log01('Rola ID Removed:', rolaId);
        if (window.formState.ListaDeAudios[rolaId - 1]) {
            window.formState.ListaDeAudios.splice(rolaId - 1, 1);
            log01('ListaDeAudios after splice:', window.formState.ListaDeAudios);
        }
        // Eliminar la URL del archivo asociada a este rolaId
        if (window.formState.uploadedFileUrls && window.formState.uploadedFileUrls[rolaId]) {
            delete window.formState.uploadedFileUrls[rolaId];
            log01('Removed URL for rolaId:', rolaId);
        }
        // Eliminar el estado de carga para este rolaId
        if (window.formState.uploadedFiles && window.formState.uploadedFiles[rolaId]) {
            delete window.formState.uploadedFiles[rolaId];
            log01('Removed upload state for rolaId:', rolaId);
        }
        newRolaForm.remove();
        log01('newRolaForm removed.');
        allRolas[rolaId - 1] = null;
        log01('allRolas after filter:', allRolas);
        // Agregar rolaId a la lista de IDs eliminados
        deletedRolaIds.push(rolaId);
        // Actualizar el estado de carga de audio
        window.formState.isAudioUploaded = Object.keys(window.formState.uploadedFileUrls || {}).length > 0;
        updateAllRolasList(allRolas);
        log01('updateAllRolasList called.');
        // Verificar si todos los archivos han sido cargados
        window.checkAllFilesUploaded();
    });
}

function addEventListenersToRola(rolaId, elements, allRolas) {
    const nameRolaElement = document.getElementById(`nameRola${rolaId}`);
    if (nameRolaElement) {
        nameRolaElement.addEventListener('input', () => {
            updateArtistRola(rolaId, elements);
            allRolas[rolaId - 1] = nameRolaElement.value;
            updateAllRolasList(allRolas);
        });
    }
    elements.artisticName.addEventListener('input', () => {
        updateArtistRola(rolaId, elements);
        updateAllRolasList(allRolas);
    });
}

function updateArtistRola(rolaId, elements) {
    const artistName = elements.artisticName.value;
    const rolaName = document.getElementById(`nameRola${rolaId}`)?.value;
    document.getElementById(`artistrola${rolaId}`).textContent = rolaName ? `${artistName} - ${rolaName}` : '';
}

function updateAllRolasList(allRolas) {
    const rolaListDiv = document.getElementById('0I18J20');
    if (rolaListDiv) {
        const rolaListHTML = allRolas
            .filter(rola => rola !== null && rola !== '')
            .map(rola => `<li>${rola}</li>`)
            .join('');
        rolaListDiv.innerHTML = `<ul>${rolaListHTML}</ul>`;
    }
}

// Funciones auxiliares
function elementsExist(elements) {
    return Object.values(elements).every(element => element !== null);
}

function ChequearFormRola() {
    return;
}

function createRolaFormElement(rolaId) {
    const newRolaForm = document.createElement('div');
    newRolaForm.className = 'rolaForm';
    newRolaForm.innerHTML = `
        <button class="deleteRolaBtn" data-rola-id="${rolaId}">Borrar Rola</button>
        <span class="artistrola-span" id="artistrola${rolaId}"></span>
        <div class="previewsForm">
            <div class="previewAreaArchivos" id="previewAreaRola${rolaId}">Arrastra tu música
                <label><?php echo $GLOBALS['subiraudio']; ?></label>
            </div>
            <input type="file" id="postAudio${rolaId}" name="post_audio${rolaId}" accept="audio/*" style="display: none;">
        </div>
        <div>
            <label for="nameRola${rolaId}">Titulo de lanzamiento</label>
            <textarea id="nameRola${rolaId}" name="name_Rola${rolaId}" rows="1" required></textarea>
        </div>
    `;
    return newRolaForm;
}

////////////////////////////////////////
function arrastrar_archivo(formNumber) {
    log01('new arrastrar_archivo', formNumber);
    const previewAreaRola = document.getElementById(`previewAreaRola${formNumber}`);
    const postAudio = document.getElementById(`postAudio${formNumber}`);

    if (!previewAreaRola || !postAudio) return;

    async function handleFileSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (file && file.type.startsWith('audio/')) {
            const existingFileIndex = window.formState.ListaDeAudios.indexOf(file.name);

            if (existingFileIndex !== -1) {
                alert('Este archivo de audio ya ha sido subido.');
                return;
            } else {
                window.formState.ListaDeAudios[formNumber - 1] = file.name;
            }
            // Reinicializar el estado de carga para este formulario
            window.formState.uploadedFiles[formNumber] = false;
            window.formState.cargaCompleta = false;
            window.checkAllFilesUploaded();
            postAudio.files = new DataTransfer().items.add(file).files;
            const progressBarId = updatePreviewArea(file);
            window.formState.isAudioUploaded = true;
            window.ChequearFormRola();

            try {
                const fileUrl = await uploadFile(file, progressBarId, formNumber);
                // Almacenar la URL del archivo para este formNumber específico
                if (!window.formState.uploadedFileUrls) {
                    window.formState.uploadedFileUrls = {};
                }
                window.formState.uploadedFileUrls[formNumber] = fileUrl;
            } catch (error) {
                console.error('Error al cargar el archivo:', error);
                alert('Hubo un problema al cargar el archivo. Por favor, inténtelo de nuevo.');
            }
        } else {
            alert('Por favor, seleccione un archivo de audio');
        }
    }

    function updatePreviewArea(file) {
        const reader = new FileReader();
        const audioContainerId = `waveformForm-${formNumber}-${Date.now()}`;
        const progressBarId = `progress-${formNumber}-${Date.now()}`;

        reader.onload = function (e) {
            previewAreaRola.innerHTML = `
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
        return progressBarId; // Retorna el ID de la barra de progreso directamente
    }

    previewAreaRola.addEventListener('click', e => {
        if (e.target.closest('.waveform-container')) {
            return;
        }
        postAudio.click();
    });
    postAudio.addEventListener('change', handleFileSelect);

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewAreaRola.addEventListener(eventName, e => {
            e.preventDefault();
            if (eventName === 'dragover') {
                previewAreaRola.style.backgroundColor = '#e9e9e9';
            } else {
                previewAreaRola.style.backgroundColor = '';
                if (eventName === 'drop') handleFileSelect(e);
            }
        });
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

function SubidaImagen() {
    log01('subida imagen ejecutado');
    const previewAreaImagen = document.getElementById('previewAreaImagen');
    const postImage = document.getElementById('postImage');
    //const additionalImageDiv = document.getElementById('0I18J19');

    if (!previewAreaImagen || !postImage) return;

    function handleImageSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            log01('Imagen seleccionada:', file);
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            postImage.files = dataTransfer.files;
            updateImagePreview(file);
            window.formState.isImageUploaded = true;
            window.formState.selectedImage = file;
            window.ChequearFormRola();
        } else {
            alert('Por favor, seleccione un archivo de imagen');
        }
    }

    function updateImagePreview(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const imgHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            previewAreaImagen.innerHTML = imgHTML;
        };
        reader.readAsDataURL(file);
    }

    previewAreaImagen.addEventListener('click', () => postImage.click());
    postImage.addEventListener('change', handleImageSelect);

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewAreaImagen.addEventListener(eventName, e => {
            e.preventDefault();
            if (eventName === 'dragover') {
                previewAreaImagen.style.backgroundColor = '#e9e9e9';
            } else {
                previewAreaImagen.style.backgroundColor = '';
                if (eventName === 'drop') handleImageSelect(e);
            }
        });
    });
}

function autoFillUserInfo() {
    const artisticNameField = document.getElementById('artisticName');
    const emailField = document.getElementById('email');
    const userName = document.getElementById('user_name');
    const userEmail = document.getElementById('user_email');

    if (!artisticNameField || !emailField || !userName || !userEmail) return;

    if (artisticNameField.value === '') artisticNameField.value = userName.value;
    if (emailField.value === '') emailField.value = userEmail.value;
}

///////////////////////////////////////////////////////////////////
//Para Form Samples
function IniciadorSample() {
    const postFormSample = document.getElementById('postFormSample');
    if (!postFormSample) {
        return;
    }

    rolaCount = 1;
    allRolas = [];
    deletedRolaIds = [];

    const elements = SampleElementos();
    if (!elementsExist(elements)) return;

    ajustarAnchoIgualAltura();
    SubidaImagen();
    IniciarElementosSamples(elements);
    verificarCamposSample();
    subidaArchivoSample();
}

function subidaArchivoSample() {
    console.log('subidaArchivoSample ejecutado');
    const previewAreaArchivo = document.getElementById('previewAreaflpSample');
    const postArchivo = document.getElementById('flp');

    if (!previewAreaArchivo || !postArchivo) return;

    function handleFileSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (file) {
            const fileType = file.type;
            const fileSize = file.size;

            // Verificar que el archivo no es ni de tipo audio ni imagen, y que su tamaño sea menor o igual a 200MB
            if (!fileType.startsWith('audio/') && !fileType.startsWith('image/') && fileSize <= 200 * 1024 * 1024) {
                alert('Archivo subido: ' + file.name);

                // Mostrar el área de previsualización
                previewAreaArchivo.style.display = 'block';

                // Inicializar el estado del formulario
                window.formState = window.formState || {};
                window.formState.archivo = false;

                console.log('Subiendo archivo:', window.formState.archivo);

                previewAreaArchivo.innerHTML = `
                    <div class="file-name">${file.name}</div>
                    <div id="barraProgresoFile" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>
                `;

                const barraProgresoFile = 'barraProgresoFile';

                uploadFile(file, barraProgresoFile, 1)
                    .then(fileUrl => {
                        console.log('URL Archivo:', fileUrl);

                        window.formState.archivo = true;
                        window.formState.archivoURL = fileUrl;

                        console.log('Subido archivo:', window.formState.archivo);

                        window.checkAllFilesUploaded();
                        window.verificarCamposPost();
                    })
                    .catch(error => {
                        console.error('Error al cargar el archivo:', error);
                        alert('Hubo un problema al cargar el archivo. Por favor, inténtelo de nuevo.');
                    });
            } else {
                alert('El archivo no es válido o excede el tamaño máximo permitido.');
            }
        }
    }

    // Click en el área de previsualización para abrir el selector de archivos
    previewAreaArchivo.addEventListener('click', () => postArchivo.click());

    // Manejo del cambio de archivo
    postArchivo.addEventListener('change', handleFileSelect);

    // Manejo de eventos de arrastrar y soltar
    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewAreaArchivo.addEventListener(eventName, e => {
            e.preventDefault();
            if (eventName === 'dragover') {
                previewAreaArchivo.style.backgroundColor = '#e9e9e9';
            } else if (eventName === 'dragleave') {
                previewAreaArchivo.style.backgroundColor = '';
            } else if (eventName === 'drop') {
                previewAreaArchivo.style.backgroundColor = '';
                handleFileSelect(e);
            }
        });
    });
}

function ajustarAnchoIgualAltura() {
    var previewArea = document.getElementById('previewAreaImagen');
    if (previewArea) {
        var altura = previewArea.offsetHeight;
        previewArea.style.width = altura + 'px';
    }
    window.onload = ajustarAnchoIgualAltura;
    window.onresize = ajustarAnchoIgualAltura;
}

function createSampleFormElement(rolaId) {
    const newRolaForm = document.createElement('div');
    newRolaForm.className = 'rolaForm';
    newRolaForm.innerHTML = `
        <button class="deleteRolaBtn" data-rola-id="${rolaId}">Borrar Rola</button>

        <div class="exQtjg">

            <div>
                <label for="nameRola${rolaId}">Titulo de Sample</label>
                <textarea id="nameRola${rolaId}" name="name_Rola${rolaId}" rows="1" required></textarea>
            </div>

            <div class="tags">
                <label for="nameRola">Tags</label>
                <div class="postTags" id="postTags${rolaId}" contenteditable="true"></div>
            </div>

            <div class="previewsForm">
                <div class="previewAreaArchivos" id="previewAreaRola${rolaId}">Arrastra tu sample
                    <label></label>
                </div>
                <input type="file" id="postAudio${rolaId}" name="post_audio${rolaId}" accept="audio/*" style="display: none;">
            </div>

            <div class="opcionesform2">
                <label class="custom-checkbox">
                    <input type="checkbox" id="allowDownload${rolaId}" name="allow_download${rolaId}" value="1">
                    <span class="checkmark"></span>
                    Permitir descargas
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="contentBlock${rolaId}" name="content_block${rolaId}" value="1">
                    <span class="checkmark"></span>
                    Privado para suscriptores
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="paraColab${rolaId}" name="para_colab${rolaId}" value="1">
                    <span class="checkmark"></span>
                    Permitir colabs
                </label>
            </div>

        </div>

    `;

    return newRolaForm;
}

function createSampleForm(elements, allRolas) {
    if (rolaCount > 20) {
        alert('Has alcanzado el límite máximo de 20 samples.');
        return;
    }
    let newRolaId;

    if (deletedRolaIds.length > 0) {
        newRolaId = deletedRolaIds.pop();
    } else {
        newRolaId = rolaCount++;
    }

    const newRolaForm = createSampleFormElement(newRolaId);
    elements.rolasContainer.appendChild(newRolaForm);

    // Ahora que el elemento está en el DOM, podemos configurar el sistema de etiquetas
    setTimeout(() => {
        setupTagSystem({
            containerId: `postTags${newRolaId}`,
            maxTags: 20,
            minLength: 3,
            maxLength: 40,
            tagClass: 'tag'
        });
    }, 0);

    arrastrar_archivo(newRolaId);
    window.initializeFormFunctions();

    addEventListenersToSample(newRolaId, elements, allRolas);

    allRolas[newRolaId - 1] = '';

    addDeleteRolaListener(newRolaForm, newRolaId, allRolas);
}

function SampleElementos() {
    return {
        otrarola: document.getElementById('otrarola'),
        rolasContainer: document.getElementById('rolasContainer'),
        previewAreaImagen: document.getElementById('previewAreaImagen'),
        postImage: document.getElementById('postImage')
    };
}

function IniciarElementosSamples(elements) {
    elements.otrarola.addEventListener('click', () => createSampleForm(elements, allRolas));
    createInitialSampleForm(elements);
}

function createInitialSampleForm(elements) {
    const initialRolaId = rolaCount++;
    arrastrar_archivo(initialRolaId);

    addEventListenersToSample(initialRolaId, elements, allRolas);
    allRolas.push('');
    updateAllRolasList(allRolas);

    //limitRolaTitleLength(`nameRola${initialRolaId}`);
}

function addEventListenersToSample(rolaId, elements, allRolas) {
    const nameRolaElement = document.getElementById(`nameRola${rolaId}`);
    if (nameRolaElement) {
        nameRolaElement.addEventListener('input', () => {
            //updateArtistRola(rolaId, elements);
            allRolas[rolaId - 1] = nameRolaElement.value;
            updateAllRolasList(allRolas);
        });
    }
    /*elements.artisticName.addEventListener('input', () => {
        //updateArtistRola(rolaId, elements);
        //updateAllRolasList(allRolas);
    });*/
}

function verificarCamposSample() {
    const postFormSample = document.getElementById('postFormSample');
    if (postFormSample) {
        const tituloInput = document.getElementById('nameRola1');
        const tagsInput = document.getElementById('postTags1');

        // Agrega eventos a los campos para ejecutar la verificación cuando cambien
        tituloInput.addEventListener('input', verificarCamposSample);
        tagsInput.addEventListener('input', verificarCamposSample);

        let titulo = tituloInput.value.trim();
        let tags = tagsInput.innerText.trim();

        // Verifica si el título tiene al menos 3 caracteres
        if (titulo.length < 3) {
            window.formState.sampleCampos = false;
            return;
        }

        // Verifica si los tags tienen al menos 3 caracteres
        if (tags.length < 3) {
            window.formState.sampleCampos = false;
            return;
        }

        // Verifica si el título excede los 150 caracteres
        if (titulo.length > 150) {
            alert('El título no puede exceder los 150 caracteres.');
            titulo = titulo.substring(0, 150);
            tituloInput.value = titulo;
            window.formState.sampleCampos = false;
            return;
        }

        // Verifica si los tags exceden los 400 caracteres
        if (tags.length > 400) {
            alert('Los tags no pueden exceder los 400 caracteres.');
            tags = tags.substring(0, 400);
            tagsInput.innerText = tags;
            window.formState.sampleCampos = false;
            return;
        }

        // Si todos los campos cumplen con las restricciones
        window.formState.sampleCampos = true;
    }
}

function procesarTagsSiExisten() {
    var tagsElement = document.getElementById('postTags1');
    var hiddenInput = document.getElementById('postTagsHidden');

    if (tagsElement && hiddenInput) {
        var tagSpans = tagsElement.querySelectorAll('.tag');
        var tags = Array.from(tagSpans).map(span => span.textContent.trim());
        hiddenInput.value = tags.join(','); // Los tags se unen con comas
        log01('Tags procesados:', hiddenInput.value);
    }
}

////////////////////////////////////////////
// Formulario Rs Form
function inicialRsForm() {
    if (document.getElementById('FormSubidaRs')) {
        rolaCount = 1;
        allRolas = [];
        deletedRolaIds = [];
        SubidaRs();
        verificarCamposPost();
        placeholder();
    }
}

function SubidaRs() {
    const formSubidaRs = document.getElementById('FormSubidaRs');
    const postAudio = document.getElementById('postAudio1');
    const postImage = document.getElementById('postImage');
    const selectFileButton = document.getElementById('U74C2P');
    const selectImageButton = document.getElementById('41076K');
    const previewAreaAudio = document.getElementById('previewAreaRola1');
    const previewAreaFlp = document.getElementById('previewAreaflp');
    const flpInput = document.getElementById('flp');
    const opciones = document.getElementById('SABTJC');
    const selectArchivoButton = document.getElementById('SGGDAS');

    if (!formSubidaRs || !postAudio || !previewAreaAudio || !postImage || !selectFileButton || !selectImageButton || !previewAreaFlp || !flpInput || !selectArchivoButton) return;

    function handleFileSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (file.size > 200 * 1024 * 1024) {
            // 200 MB limit
            alert('El archivo no puede superar los 200 MB.');
            return;
        }

        if (file.type.startsWith('audio/')) {
            handleAudioFile(file);
        } else if (file.type.startsWith('image/')) {
            handleImageFile(file);
        } else {
            handleFile(file);
        }
    }

    function handleAudioFile(file) {
        previewAreaAudio.style.display = 'block';
        opciones.style.display = 'flex';

        const formNumber = 1;
        const existingFileIndex = window.formState.ListaDeAudios.indexOf(file.name);
        if (existingFileIndex !== -1) {
            alert('Este archivo de audio ya ha sido subido.');
            return;
        }

        //Estados de formulario
        window.formState.ListaDeAudios[formNumber - 1] = file.name;
        window.formState.uploadedFiles[formNumber] = false;
        window.formState.cargaCompleta = false;
        window.formState.isAudioUploaded = true;
        window.checkAllFilesUploaded();
        window.verificarCamposPost();

        postAudio.files = new DataTransfer().items.add(file).files;
        const progressBarId = updatePreviewArea(file, formNumber);

        uploadFile(file, progressBarId, formNumber)
            .then(fileUrl => {
                if (!window.formState.uploadedFileUrls) {
                    window.formState.uploadedFileUrls = {};
                }
                window.formState.uploadedFileUrls[formNumber] = fileUrl;
            })
            .catch(error => {
                console.error('Error al cargar el archivo:', error);
                alert('Hubo un problema al cargar el archivo. Por favor, inténtelo de nuevo.');
            });
    }

    function handleImageFile(file) {
        log01('Imagen seleccionada:', file);
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        postImage.files = dataTransfer.files;
        opciones.style.display = 'flex';
        updateImagePreview(file);
        window.formState.isImageUploaded = true;
        window.formState.selectedImage = file;
        window.verificarCamposPost();
    }

    function handleFile(file) {
        alert('Archivo subido: ' + file.name);
        formNumber = '1';
        previewAreaFlp.style.display = 'block';
        window.formState.archivo = false;
        log01('Subiendo archivo:', formState.archivo);
        previewAreaFlp.innerHTML = `<div class="file-name">${file.name}</div>
        <div id="barraProgresoFile" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>`;
        var barraProgresoFile = 'barraProgresoFile';
        uploadFile(file, barraProgresoFile, 1)
            .then(fileUrl => {
                log01('URL Archivo:', fileUrl);
                window.formState.archivo = true;
                window.formState.archivoURL = fileUrl;
                log01('Subido archivo:', formState.archivo);
                window.checkAllFilesUploaded();
                window.verificarCamposPost();
            })
            .catch(error => {
                console.error('Error al cargar el archivo:', error);
                alert('Hubo un problema al cargar el archivo. Por favor, inténtelo de nuevo.');
            });
    }

    function updatePreviewArea(file, formNumber) {
        const reader = new FileReader();
        const audioContainerId = `waveformForm-${formNumber}-${Date.now()}`;
        const progressBarId = `progress-${formNumber}-${Date.now()}`;

        reader.onload = function (e) {
            previewAreaAudio.innerHTML = `
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
    }

    function updateImagePreview(file) {
        const previewAreaImagen = document.getElementById('previewAreaImagen');
        const reader = new FileReader();
        reader.onload = function (e) {
            const imgHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            previewAreaImagen.innerHTML = imgHTML;
            previewAreaImagen.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    formSubidaRs.addEventListener('click', () => {
        const clickedElement = event.target;
        if (clickedElement.closest('#previewAreaRola1')) {
            postAudio.click();
        } else if (clickedElement.closest('#previewAreaImagen')) {
            postImage.click();
        }
    });

    // Agregar evento de clic al botón SGGDAS
    selectArchivoButton.addEventListener('click', () => {
        flpInput.click(); // Simula un clic en el input de archivo
    });

    // Agregar evento de cambio al input de archivo
    flpInput.addEventListener('change', event => {
        const file = event.target.files[0];
        if (file) {
            handleFile(file);
        }
    });

    selectFileButton.addEventListener('click', () => postAudio.click());
    selectImageButton.addEventListener('click', () => postImage.click());

    postAudio.addEventListener('change', handleFileSelect);
    postImage.addEventListener('change', handleFileSelect);

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        formSubidaRs.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
            if (eventName === 'dragover') {
                formSubidaRs.style.backgroundColor = '#e9e9e9';
            } else {
                formSubidaRs.style.backgroundColor = '';
                if (eventName === 'drop') handleFileSelect(e);
            }
        });
    });
}

function verificarCamposPost() {
    const postFormSample = document.getElementById('postFormRs');
    if (postFormSample) {
        const textoRsDiv = document.getElementById('textoRs');
        const getContent = TagEnTexto({containerId: 'textoRs'});

        textoRsDiv.setAttribute('placeholder', 'Puedes agregar tags agregando un #');
        textoRsDiv.addEventListener('input', verificarCampos);

        function verificarCampos() {
            const {tags, normalText} = getContent();

            window.formState.postCampos = false;
            window.formState.postErrorMessage = '';

            if (normalText.length < 3) {
                window.formState.postErrorMessage = 'El texto debe tener al menos 3 caracteres';
                return;
            }

            if (normalText.length > 800) {
                window.formState.postErrorMessage = 'El texto no puede exceder los 800 caracteres';
                textoRsDiv.innerText = normalText.substring(0, 800);
                return;
            }

            if (tags.length === 0) {
                window.formState.postErrorMessage = 'Debe incluir al menos un tag';
                return;
            }

            if (tags.some(tag => tag.length < 3)) {
                window.formState.postErrorMessage = 'Cada tag debe tener al menos 3 caracteres';
                return;
            }

            // Si llegamos aquí, todos los campos son válidos
            window.formState.postCampos = true;
            log01('Todos los campos son válidos');
        }

        verificarCampos();
    }
}

function placeholder() {
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
