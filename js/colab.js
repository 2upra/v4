function empezarcolab() {
    const buttons = document.querySelectorAll('.ZYSVVV');
    const modal = document.getElementById('modalcolab');
    const modalEnviarBtn = document.getElementById('empezarColab');
    let postId = null;

    if (!buttons.length) {
        console.log('No se encontraron botones con la clase .ZYSVVV');
        return;
    }

    buttons.forEach(button => {
        button.addEventListener('click', event => {
            postId = event.currentTarget?.dataset.postId;

            if (!postId) {
                console.error('El post ID no se encontró en el botón.');
                return;
            }
            console.log('Post ID:', postId);
            subidaArchivoColab(); 
            modal.style.display = 'block';
        });
    });

    modalEnviarBtn.addEventListener('click', async () => {
        const mensaje = document.querySelector('#modalcolab textarea').value;

        if (!mensaje.trim()) {
            alert('Por favor, escribe un mensaje antes de enviar.');
            return;
        }

        const data = await enviarAjax('empezarColab', {postId, mensaje});
        if (data?.success) {
            alert('Colaboración iniciada con éxito');
            modal.style.display = 'none'; // Cerrar modal
        } else {
            alert(`Error al iniciar la colaboración: ${data?.message || 'Desconocido'}`);
        }
    });
    document.querySelector('#modalcolab button').addEventListener('click', () => {
        modal.style.display = 'none';
    });
}

function subidaArchivoColab() {
    const previewArchivo = document.getElementById('previewColab');
    const postArchivoColab = document.getElementById('postArchivoColab');
    if (!previewArchivo || !postArchivoColab) return;

    let fileSelected = false;  // Bandera para evitar múltiples aperturas

    async function handleFileSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (!file) return;

        // Evitar múltiples selecciones
        if (fileSelected) {
            console.log('Archivo ya seleccionado');
            return;
        }
        fileSelected = true;

        const progressBarId = updatePreviewArea(file);

        try {
            const fileUrl = await subirArchivoColab(file, progressBarId);
            previewArchivo.innerHTML = `Archivo subido: ${file.name} (${file.type})`;
            window.formColab = fileUrl;
            console.log('Archivo subido a:', fileUrl);

            // Si el archivo es de audio, manejar el preview aquí
            if (file.type.startsWith('audio')) {
                // Agrega lógica si necesitas mostrar un preview de audio
            } else {
                // Agrega lógica para otros tipos de archivos
            }
        } catch (error) {
            console.error('Error al cargar el archivo:', error);
            alert('Hubo un problema al cargar el archivo. Inténtalo de nuevo.');
        }
    }

    previewArchivo.addEventListener('click', () => {
        if (!fileSelected) {
            postArchivoColab.click();  // Solo abrir si no hay un archivo seleccionado
        }
    });

    postArchivoColab.addEventListener('change', handleFileSelect);

    // Eventos de drag and drop
    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        previewArchivo.addEventListener(eventName, e => {
            e.preventDefault();
            if (eventName === 'dragover') {
                previewArchivo.style.backgroundColor = '#e9e9e9';
            } else {
                previewArchivo.style.backgroundColor = '';
                if (eventName === 'drop') handleFileSelect(e);
            }
        });
    });
}

async function subirArchivoColab(file, progressBarId) {
    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);

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
    previewArea.innerHTML = `<div id="${progressBarId}" class="progress-bar" style="width: 0%;"></div>`;
    return progressBarId;
}
