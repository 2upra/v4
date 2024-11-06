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


async function verificarPost() {
    await accionClick(
        '.verificarPost',
        'verificarPost',
        'Verificar este post tilin',
        async (statusElement, data, post_id) => {
            actualizarElemento(statusElement, data.new_status);
            
            // Seleccionar el div específico de verificarPost usando el post_id
            const verificarPostDiv = document.querySelector(`.verificarPost[data-post-id="${post_id}"]`);
            
            if (verificarPostDiv) {
                // Limpiar el contenido actual
                verificarPostDiv.innerHTML = '';
                
                // Agregar el nuevo SVG
                const newSvg = `
                    <svg data-testid="geist-icon" height="16" stroke-linejoin="round" viewBox="0 0 16 16" width="16" style="color: currentcolor;">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M3.5 9.52717V4.057C3.69054 4.00405 3.8926 3.95131 4.10681 3.8954L4.10684 3.89539C4.25396 3.85699 4.40682 3.81709 4.5656 3.7746C5.15243 3.61758 5.79596 3.43066 6.38899 3.17017C6.97334 2.91351 7.55664 2.56529 8 2.05704C8.44336 2.56529 9.02666 2.91351 9.61101 3.17017C10.204 3.43066 10.8476 3.61758 11.4344 3.7746C11.5932 3.81709 11.746 3.85699 11.8932 3.89539C12.1074 3.9513 12.3094 4.00405 12.5 4.057V9.52717C12.5 10.9221 11.7257 12.2018 10.49 12.849L8 14.1533L5.50997 12.849C4.27429 12.2018 3.5 10.9221 3.5 9.52717ZM6.87802 1.06132C7.10537 0.796772 7.25 0.467199 7.25 0H8.75C8.75 0.467199 8.89463 0.796772 9.12198 1.06132C9.3643 1.34329 9.73045 1.58432 10.2142 1.79681C10.6962 2.00853 11.2465 2.17155 11.8221 2.32558C11.9557 2.36133 12.0926 2.39704 12.2305 2.43301L12.2307 2.43305C12.6631 2.54586 13.1054 2.66124 13.4872 2.78849L14 2.95943V3.5V9.52717C14 11.4801 12.916 13.2716 11.186 14.1778L8.34801 15.6644L8 15.8467L7.65199 15.6644L4.81396 14.1778C3.084 13.2716 2 11.4801 2 9.52717V3.5V2.95943L2.51283 2.78849C2.89458 2.66124 3.33687 2.54586 3.76932 2.43305L3.7694 2.43303C3.90732 2.39706 4.04424 2.36134 4.17787 2.32558C4.75351 2.17155 5.30375 2.00853 5.78576 1.79681C6.26955 1.58432 6.6357 1.34329 6.87802 1.06132ZM10.5303 7.53033L11.0607 7L10 5.93934L9.46967 6.46967L7 8.93934L6.53033 8.46967L6 7.93934L4.93934 9L5.46967 9.53033L6.46967 10.5303C6.76256 10.8232 7.23744 10.8232 7.53033 10.5303L10.5303 7.53033Z" fill="currentColor">
                        </path>
                    </svg>
                `;
                verificarPostDiv.innerHTML = newSvg;
            }
            
            await alert('Actualizado');
        },
        '.EDYQHV'
    );
}
// Función genérica para manejar acciones con confirmación y AJAX
async function accionClick(selector, action, confirmMessage, successCallback, elementToRemoveSelector = null) {
    const elements = document.querySelectorAll(selector); // Selecciona cualquier elemento que coincida con el selector

    elements.forEach(element => {
        // Verifica si el listener ya fue añadido
        if (!element.dataset.listenerAdded) {
            element.addEventListener('click', async event => {
                // Previene comportamiento por defecto si es un botón
                if (element.tagName.toLowerCase() === 'button') {
                    event.preventDefault();
                }

                // Obtiene el post_id del elemento actual
                const post_id = event.currentTarget.dataset.postId || 
                              event.currentTarget.getAttribute('data-post-id');
                              
                const tipoContenido = event.currentTarget.dataset.tipoContenido;

                if (!post_id) {
                    console.error('No se encontró post_id en el elemento');
                    return;
                }

                const confirmed = await confirm(confirmMessage);

                if (confirmed) {
                    const detalles = document.getElementById('mensajeError')?.value || '';
                    const descripcion = document.getElementById('mensajeEdit')?.value || '';

                    try {
                        const data = await enviarAjax(action, {
                            post_id,
                            tipoContenido,
                            detalles,
                            descripcion
                        });

                        if (data.success) {
                            successCallback(null, data, post_id);
                        } else {
                            console.error(`Error: ${data.message}`);
                            alert('Error al enviar petición: ' + (data.message || 'Error desconocido'));
                        }
                    } catch (error) {
                        console.error('Error al procesar la acción:', error);
                        alert('Ocurrió un error al procesar la acción.');
                    }
                }
            });
            
            // Añade cursor pointer si es un div u otro elemento que no sea botón
            if (element.tagName.toLowerCase() !== 'button') {
                element.style.cursor = 'pointer';
            }
            
            // Marca el elemento para indicar que ya tiene un listener
            element.dataset.listenerAdded = 'true';
        }
    });
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


