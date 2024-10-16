const ajaxUrl = typeof ajax_params !== 'undefined' && ajax_params.ajax_url ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';

function setupEventDelegation() {
    const contenedor = document.querySelector('.A1806241'); // Reemplaza con el contenedor adecuado

    if (!contenedor) {
        console.error('Contenedor principal no encontrado.');
        return;
    }

    const acciones = [
        {
            selector: '.eliminarPost',
            action: 'eliminarPostRs',
            confirmMessage: '¿Estás seguro que quieres eliminar este post?',
            successCallback: async (statusElement, data, postId) => {
                console.log('Respuesta del servidor:', data);
                if (data.success) {
                    removerPost('.EDYQHV', data.post_id || postId);
                    await alert('El post ha sido eliminado');
                } else {
                    console.error('Error al eliminar post', data.message);
                    actualizarElemento(statusElement, data.new_status);
                    await alert('Hubo un error al eliminar el post');
                }
            },
        },
        {
            selector: '.permitirDescarga',
            action: 'permitirDescarga',
            confirmMessage: '¿Estás seguro de permitir la descarga?',
            successCallback: async (statusElement, data) => {
                actualizarElemento(statusElement, data.new_status);
                await alert('Descarga permitida.');
            },
        },
        {
            selector: '.banearUsuario',
            action: 'banearUsuario',
            confirmMessage: '¿Estás seguro de banear a este usuario?',
            successCallback: async (statusElement, data, userId) => {
                actualizarElemento(statusElement, data.new_status);
                alert('Usuario baneado.');
                // Opcional: Actualizar el botón para permitir desbloquear
                const button = contenedor.querySelector(`.banearUsuario[data-user-id="${userId}"]`);
                if (button) {
                    button.textContent = 'Desbloquear';
                    button.classList.remove('banearUsuario');
                    button.classList.add('desbloquearUsuario');
                }
            },
        },
        {
            selector: '.desbloquearUsuario',
            action: 'desbloquearUsuario',
            confirmMessage: '¿Estás seguro de desbloquear a este usuario?',
            successCallback: async (statusElement, data, userId) => {
                actualizarElemento(statusElement, data.new_status);
                alert('Usuario desbloqueado.');
                // Opcional: Actualizar el botón para permitir banear nuevamente
                const button = contenedor.querySelector(`.desbloquearUsuario[data-user-id="${userId}"]`);
                if (button) {
                    button.textContent = 'Banear';
                    button.classList.remove('desbloquearUsuario');
                    button.classList.add('banearUsuario');
                }
            },
        },
        {
            selector: '.editarPost',
            action: 'cambiarDescripcion',
            confirmMessage: '¿Estás seguro de que quieres editar este post?',
            openModal: abrirModalEditarPost, // Función para manejar la apertura del modal
            successCallback: async (statusElement, data, postId) => {
                alert('Post editado correctamente');
                const postContentDiv = contenedor.querySelector(`.thePostContet[data-post-id="${postId}"]`);
                const mensajeEditTextarea = document.getElementById('mensajeEdit');
                if (postContentDiv && mensajeEditTextarea) {
                    postContentDiv.textContent = mensajeEditTextarea.value.trim();
                }
                modalManager.toggleModal('editarPost', false);
            },
        },
        {
            selector: '.reporte',
            action: 'guardarReporte',
            confirmMessage: '¿Estás seguro de que quieres enviar este reporte?',
            openModal: abrirModalReporte, // Función para manejar la apertura del modal
            successCallback: async (statusElement, data) => {
                alert('Reporte enviado correctamente');
                modalManager.toggleModal('formularioError', false);
                document.getElementById('mensajeError').value = '';
            },
        },
    ];

    contenedor.addEventListener('click', async (event) => {
        const button = event.target.closest('button');
        if (!button) return;

        const accion = acciones.find(a => button.matches(a.selector));
        if (!accion) return; // No es una acción que manejamos

        event.preventDefault();

        // Manejar apertura de modales si aplica
        if (accion.openModal) {
            const dataId = button.dataset.postId || button.dataset.userId;
            accion.openModal(dataId, button.dataset.tipoContenido);
            return;
        }

        const post_id = button.dataset.postId || button.dataset.userId;
        const tipoContenido = button.dataset.tipoContenido;

        if (!post_id) {
            console.error('No se encontró post_id o user_id en el botón');
            return;
        }

        const confirmed = await confirm(accion.confirmMessage);
        if (!confirmed) return;

        const detalles = document.getElementById('mensajeError')?.value || '';
        const descripcion = document.getElementById('mensajeEdit')?.value || '';

        try {
            const data = await enviarAjax(accion.action, {
                post_id,
                tipoContenido,
                detalles,
                descripcion
            });

            if (data.success) {
                accion.successCallback(null, data, post_id);
            } else {
                console.error(`Error: ${data.message}`);
                alert('Error al enviar petición: ' + (data.message || 'Error desconocido'));
            }
        } catch (error) {
            console.error('Error en la solicitud AJAX:', error);
            alert('Hubo un error al procesar la solicitud.');
        }
    });

    // Funciones para manejar modales de reporte y edición
    function abrirModalReporte(idContenido, tipoContenido) {
        modalManager.añadirModal('formularioError', '#formularioError', ['.reporte']);
        modalManager.toggleModal('formularioError', true);

        const enviarErrorBtn = document.getElementById('enviarError');
        if (enviarErrorBtn) {
            enviarErrorBtn.dataset.postId = idContenido;
            enviarErrorBtn.dataset.tipoContenido = tipoContenido;
        }
    }

    function abrirModalEditarPost(idContenido) {
        modalManager.añadirModal('editarPost', '#editarPost', ['.editarPost']);
        modalManager.toggleModal('editarPost', true);

        const postContentDiv = contenedor.querySelector(`.thePostContet[data-post-id="${idContenido}"]`);
        let postContent = postContentDiv ? postContentDiv.textContent.trim() : '';

        const mensajeEditTextarea = document.getElementById('mensajeEdit');
        if (mensajeEditTextarea) {
            mensajeEditTextarea.value = postContent;
        }

        const enviarEditBtn = document.getElementById('enviarEdit');
        if (enviarEditBtn) {
            enviarEditBtn.dataset.postId = idContenido;
        }
    }
}


async function handleAllRequests() {
    try {
        await requestDeletion();
        await estadorola();
        await rejectPost();
        // await eliminarPost();
        await rechazarColab();
        await aceptarcolab();
        //await reporte();
        //await bloqueos();
        //await banearUsuario();
        //await editarPost();
        //await permitirDescarga();
    } catch (error) {
        console.error('Ocurrió un error al procesar las solicitudes:', error);
    }
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

/*
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
*/
/*
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
*/
/*
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
*/



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


