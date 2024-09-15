async function handleAllRequests() {
    try {
        await requestDeletion();
        await estadorola();
        await rejectPost();
        await eliminarPost();
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

//GENERIC AJAX
//GENERIC AJAX
async function enviarAjax(action, data = {}) {
    try {
        // Construimos el cuerpo de la solicitud
        const body = new URLSearchParams({
            action: action,
            ...data
        });

        console.log('Cuerpo de la solicitud que se enviará:', body.toString()); // Log para ver el cuerpo de la solicitud

        // Enviamos la solicitud
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

        console.log('Respuesta del servidor:', responseData);
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
            // Obtener el postId
            const postId = event.currentTarget.dataset.postId;
            console.log(`Botón clicado. postId encontrado: ${postId}`); // Log para verificar el postId

            if (!postId) {
                console.error('No se encontró postId en el botón');
                return; // Salir si no hay postId
            }

            // Encontrar el elemento de la publicación en el DOM
            const socialPost = event.currentTarget.closest('.social-post');
            const statusElement = socialPost?.querySelector('.post-status');

            // Confirmar la acción
            const confirmed = await confirm(confirmMessage);
            console.log(`Confirmación de usuario: ${confirmed ? 'Sí' : 'No'}`); // Log para ver la confirmación

            if (confirmed) {
                console.log(`Enviando solicitud AJAX para la acción: ${action} con postId: ${postId}`); // Log antes de enviar AJAX
                const data = await enviarAjax(action, { postId }); 
                
                console.log('Respuesta AJAX recibida:', data); // Log para ver la respuesta AJAX

                if (data.success) {
                    console.log(`Acción ${action} exitosa. Ejecutando callback de éxito.`); // Log para éxito
                    successCallback(statusElement, data);
                    
                    if (elementToRemoveSelector) {
                        console.log(`Removiendo elemento con selector: ${elementToRemoveSelector} y postId: ${postId}`); // Log para remover
                        removerPost(elementToRemoveSelector, postId);
                    }
                } else {
                    console.error(`Error al realizar la acción: ${action}. Mensaje: ${data.message}`);
                }
            } else {
                console.log('Acción cancelada por el usuario.');
            }
        });
    });
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

function inicializarDescargas() {
    document.addEventListener('click', async function (e) {
        if (e.target && e.target.classList.contains('download-button')) {
            e.preventDefault();
            const url = e.target.getAttribute('data-audio-url');
            const filename = e.target.getAttribute('data-filename');

            if (!url || !filename) return;

            try {
                const blob = await fetch(url).then(resp => {
                    if (!resp.ok) throw new Error('Error al descargar el archivo');
                    return resp.blob();
                });

                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = downloadUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(downloadUrl);
            } catch (error) {
                alert('Error al descargar el archivo');
            }
        }
    });
}
