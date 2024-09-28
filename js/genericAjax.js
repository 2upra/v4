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
    } catch (error) {
        console.error('Ocurrió un error al procesar las solicitudes:', error);
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

async function eliminarPost() {
    await accionClick(
        '.eliminarPost',
        'eliminarPostRs',
        '¿Estás seguro que quieres eliminar este post?',
        async (statusElement, data, postId) => {
            console.log('Respuesta del servidor:', data);
            if (data.success) {
                // Usa data.post_id si existe, de lo contrario usa postId
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
            abrirModal(postId, tipoContenido);
        });
    });

    function abrirModal(idContenido, tipoContenido) {
        modalManager.toggleModal('formularioError', true);

        accionClick('#enviarError', 'guardarReporte', '¿Estás seguro de que quieres enviar este reporte?', (statusElement, data) => {
            alert('Reporte enviado correctamente');
            modalManager.toggleModal('formularioError', false);
            document.getElementById('mensajeError').value = '';
        });

        const enviarErrorBtn = document.getElementById('enviarError');
        if (enviarErrorBtn) {
            enviarErrorBtn.dataset.postId = idContenido;
            enviarErrorBtn.dataset.tipoContenido = tipoContenido;
        }
    }
}

/*

<button class="bloquear" data-post-id="232082">Bloquear</button>
genericAjax.js?ver=2.1.13.454380745:148  Uncaught (in promise) TypeError: Cannot read properties of null (reading 'currentTarget')
    at bloquearUsuario (genericAjax.js?ver=2.1.13.454380745:148:30)
    at HTMLButtonElement.<anonymous> (genericAjax.js?ver=2.1.13.454380745:224:21)
*/

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

// GENERIC CLICK - DEBE SER FLEXIBLE PORQUE TODA LA LOGICA DE CLICK PASA POR AQUI
async function accionClick(selector, action, confirmMessage, successCallback, elementToRemoveSelector = null) {
    const buttons = document.querySelectorAll(selector);

    buttons.forEach(button => {
        button.addEventListener('click', async event => {
            const post_id = event.currentTarget.dataset.postId || event.currentTarget.getAttribute('data-post-id');
            const tipoContenido = event.currentTarget.dataset.tipoContenido;

            if (!post_id) {
                console.error('No se encontró post_id en el botón');
                return;
            }

            const confirmed = await confirm(confirmMessage);

            if (confirmed) {
                const mensajeErrorInput = document.getElementById('mensajeError');
                const detalles = mensajeErrorInput ? mensajeErrorInput.value : '';
                const data = await enviarAjax(action, {
                    post_id: post_id,
                    tipoContenido: tipoContenido,
                    detalles: detalles
                });

                if (data.success) {
                    successCallback(null, data, post_id); 
                } else {
                    console.error(`Error al realizar la acción: ${action}. Mensaje: ${data.message}`);
                    alert('Error al enviar el reporte: ' + (data.message || 'Error desconocido'));
                }
            }
        });
    });
}

//GENERIC AJAX - DEBE SER FLEXIBLE PORQUE TODA LA LOGICA DE AJAX PASA POR AQUI
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
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        let responseData;
        const responseText = await response.text();
        try {
            responseData = JSON.parse(responseText);
        } catch (jsonError) {
            console.error('No se pudo interpretar la respuesta como JSON:', jsonError);
            responseData = responseText;
        }
        return responseData;
    } catch (error) {
        console.error('Error en la solicitud:', error);
        return {success: false, message: error.message};
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


