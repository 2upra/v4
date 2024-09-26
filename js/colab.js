let fileUrl;
let fileId;

//Abre el modal y añade el evento para envair la solicitud 
function empezarcolab() {
    contenidoColab();
    const buttons = document.querySelectorAll('.ZYSVVV');
    const modal = document.getElementById('modalcolab');
    const modalEnviarBtn = document.getElementById('empezarColab');
    const modalBackground = document.getElementById('modalBackground2'); // Fondo del modal
    let postId;

    if (!buttons.length) return; 

    const addEventListeners = (elements, event, handler) =>
        elements.forEach(el => {
            el.removeEventListener(event, handler);
            el.addEventListener(event, handler);
        });

    // Mostrar el modal, el fondo y bloquear el scroll
    addEventListeners(buttons, 'click', e => {
        postId = e.currentTarget?.dataset.postId;
        if (!postId) return console.error('El post ID no se encontró en el botón.');
        console.log('Post ID:', postId);
        subidaFrontalArchivoColab(); 
        modal.style.display = 'flex';
        modalBackground.classList.add('active'); // Agregar clase activa para mostrar el fondo
        document.body.style.overflow = 'hidden'; // Bloquear el scroll
    });

    // Enviar la colaboración
    addEventListeners([modalEnviarBtn], 'click', async () => {
        const mensaje = document.querySelector('#modalcolab textarea').value.trim();
        if (!mensaje) return alert('Por favor, escribe un mensaje antes de enviar.');
        console.log('Enviando datos:', {postId, mensaje, fileUrl, fileId});
        const data = await enviarAjax('empezarColab', {postId, mensaje, fileUrl, fileId});
        if (data?.success) {
            alert('Colaboración iniciada con éxito');
            modal.style.display = 'none';
            modalBackground.classList.remove('active'); // Remover clase activa para ocultar el fondo
            document.body.style.overflow = 'auto'; // Restaurar el scroll
            fileUrl = null; 
        } else {
            alert(`Error al iniciar la colaboración: ${data?.message || 'Desconocido'}`);
        }
    });

    // Cerrar el modal y restaurar el scroll
    addEventListeners([document.querySelector('#modalcolab button')], 'click', () => {
        modal.style.display = 'none';
        modalBackground.classList.remove('active'); // Remover clase activa para ocultar el fondo
        document.body.style.overflow = 'auto'; // Restaurar el scroll
    });
}



//Procesamiento del archivo frontal
function subidaFrontalArchivoColab() {
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
    
        const progressBarId = barradeProgreso(file);
        modalEnviarBtn.disabled = true; 
        try {
            const archivoRecibido = await subidaArchivoColabBackend(file, progressBarId);
            fileUrl = archivoRecibido.fileUrl;
            fileId = archivoRecibido.fileId;
            previewArchivo.innerHTML = `Archivo subido: ${file.name} (${file.type})`;
            modalEnviarBtn.disabled = false;
        } catch (error) {
            alert('Hubo un problema al cargar el archivo. Inténtalo de nuevo.');
            resetState();
        }
    };
    const handleDragDropEvents = event => {
        event.preventDefault();
        previewArchivo.style.backgroundColor = event.type === 'dragover' ? '#e9e9e9' : '';
        if (event.type === 'drop') handleFileSelect(event);
    };
    const handlePreviewClick = e => {
        e.stopPropagation();
        if (!fileSelected) postArchivoColab.click();
    };
    function resetState() {
        fileSelected = false;
        fileUrl = null;
        previewArchivo.innerHTML = 'Puedes enviar un archivo para la colaboración';
        previewArchivo.style.backgroundColor = '';
        modalEnviarBtn.disabled = false;
        previewArchivo.removeEventListener('click', handlePreviewClick);
        postArchivoColab.removeEventListener('change', handleFileSelect);
        ['dragover', 'dragleave', 'drop'].forEach(eventName => 
            previewArchivo.removeEventListener(eventName, handleDragDropEvents)
        );
    }
    resetState();
    previewArchivo.addEventListener('click', handlePreviewClick);
    postArchivoColab.addEventListener('change', handleFileSelect);
    ['dragover', 'dragleave', 'drop'].forEach(eventName => 
        previewArchivo.addEventListener(eventName, handleDragDropEvents)
    );
}

//Subida del archivo backend
async function subidaArchivoColabBackend(file, progressBarId) {
    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    formData.append('file_hash', await generateFileHash(file));

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable) {
                const progressBar = document.getElementById(progressBarId);
                if (progressBar) progressBar.style.width = `${(e.loaded / e.total) * 100}%`;
            }
        };
        xhr.onload = () => {
            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    result.success ? resolve(result.data) : reject(new Error('Error en la respuesta del servidor'));
                } catch (error) {
                    reject(error);
                }
            } else {
                reject(new Error('Error en la carga del archivo'));
            }
        };

        xhr.onerror = () => reject(new Error('Error en la conexión con el servidor'));
        xhr.send(formData);
    });
}
//Barra de progreso
function barradeProgreso(file) {
    const progressBarId = 'progressBar_' + Math.random().toString(36).substr(2, 9);
    const previewArea = document.getElementById('previewColab');
    previewArea.innerHTML = `<div id="${progressBarId}" class="progress-bar" style="width: 0%; height: 2px; background-color: #4CAF50;"></div>`;
    return progressBarId;
}

function contenidoColab() {
    const botonesVerContenido = document.querySelectorAll('.ver-contenido');
    
    // Verificar si hay botones con la clase 'ver-contenido'
    if (botonesVerContenido.length > 0) {
        botonesVerContenido.forEach(boton => {
            boton.addEventListener('click', function () {
                const postId = this.getAttribute('data-post-id');
                const colabFiles = document.getElementById('colabfiles-' + postId);
                
                // Verificar si el contenido colabFiles existe
                if (colabFiles) {
                    if (colabFiles.style.display === 'none' || colabFiles.style.display === '') {
                        colabFiles.style.display = 'block';
                    } else {
                        colabFiles.style.display = 'none';
                    }
                } else {
                    console.warn(`Contenido de colaboración no encontrado para el post ID: ${postId}`);
                }
            });
        });
    } else {
        console.warn('No se encontraron botones con la clase "ver-contenido".');
    }
}