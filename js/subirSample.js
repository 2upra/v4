//rola
const enableLogs = false; // Cambia a true para activar los logs
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
    urlAudio: {},
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
        inputImagen: document.getElementById('inputImagen'),
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
        if (window.formState.urlAudio && window.formState.urlAudio[rolaId]) {
            delete window.formState.urlAudio[rolaId];
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
        window.formState.isAudioUploaded = Object.keys(window.formState.urlAudio || {}).length > 0;
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
            <input type="file" id="inputAudio${rolaId}" name="post_audio${rolaId}" accept="audio/*" style="display: none;">
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
    const inputAudio = document.getElementById(`inputAudio${formNumber}`);

    if (!previewAreaRola || !inputAudio) return;

    async function inicialSubida(event) {
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
            inputAudio.files = new DataTransfer().items.add(file).files;
            const progressBarId = waveAudio(file);
            window.formState.isAudioUploaded = true;
            window.ChequearFormRola();

            try {
                const fileUrl = await uploadFile(file, progressBarId, formNumber);
                // Almacenar la URL del archivo para este formNumber específico
                if (!window.formState.urlAudio) {
                    window.formState.urlAudio = {};
                }
                window.formState.urlAudio[formNumber] = fileUrl;
            } catch (error) {
                console.error('Error al cargar el archivo:', error);
                alert('Hubo un problema al cargar el archivo. Por favor, inténtelo de nuevo.');
            }
        } else {
            alert('Por favor, seleccione un archivo de audio');
        }
    }

    function waveAudio(file) {
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
        inputAudio.click();
    });
    inputAudio.addEventListener('change', inicialSubida);

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewAreaRola.addEventListener(eventName, e => {
            e.preventDefault();
            if (eventName === 'dragover') {
                previewAreaRola.style.backgroundColor = '#e9e9e9';
            } else {
                previewAreaRola.style.backgroundColor = '';
                if (eventName === 'drop') inicialSubida(e);
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
    log01('subidaArchivoRsBack imagen ejecutado');
    const previewAreaImagen = document.getElementById('previewAreaImagen');
    const inputImagen = document.getElementById('inputImagen');
    //const additionalImageDiv = document.getElementById('0I18J19');

    if (!previewAreaImagen || !inputImagen) return;

    function handleImageSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (file && file.type.startsWith('image/')) {
            log01('Imagen seleccionada:', file);
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            inputImagen.files = dataTransfer.files;
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

    previewAreaImagen.addEventListener('click', () => inputImagen.click());
    inputImagen.addEventListener('change', handleImageSelect);

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