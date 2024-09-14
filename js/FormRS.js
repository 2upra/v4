window.formRS = {
    urlImagen: null,
    urlAudio: null,
};

//Funciones que se van a reiniciar al cambiar de pagina
function formRs() {
    SubidaRs();
}

function SubidaRs() {
    return;
    const formulario = document.getElementById('FormSubidaRs');
    const inputAudio = document.getElementById('postAudio1');
    const inputImagen = document.getElementById('inputImagen');
    const botonAudio = document.getElementById('U74C2P');
    const botonImagen = document.getElementById('41076K');
    const previewAudio = document.getElementById('previewAreaRola1');
    const previewArchivo = document.getElementById('previewAreaflp');
    const inputArchivo = document.getElementById('flp');
    const opciones = document.getElementById('SABTJC');
    const botonArchivo = document.getElementById('SGGDAS');

    if (!formulario || !inputAudio || !previewAudio || !inputImagen || !botonAudio || !botonImagen || !previewArchivo || !inputArchivo || !botonArchivo) return;

    function subidaInicialRs(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (file.size > 200 * 1024 * 1024) {
            alert('El archivo no puede superar los 200 MB.');
            return;
        }
        if (file.type.startsWith('audio/')) {
            subidaAudioRs(file);
        } else if (file.type.startsWith('image/')) {
            subidaImagenRs(file);
        } else {
            subidaArchivoRs(file);
        }
    }

    function subidaAudioRs(file) {
        
        previewAudio.style.display = 'block';
        opciones.style.display = 'flex';
        const formNumber = 1;
        inputAudio.files = new DataTransfer().items.add(file).files;
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

    function subidaImagenRs(file) {
        log01('Imagen seleccionada:', file);
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        inputImagen.files = dataTransfer.files;
        opciones.style.display = 'flex';
        updateImagePreview(file);
        window.formState.isImageUploaded = true;
        window.formState.selectedImage = file;
        window.verificarCamposPost();
    }

    function subidaArchivoRs(file) {
        alert('Archivo subido: ' + file.name);
        formNumber = '1';
        previewArchivo.style.display = 'block';
        window.formState.archivo = false;
        log01('Subiendo archivo:', formState.archivo);
        previewArchivo.innerHTML = `<div class="file-name">${file.name}</div>
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

    formulario.addEventListener('click', () => {
        const clickedElement = event.target;
        if (clickedElement.closest('#previewAreaRola1')) {
            inputAudio.click();
        } else if (clickedElement.closest('#previewAreaImagen')) {
            inputImagen.click();
        }
    });

    // Agregar evento de clic al botón SGGDAS
    botonArchivo.addEventListener('click', () => {
        inputArchivo.click(); // Simula un clic en el input de archivo
    });

    // Agregar evento de cambio al input de archivo
    inputArchivo.addEventListener('change', event => {
        const file = event.target.files[0];
        if (file) {
            subidaArchivoRs(file);
        }
    });

    botonAudio.addEventListener('click', () => inputAudio.click());
    botonImagen.addEventListener('click', () => inputImagen.click());

    inputAudio.addEventListener('change', subidaInicialRs);
    inputImagen.addEventListener('change', subidaInicialRs);

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        formulario.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
            if (eventName === 'dragover') {
                formulario.style.backgroundColor = '#e9e9e9';
            } else {
                formulario.style.backgroundColor = '';
                if (eventName === 'drop') subidaInicialRs(e);
            }
        });
    });
}