const ajaxUrl = typeof ajax_params !== 'undefined' && ajax_params.ajax_url ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';

async function handleAllRequests() {
    try {
        await requestDeletion();
        await estadorola();
        await rejectPost();
        await eliminarPost();
        await rechazarColab();
        await aceptarcolab();
        await reporte();
        await bloqueos();
        await banearUsuario();
        await editarPost();
        await permitirDescarga();
    } catch (error) {
        console.error('Ocurrió un error al procesar las solicitudes:', error);
    }
}

// GENERIC CLICK - DEBE SER FLEXIBLE PORQUE TODA LA LOGICA DE CLICK PASA POR AQUI
async function accionClick(selector, action, confirmMessage, successCallback, elementToRemoveSelector = null) {
    const buttons = document.querySelectorAll(selector); // Selecciona los botones.

    buttons.forEach(button => {
        // Verifica si el listener ya fue añadido
        if (!button.dataset.listenerAdded) {
            button.addEventListener('click', async event => { // Añade evento 'click'.
                const post_id = event.currentTarget.dataset.postId || event.currentTarget.getAttribute('data-post-id'); // Obtiene el post_id.
                const tipoContenido = event.currentTarget.dataset.tipoContenido; // Obtiene el tipo de contenido.

                if (!post_id) { // Verifica si post_id existe.
                    console.error('No se encontró post_id en el botón');
                    return;
                }

                const confirmed = await confirm(confirmMessage); // Cuadro de confirmación.

                if (confirmed) {
                    const detalles = document.getElementById('mensajeError')?.value || ''; // Obtiene detalles (si aplica).
                    const descripcion = document.getElementById('mensajeEdit')?.value || ''; // Obtiene descripción (si aplica).

                    const data = await enviarAjax(action, { // Envía datos vía AJAX.
                        post_id, 
                        tipoContenido,
                        detalles,
                        descripcion
                    });

                    if (data.success) {
                        successCallback(null, data, post_id); // Llama a callback en caso de éxito.
                    } else {
                        console.error(`Error: ${data.message}`); // Muestra error.
                        alert('Error al enviar petición ' + (data.message || 'Error desconocido'));
                    }
                }
            });
            // Marca el botón para indicar que ya tiene un listener
            button.dataset.listenerAdded = 'true';
        }
    });
}

//ejemplo de algunas acciones
async function eliminarPost() {
    await accionClick(
        '.eliminarPost',
        'eliminarPostRs',
        '¿Estás seguro que quieres eliminar este post?',
        async (statusElement, data, postId) => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                const idToRemove = data.post_id || postId;
                console.log('Intentando remover post con ID:', idToRemove);
                removerPost('.EDYQHV', idToRemove);
                console.log('¿Se removió el post?');
                await alert('El post ha sido eliminado');
            } else {
                console.log('Error al eliminar post');
                actualizarElemento(statusElement, data.new_status);
                await alert('Hubo un error al eliminar el post');
            }
        },
        '.EDYQHV'
    );
}

async function permitirDescarga() {	
    await accionClick(
        '.permitirDescarga',
        'permitirDescarga',
        '¿Estas seguro de permitir la descarga?',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Permitiendo descarga.');
        },
        '.EDYQHV'
    );
}

async function banearUsuario() {
    await accionClick(
        '.banearUsuario',
        'banearUsuario',
        'Eh, vais a banear a alguien',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Baneando');
        },
        '.EDYQHV'
    );
}


async function reporte() {
    modalManager.añadirModal('formularioError', '#formularioError', ['.reporte']);
    const reportButtons = document.querySelectorAll('.reporte');
    if (reportButtons.length === 0) {
        console.log('No se encontraron botones de reporte');
        return;
    }

    reportButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');
            const tipoContenido = this.getAttribute('tipoContenido');
            abrirModalReporte(postId, tipoContenido);
        });
    });

    function abrirModalReporte(idContenido, tipoContenido) {
        modalManager.toggleModal('formularioError', true);

        accionClick('#enviarError', 'guardarReporte', '¿Estás seguro de que quieres enviar este reporte?', (statusElement, data) => {
            alert('Reporte enviado correctamente');
            modalManager.toggleModal('formularioError', false);
            // Limpiar el formulario
            document.getElementById('mensajeError').value = '';
        });

        //  Agrega el ID del post y el tipo de contenido
        const enviarErrorBtn = document.getElementById('enviarError');
        if (enviarErrorBtn) {
            enviarErrorBtn.dataset.postId = idContenido;
            enviarErrorBtn.dataset.tipoContenido = tipoContenido;
        }
    }
}



async function bloqueos() {
    async function bloquearUsuario(event, response, post_id) {
        const button = document.querySelector(`.bloquear[data-post-id="${post_id}"]`);
        if (button) {
            alert('Usuario bloqueado.');
            button.textContent = 'Desbloquear';
            button.classList.remove('bloquear');
            button.classList.add('desbloquear');
        } else {
            return; 
        }
    }
    accionClick('.bloquear', 'guardarBloqueo', '¿Estás seguro de bloquear este usuario?', bloquearUsuario);

    async function desbloquearUsuario(event, response, post_id) {
        const button = document.querySelector(`.desbloquear[data-post-id="${post_id}"]`);
        if (button) {
            alert('Usuario desbloqueado.');
            button.textContent = 'Bloquear';
            button.classList.remove('desbloquear');
            button.classList.add('bloquear');
        } else {
            return; 
        }
    }
    accionClick('.desbloquear', 'guardarBloqueo', '¿Estás seguro de desbloquear este usuario?', desbloquearUsuario);
}

async function editarPost() {
    modalManager.añadirModal('editarPost', '#editarPost', ['.editarPost']);
    const editButtons = document.querySelectorAll('.editarPost');
    
    if (editButtons.length === 0) {
        return;
    }

    // Añadir evento click a cada botón de editar
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');
            abrirModalEditarPost(postId);
        });
    });

    // Función para abrir el modal de edición y rellenarlo con el contenido del post
    function abrirModalEditarPost(idContenido) {
        modalManager.toggleModal('editarPost', true);

        // Buscar el contenido del post correspondiente en el DOM
        const postContentDiv = document.querySelector(`.thePostContet[data-post-id="${idContenido}"]`);
        let postContent = postContentDiv ? postContentDiv.innerHTML.trim() : '';

        // Eliminar etiquetas <p> y otras etiquetas innecesarias, manteniendo solo el texto
        postContent = postContent.replace(/<[^>]+>/g, ''); // Elimina todas las etiquetas HTML

        // Insertar el contenido limpio del post en el textarea del modal
        const mensajeEditTextarea = document.getElementById('mensajeEdit');
        if (mensajeEditTextarea) {
            mensajeEditTextarea.value = postContent;
        }

        // Agregar el ID del post al botón de enviar
        const enviarEditBtn = document.getElementById('enviarEdit');
        if (enviarEditBtn) {
            enviarEditBtn.dataset.postId = idContenido;
        }

        // Remover eventos previos antes de añadir un nuevo evento
        enviarEditBtn.replaceWith(enviarEditBtn.cloneNode(true)); 
        const newEnviarEditBtn = document.getElementById('enviarEdit');
        
        // Volver a agregar el evento click al nuevo botón clonado
        accionClick('#enviarEdit', 'cambiarDescripcion', '¿Estás seguro de que quieres editar este post?', (statusElement, data) => {
            alert('Post editado correctamente');
            if (postContentDiv) {
                // Actualizar el contenido del post sin etiquetas <p>
                postContentDiv.innerHTML = mensajeEditTextarea.value;
            }

            modalManager.toggleModal('editarPost', false);
        });
    }
}

async function requestDeletion() {
    await accionClick(
        '.request-deletion',
        'request_post_deletion',
        '¿Estás seguro de solicitar la eliminación de esta rola?',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('La solicitud de eliminación ha sido enviada.');
        },
        '.EDYQHV'
    );
}

async function rechazarColab() {
    await accionClick(
        '.rechazarcolab',
        'rechazarcolab',
        '¿Estás seguro que quieres rechazar la solicitud?',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Rechazando colab.');
        },
        '.EDYQHV'
    );
}

async function aceptarcolab() {
    await accionClick(
        '.aceptarcolab',
        'aceptarcolab',
        '¿Estas seguro de empezar la colaboración?',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Empezando colaboración');
        },
        '.EDYQHV'
    );
}

async function reportarcolab() {
    await accionClick(
        '.reportarcolab',
        'reportarcolab',
        '¿Estas seguro de reportar esta solicitud?',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Enviando reporte');
        },
        '.EDYQHV'
    );
}

async function estadorola() {
    await accionClick('.toggle-status-rola', 'toggle_post_status', '¿Estás seguro de cambiar el estado de la rola?', async (statusElement, data) => {
        actualizarElemento(statusElement, data.new_status);
        await alert('El estado ha sido cambiado');
    });
}

async function rejectPost() {
    await accionClick(
        '.rechazar-rola',
        'reject_post',
        '¿Estás seguro de rechazar esta rola?',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('La rola ha sido rechazada.');
        },
        '.EDYQHV'
    );
}



// GENERIC CLICK - DEBE SER FLEXIBLE PORQUE TODA LA LOGICA DE CLICK PASA POR AQUI
async function accionClick(selector, action, confirmMessage, successCallback, elementToRemoveSelector = null) {
    const buttons = document.querySelectorAll(selector); // Selecciona los botones.

    buttons.forEach(button => {
        button.addEventListener('click', async event => { // Añade evento 'click'.
            const post_id = event.currentTarget.dataset.postId || event.currentTarget.getAttribute('data-post-id'); // Obtiene el post_id.
            const tipoContenido = event.currentTarget.dataset.tipoContenido; // Obtiene el tipo de contenido.

            if (!post_id) { // Verifica si post_id existe.
                console.error('No se encontró post_id en el botón');
                return;
            }

            const confirmed = await confirm(confirmMessage); // Cuadro de confirmación.

            if (confirmed) {
                const detalles = document.getElementById('mensajeError')?.value || ''; // Obtiene detalles (si aplica).
                const descripcion = document.getElementById('mensajeEdit')?.value || ''; // Obtiene descripción (si aplica).

                const data = await enviarAjax(action, { // Envía datos vía AJAX.
                    post_id, 
                    tipoContenido,
                    detalles,
                    descripcion
                });

                if (data.success) {
                    successCallback(null, data, post_id); // Llama a callback en caso de éxito.
                } else {
                    console.error(`Error: ${data.message}`); // Muestra error.
                    alert('Error al enviar petición ' + (data.message || 'Error desconocido'));
                }
            }
        });
    });
}

//GENERIC FETCH
async function enviarAjax(action, data = {}) {
    try {
        const body = new URLSearchParams({
            action: action,
            ...data
        });
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: body
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status} - ${response.statusText}`);
        }
        let responseData;
        const responseText = await response.text();
        try {
            responseData = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('No se pudo interpretar la respuesta como JSON:', {
                error: jsonError,
                responseText: responseText,
                action: action,
                requestData: data
            });
            responseData = responseText;
        }
        return responseData; 
    } catch (error) {
        console.error('Error en la solicitud AJAX:', {
            error: error,
            action: action,
            requestData: data,
            ajaxUrl: ajaxUrl
        });
        return { success: false, message: error.message }; 
    }
}

//REMOVER POST
function removerPost(selector, postId) {
    console.log('Buscando elemento para remover:', selector, postId);
    const element = document.querySelector(`${selector}[id-post="${postId}"]`);
    if (element) {
        console.log('Elemento encontrado, removiendo...');
        element.remove();
    } else {
        console.log('No se encontró el elemento para remover');
    }
}

//GENERIC CAMBIAR DOM
function actualizarElemento(element, newStatus) {
    if (element) {
        element.textContent = newStatus;
    }
}


