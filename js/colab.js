function empezarcolab() {
    const buttons = document.querySelectorAll('.ZYSVVV');
    const modal = document.getElementById('modalcolab');
    const modalEnviarBtn = document.getElementById('empezarColab');
    let postId, fileUrl;

    if (!buttons.length) return; 

    const addEventListeners = (elements, event, handler) =>
        elements.forEach(el => {
            el.removeEventListener(event, handler);
            el.addEventListener(event, handler);
        });

    addEventListeners(buttons, 'click', e => {
        postId = e.currentTarget?.dataset.postId;
        if (!postId) return console.error('El post ID no se encontró en el botón.');
        console.log('Post ID:', postId);
        subidaArchivoColab(); 
        modal.style.display = 'flex';
    });

    addEventListeners([modalEnviarBtn], 'click', async () => {
        const mensaje = document.querySelector('#modalcolab textarea').value.trim();
        if (!mensaje) return alert('Por favor, escribe un mensaje antes de enviar.');
        if (!fileUrl) return alert('Por favor, sube un archivo antes de enviar.'); 
        console.log('Enviando datos:', {postId, mensaje, fileUrl});
        const data = await enviarAjax('empezarColab', {postId, mensaje, fileUrl});
        if (data?.success) {
            alert('Colaboración iniciada con éxito');
            modal.style.display = 'none';
        } else {
            alert(`Error al iniciar la colaboración: ${data?.message || 'Desconocido'}`);
        }
    });

    addEventListeners([document.querySelector('#modalcolab button')], 'click', () => (modal.style.display = 'none'));
}

function subidaArchivoColab() {
    const previewArchivo = document.getElementById('previewColab');
    const postArchivoColab = document.getElementById('postArchivoColab');
    const modalEnviarBtn = document.getElementById('empezarColab');
    let fileSelected = false;

    const handleFileSelect = async event => {
        event.preventDefault();
        event.stopPropagation();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (!file || fileSelected) return;
        fileSelected = true;

        const progressBarId = updatePreviewArea(file);
        modalEnviarBtn.disabled = true; // Desactiva el botón mientras se carga el archivo.
        try {
            const uploadedFileUrl = await subirArchivoColab(file, progressBarId);
            previewArchivo.innerHTML = `Archivo subido: ${file.name} (${file.type})`;
            fileUrl = uploadedFileUrl; // Asigna la URL del archivo subido.
            console.log('Archivo subido a:', fileUrl);
            modalEnviarBtn.disabled = false; // Reactiva el botón después de la carga.
        } catch (error) {
            console.error('Error al cargar el archivo:', error);
            alert('Hubo un problema al cargar el archivo. Inténtalo de nuevo.');
            modalEnviarBtn.disabled = false;
        }
    };

    const handleDragDropEvents = event => {
        event.preventDefault();
        previewArchivo.style.backgroundColor = event.type === 'dragover' ? '#e9e9e9' : '';
        if (event.type === 'drop') handleFileSelect(event);
    };

    previewArchivo.addEventListener('click', e => {
        e.stopPropagation();
        if (!fileSelected) postArchivoColab.click();
    });

    postArchivoColab.addEventListener('change', handleFileSelect);
    ['dragover', 'dragleave', 'drop'].forEach(eventName => 
        previewArchivo.addEventListener(eventName, handleDragDropEvents)
    );
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
