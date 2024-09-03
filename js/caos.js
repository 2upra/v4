async function enviarAjax(action, postId, additionalData = {}) {
    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: action,
                post_id: postId,
                ...additionalData
            })
        });

        return await response.json();
    } catch (error) {
        console.error('Error en la solicitud:', error);
        return {success: false, error: error.message};
    }
}

//GENERIC CLICK
async function accionClick(selector, action, confirmMessage, successCallback, elementToRemoveSelector = null) {
    const buttons = document.querySelectorAll(selector);

    buttons.forEach(button => {
        button.addEventListener('click', async event => {
            const postId = event.target.dataset.postId;
            const socialPost = event.target.closest('.social-post');
            const statusElement = socialPost?.querySelector('.post-status');

            const confirmed = await confirm(confirmMessage);
            if (confirmed) {
                const data = await enviarAjax(action, postId);

                if (data.success) {
                    successCallback(statusElement, data);
                    if (elementToRemoveSelector) {
                        removerPost(elementToRemoveSelector, postId);
                    }
                } else {
                    console.log(`Error al realizar la acción: ${action}`);
                }
            } else {
                console.log('Cancelado');
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
