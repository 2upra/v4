function empezarcolab() {
    const buttons = document.querySelectorAll('.ZYSVVV');
    const modal = document.getElementById('modalcolab');
    const modalEnviarBtn = document.getElementById('empezarColab');
    let postId = null;
    let fileUrl = null; 
    if (!buttons.length) {
        console.log('No se encontraron botones con la clase .ZYSVVV');
        return;
    }

    // Prevenir múltiples asignaciones de eventos
    buttons.forEach(button => {
        button.removeEventListener('click', handleButtonClick);
        button.addEventListener('click', handleButtonClick);
    });

    modalEnviarBtn.removeEventListener('click', handleModalEnviar);
    modalEnviarBtn.addEventListener('click', handleModalEnviar);

    document.querySelector('#modalcolab button').removeEventListener('click', handleModalClose);
    document.querySelector('#modalcolab button').addEventListener('click', handleModalClose);

    function handleButtonClick(event) {
        postId = event.currentTarget?.dataset.postId;
        if (!postId) {
            console.error('El post ID no se encontró en el botón.');
            return;
        }
        console.log('Post ID:', postId);
        subidaArchivoColab();
        modal.style.display = 'flex';
    }

    async function handleModalEnviar() {
        const mensaje = document.querySelector('#modalcolab textarea').value;
        if (!mensaje.trim()) {
            alert('Por favor, escribe un mensaje antes de enviar.');
            return;
        }
        console.log('Enviando datos:', { postId, mensaje, fileUrl });
        const data = await enviarAjax('empezarColab', { postId, mensaje, fileUrl });
        if (data?.success) {
            alert('Colaboración iniciada con éxito');
            modal.style.display = 'none';
        } else {
            alert(`Error al iniciar la colaboración: ${data?.message || 'Desconocido'}`);
        }
    }

    function handleModalClose() {
        modal.style.display = 'none';
    }
}

function subidaArchivoColab() {
    const previewArchivo = document.getElementById('previewColab');
    const postArchivoColab = document.getElementById('postArchivoColab');
    const modalEnviarBtn = document.getElementById('empezarColab');
    let fileSelected = false;

    // Evitar eventos duplicados
    previewArchivo.removeEventListener('click', handlePreviewClick);
    previewArchivo.addEventListener('click', handlePreviewClick);
    postArchivoColab.removeEventListener('change', handleFileSelect);
    postArchivoColab.addEventListener('change', handleFileSelect);

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewArchivo.removeEventListener(eventName, handleDragDropEvents);
        previewArchivo.addEventListener(eventName, handleDragDropEvents);
    });

    function handlePreviewClick(event) {
        event.stopPropagation(); // Evitar propagación del evento
        if (!fileSelected) {
            postArchivoColab.click();
        }
    }

    async function handleFileSelect(event) {
        event.preventDefault();
        event.stopPropagation(); // Prevenir eventos múltiples
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (!file || fileSelected) return;
        fileSelected = true;

        const progressBarId = updatePreviewArea(file);
        modalEnviarBtn.disabled = true;
        try {
            const uploadedFileUrl = await subirArchivoColab(file, progressBarId);
            previewArchivo.innerHTML = `Archivo subido: ${file.name} (${file.type})`;
            window.formColab = uploadedFileUrl;
            console.log('Archivo subido a:', uploadedFileUrl);
            fileUrl = uploadedFileUrl;
            modalEnviarBtn.disabled = false;
        } catch (error) {
            console.error('Error al cargar el archivo:', error);
            alert('Hubo un problema al cargar el archivo. Inténtalo de nuevo.');
            modalEnviarBtn.disabled = false;
        }
    }

    function handleDragDropEvents(event) {
        event.preventDefault();
        if (event.type === 'dragover') {
            previewArchivo.style.backgroundColor = '#e9e9e9';
        } else {
            previewArchivo.style.backgroundColor = '';
            if (event.type === 'drop') handleFileSelect(event);
        }
    }
}

async function subirArchivoColab(file, progressBarId) {
    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    const fileHash = await generateFileHash(file);
    formData.append('file_hash', fileHash);

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);
        xhr.upload.onprogress = function (e) {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                const progressBar = document.getElementById(progressBarId);
                if (progressBar) {
                    progressBar.style.width = percentComplete + '%';
                }
            }
        };
        xhr.onload = function () {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        console.log('Archivo subido:', result.data.fileUrl);
                        resolve(result.data.fileUrl);
                    } else {
                        reject(new Error('Error en la respuesta del servidor'));
                    }
                } catch (error) {
                    reject(error);
                }
            } else {
                reject(new Error('Error en la carga del archivo'));
            }
        };
        xhr.onerror = function () {
            reject(new Error('Error en la conexión con el servidor'));
        };
        xhr.send(formData);
    });
}

function updatePreviewArea(file) {
    const progressBarId = 'progressBar_' + Math.random().toString(36).substr(2, 9);
    const previewArea = document.getElementById('previewColab');
    previewArea.innerHTML = `<div id="${progressBarId}" class="progress-bar" style="width: 0%; height: 2px; background-color: #4CAF50;"></div>`;
    return progressBarId;
}
