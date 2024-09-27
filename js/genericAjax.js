let enablelogAjax = false;
const logAjax = enablelogAjax ? console.log : function () {};
const ajaxUrl = (typeof ajax_params !== 'undefined' && ajax_params.ajax_url) ? ajax_params.ajax_url : '/wp-admin/admin-ajax.php';

//GENERIC AJAX
async function enviarAjax(action, data = {}) {
    try {
        const body = new URLSearchParams({
            action: action,
            ...data
        });

        logAjax('Cuerpo de la solicitud que se enviará:', body.toString()); 

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
            console.warn('No se pudo interpretar la respuesta como JSON:', jsonError);
            responseData = responseText; 
        }

        logAjax('Respuesta del servidor:', responseData);
        return responseData;

    } catch (error) {
        console.error('Error en la solicitud:', error);
        return { success: false, message: error.message };
    }
}

//GENERIC CLICK
async function accionClick(selector, action, confirmMessage, successCallback, elementToRemoveSelector = null) {
    const buttons = document.querySelectorAll(selector);

    buttons.forEach(button => {
        button.addEventListener('click', async event => {
            const postId = event.currentTarget.dataset.postId;
            const tipoContenido = event.currentTarget.dataset.tipoContenido;
            logAjax(`Botón clicado. postId encontrado: ${postId}, tipoContenido: ${tipoContenido}`);

            if (!postId) {
                console.error('No se encontró postId en el botón');
                return;
            }

            const confirmed = await confirm(confirmMessage);
            logAjax(`Confirmación de usuario: ${confirmed ? 'Sí' : 'No'}`);

            if (confirmed) {
                const mensajeErrorInput = document.getElementById('mensajeError');
                const detalles = mensajeErrorInput ? mensajeErrorInput.value : '';

                logAjax(`Enviando solicitud AJAX para la acción: ${action} con postId: ${postId}`);
                const data = await enviarAjax(action, { 
                    idContenido: postId,
                    tipoContenido: tipoContenido,
                    detalles: detalles
                });
                
                logAjax('Respuesta AJAX recibida:', data);

                if (data.success) {
                    logAjax(`Acción ${action} exitosa. Ejecutando callback de éxito.`);
                    successCallback(null, data);
                } else {
                    console.error(`Error al realizar la acción: ${action}. Mensaje: ${data.message}`);
                    alert('Error al enviar el reporte: ' + (data.message || 'Error desconocido'));
                }
            } else {
                logAjax('Acción cancelada por el usuario.');
            }
        });
    });
}

async function handleAllRequests() {
    try {
        await requestDeletion();
        await estadorola();
        await rejectPost();
        await eliminarPost();
        await rechazarColab();
        await aceptarcolab();
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
    await accionClick(
        '.toggle-status-rola', 
        'toggle_post_status', 
        '¿Estás seguro de cambiar el estado de la rola?', 
        async (statusElement, data) => {
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
        async (statusElement, data) => {
            actualizarElemento(statusElement, data.new_status);
            await alert('El post ha sido eliminado');
        },
        '.EDYQHV'
    );
}

//GENERIC CAMBIAR DOM
function actualizarElemento(element, newStatus) {
    if (element) {
        element.textContent = newStatus;
    }
}

function removerPost(selector, postId) {
    const element = document.querySelector(`${selector}[id-post="${postId}"]`);
    if (element) {
        element.remove();
    }
}

