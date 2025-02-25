function formatearTiempoRelativo(fecha) {
    const ahora = new Date();
    const diferenciaSegundos = Math.floor((ahora - new Date(fecha)) / 1000);

    const minutos = Math.floor(diferenciaSegundos / 60);
    const horas = Math.floor(minutos / 60);
    const dias = Math.floor(horas / 24);
    const semanas = Math.floor(dias / 7);

    if (semanas > 0) {
        return semanas === 1 ? '1 w' : `${semanas} w`;
    } else if (dias > 0) {
        return dias === 1 ? '1 d' : `${dias} d`;
    } else if (horas > 0) {
        return horas === 1 ? '1 h' : `${horas} h`;
    } else if (minutos > 0) {
        return minutos === 1 ? '1 m' : `${minutos} m`;
    } else {
        return 'ahora';
    }
}

function galle() {
    const channel = new BroadcastChannel('galle_chat_channel');
    const wsUrl = 'wss://2upra.com/ws';
    const emisor = galleV2.emisor;
    let receptor = null;
    let conversacion = null;
    let ws;
    let currentPage = 1;
    let pingInterval;
    let subidaChatProgreso;
    let archivoChatId = null;
    let archivoChatUrl = null;

    function init() {
        manejarScroll();
        actualizarConexionEmisor();
        iniciarChat();
        clickMensaje();
        chatColab(); // debería iniciarse de otra manera
        setupBroadcastChannel();
        manageControllerTab();
        abrirColab();
        actualizarTiemposRelativos();
        clickMensajeBoton();
    }

    function abrirColab() {
        // Selecciona todos los elementos <li> con la clase 'colabResumen'
        const colabResumenItems = document.querySelectorAll('li.colabResumen');

        // Referencia al fondo modal
        const modalBackground = document.getElementById('modalBackground2');

        colabResumenItems.forEach(function (item) {
            item.addEventListener('click', function () {
                // Cierra todos los modales abiertos antes de abrir uno nuevo
                const openModals = document.querySelectorAll('li.modal.POST-colab[style*="display: flex"]');
                openModals.forEach(function (modal) {
                    modal.style.display = 'none';
                });

                // Oculta el fondo modal si estaba visible
                modalBackground.style.display = 'none';

                // Obtiene el data-post_id del elemento clicado
                const postId = this.getAttribute('data-post_id');

                // Construye el selector para encontrar el <li> correspondiente
                const selector = `li.modal.POST-colab[id-post="${postId}"]`;
                const targetModal = document.querySelector(selector);

                if (targetModal) {
                    // Muestra el modal estableciendo display a 'flex'
                    targetModal.style.display = 'flex';

                    // Muestra el fondo modal
                    modalBackground.style.display = 'flex';
                } else {
                    console.warn(`No se encontró una conversación con id-post="${postId}"`);
                }
            });
        });

        // Selecciona todos los botones de cierre con la clase 'cerrarColab'
        const closeModalButtons = document.querySelectorAll('.cerrarColab');

        closeModalButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                // Obtiene el id-post del botón de cierre
                const postId = this.getAttribute('id-post');

                // Construye el selector para encontrar el <li> correspondiente
                const selector = `li.modal.POST-colab[id-post="${postId}"]`;
                const targetModal = document.querySelector(selector);

                if (targetModal) {
                    // Oculta el modal estableciendo display a 'none'
                    targetModal.style.display = 'none';

                    // Oculta el fondo modal si no hay otros modales abiertos
                    const anyVisibleModal = document.querySelector('li.modal.POST-colab[style*="display: flex"]');
                    if (!anyVisibleModal) {
                        modalBackground.style.display = 'none';
                    }
                } else {
                    console.warn(`No se encontró una conversación con id-post="${postId}"`);
                }
            });
        });

        // Opcional: Cerrar el modal al  clic en el fondo modal
        modalBackground.addEventListener('click', function () {
            // Selecciona el modal actualmente visible
            const visibleModal = document.querySelector('li.modal.POST-colab[style*="display: flex"]');

            if (visibleModal) {
                // Oculta el modal
                visibleModal.style.display = 'none';
            }

            // Oculta el fondo modal
            this.style.display = 'none';
        });
    }

    /*
     *   FUNCIONES RELACIONADAS A VERIFICAR EL STATUS ONLINE
     */

    function actualizarConexionEmisor() {
        const emisorId = galleV2.emisor;
        enviarAjax('actualizarConexion', {user_id: emisorId})
            .then(response => {
                if (response.success) {
                } else {
                    //console.error('No se pudo actualizar la conexión del emisor:', response.message);
                }
            })
            .catch(error => {
                //console.error('Error al actualizar la conexión del emisor:', error);
            });
    }

    async function actualizarEstadoConexion(receptor, bloqueChat) {
        actualizarConexionEmisor();

        try {
            const onlineStatus = await verificarConexionReceptor(receptor);
            const estadoConexion = bloqueChat.querySelector('.estadoConexion');

            if (onlineStatus?.online) {
                estadoConexion.textContent = 'Conectado';
                estadoConexion.classList.remove('desconectado');
                estadoConexion.classList.add('conectado');
            } else {
                estadoConexion.textContent = 'Desconectado';
                estadoConexion.classList.remove('conectado');
                estadoConexion.classList.add('desconectado');
            }
        } catch (error) {
            //console.error('Error al verificar el estado de conexión del receptor:', error);
        }
    }

    function verificarConexionReceptor(receptorId) {
        return enviarAjax('verificarConexionReceptor', {receptor_id: receptorId})
            .then(response => {
                if (response.success) {
                    return response.data;
                } else {
                    //console.error('Error al verificar la conexión del receptor:', response.message);
                    return null;
                }
            })
            .catch(error => {
                //console.error('Error en la solicitud para verificar la conexión del receptor:', error);
                return null;
            });
    }

    /*
     *   FUNCIONES RELACIONADAS A ABRIR UNA CONVERSACION
     */

    async function obtenerInfoUsuarios(userIds) {
        const userInfos = new Map();

        await Promise.all(
            userIds.map(async userId => {
                try {
                    const data = await enviarAjax('infoUsuario', {receptor: userId});
                    if (data?.success) {
                        const imagenPerfil = data.data.imagenPerfil || 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/05/perfildefault.jpg?quality=40&strip=all';
                        const nombreUsuario = data.data.nombreUsuario || 'Usuario Desconocido';
                        userInfos.set(userId, {imagenPerfil, nombreUsuario});
                    } else {
                        //console.error('Error al obtener información del usuario:', data.message);
                        userInfos.set(userId, {imagenPerfil: 'default.jpg', nombreUsuario: 'Usuario Desconocido'});
                    }
                } catch (error) {
                    //console.error('Error al obtener información del usuario:', error);
                    userInfos.set(userId, {imagenPerfil: 'default.jpg', nombreUsuario: 'Usuario Desconocido'});
                }
            })
        );

        return userInfos;
    }

    async function chatColab() {
        const chatColabElements = document.querySelectorAll('.bloqueChatColab');

        chatColabElements.forEach(async chatColabElement => {
            const conversacion_id = chatColabElement.dataset.conversacionId;
            if (!conversacion_id) {
                //console.error('El elemento no tiene data-post-id.');
                return;
            }
            currentPage = 1;

            try {
                const data = await enviarAjax('obtenerChatColab', {conversacion_id: conversacion_id, page: currentPage});

                if (data?.success) {
                    //console.log('Mensaje completo:', data);
                    const tipoMensaje = 'Colab';
                    mostrarMensajes(data.data.mensajes, chatColabElement, tipoMensaje);
                    manejarScrollColab(data.data.conversacion, chatColabElement);
                    subidaArchivosChat(chatColabElement);
                    msColabSetup(chatColabElement);
                    const listaMensajes = chatColabElement.querySelector('.listaMensajes');
                    if (listaMensajes) {
                        listaMensajes.scrollTop = listaMensajes.scrollHeight;
                    }
                } else {
                    //console.error('Error al obtener la conversación colab:', data.message);
                }
            } catch (error) {
                //console.error('Error al obtener la conversación colab:', error);
            }
        });
    }

    async function minimizarChat() {
        try {
            const bloqueChat = document.getElementById('bloqueChat');
            const botonMinimizar = document.getElementById('minizarChat');

            botonMinimizar.addEventListener('click', event => {
                event.stopPropagation(); // Evita que el evento se propague al contenedor padre
                bloqueChat.classList.add('minimizado');

                // Oculta los elementos internos
                const elementosAOcultar = bloqueChat.querySelectorAll('.listaMensajes, .previewsChat, .chatEnvio');
                elementosAOcultar.forEach(elem => (elem.style.display = 'none'));

                // Resetear variables globales del chat
                if (typeof archivoChatId !== 'undefined') archivoChatId = null;
                if (typeof archivoChatUrl !== 'undefined') archivoChatUrl = null;

                // Borrar cualquier texto en el área de texto
                const textareaMensaje = bloqueChat.querySelector('.mensajeContenido');
                if (textareaMensaje) textareaMensaje.value = '';
            });
        } catch (error) {
            //console.error('Error al minimizar el chat:', error);
            alert('Ha ocurrido un error al intentar minimizar el chat.');
        }
    }

    async function maximizarChat() {
        try {
            const bloqueChat = document.getElementById('bloqueChat');

            bloqueChat.addEventListener('click', event => {
                if (bloqueChat.classList.contains('minimizado')) {
                    bloqueChat.classList.remove('minimizado');

                    // Muestra los elementos internos
                    const elementosAMostrar = bloqueChat.querySelectorAll('.listaMensajes, .previewsChat, .chatEnvio');
                    elementosAMostrar.forEach(elem => (elem.style.display = ''));
                }
            });
        } catch (error) {
            //console.error('Error al maximizar el chat:', error);
            alert('Ha ocurrido un error al intentar maximizar el chat.');
        }
    }

    async function maximizarChatDirectamente() {
        try {
            const bloqueChat = document.getElementById('bloqueChat');

            if (bloqueChat.classList.contains('minimizado')) {
                bloqueChat.classList.remove('minimizado');

                // Muestra los elementos internos
                const elementosAMostrar = bloqueChat.querySelectorAll('.listaMensajes, .previewsChat, .chatEnvio');
                elementosAMostrar.forEach(elem => {
                    elem.style.display = '';
                });
            }
        } catch (error) {
            alert('Ha ocurrido un error al intentar maximizar el chat directamente.');
        }
    }

    maximizarChat();
    cerrarChat();
    minimizarChat();

    // Variable global para el ID de la conversación abierta
    let conversacionAbierta = null;

    function actualizarListaConversaciones(usuarioId, ultimoMensaje, conversacionId = null) {
        console.log('Iniciando actualizarListaConversaciones');

        const mensajesUl = document.querySelector('.mensajes');
        if (!mensajesUl) {
            console.warn('No se encontró el elemento .mensajes en el DOM.');
            return;
        }
        console.log('Elemento .mensajes encontrado:', mensajesUl);

        const listaMensajes = mensajesUl.querySelectorAll('.mensaje');
        //console.log('Número de mensajes encontrados:', listaMensajes.length);
        let conversacionActualizada = false;

        listaMensajes.forEach(mensaje => {
            const receptorId = mensaje.getAttribute('data-receptor');
            const mensajeConversacionId = mensaje.getAttribute('data-conversacion');
            //console.log(`Procesando mensaje con receptorId: ${receptorId}, conversacionId: ${mensajeConversacionId}`);

            if (receptorId == usuarioId) {
                const vistaPrevia = mensaje.querySelector('.vistaPrevia p');
                if (vistaPrevia) {
                    vistaPrevia.textContent = ultimoMensaje;
                    //console.log('Actualizada la vista previa del mensaje:', ultimoMensaje);
                }

                const tiempoMensajeDiv = mensaje.querySelector('.tiempoMensaje');
                if (tiempoMensajeDiv) {
                    const fechaActual = new Date();
                    tiempoMensajeDiv.setAttribute('data-fecha', fechaActual.toISOString());
                    const tiempoMensajeSpan = tiempoMensajeDiv.querySelector('span');
                    if (tiempoMensajeSpan) {
                        tiempoMensajeSpan.textContent = formatearTiempoRelativo(fechaActual);
                        //console.log('Actualizado el tiempo del mensaje:', tiempoMensajeSpan.textContent);
                    }
                }

                mensajesUl.insertBefore(mensaje, mensajesUl.firstChild);
                //console.log('Movimiento del mensaje al inicio de la lista');

                conversacionActualizada = true;

                // Logs adicionales para diagnosticar
                //console.log(`conversacionId: ${mensajeConversacionId}, conversacionAbierta: ${conversacionAbierta}`);

                // Verificación de condición con más control
                if (mensajeConversacionId && (conversacionAbierta === null || mensajeConversacionId != conversacionAbierta)) {
                    //console.log('Condición para cambiar color cumplida.');

                    const chatIconoDiv = document.querySelector('#chatIcono');
                    if (chatIconoDiv) {
                        //console.log('Añadiendo clase icono-rojo al contenedor #chatIcono');
                        chatIconoDiv.classList.add('icono-rojo');
                    } else {
                        console.warn('No se encontró el elemento #chatIcono');
                    }
                } else {
                    //console.log('Condición para cambiar color NO cumplida.');
                }
            }
        });

        if (!conversacionActualizada) {
            //console.log('No se encontró la conversación, reiniciando chats después de 1 segundo');
            setTimeout(() => {
                reiniciarChats();
            }, 1000);
        }
    }

    // Evento para quitar el color rojo al  clic en el icono de chat
    const chatIconoElemento = document.querySelector('#chatIcono');
    if (chatIconoElemento) {
        chatIconoElemento.addEventListener('click', function () {
            //console.log('Removiendo clase icono-rojo del contenedor #chatIcono');
            this.classList.remove('icono-rojo');
        });
    } else {
        console.warn('No se encontró el elemento #chatIcono al agregar el event listener.');
    }

    function reiniciarChats() {
        enviarAjax('reiniciarChats', {})
            .then(response => {
                if (response.success && response.data.html) {
                    const chatListContainer = document.querySelector('.bloqueChatReiniciar');
                    if (chatListContainer) {
                        // Borra el contenido anterior
                        chatListContainer.innerHTML = '';
                        // Reemplaza con el nuevo contenido
                        chatListContainer.innerHTML = response.data.html;
                        clickMensaje();
                    }
                } else {
                    //console.error('Error al reiniciar los chats:', response);
                }
            })
            .catch(error => {
                //console.error('Error al reiniciar los chats:', error);
            });
    }

    async function abrirConversacion({conversacion, receptor, imagenPerfil, nombreUsuario}) {
        try {
            let data = {success: true, data: {mensajes: [], conversacion: null}};
            currentPage = 1;
            if (conversacion) {
                data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});
                conversacionAbierta = conversacion;
            } else if (receptor) {
                data = await enviarAjax('obtenerChat', {receptor, page: currentPage});
                conversacionAbierta = data.data.conversacion;
            }
            if (data?.success) {
                const bloqueChat = document.querySelector('.bloqueChat');
                if (!bloqueChat) {
                    console.error('No se encontró el elemento .bloqueChat en el DOM.');
                    return;
                }

                // Configuraciones del chat
                subidaArchivosChat(bloqueChat);
                msSetup(bloqueChat);
                mostrarMensajes(data.data.mensajes, bloqueChat);
                bloqueChat.setAttribute('data-user-id', receptor);
                bloqueChat.querySelector('.imagenMensaje img').src = imagenPerfil;
                bloqueChat.querySelector('.nombreConversacion p').textContent = nombreUsuario;
                bloqueChat.style.display = 'flex';
                maximizarChatDirectamente();
                manejarScroll(data.data.conversacion, bloqueChat);
                const listaMensajes = bloqueChat.querySelector('.listaMensajes');
                if (listaMensajes) {
                    listaMensajes.scrollTop = listaMensajes.scrollHeight;
                }

                await actualizarEstadoConexion(receptor, bloqueChat);
                setInterval(() => actualizarEstadoConexion(receptor, bloqueChat), 30000);
            } else {
                alert(data.message || 'Error desconocido al obtener los mensajes.');
            }
        } catch (error) {
            console.error('Error al abrir la conversación:', error);
            alert('Ha ocurrido un error al intentar abrir la conversación.');
        }
    }

    async function cerrarChat() {
        try {
            const bloqueChat = document.querySelector('.bloqueChat');
            const botonCerrar = document.getElementById('cerrarChat');

            botonCerrar.addEventListener('click', () => {
                bloqueChat.style.display = 'none';
                bloqueChat.classList.remove('minimizado');
                conversacionAbierta = null;
                const textareaMensaje = document.querySelector('.mensajeContenido');
                textareaMensaje.value = '';
            });
        } catch (error) {
            console.error('Error al cerrar el chat:', error);
            alert('Ha ocurrido un error al intentar cerrar el chat.');
        }
    }

    async function manejarClickEnConversacion(item) {
        item.addEventListener('click', async () => {
            let conversacion = item.getAttribute('data-conversacion');
            receptor = item.getAttribute('data-receptor');
            let imagenPerfil = item.querySelector('.imagenMensaje img')?.src || null;
            let nombreUsuario = item.querySelector('.nombreUsuario strong')?.textContent || null;

            if (!imagenPerfil || !nombreUsuario) {
                try {
                    const data = await enviarAjax('infoUsuario', {receptor});
                    if (data?.success) {
                        imagenPerfil = data.data.imagenPerfil || 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/05/perfildefault.jpg?quality=40&strip=all';
                        nombreUsuario = data.data.nombreUsuario || 'Usuario Desconocido'; // Nombre por defecto si no se encuentra
                    } else {
                        //console.error('Error del servidor:', data.message);
                        alert(data.message || 'Error al obtener la información del usuario.');
                        return;
                    }
                } catch (error) {
                    //console.error('Error de conexión:', error);
                    alert('Error al intentar obtener la información del usuario.');
                    return;
                }
            }

            // Abrir la conversación
            abrirConversacion({
                conversacion: conversacion || null,
                receptor,
                imagenPerfil,
                nombreUsuario
            });
        });
    }

    function clickMensaje() {
        const mensajes = document.querySelectorAll('.mensaje');
        if (mensajes.length > 0) {
            mensajes.forEach(item => manejarClickEnConversacion(item));
        }
    }

    function clickMensajeBoton() {
        const botonesMensaje = document.querySelectorAll('.mensajeBoton');
        if (botonesMensaje.length > 0) {
            botonesMensaje.forEach(item => manejarClickEnConversacion(item));
        }
    }

    /*
     *   FUNCIONES RELACIONADAS CON LA CARGAN LOS MENSAJES DE UNA CONVERSACION
     */

    function manejarAdjunto(adjunto, li) {
        if (adjunto) {
            const adjuntoContainer = document.createElement('div');
            adjuntoContainer.classList.add('adjunto-container');

            if (adjunto.archivoChatUrl) {
                const ext = adjunto.archivoChatUrl.split('.').pop().toLowerCase();
                const fileName = adjunto.archivoChatUrl.split('/').pop(); // Obtiene el nombre del archivo

                // Si es una imagen
                if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
                    adjuntoContainer.innerHTML = `<img src="${adjunto.archivoChatUrl}" alt="Imagen adjunta" style="width: 100%; height: auto; object-fit: cover;">`;
                }
                // Si es un archivo de audio
                else if (['mp3', 'ogg', 'wav'].includes(ext)) {
                    const audioContainerId = `waveform-container-${Date.now()}`;
                    adjuntoContainer.innerHTML = `
                        <div id="${audioContainerId}" class="waveform-container without-image" data-audio-url="${adjunto.archivoChatUrl}">
                            <audio controls style="width: 100%;"><source src="${adjunto.archivoChatUrl}" type="audio/${ext}"></audio>
                        </div>
                        <div class="archivoChat">
                            <div class="file-name">${fileName}</div>
                            <a href="${adjunto.archivoChatUrl}" target="_blank"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.875 1.5C3.01104 1.5 1.5 3.01104 1.5 4.875V6.40385C1.5 7.22166 1.87727 7.95083 2.47018 8.42791L3.0545 8.89808L2.11415 10.0667L1.52982 9.59656C0.598275 8.84699 0 7.69502 0 6.40385V4.875C0 2.18261 2.18261 0 4.875 0C6.79184 0 8.44888 1.1064 9.2449 2.71201C9.35322 2.93049 9.51183 3 9.60112 3H12.25C14.3211 3 16 4.67893 16 6.75C16 7.8785 15.5006 8.89123 14.7133 9.57758L14.1479 10.0704L13.1622 8.93972L13.7276 8.44689C14.202 8.03329 14.5 7.42688 14.5 6.75C14.5 5.50736 13.4926 4.5 12.25 4.5H9.60112C8.79547 4.5 8.1902 3.96162 7.901 3.37829C7.34838 2.26364 6.20018 1.5 4.875 1.5ZM8.75 13.4393L10.7197 11.4697L11.25 10.9393L12.3107 12L11.7803 12.5303L8.70711 15.6036C8.31658 15.9941 7.68342 15.9941 7.29289 15.6036L4.21967 12.5303L3.68934 12L4.75 10.9393L5.28033 11.4697L7.25 13.4393V7.75V7H8.75V7.75V13.4393Z" fill="currentColor"></path></svg></a>
                        </div>`;

                    // Inicializar el waveform después de que el elemento se haya agregado al DOM
                    setTimeout(() => {
                        // Utilizamos `adjunto.archivoChatUrl` como el identificador para el caché
                        inicializarWaveform(audioContainerId, adjunto.archivoChatUrl);
                    }, 0);
                }
                // Si es otro tipo de archivo
                else {
                    adjuntoContainer.innerHTML = `
                        <div class="archivoChat">
                            <div class="file-name">${fileName}</div>
                            <a href="${adjunto.archivoChatUrl}" target="_blank"><svg data-testid="geist-icon" height="16" stroke-linejoin="round" style="color:currentColor" viewBox="0 0 16 16" width="16"><path fill-rule="evenodd" clip-rule="evenodd" d="M4.875 1.5C3.01104 1.5 1.5 3.01104 1.5 4.875V6.40385C1.5 7.22166 1.87727 7.95083 2.47018 8.42791L3.0545 8.89808L2.11415 10.0667L1.52982 9.59656C0.598275 8.84699 0 7.69502 0 6.40385V4.875C0 2.18261 2.18261 0 4.875 0C6.79184 0 8.44888 1.1064 9.2449 2.71201C9.35322 2.93049 9.51183 3 9.60112 3H12.25C14.3211 3 16 4.67893 16 6.75C16 7.8785 15.5006 8.89123 14.7133 9.57758L14.1479 10.0704L13.1622 8.93972L13.7276 8.44689C14.202 8.03329 14.5 7.42688 14.5 6.75C14.5 5.50736 13.4926 4.5 12.25 4.5H9.60112C8.79547 4.5 8.1902 3.96162 7.901 3.37829C7.34838 2.26364 6.20018 1.5 4.875 1.5ZM8.75 13.4393L10.7197 11.4697L11.25 10.9393L12.3107 12L11.7803 12.5303L8.70711 15.6036C8.31658 15.9941 7.68342 15.9941 7.29289 15.6036L4.21967 12.5303L3.68934 12L4.75 10.9393L5.28033 11.4697L7.25 13.4393V7.75V7H8.75V7.75V13.4393Z" fill="currentColor"></path></svg></a>
                        </div>`;
                }
            }

            li.appendChild(adjuntoContainer);
        }
    }

    // TIEMPO

    setInterval(actualizarTiemposRelativos, 4000);
    actualizarTiemposRelativos();

    function actualizarTiemposRelativos() {
        const actualizarElementosFecha = selector => {
            const elementos = document.querySelectorAll(selector);
            elementos.forEach(elemento => {
                const fechaMensaje = new Date(elemento.getAttribute('data-fecha'));
                elemento.textContent = formatearTiempoRelativo(fechaMensaje);
            });
        };
        actualizarElementosFecha('.fechaSeparador');
        actualizarElementosFecha('.tiempoMensaje');
    }

    // VERIFICAR TOKEN

    let token = null;
    async function obtenerToken() {
        try {
            const response = await enviarAjax('generarToken', {});
            if (response.success) {
                return response.data.token;
            } else {
                //console.error('No se pudo obtener el token:', response.message);
                return null;
            }
        } catch (error) {
            //console.error('Error al obtener el token:', error);
            return null;
        }
    }

    // FUNCIONES SOCKET

    async function iniciarChat() {
        token = await obtenerToken();
        if (token) {
            connectWebSocket();
        } else {
            console.error('No se pudo iniciar el chat sin un token válido');
        }
    }

    function setupBroadcastChannel() {
        channel.onmessage = event => {
            const message = event.data;
            manejarMensajeWebSocket(JSON.stringify(message));
        };
    }

    function manageControllerTab() {
        if (!localStorage.getItem('chat_controller')) {
            localStorage.setItem('chat_controller', Date.now());
            connectWebSocket();
        } else {
            // Escuchar mensajes solo a través de BroadcastChannel
        }

        window.addEventListener('storage', event => {
            if (event.key === 'chat_controller' && !event.newValue) {
                localStorage.setItem('chat_controller', Date.now());
                connectWebSocket();
            }
        });
    }

    function connectWebSocket() {
        //console.log('Intentando conectar a WebSocket...');
        ws = new WebSocket(wsUrl);
        ws.onopen = () => {
            //console.log('Conectado a WebSocket, enviando autenticación...');
            ws.send(
                JSON.stringify({
                    emisor,
                    type: 'auth',
                    token: token
                })
            );
            pingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    //console.log('Enviando ping...');
                    ws.send(JSON.stringify({type: 'ping'}));
                }
            }, 30000);
        };
        ws.onclose = () => {
            clearInterval(pingInterval);
            setTimeout(connectWebSocket, 5000);
        };
        ws.onerror = error => {
            //console.error('Error en WebSocket:', error);
        };
        ws.onmessage = ({data}) => {
            const message = JSON.parse(data);
            channel.postMessage(message);
            if (message.type === 'pong') {
                //console.log('Recibido pong, todo bien...');
            } else if (message.type === 'set_emisor') {
                //console.log('Recibido set_emisor, reenviando emisor...');
                ws.send(JSON.stringify({emisor}));
            } else if (message.type === 'message_saved') {
                //console.log('Recibido message_saved:', message);
                confirmarMensaje(message);
            } else if (message.type === 'message_error') {
                //console.log('Recibido message_error, manejando error...');
                manejarError(message);
            } else {
                //console.log('Recibido mensaje desconocido, manejando como mensaje WebSocket...');
                manejarMensajeWebSocket(JSON.stringify(message));
            }
        };
    }

    // Escuchar mensajes entrantes desde otras pestañas
    channel.onmessage = event => {
        const message = event.data;

        // Procesar el mensaje recibido
        if (message.type === 'message_saved') {
            confirmarMensaje(message);
        } else if (message.type === 'message_error') {
            manejarError(message);
        } else {
            manejarMensajeWebSocket(JSON.stringify(message));
        }
    };

    function enviarMensajeWs(receptor, mensaje, adjunto = null, metadata = null, conversacion_id = null, listaMensajes = null) {
        const temp_id = Date.now();

        const receptorFinal = typeof receptor === 'string' ? receptor : typeof receptor === 'object' ? JSON.stringify(receptor) : null;

        if (!receptorFinal) {
            return; //console.error('Formato de receptor no válido.');
        }

        const messageData = {
            emisor,
            receptor: receptorFinal,
            mensaje,
            adjunto,
            metadata,
            conversacion_id,
            temp_id
        };

        if (ws?.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(messageData));
            listaMensajes ||= document.querySelector('.listaMensajes');
            channel.postMessage(messageData);
            agregarMensajeAlChat(mensaje, 'mensajeDerecha', new Date(), listaMensajes, null, false, adjunto, temp_id);
        } else {
            //console.error('enviarMensajeWs: WebSocket no conectado, mensaje no enviado.');
            alert('No se puede enviar el mensaje, por favor, reinicia la página.');
        }
    }

    function confirmarMensaje(message) {
        const conversacionId = message.original_message.conversacion_id;
        let listaMensajes;

        if (conversacionId) {
            const bloqueChatColab = document.querySelector(`.bloqueChatColab[data-conversacion-id="${conversacionId}"]`);
            if (!bloqueChatColab) {
                return; //console.warn(`No se encontró el bloqueChatColab con conversacion_id ${conversacionId}.`);
            }
            listaMensajes = bloqueChatColab.querySelector('.listaMensajes');
        } else {
            listaMensajes = document.querySelector('.listaMensajes');
        }

        const mensajeElemento = listaMensajes.querySelector(`[data-temp-id="${message.original_message.temp_id}"]`);

        if (mensajeElemento) {
            mensajeElemento.classList.replace('mensajePendiente', 'mensajeEnviado');
        } else {
            //console.warn(`No se encontró el mensaje con ID temporal ${message.original_message.temp_id}.`);
        }
    }

    function manejarError(message) {
        const listaMensajes = document.querySelector('.listaMensajes');
        const mensajeElemento = listaMensajes.querySelector(`[data-temp-id="${message.original_message.temp_id}"]`);

        if (mensajeElemento) {
            //console.error(`manejarError: Error en el mensaje con ID temporal ${message.original_message.temp_id}.`);
            mensajeElemento.classList.add('mensajeError');
        } else {
            //console.warn(`manejarError: No se encontró el elemento del mensaje con ID temporal ${message.original_message.temp_id}.`);
        }
    }

    // FUNCIONES PARA ENVIAR MENSAJE

    function msSetup(chatContainer) {
        document.addEventListener('click', event => {
            if (event.target.matches('.enviarMensaje')) {
                enviarMensaje();
            }
        });

        const mensajeInput = document.querySelector('.mensajeContenido');
        mensajeInput.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.altKey) {
                event.preventDefault();
                enviarMensaje(chatContainer);
            }
        });

        function enviarMensaje(chatContainer) {
            if (subidaChatProgreso) {
                return alert('Por favor espera a que se complete la subida del archivo.');
            }
            const mensaje = mensajeInput.value.trim();
            if (!mensaje) return;
            ocultarPreviews(chatContainer);
            let adjunto = archivoChatId || archivoChatUrl ? {archivoChatId, archivoChatUrl} : null;
            archivoChatId = archivoChatUrl = null;
            enviarMensajeWs(receptor, mensaje, adjunto);
            mensajeInput.value = '';
            actualizarListaConversaciones(receptor, `Tu: ${mensaje}`);
        }
    }

    function ocultarPreviews(chatContainer) {
        const previewChatAudio = chatContainer.querySelector('.previewChatAudio');
        const previewChatImagen = chatContainer.querySelector('.previewChatImagen');
        const previewChatArchivo = chatContainer.querySelector('.previewChatArchivo');
        const cancelUploadButton = chatContainer.querySelector('.cancelUploadButton');

        if (previewChatAudio) previewChatAudio.style.display = 'none';
        if (previewChatImagen) previewChatImagen.style.display = 'none';
        if (previewChatArchivo) previewChatArchivo.style.display = 'none';
        if (cancelUploadButton) cancelUploadButton.style.display = 'none';
    }

    function subidaArchivosChat(chatContainer) {
        const enviarAdjunto = chatContainer.querySelector('.enviarAdjunto');
        const previewChatArchivo = chatContainer.querySelector('.previewChatArchivo');
        const previewChatAudio = chatContainer.querySelector('.previewChatAudio');
        const previewChatImagen = chatContainer.querySelector('.previewChatImagen');
        const cancelUploadButton = chatContainer.querySelector('.cancelUploadButton');
        const elements = {enviarAdjunto, previewChatArchivo, previewChatAudio, previewChatImagen, cancelUploadButton};
        const missingElements = Object.entries(elements)
            .filter(([_, el]) => !el)
            .map(([key]) => key);

        if (missingElements.length) {
            //console.warn(`Missing elements in chat container: ${missingElements.join(', ')}`);
            return;
        }

        cancelUploadButton.addEventListener('click', () => {
            subidaChatProgreso = false;
            archivoChatId = null;
            archivoChatUrl = null;
            ocultarPreviews(chatContainer);
        });

        enviarAdjunto.addEventListener('click', () => abrirSelectorArchivos('*/*'));

        const inicialChatSubida = event => {
            event.preventDefault();
            const file = event.dataTransfer?.files[0] || event.target.files[0];

            if (!file) return;
            if (file.size > 50 * 1024 * 1024) {
                //console.log('El archivo no puede superar los 50 MB.');
                return;
            }
            if (file.type.startsWith('audio/')) {
                subidaChatAudio(file);
            } else if (file.type.startsWith('image/')) {
                subidaChatImagen(file);
            } else {
                subidaChatArchivo(file);
            }
        };

        const subidaChatAudio = async file => {
            //console.log('Iniciando subida de audio:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            ocultarPreviews(chatContainer); // Ocultar otros previews

            try {
                //console.log('Cargando archivo de audio...');
                previewChatAudio.style.display = 'flex';
                cancelUploadButton.style.display = 'flex';
                const progressBarId = waveAudio(file);
                //console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                //console.log('Audio cargado con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;
                subidaChatProgreso = false;
            } catch (error) {
                //console.error('Error al cargar el audio:', error);
                subidaChatProgreso = false;
            }
        };

        const subidaChatImagen = async file => {
            //console.log('Iniciando subida de imagen:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            ocultarPreviews(chatContainer); // Ocultar otros previews
            updateChatPreviewImagen(file);

            try {
                //console.log('Cargando archivo de imagen...');
                previewChatImagen.style.display = 'flex';
                cancelUploadButton.style.display = 'flex';
                const progressBarId = `progress-${Date.now()}`;
                //console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                //console.log('Imagen cargada con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;

                subidaChatProgreso = false;
            } catch (error) {
                //console.error('Error al cargar la imagen:', error);
                subidaChatProgreso = false;
            }
        };

        const subidaChatArchivo = async file => {
            //console.log('Iniciando subida de archivo:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            cancelUploadButton.style.display = 'flex';
            ocultarPreviews(chatContainer); // Ocultar otros previews
            previewChatArchivo.style.display = 'flex';
            previewChatArchivo.innerHTML = `
                <div class="file-name">${file.name}</div>
                <div id="barraProgresoFile" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>`;

            try {
                //console.log('Cargando archivo...');
                const progressBarId = `progress-${Date.now()}`;
                //console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                //console.log('Archivo cargado con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;
                subidaChatProgreso = false;
            } catch (error) {
                //console.error('Error al cargar el archivo:', error);
                subidaChatProgreso = false;
            }
        };

        const waveAudio = file => {
            const reader = new FileReader();
            const audioContainerId = `waveform-container-${Date.now()}`;
            const progressBarId = `progress-${Date.now()}`;
            reader.onload = e => {
                previewChatAudio.innerHTML = `
                    <div id="${audioContainerId}" class="waveform-container without-image" data-audio-url="${e.target.result}">
                        <div class="waveform-background"></div>
                        <div class="waveform-message"></div>
                        <div class="waveform-loading" style="display: none;">Cargando...</div>
                        <audio controls style="width: 100%;"><source src="${e.target.result}" type="${file.type}"></audio>
                        <div class="file-name">${file.name}</div>
                    </div>
                    <div class="progress-bar" style="width: 100%; height: 2px; background-color: #ddd; margin-top: 10px;">
                        <div id="${progressBarId}" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>
                    </div>`;
                inicializarWaveform(audioContainerId, e.target.result);
            };
            reader.readAsDataURL(file);
            return progressBarId;
        };

        const updateChatPreviewImagen = file => {
            const reader = new FileReader();
            reader.onload = e => {
                previewChatImagen.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; object-fit: cover;">`;
                previewChatImagen.style.display = 'flex';
            };
            reader.readAsDataURL(file);
        };

        bloqueChat.addEventListener('click', event => {
            const clickedElement = event.target.closest('.previewChatAudio, .previewChatImagen');
            if (clickedElement) {
                abrirSelectorArchivos(clickedElement.classList.contains('previewChatAudio') ? 'audio/*' : 'image/*');
            }
        });

        const abrirSelectorArchivos = tipoArchivo => {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = tipoArchivo;
            input.onchange = inicialChatSubida;
            input.click();
        };

        ['dragover', 'dragleave', 'drop'].forEach(eventName => {
            bloqueChat.addEventListener(eventName, e => {
                e.preventDefault();
                bloqueChat.style.backgroundColor = eventName === 'dragover' ? '#e9e9e9' : '';
                if (eventName === 'drop') inicialChatSubida(e);
            });
        });
    }

    async function subidaChatBackend(file, progressBarId) {
        const formData = new FormData();
        let chat = true; // Asegúrate de declarar correctamente la variable "chat".

        console.log('Iniciando generación de hash para el archivo...');

        // Agrega logs antes y después de las operaciones más relevantes
        try {
            const fileHash = await generateFileHash(file, chat);
            console.log('Hash generado:', fileHash);
            formData.append('file_hash', fileHash);
        } catch (error) {
            console.error('Error al generar el hash del archivo:', error);
            throw error; // Lanza el error para detener la ejecución si falla
        }

        formData.append('action', 'file_upload');
        formData.append('file', file);
        formData.append('chat', chat);

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', ajaxUrl, true);

            // Log para confirmar que la solicitud se ha abierto correctamente
            console.log('Solicitud POST abierta a:', ajaxUrl);

            // Actualización de la barra de progreso
            xhr.upload.onprogress = e => {
                if (e.lengthComputable) {
                    const progressBar = document.getElementById(progressBarId);
                    const progressPercent = (e.loaded / e.total) * 100;
                    console.log(`Progreso de subida: ${progressPercent}%`);
                    if (progressBar) progressBar.style.width = `${progressPercent}%`;
                }
            };

            // Manejo de la respuesta del servidor
            xhr.onload = () => {
                console.log('Respuesta recibida del servidor. Status:', xhr.status);
                if (xhr.status === 200) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        console.log('Respuesta JSON parseada:', result);
                        if (result.success) {
                            console.log('Subida exitosa, datos recibidos:', result.data);
                            resolve(result.data); // Devuelve la data en caso de éxito
                        } else {
                            console.error('La respuesta del servidor no indica éxito:', result);
                            reject(new Error('Error en la respuesta del servidor'));
                        }
                    } catch (error) {
                        console.error('Error al parsear la respuesta JSON:', error);
                        reject(error); // Error al parsear la respuesta
                    }
                } else {
                    console.error(`Error en la carga del archivo. Status: ${xhr.status}`);
                    reject(new Error(`Error en la carga del archivo. Status: ${xhr.status}`));
                }
            };

            // Manejo de errores de conexión
            xhr.onerror = () => {
                console.error('Error en la conexión con el servidor');
                reject(new Error('Error en la conexión con el servidor'));
            };

            // Enviar solicitud AJAX
            try {
                console.log('Enviando la solicitud AJAX...');
                xhr.send(formData);
            } catch (error) {
                console.error('Error al enviar la solicitud AJAX:', error);
                reject(new Error('Error al enviar la solicitud AJAX'));
            }
        });
    }

    // SCROLLS

    function manejarScroll(conversacion) {
        const listaMensajes = document.querySelector('.listaMensajes');
        let puedeDesplazar = true;
        let currentPage = 1;

        listaMensajes?.addEventListener('scroll', async e => {
            if (e.target.scrollTop === 0 && puedeDesplazar) {
                puedeDesplazar = false;
                setTimeout(() => (puedeDesplazar = true), 2000);
                currentPage++;
                const data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});
                if (!data?.success) {
                    return; //console.error('Error al obtener más mensajes.');
                }
                const mensajes = data.data.mensajes.reverse();
                let fechaAnterior = null;

                mensajes.forEach(mensaje => {
                    agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, true, mensaje.adjunto, null, null, null, null, 'Individual');
                    fechaAnterior = new Date(mensaje.fecha);
                });

                listaMensajes.querySelector('li')?.scrollIntoView();
            }
        });
    }

    async function manejarScrollColab(conversacion, contenedor = null) {
        const listaMensajes = (contenedor || document).querySelector('.listaMensajes');
        let puedeDesplazar = true,
            currentPage = 1,
            conversacion_id = conversacion;

        listaMensajes.addEventListener('scroll', async e => {
            if (e.target.scrollTop === 0 && puedeDesplazar) {
                puedeDesplazar = false;
                setTimeout(() => (puedeDesplazar = true), 2000);
                currentPage++;

                const data = await enviarAjax('obtenerChatColab', {conversacion_id, page: currentPage});
                if (!data?.success) {
                    return; //console.error('Error al obtener más mensajes.');
                }

                let mensajes = data.data.mensajes;

                const remitentesUnicos = [...new Set(mensajes.map(m => m.remitente))];
                const userInfos = await obtenerInfoUsuarios(remitentesUnicos);

                let fechaAnterior = null;

                const primerMensajeMostrado = listaMensajes.querySelector('.messageflex');
                let nextRemitente = null;

                if (primerMensajeMostrado) {
                    const primerMensajeElem = primerMensajeMostrado.querySelector('.mensajeText');
                    if (primerMensajeElem) {
                        nextRemitente = primerMensajeElem.getAttribute('data-emisor') || null;
                    }
                }

                //console.log('[[manejarScrollColab]] nextRemitente inicial:', nextRemitente);

                // Guardar la posición del scroll actual antes de insertar nuevos mensajes
                const scrollPosAntesDeInsertar = listaMensajes.scrollHeight - listaMensajes.scrollTop;

                // Procesar los mensajes en orden descendente (del más reciente al más antiguo)
                for (let i = mensajes.length - 1; i >= 0; i--) {
                    const mensaje = mensajes[i];

                    // Determinar el remitente del mensaje que sigue (el que se mostrará después en el chat)
                    let mensajeSiguienteRemitente;
                    if (i > 0) {
                        // Si no es el último mensaje, obtener el remitente del mensaje siguiente
                        mensajeSiguienteRemitente = mensajes[i - 1].remitente;
                    } else {
                        // Si es el último mensaje, usar el 'nextRemitente' obtenido del chat actual
                        mensajeSiguienteRemitente = nextRemitente;
                    }

                    // Determinar si es un nuevo hilo comparando con el remitente del mensaje siguiente
                    const esNuevoHilo = mensaje.remitente !== mensajeSiguienteRemitente;

                    //console.log(`[[manejarScrollColab]] Índice: ${i} mensaje.mensaje: ${mensaje.mensaje} mensaje.remitente: ${mensaje.remitente} mensajeSiguienteRemitente: ${mensajeSiguienteRemitente} esNuevoHilo: ${esNuevoHilo}`);

                    const userInfo = userInfos.get(mensaje.remitente);

                    // Insertar el mensaje en el chat al principio
                    agregarMensajeAlChat(
                        mensaje.mensaje,
                        mensaje.clase,
                        mensaje.fecha,
                        listaMensajes,
                        fechaAnterior,
                        true, // insertAtTop = true
                        mensaje.adjunto,
                        null,
                        mensaje.remitente,
                        esNuevoHilo, // Indica si es el primer mensaje de un nuevo hilo
                        userInfo,
                        'Colab'
                    );

                    // Actualizamos la fecha anterior para el siguiente mensaje
                    fechaAnterior = new Date(mensaje.fecha);
                }

                // Ajustar manualmente la posición del scroll tras insertar los mensajes
                listaMensajes.scrollTop = listaMensajes.scrollHeight - scrollPosAntesDeInsertar;
            }
        });
    }

    async function mostrarMensajes(mensajes, contenedor = null, tipoMensaje = null) {
        const listaMensajes = contenedor ? contenedor.querySelector('.listaMensajes') : document.querySelector('.listaMensajes');

        if (!listaMensajes) {
            //console.error('No se encontró el contenedor de mensajes.');
            return;
        }
        listaMensajes.innerHTML = '';
        if (mensajes.length === 0) {
            const mensajeVacio = document.createElement('p');
            mensajeVacio.textContent = 'Aún no hay mensajes';
            mensajeVacio.classList.add('mensajeVacio');
            listaMensajes.appendChild(mensajeVacio);
            return;
        }

        const uniqueRemitentes = [...new Set(mensajes.map(mensaje => mensaje.remitente))];
        const userInfos = await obtenerInfoUsuarios(uniqueRemitentes);

        let fechaAnterior = null;
        let prevEmisor = null;
        mensajes.forEach(mensaje => {
            const isFirstMessageOfThread = mensaje.remitente !== prevEmisor;
            prevEmisor = mensaje.remitente;

            // Aquí añadimos el //console.log con un resumen de los datos
            const userInfo = userInfos.get(mensaje.remitente);
            /* //console.log('Enviando a agregarMensajeAlChat:', {
                mensaje: mensaje.mensaje,
                clase: mensaje.clase,
                fecha: mensaje.fecha,
                fechaAnterior,
                adjunto: mensaje.adjunto,
                remitente: mensaje.remitente,
                isFirstMessageOfThread,
                userInfo,
                tipoMensaje,
                leido: mensaje.leido
            }); */

            agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, false, mensaje.adjunto, null, mensaje.remitente, isFirstMessageOfThread, userInfo, tipoMensaje, mensaje.leido);

            fechaAnterior = new Date(mensaje.fecha);
        });
    }
    async function manejarMensajeWebSocket(data) {
        //console.log('🚀 Función manejarMensajeWebSocket llamada con data:', data);

        try {
            const parsedData = JSON.parse(data);

            // Resumen de los datos recibidos
            const resumenDatos = {
                emisor: parsedData.emisor,
                receptor: parsedData.receptor,
                mensaje: parsedData.mensaje,
                conversacion_id: parsedData.conversacion_id,
                adjunto: parsedData.adjunto || null,
                temp_id: parsedData.temp_id || null
            };
            //console.log('📝 Resumen de datos recibidos:', resumenDatos);

            const msgEmisor = String(parsedData.emisor);
            const msgReceptor = parsedData.receptor;
            const msgMensaje = parsedData.mensaje;
            const msgConversacionId = parsedData.conversacion_id;
            const msgAdjunto = parsedData.adjunto || null;
            const tempId = parsedData.temp_id || null;
            const leido = 0;
            const currentUserId = String(emisor);
            let receptorIds;

            // Depuración de receptorIds
            try {
                receptorIds = JSON.parse(msgReceptor);
                //console.log('🔍 receptorIds después de JSON.parse:', receptorIds);

                if (!Array.isArray(receptorIds)) {
                    //console.warn('⚠️ receptorIds no es un array. Convertiéndolo a array.');
                    receptorIds = [String(receptorIds)];
                } else {
                    receptorIds = receptorIds.map(id => String(id));
                    //console.log('🔄 receptorIds mapeados a strings:', receptorIds);
                }
            } catch (e) {
                //console.error('❌ Error al parsear msgReceptor. Asignando receptorIds como array con msgReceptor:', e);
                receptorIds = [String(msgReceptor)];
            }

            //console.log('✅ receptorIds final:', receptorIds);

            // Verificar si el mensaje es para el usuario actual o si fue enviado por el usuario actual
            if (receptorIds.includes(currentUserId) || msgEmisor === currentUserId) {
                let chatWindow;

                // Determinar el tipo de mensaje (grupal o individual)
                let tipoMensaje = null;
                if (msgConversacionId && msgConversacionId !== 'null') {
                    // Mensaje grupal o con conversacion_id
                    chatWindow = document.querySelector(`.bloqueChatColab[data-conversacion-id="${msgConversacionId}"]`);
                    tipoMensaje = 'Colab';
                } else {
                    // Mensaje individual
                    const contactoId = msgEmisor === currentUserId ? msgReceptor : msgEmisor;
                    chatWindow = document.querySelector(`.bloqueChat[data-user-id="${contactoId}"]`);
                    tipoMensaje = 'Individual';

                    // Actualizar lista de conversaciones
                    actualizarListaConversaciones(msgConversacionId || contactoId, msgMensaje, msgConversacionId);
                    //console.log(`A1: Lista de conversaciones actualizada para ${contactoId}: ${msgMensaje}`);
                }

                if (chatWindow) {
                    const listaMensajes = chatWindow.querySelector('.listaMensajes');
                    const fechaActual = new Date();

                    // Obtener el último mensaje para determinar isFirstMessageOfThread
                    const mensajes = listaMensajes.querySelectorAll('.mensajeText');
                    let prevEmisor = null;

                    if (mensajes.length > 0) {
                        const ultimoMensaje = mensajes[mensajes.length - 1];
                        prevEmisor = ultimoMensaje.getAttribute('data-emisor');
                    }

                    const isFirstMessageOfThread = msgEmisor !== prevEmisor;

                    // Obtener la información del usuario si es necesario
                    let userInfo = null;
                    if (isFirstMessageOfThread && msgEmisor !== currentUserId) {
                        const userInfos = await obtenerInfoUsuarios([msgEmisor]);
                        userInfo = userInfos.get(msgEmisor);
                    }

                    // Determinar la clase del mensaje
                    let claseMensaje;
                    if (msgEmisor === currentUserId) {
                        claseMensaje = 'mensajeDerecha';
                    } else {
                        claseMensaje = 'mensajeIzquierda';
                    }

                    // Añadir el mensaje al chat
                    agregarMensajeAlChat(msgMensaje, claseMensaje, fechaActual, listaMensajes, null, false, msgAdjunto, tempId, msgEmisor, isFirstMessageOfThread, userInfo, tipoMensaje, leido);
                    //console.log(`A2: Mensaje agregado: ${msgMensaje} por ${msgEmisor}`);

                    // Actualizar lista de conversaciones
                    if (tipoMensaje === 'Individual') {
                        const contactoId = msgEmisor === currentUserId ? msgReceptor : msgEmisor;
                        actualizarListaConversaciones(msgConversacionId || contactoId, msgMensaje);
                        //console.log(`A3: Lista de conversaciones actualizada para ${contactoId}: ${msgMensaje}`);
                    } else {
                        //actualizarListaConversaciones(msgConversacionId, msgMensaje);
                    }
                }
            }
        } catch (error) {
            console.error('Error al manejar el mensaje de WebSocket:', error);
        }
    }

    function agregarMensajeAlChat(mensajeTexto, clase, fecha, listaMensajes = document.querySelector('.listaMensajes'), fechaAnterior = null, insertAtTop = false, adjunto = null, temp_id = null, msgEmisor = null, isFirstMessageOfThread = false, userInfo = null, tipoMensaje = null, mensajeLeido = false) {
        const fechaMensaje = new Date(fecha);
        fechaAnterior = fechaAnterior || obtenerFechaAnterior(listaMensajes, insertAtTop);

        manejarFecha(fechaMensaje, fechaAnterior, listaMensajes, insertAtTop);

        const esUsuarioActual = msgEmisor === emisor;
        const esColabPrimerMensaje = tipoMensaje === 'Colab' && isFirstMessageOfThread && userInfo && !esUsuarioActual;

        const messageflex = crearElemento('div', 'messageflex');
        const messageContainer = crearElemento('div', 'messageContainer');

        const mensajeElem = crearElemento('div', ['mensajeText', clase], {
            'data-fecha': fechaMensaje.toISOString(),
            'data-emisor': msgEmisor || undefined,
            'data-temp-id': temp_id || undefined,
            'data-leido': mensajeLeido || undefined
        });

        if (temp_id) mensajeElem.classList.add('mensajePendiente');

        if (esColabPrimerMensaje) {
            // Añadimos una clase identificadora al messageflex
            messageflex.classList.add('firstMessageOfThread');

            const userNameElem = crearElemento('span', 'userName', {textContent: userInfo.nombreUsuario});
            const avatarImg = crearElemento('img', 'avatarImage', {
                src: userInfo.imagenPerfil,
                alt: userInfo.nombreUsuario
            });
            messageflex.appendChild(userNameElem);
            messageContainer.appendChild(avatarImg);
        } else if (tipoMensaje === 'Colab') {
            // Si es un mensaje de tipo Colab pero no es el primer mensaje, agregamos un div vacío
            const spaceDiv = crearElemento('div', 'spaceDivMs');
            messageContainer.appendChild(spaceDiv);
        }

        const messageTextElem = crearElemento('p', null, {textContent: mensajeTexto});
        mensajeElem.appendChild(messageTextElem);
        manejarAdjunto(adjunto, mensajeElem);

        messageContainer.appendChild(mensajeElem);
        messageflex.appendChild(messageContainer);

        if (insertAtTop) {
            listaMensajes.insertBefore(messageflex, listaMensajes.firstChild);
        } else {
            listaMensajes.appendChild(messageflex);
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
        }
    }

    function msColabSetup(chatColabElement) {
        // Maneja los clics dentro de este chatColabElement específico
        chatColabElement.addEventListener('click', event => {
            if (event.target.matches('.enviarMensajeColab')) {
                enviarMensajeColab(event.target, chatColabElement);
            }
        });

        // Selecciona el input de mensaje dentro de este chatColabElement
        const mensajeInput = chatColabElement.querySelector('.mensajeContenidoColab');
        if (mensajeInput) {
            mensajeInput.addEventListener('keydown', event => {
                if (event.key === 'Enter' && !event.altKey) {
                    event.preventDefault();
                    const enviarButton = chatColabElement.querySelector('.enviarMensajeColab');
                    if (enviarButton) {
                        enviarMensajeColab(enviarButton, chatColabElement);
                    }
                }
            });
        }

        function enviarMensajeColab(button, chatColabElement) {
            if (subidaChatProgreso) {
                return alert('Por favor espera a que se complete la subida del archivo.');
            }

            const bloqueChat = button.closest('.bloqueChatColab');
            if (!bloqueChat) {
                return alert('No se encontró el bloque de chat.');
            }

            const mensajeInput = bloqueChat.querySelector('.mensajeContenidoColab');
            const mensaje = mensajeInput.value.trim();

            if (!mensaje) {
                return alert('Por favor, ingresa un mensaje.');
            }

            ocultarPreviews(chatColabElement);

            const conversacion_id = button.getAttribute('data-conversacion-id');
            const participantesData = bloqueChat.getAttribute('data-participantes');
            let participantes = [];
            try {
                participantes = JSON.parse(participantesData);
            } catch (e) {
                console.error('Error al parsear participantes:', e);
                return alert('Error al procesar los participantes de la conversación.');
            }

            const metadata = 'colab';
            const listaMensajes = bloqueChat.querySelector('.listaMensajes');

            let adjunto = null;
            if (window.archivoChatId || window.archivoChatUrl) {
                adjunto = {archivoChatId: window.archivoChatId, archivoChatUrl: window.archivoChatUrl};
                window.archivoChatId = null;
                window.archivoChatUrl = null;
            }

            enviarMensajeWs(participantes, mensaje, adjunto, metadata, conversacion_id, listaMensajes);

            mensajeInput.value = '';
        }
    }

    function manejarFecha(fechaMensaje, fechaAnterior, listaMensajes, insertAtTop) {
        if (!listaMensajes || !(listaMensajes instanceof Element)) {
            //console.error('listaMensajes no es un elemento DOM válido');
            return;
        }

        if (!fechaAnterior || Math.abs(fechaMensaje - fechaAnterior) >= 3 * 60 * 1000) {
            // Convertir la fecha al formato deseado (por ejemplo, solo la fecha sin hora)
            const fechaFormateada = formatearTiempoRelativo(fechaMensaje);

            // Verificar si ya existe un separador con la misma fecha
            let existingSeparator = null;
            if (insertAtTop) {
                existingSeparator = listaMensajes.querySelector(`.fechaSeparador[data-fecha="${fechaMensaje.toISOString()}"]`);
            } else {
                // Buscar sólo entre los últimos elementos para mejorar el rendimiento
                const lastElements = Array.from(listaMensajes.children).slice(-5);
                for (const elem of lastElements) {
                    if (elem.classList.contains('fechaSeparador') && elem.getAttribute('data-fecha') === fechaMensaje.toISOString()) {
                        existingSeparator = elem;
                        break;
                    }
                }
            }

            if (!existingSeparator) {
                const liFecha = document.createElement('div'); // Cambiar a 'div' si estás usando 'div' para mensajes
                liFecha.textContent = fechaFormateada;
                liFecha.classList.add('fechaSeparador');
                liFecha.setAttribute('data-fecha', fechaMensaje.toISOString());

                if (insertAtTop) {
                    if (listaMensajes.firstChild) {
                        listaMensajes.insertBefore(liFecha, listaMensajes.firstChild);
                    } else {
                        listaMensajes.appendChild(liFecha);
                    }
                } else {
                    listaMensajes.appendChild(liFecha);
                }
            }
        }
    }

    function obtenerFechaAnterior(listaMensajes, insertAtTop) {
        let lastElement = null;
        const children = Array.from(listaMensajes.children || []);
        const searchOrder = insertAtTop ? 1 : -1;
        const startIndex = insertAtTop ? 0 : children.length - 1;

        for (let i = startIndex; insertAtTop ? i < children.length : i >= 0; i += searchOrder) {
            const child = children[i];
            // Buscar el elemento que contiene el mensaje
            const mensajeElem = child.querySelector('.mensajeText.mensajeDerecha, .mensajeText.mensajeIzquierda');
            if (mensajeElem) {
                lastElement = mensajeElem;
                break;
            }
        }

        return lastElement ? new Date(lastElement.getAttribute('data-fecha')) : null;
    }

    function crearElemento(tag, clase, atributos = {}) {
        const elem = document.createElement(tag);
        if (Array.isArray(clase)) clase.forEach(c => elem.classList.add(c));
        else if (clase) elem.classList.add(clase);

        for (const [key, value] of Object.entries(atributos)) {
            if (key === 'textContent') {
                elem.textContent = value;
            } else if (value !== undefined) {
                elem.setAttribute(key, value);
            }
        }
        return elem;
    }
    init();
}
