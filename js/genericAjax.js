const ajaxUrl = typeof ajax_params !== 'undefined' && ajax_params.ajax_url ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';



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

async function verificarPost() {
    await accionClick(
        '.verificarPost',
        'verificarPost',
        'Verificar este post tilin',
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('Actualizado');
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

function initEditWordPress() {

    // Seleccionamos todos los botones con clase 'editarWordPress'
    const buttons = document.querySelectorAll('.editarWordPress');
    
    if (buttons.length > 0) {
        console.log('Botones encontrados:', buttons.length);

        // Añadimos un listener de click a cada botón individualmente
        buttons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); // Prevenir cualquier comportamiento por defecto (si lo hay)

                const postId = button.dataset.postId;
                if (postId) {
                    const url = `/wp-admin/post.php?post=${postId}&action=edit&classic-editor`;

                    window.open(url, '_blank');
                } else {

                }
            });
        });
    } else {
        return; 
    }
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

// Inicializa la funcionalidad de edición de publicaciones
async function editarPost() {
    // Añade el modal de edición si aún no está presente
    modalManager.añadirModal('editarPost', '#editarPost', ['.editarPost']);
    
    // Selecciona todos los botones de edición
    const editButtons = document.querySelectorAll('.editarPost');
    
    if (editButtons.length === 0) {
        return;
    }

    // Agrega un event listener a cada botón de edición
    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const postId = this.getAttribute('data-post-id');
            abrirModalEditarPost(postId);
        });
    });

    // Configura el botón de enviar una vez
    const enviarEditBtn = document.getElementById('enviarEdit');
    if (enviarEditBtn) {
        // Verifica si ya se ha añadido el listener para evitar duplicados
        if (!enviarEditBtn.dataset.listenerAdded) {
            enviarEditBtn.addEventListener('click', async function () {
                const postId = this.dataset.postId;
                
                if (!postId) {
                    console.error('No se encontró post_id en el botón enviarEdit');
                    return;
                }
                
                // Muestra una confirmación al usuario
                const confirmed = await confirm('¿Estás seguro de que quieres editar este post?');
                if (!confirmed) return;

                // Obtiene la descripción editada del textarea
                const descripcion = document.getElementById('mensajeEdit')?.value.trim() || '';
                
                try {
                    // Envía la solicitud AJAX para actualizar la descripción
                    const data = await enviarAjax('cambiarDescripcion', {
                        post_id: postId,
                        descripcion: descripcion
                    });

                    if (data.success) {
                        alert('Post editado correctamente');

                        // Actualiza el contenido de la publicación en el DOM
                        const postContentDiv = document.querySelector(`.thePostContet[data-post-id="${postId}"]`);
                        if (postContentDiv) {
                            postContentDiv.textContent = descripcion; // Usa textContent para evitar inyecciones de HTML
                        }

                        // Cierra el modal
                        modalManager.toggleModal('editarPost', false);
                    } else {
                        console.error(`Error: ${data.message}`);
                        alert('Error al enviar petición: ' + (data.message || 'Error desconocido'));
                    }
                } catch (error) {
                    console.error('Error al editar el post:', error);
                    alert('Ocurrió un error al editar el post.');
                }
            });
            // Marca que el listener ya ha sido añadido
            enviarEditBtn.dataset.listenerAdded = 'true';
        }
    }
}

// Abre el modal de edición y rellena el contenido correspondiente
function abrirModalEditarPost(idContenido) {
    // Muestra el modal
    modalManager.toggleModal('editarPost', true);

    // Busca el contenido del post correspondiente en el DOM
    const postContentDiv = document.querySelector(`.thePostContet[data-post-id="${idContenido}"]`);
    let postContent = postContentDiv ? postContentDiv.innerHTML.trim() : '';

    // Elimina todas las etiquetas HTML para obtener solo el texto
    postContent = postContent.replace(/<[^>]+>/g, '');

    // Inserta el contenido limpio en el textarea del modal
    const mensajeEditTextarea = document.getElementById('mensajeEdit');
    if (mensajeEditTextarea) {
        mensajeEditTextarea.value = postContent;
    }

    // Asigna el ID de la publicación al botón de enviar mediante un atributo de datos
    const enviarEditBtn = document.getElementById('enviarEdit');
    if (enviarEditBtn) {
        enviarEditBtn.dataset.postId = idContenido;
    }
}

// Maneja todas las solicitudes cada vez que hay una actualización de contenido vía AJAX
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
        await verificarPost();
    } catch (error) {
        console.error('Ocurrió un error al procesar las solicitudes:', error);
    }
}

// Función genérica para manejar acciones con confirmación y AJAX
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

                    try {
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
                            alert('Error al enviar petición: ' + (data.message || 'Error desconocido'));
                        }
                    } catch (error) {
                        console.error('Error al procesar la acción:', error);
                        alert('Ocurrió un error al procesar la acción.');
                    }
                }
            });
            // Marca el botón para indicar que ya tiene un listener
            button.dataset.listenerAdded = 'true';
        }
    });
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


