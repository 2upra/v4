let CimagenUrl, CimagenId, CaudioId, CaudioUrl, CpostId;
let subidaEnComentario = 0;
let waveSurferInstancesCom = {};

let comIniciado = false;

/*
[x] background
[x] Cerrar modal 
[x] Eliminar imagen
[x] Ocultar comentario a enviar comentario
[x] Esperar que los elementos se carguen 
[ ] Ver comentarios anteriores 
[ ] Responder comentarios 
[ ] Notificacion de comentarios 
[ ] Parece que no regresa los id hash
*/

function limpiarcamposCom() {
    document.getElementById('comentContent').value = '';
    CimagenUrl = null;
    CaudioUrl = null;
    CimagenId = null;
    CaudioId = null;
    CpostId = null;
    subidaEnComentario = 0;
    waveSurferInstancesCom = {};

    // Eliminar contenido de los divs
    const audioDiv = document.querySelector('.previewAreaArchivos.paudio#pcomentAudio');
    const imagenDiv = document.querySelector('.previewAreaArchivos.pimagen#pcomentImagen');
    const comentarios = document.querySelector('.listComentarios');

    if (audioDiv) {
        audioDiv.innerHTML = '';
        audioDiv.style.display = 'none';
    }

    if (imagenDiv) {
        imagenDiv.innerHTML = '';
        imagenDiv.style.display = 'none';
    }

    if (comentarios) {
        comentarios.innerHTML = '';
        comentarios.style.display = 'none';
    }

    const elementsToHide = ['pcomentImagen', 'pcomentAudio', 'previevsComent'];
    elementsToHide.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.style.display = 'none';
        }
    });
}
function iniciarcm() {
    ocultarColec();
    limpiarcamposCom();
    if (comIniciado) return;
    comIniciado = true;
    if (document.getElementById('rsComentario')) {
        CimagenId = null;
        CimagenUrl = null;
        CaudioId = null;
        CaudioUrl = null;
        subidasEnProgreso = 0;
        enviarComentario();
        subidaComentario();
        abrirComentario();
    }
}

function verificarComentario() {
    function verificarCamposCom() {
        const textComent = document.getElementById('comentContent').value;

        if (!CpostId || isNaN(CpostId) || parseInt(CpostId) <= 0) {
            alert('Error: ID de publicación inválido.');
            return false;
        }

        if (textComent.length < 1) {
            alert('Ingresa un comentario para enviar');
            return false;
        }

        if (textComent.length > 500) {
            alert('Tu comentario no puede exceder los 500 caracteres');
            return false;
        }

        return true;
    }

    if (typeof subidaEnComentario !== 'undefined' && subidaEnComentario > 0) {
        alert('Por favor, espera a que se completen las subidas de los archivos adjuntos antes de enviar el comentario.');
        return false;
    }

    return verificarCamposCom();
}

async function enviarComentario() {
    const button = document.getElementById('enviarComent');

    button.addEventListener('click', async event => {
        const originalText = button.innerText;
        button.innerText = 'Procesando';
        button.disabled = true;

        const valid = verificarComentario();
        if (!valid) {
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        // Función para validar la URL
        const isValidUrl = url => {
            // Si la URL es null, la consideramos válida (ya que no es obligatoria)
            if (url === null) {
                return true;
            }
            const requiredPrefix = 'https://2upra.com/wp-content/uploads';
            return url.startsWith(requiredPrefix);
        };

        // Solo validamos las URLs si no son null
        if (CaudioUrl !== null && !isValidUrl(CaudioUrl)) {
            alert('La URL del audio no es válida.');
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        if (CimagenUrl !== null && !isValidUrl(CimagenUrl)) {
            alert('La URL de la imagen no es válida.');
            button.innerText = originalText;
            button.disabled = false;
            return;
        }

        const data = {
            comentario: document.getElementById('comentContent').value,
            imagenUrl: CimagenUrl, // Puede ser null
            audioUrl: CaudioUrl, // Puede ser null
            imagenId: CimagenId,
            audioId: CaudioId,
            postId: CpostId
        };

        try {
            const response = await enviarAjax('procesarComentario', data);
            if (response.success) {
                limpiarcamposCom();
                ocultarColec();
                alert('Comentario enviado con éxito');
            } else {
                alert('Error al enviar el comentario');
            }
        } catch (error) {
            console.error('Error al enviar el comentario:', error);
            alert('Error al enviar el comentario');
        } finally {
            button.innerText = originalText;
            button.disabled = false;
        }
    });
}

function subidaComentario() {
    const ids = ['imagenComent', 'audioComent', 'pcomentImagen', 'pcomentAudio', 'previevsComent', 'rsComentario'];

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

    const {imagenComent, audioComent, pcomentImagen, pcomentAudio, previevsComent, rsComentario} = elements;

    const inicialSubida = event => {
        event.preventDefault();
        const file = event.dataTransfer?.files[0] || event.target.files[0];

        if (!file) return;
        if (file.size > 12 * 1024 * 1024) return alert('El archivo no puede superar los 12 MB.');

        if (file.type.startsWith('audio/')) {
            if (CaudioUrl) {
                alert('Solo se permite subir un audio.');
                return;
            }
            subidaAudio(file);
        } else if (file.type.startsWith('image/')) {
            subidaImagen(file);
        }
    };

    const subidaAudio = async file => {
        try {
            pcomentAudio.style.display = 'flex';
            previevsComent.style.display = 'flex';
            const tempId = `temp-${Date.now()}`;
            const progressBarId = waveAudio(file, tempId);
            const {fileUrl, fileId} = await subidaComBackend(file, progressBarId);
            CaudioUrl = fileUrl;
            CaudioId = fileId;
        } catch {
            alert('Hubo un problema al cargar el audio. Inténtalo de nuevo.');
        }
    };

    const waveAudio = (file, tempId) => {
        const reader = new FileReader(),
            audioContainerId = `waveform-container-${Date.now()}`,
            progressBarId = `progress-${Date.now()}`;

        reader.onload = e => {
            pcomentAudio.innerHTML = ''; // Limpiar el contenedor de audio
            const newWaveform = document.createElement('div');
            newWaveform.innerHTML = `
            <div id="${audioContainerId}" class="waveform-wrapper">
                <div class="waveform-container without-image" data-temp-id="${tempId}">
                    <div class="waveform-loading" style="display: none;">Cargando...</div>
                    <audio controls style="width: 100%;"><source src="${e.target.result}" type="${file.type}"></audio>
                    <div class="file-name">${file.name}</div>
                    <button class="delete-waveform">Eliminar</button>
                </div>
                <div class="progress-bar" style="width: 100%; height: 2px; background-color: #ddd; margin-top: 10px;">
                    <div id="${progressBarId}" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>
                </div>
                <!-- Campo de texto para el nombre del audio -->
                <input type="text" id="nombre-${tempId}" class="nombreAudioRs" placeholder="Titulo" style="display: none; margin-top: 5px; width: 100%;">
            </div>`;

            pcomentAudio.appendChild(newWaveform);
            inicializarWaveform(audioContainerId, e.target.result);
            const deleteButton = newWaveform.querySelector('.delete-waveform');
            deleteButton.addEventListener('click', event => {
                event.stopPropagation();
                eliminarWaveform(audioContainerId, tempId);
            });
        };

        reader.readAsDataURL(file);
        return progressBarId;
    };

    const eliminarWaveform = (containerId, tempId) => {
        const wrapper = document.getElementById(containerId);

        if (wrapper) {
            wrapper.parentNode.removeChild(wrapper);
            if (waveSurferInstancesCom[containerId]) {
                waveSurferInstancesCom[containerId].unAll();
                if (waveSurferInstancesCom[containerId].isPlaying()) {
                    waveSurferInstancesCom[containerId].stop();
                }
                waveSurferInstancesCom[containerId].destroy();
                delete waveSurferInstancesCom[containerId];
            }
        }

        CaudioUrl = null;
        CaudioId = null;

        pcomentAudio.style.display = 'none';
        if (!CimagenUrl) {
            previevsComent.style.display = 'none';
        }
    };

    const subidaImagen = async file => {
        try {
            const {fileUrl, fileId} = await subidaComBackend(file, 'barraProgresoImagen');
            previevsComent.style.display = 'flex';
            CimagenUrl = fileUrl;
            CimagenId = fileId;
            updatePreviewImagen(file);
        } catch {
            alert('Hubo un problema al cargar la imagen. Inténtalo de nuevo.');
        }
    };

    const updatePreviewImagen = file => {
        const reader = new FileReader();
        reader.onload = e => {
            pcomentImagen.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; aspect-ratio: 1 / 1; object-fit: cover;">`;
            pcomentImagen.style.display = 'flex';
        };
        reader.readAsDataURL(file);
    };

    rsComentario.addEventListener('click', event => {
        const clickedElement = event.target.closest('.paudio, .pimagen');
        if (clickedElement) {
            if (clickedElement.classList.contains('paudio')) {
                if (!CaudioUrl) {
                    abrirSelectorArchivos('audio/*');
                } else {
                    alert('Ya se ha subido un audio.');
                }
            } else {
                abrirSelectorArchivos('image/*');
            }
        }
    });

    const abrirSelectorArchivos = tipoArchivo => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = tipoArchivo;
        input.onchange = inicialSubida;
        input.click();
    };

    audioComent.addEventListener('click', () => {
        if (!CaudioUrl) {
            abrirSelectorArchivos('audio/*');
        } else {
            alert('Ya se ha subido un audio.');
        }
    });
    imagenComent.addEventListener('click', () => abrirSelectorArchivos('image/*'));

    ['dragover', 'dragleave', 'drop'].forEach(eventName => {
        rsComentario.addEventListener(eventName, e => {
            e.preventDefault();
            rsComentario.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
            if (eventName === 'drop') {
                if (e.dataTransfer.files.length > 0) {
                    if (e.dataTransfer.files[0].type.startsWith('audio/')) {
                        if (!CaudioUrl) {
                            inicialSubida(e);
                        } else {
                            alert('Solo se permite subir un audio.');
                        }
                    } else {
                        inicialSubida(e);
                    }
                }
            }
        });
    });
}

async function subidaComBackend(file, progressBarId) {
    console.log('Iniciando subida de archivo', {fileName: file.name, fileSize: file.size});

    // Incrementar el contador de subidas en progreso
    subidaEnComentario++;

    const formData = new FormData();
    formData.append('action', 'file_upload');
    formData.append('file', file);
    formData.append('file_hash', await generateFileHash(file));

    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', my_ajax_object.ajax_url, true);

        console.log('Preparando solicitud AJAX', {url: my_ajax_object.ajax_url});

        xhr.upload.onprogress = e => {
            if (e.lengthComputable) {
                const progressBar = document.getElementById(progressBarId);
                const progressPercent = (e.loaded / e.total) * 100;
                if (progressBar) progressBar.style.width = `${progressPercent}%`;

                console.log('Actualizando barra de progreso', {loaded: e.loaded, total: e.total, progressPercent});
            }
        };

        xhr.onload = () => {
            console.log('Respuesta recibida', {status: xhr.status, response: xhr.responseText});

            // Decrementar el contador al finalizar la subida
            subidaEnComentario--;

            if (xhr.status === 200) {
                try {
                    const result = JSON.parse(xhr.responseText);
                    if (result.success) {
                        console.log('Archivo subido exitosamente', {data: result.data});
                        resolve(result.data);
                    } else {
                        console.log('Error en la respuesta del servidor (No éxito)', {response: result});
                        reject(new Error('Error en la respuesta del servidor'));
                    }
                } catch (error) {
                    console.log('Error al parsear la respuesta', {errorMessage: error.message, response: xhr.responseText});
                    reject(error);
                }
            } else {
                console.log('Error en la carga del archivo', {status: xhr.status, response: xhr.responseText});
                reject(new Error(`Error en la carga del archivo. Status: ${xhr.status}`));
            }
        };

        xhr.onerror = () => {
            console.log('Error en la conexión con el servidor', {status: xhr.status});
            subidaEnComentario--; // Decrementar el contador en caso de error
            reject(new Error('Error en la conexión con el servidor'));
        };

        try {
            console.log('Enviando solicitud AJAX', {formData});
            xhr.send(formData);
        } catch (error) {
            console.log('Error al enviar la solicitud AJAX', {errorMessage: error.message});
            subidaEnComentario--; // Decrementar el contador en caso de error
            reject(new Error('Error al enviar la solicitud AJAX'));
        }
    });
}

function ocultarColec() {
    const comentariosPost = document.getElementById('comentariosPost');
    const rsComentario = document.getElementById('rsComentario');
    const listComentarios = document.getElementById('listComentarios');
    rsComentario.style.display = 'none';
    listComentarios.style.display = 'none';
    comentariosPost.style.display = 'none';
    removeComDarkBackground(); // Asegúrate de eliminar el fondo también
}

/*
sucede este problema cuando no hay comentarios 

Cargando página de comentarios: 1
comentarios.js?ver=3.0.53.1448960775:489 Enviando datos: {postId: '322741', page: 1}
comentarios.js?ver=3.0.53.1448960775:493 Respuesta recibida:  <p class="sinnotifi">No hay comentarios para este post</p>0
comentarios.js?ver=3.0.53.1448960775:505 Reemplazando contenido de comentariosList.
comentarios.js?ver=3.0.53.1448960775:514 Página cargada. Nueva página actual: 2
genericAjax.js?ver=3.0.53.1071147829:733  No se pudo interpretar la respuesta como JSON: {error: SyntaxError: Unexpected token '<', " <p class=""... is not valid JSON
at JSON.parse (<anonymous…, responseText: ' <p class="sinnotifi">No hay comentarios para este post</p>0', action: 'renderComentarios', requestData: {…}}


    } else {
        echo '0';
    }
    wp_reset_postdata();
    $output = ob_get_clean();

    $response = array();
    if (trim($output) === '0') {
        $response['noComentarios'] = true;
        $response['html'] = '<p class="sinnotifi">No hay comentarios para este post</p>'; 
    } else {
        $response['noComentarios'] = false;
        $response['html'] = $output;
    }

    // Devuelve la respuesta como JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    wp_die();
}

add_action('wp_ajax_renderComentarios', 'renderComentarios');

*/
function cargarComentarios() {
    let paginaActual = 1;
    let cargando = false;
    const comentariosList = document.querySelector('.listComentarios');

    console.log('Función cargarComentarios iniciada.');

    function cargarPaginaComentario() {
        console.log(`Cargando página de comentarios: ${paginaActual}`);
        if (cargando) {
            console.log('Ya se está cargando una página. Retornando.');
            return;
        }
        cargando = true;

        const data = {
            postId: CpostId,
            page: paginaActual
        };

        console.log('Enviando datos:', data);

        enviarAjax('renderComentarios', data)
            .then(response => {
                console.log('Respuesta recibida:', response);

                let data;
                // Intenta interpretar la respuesta como JSON
                try {
                    data = JSON.parse(response);
                } catch (e) {
                    // Si falla, asume que es HTML
                    console.warn('No se pudo interpretar la respuesta como JSON:', e, 'responseText:', response, 'action:', 'renderComentarios', 'requestData:', data);
                    data = {
                        noComentarios: true,
                        html: response // Asigna la respuesta completa como HTML
                    };
                }

                if (data.noComentarios) {
                    console.log('No hay más comentarios.');
                    cargando = true;
                    if (paginaActual === 1) {
                        // Usa data.html para mostrar el mensaje
                        comentariosList.innerHTML = data.html.includes('No hay comentarios') ? data.html : '<p class="sinnotifi">No hay comentarios</p>';
                    }
                    return;
                }

                if (paginaActual === 1) {
                    console.log('Reemplazando contenido de comentariosList.');
                    comentariosList.innerHTML = data.html;
                } else {
                    console.log('Agregando contenido a comentariosList.');
                    comentariosList.insertAdjacentHTML('beforeend', data.html);
                }

                paginaActual++;
                cargando = false;
                console.log(`Página cargada. Nueva página actual: ${paginaActual}`);
                createSubmenu('.submenucomentario', 'opcionescomentarios', 'abajo');
                ['inicializarWaveforms', 'empezarcolab', 'seguir', 'modalDetallesIA', 'tagsPosts', 'handleAllRequests', 'colec'].forEach(funcion => {
                    if (typeof window[funcion] === 'function') window[funcion]();
                });
            })
            .catch(error => {
                console.error('Error en la promesa:', error);
                cargando = false;
            });
    }

    cargarPaginaComentario();

    comentariosList.addEventListener('scroll', () => {
        console.log('Evento scroll detectado.');
        console.log(`ScrollTop: ${comentariosList.scrollTop}, ScrollHeight: ${comentariosList.scrollHeight}, ClientHeight: ${comentariosList.clientHeight}`);
        if (comentariosList.scrollHeight - (comentariosList.scrollTop + comentariosList.clientHeight) <= 200 && !cargando) {
            console.log('Condición de carga de nueva página cumplida.');
            cargando = true;
            cargarPaginaComentario(paginaActual);
        }
    });
}

function abrirComentario() {
    const comentariosPost = document.getElementById('comentariosPost');
    const rsComentario = document.getElementById('rsComentario');
    const listComentarios = document.getElementById('listComentarios');
    if (!rsComentario) {
        console.log('Elemento rsComentario no encontrado.');
        return;
    }

    console.log('Función abrirComentario iniciada.');

    document.body.addEventListener('click', event => {
        const boton = event.target.closest('.WNLOFT');
        if (boton) {
            console.log('Botón WNLOFT clickeado.');
            event.stopPropagation(); // Detiene la propagación aquí
            CpostId = boton.dataset.postId;
            console.log(`CpostId obtenido: ${CpostId}`);
            cargarComentarios();
            comentariosPost.style.display = 'flex';
            listComentarios.style.display = 'flex';
            rsComentario.style.display = 'flex';

            createComDarkBackground();

            rsComentario.addEventListener('click', event => {
                console.log('Click dentro de rsComentario. Deteniendo propagación.');
                event.stopPropagation();
            });
        }
    });
}
window.createComDarkBackground = function () {
    let darkBackground = document.getElementById('submenu-background5323');
    if (!darkBackground) {
        // Crear el fondo oscuro si no existe
        darkBackground = document.createElement('div');
        darkBackground.id = 'submenu-background5323';
        darkBackground.style.position = 'fixed';
        darkBackground.style.top = 0;
        darkBackground.style.left = 0;
        darkBackground.style.width = '100%';
        darkBackground.style.height = '100%';
        darkBackground.style.backgroundColor = 'rgba(0, 0, 0, 0.5)';
        darkBackground.style.zIndex = 1000;
        darkBackground.style.display = 'none';
        // darkBackground.style.pointerEvents = 'none';  // No es necesario al inicio
        darkBackground.style.opacity = '0';
        darkBackground.style.transition = 'opacity 0.3s ease';
        document.body.appendChild(darkBackground);
    }

    // Se remueve el listener del fondo oscuro si ya existe para evitar duplicados.
    darkBackground.removeEventListener('click', ocultarColec);
    darkBackground.addEventListener('click', ocultarColec);

    darkBackground.style.display = 'block';
    setTimeout(() => {
        darkBackground.style.opacity = '1';
    }, 10);
    // darkBackground.style.pointerEvents = 'auto'; // No es necesario, se establece al hacer display block
};

// Eliminar el fondo oscuro
window.removeComDarkBackground = function () {
    const comentariosPost = document.getElementById('comentariosPost');
    const darkBackground = document.getElementById('submenu-background5323');
    if (darkBackground) {
        darkBackground.style.opacity = '0';
        setTimeout(() => {
            darkBackground.style.display = 'none';
            comentariosPost.style.display = 'none';
        }, 300);
    }
};
