
/*
ajaxPage.js?ver=5.0.11.1146385149:9  Error al ejecutar empezarcolab: TypeError: Cannot read properties of null (reading 'addEventListener')
    at empezarcolab (colab.js?ver=1.0.2.840723177:52:20)
    at ajaxPage.js?ver=5.0.11.1146385149:7:29
    at Array.forEach (<anonymous>)
    at inicializarScripts (ajaxPage.js?ver=5.0.11.1146385149:4:1014)
    at reinicializar (ajaxPage.js?ver=5.0.11.1146385149:22:5)
    at HTMLDocument.<anonymous> (ajaxPage.js?ver=5.0.11.1146385149:95:9)
(anónimo) @ ajaxPage.js?ver=5.0.11.1146385149:9
inicializarScripts @ ajaxPage.js?ver=5.0.11.1146385149:4
reinicializar @ ajaxPage.js?ver=5.0.11.1146385149:22
(anónimo) @ ajaxPage.js?ver=5.0.11.1146385149:95
2colab.js?ver=1.0.2.840723177:49  Uncaught TypeError: Cannot read properties of null (reading 'style')
    at HTMLButtonElement.<anonymous> (colab.js?ver=1.0.2.840723177:49:19)
*/

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

            // Mostrar el modal
            modal.style.display = 'block';
        });
    });

    // Manejar el envío del formulario del modal
    modalEnviarBtn.addEventListener('click', async () => {
        const mensaje = document.querySelector('#modalcolab textarea').value;

        if (!mensaje.trim()) {
            alert('Por favor, escribe un mensaje antes de enviar.');
            return;
        }

        // Confirmación antes de enviar la colaboración
        if (confirm('¿Estás seguro de que quieres empezar la colaboración?')) {
            const data = await enviarAjax('empezarColab', { postId, mensaje });
            if (data?.success) {
                alert('Colaboración iniciada con éxito');
                modal.style.display = 'none'; // Cerrar modal
            } else {
                alert(`Error al iniciar la colaboración: ${data?.message || 'Desconocido'}`);
            }
        }
    });

    // Cerrar el modal al hacer clic en el botón de cancelar
    document.querySelector('#modalcolab button').addEventListener('click', () => {
        modal.style.display = 'none';
    });
}


function subidaArchivoColab() {
    const previewArchivo = document.getElementById('previewColab');
    const postArchivoColab = document.getElementById('postArchivoColab');
    if (!previewArchivo || !postArchivoColab) return;

    async function handleFileSelect(event) {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];
        if (!file) return;

        const progressBarId = updatePreviewArea(file); 

        try {
            const fileUrl = await subirArchivoColab(file, progressBarId);
            
            // Mostrar preview del archivo subido
            previewArchivo.innerHTML = `Archivo subido: ${file.name} (${file.type})`;

            // Guardar la URL del archivo en el cliente
            window.formColab = fileUrl;
            
            console.log('Archivo subido a:', fileUrl);

            // Si el archivo es de audio, puedes manejar el preview aquí
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

    previewArchivo.addEventListener('click', () => postArchivoColab.click());
    postArchivoColab.addEventListener('change', handleFileSelect);

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



