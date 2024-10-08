function formatearTiempoRelativo(fecha) {
    const ahora = new Date();
    const diferenciaSegundos = Math.floor((ahora - new Date(fecha)) / 1000);

    const minutos = Math.floor(diferenciaSegundos / 60);
    const horas = Math.floor(minutos / 60);
    const dias = Math.floor(horas / 24);
    const semanas = Math.floor(dias / 7);

    if (semanas > 0) {
        return semanas === 1 ? 'hace 1 semana' : `hace ${semanas} semanas`;
    } else if (dias > 0) {
        return dias === 1 ? 'hace 1 día' : `hace ${dias} días`;
    } else if (horas > 0) {
        return horas === 1 ? 'hace 1 hora' : `hace ${horas} horas`;
    } else if (minutos > 0) {
        return minutos === 1 ? 'hace 1 minuto' : `hace ${minutos} minutos`;
    } else {
        return 'hace unos segundos';
    }
}

function galle() {
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
        msSetup();
        actualizarConexionEmisor();
        iniciarChat();
        clickMensaje();
        subidaArchivosChat();
        chatColab();
        msColabSetup();
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
                    console.error('No se pudo actualizar la conexión del emisor:', response.message);
                }
            })
            .catch(error => {
                console.error('Error al actualizar la conexión del emisor:', error);
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
            console.error('Error al verificar el estado de conexión del receptor:', error);
        }
    }

    function verificarConexionReceptor(receptorId) {
        return enviarAjax('verificarConexionReceptor', {receptor_id: receptorId})
            .then(response => {
                if (response.success) {
                    return response.data;
                } else {
                    console.error('Error al verificar la conexión del receptor:', response.message);
                    return null;
                }
            })
            .catch(error => {
                console.error('Error en la solicitud para verificar la conexión del receptor:', error);
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
                        console.error('Error al obtener información del usuario:', data.message);
                        userInfos.set(userId, {imagenPerfil: 'default.jpg', nombreUsuario: 'Usuario Desconocido'});
                    }
                } catch (error) {
                    console.error('Error al obtener información del usuario:', error);
                    userInfos.set(userId, {imagenPerfil: 'default.jpg', nombreUsuario: 'Usuario Desconocido'});
                }
            })
        );

        return userInfos;
    }

    async function chatColab() {
        const chatColabElements = document.querySelectorAll('.bloqueChatColab');

        chatColabElements.forEach(async chatColabElement => {
            const postId = chatColabElement.dataset.postId;
            if (!postId) {
                console.error('El elemento no tiene data-post-id.');
                return;
            }
            currentPage = 1;

            try {
                const data = await enviarAjax('obtenerChatColab', {colab_id: postId, page: currentPage});

                if (data?.success) {
                    console.log('Mensaje completo:', data);
                    const tipoMensaje = 'Colab';
                    mostrarMensajes(data.data.mensajes, chatColabElement, tipoMensaje);
                    manejarScrollColab(data.data.conversacion, chatColabElement);

                    const listaMensajes = chatColabElement.querySelector('.listaMensajes');
                    if (listaMensajes) {
                        listaMensajes.scrollTop = listaMensajes.scrollHeight;
                    }
                } else {
                    console.error('Error al obtener la conversación colab:', data.message);
                }
            } catch (error) {
                console.error('Error al obtener la conversación colab:', error);
            }
        });
    }

    async function abrirConversacion({conversacion, receptor, imagenPerfil, nombreUsuario}) {
        try {
            let data = {success: true, data: {mensajes: [], conversacion: null}};
            currentPage = 1;
            if (conversacion) {
                data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});
            } else if (receptor) {
                data = await enviarAjax('obtenerChat', {receptor, page: currentPage});
            }
            if (data?.success) {
                mostrarMensajes(data.data.mensajes);
                const bloqueChat = document.querySelector('.bloqueChat');
                bloqueChat.setAttribute('data-user-id', receptor);
                bloqueChat.querySelector('.imagenMensaje img').src = imagenPerfil;
                bloqueChat.querySelector('.nombreConversacion p').textContent = nombreUsuario;
                bloqueChat.style.display = 'block';
                manejarScroll(data.data.conversacion);
                const listaMensajes = document.querySelector('.listaMensajes');
                listaMensajes.scrollTop = listaMensajes.scrollHeight;
                await actualizarEstadoConexion(receptor, bloqueChat);
                setInterval(() => actualizarEstadoConexion(receptor, bloqueChat), 30000);
            } else {
                alert(data.message || 'Error desconocido al obtener los mensajes.');
            }
        } catch (error) {
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

                // Resetear variables globales del chat
                archivoChatId = null;
                archivoChatUrl = null;

                // Borrar cualquier texto en el área de texto
                const textareaMensaje = document.querySelector('.mensajeContenido');
                textareaMensaje.value = '';
            });
        } catch (error) {
            alert('Ha ocurrido un error al intentar cerrar el chat.');
        }
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
            console.error('Error al minimizar el chat:', error);
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
            console.error('Error al maximizar el chat:', error);
            alert('Ha ocurrido un error al intentar maximizar el chat.');
        }
    }

    maximizarChat();
    cerrarChat();
    minimizarChat();

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
                        console.error('Error del servidor:', data.message);
                        alert(data.message || 'Error al obtener la información del usuario.');
                        return;
                    }
                } catch (error) {
                    console.error('Error de conexión:', error);
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

        const botonesMensaje = document.querySelectorAll('.mensajeBoton');
        if (botonesMensaje.length > 0) {
            botonesMensaje.forEach(item => manejarClickEnConversacion(item));
        }
    }

    /*
     *   FUNCIONES RELACIONADAS CON LA CARGAN LOS MENSAJES DE UNA CONVERSACION
     */

    async function mostrarMensajes(mensajes, contenedor = null, tipoMensaje = null) {
        const listaMensajes = contenedor ? contenedor.querySelector('.listaMensajes') : document.querySelector('.listaMensajes');

        if (!listaMensajes) {
            console.error('No se encontró el contenedor de mensajes.');
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

            const userInfo = userInfos.get(mensaje.remitente);
            agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, false, mensaje.adjunto, null, mensaje.remitente, isFirstMessageOfThread, userInfo, tipoMensaje);
            fechaAnterior = new Date(mensaje.fecha);
        });
    }

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
                            <a href="${adjunto.archivoChatUrl}" target="_blank">Descargar archivo</a>
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
                            <a href="${adjunto.archivoChatUrl}" target="_blank">Descargar archivo</a>
                        </div>`;
                }
            }

            li.appendChild(adjuntoContainer);
        }
    }

    function manejarFecha(fechaMensaje, fechaAnterior, listaMensajes, insertAtTop) {
        // Comprobar si listaMensajes es un elemento DOM válido
        if (!listaMensajes || !(listaMensajes instanceof Element)) {
            console.error('listaMensajes no es un elemento DOM válido');
            return;
        }

        // Verificar si la fecha del nuevo mensaje es mayor a 3 minutos respecto a la fecha anterior
        if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
            // Crear un elemento <li> en lugar de un <div>
            const liFecha = document.createElement('li');
            liFecha.textContent = formatearTiempoRelativo(fechaMensaje);
            liFecha.classList.add('fechaSeparador');
            liFecha.setAttribute('data-fecha', fechaMensaje.toISOString());

            try {
                if (insertAtTop) {
                    // Insertar en la parte superior de la lista, si es necesario
                    if (listaMensajes.firstChild) {
                        listaMensajes.insertBefore(liFecha, listaMensajes.firstChild);
                    } else {
                        listaMensajes.appendChild(liFecha);
                    }
                } else {
                    // Insertar al final de la lista
                    listaMensajes.appendChild(liFecha);
                }
                //console.log('Fecha insertada en listaMensajes:', liFecha);
            } catch (error) {
                console.error('Error al insertar la fecha en listaMensajes:', error);
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
            if (child.tagName.toLowerCase() === 'li' && (child.classList.contains('mensajeDerecha') || child.classList.contains('mensajeIzquierda'))) {
                lastElement = child;
                break;
            }
        }

        return lastElement ? new Date(lastElement.getAttribute('data-fecha')) : null;
    }

    function agregarMensajeAlChat(mensajeTexto, clase, fecha, listaMensajes = document.querySelector('.listaMensajes'), fechaAnterior = null, insertAtTop = false, adjunto = null, temp_id = null, msgEmisor = null, isFirstMessageOfThread = false, userInfo = null, tipoMensaje = null) {
        const fechaMensaje = new Date(fecha);
        fechaAnterior = fechaAnterior || obtenerFechaAnterior(listaMensajes, insertAtTop);

        manejarFecha(fechaMensaje, fechaAnterior, listaMensajes, insertAtTop);

        const esUsuarioActual = msgEmisor === emisor;
        const esColabPrimerMensaje = tipoMensaje === 'Colab' && isFirstMessageOfThread && userInfo && !esUsuarioActual;

        const messageBlock = crearElemento('div', 'messageBlock');
        const messageContainer = crearElemento('div', 'messageContainer');

        const mensajeElem = crearElemento('div', ['mensajeText', clase], {
            'data-fecha': fechaMensaje.toISOString(),
            'data-emisor': msgEmisor || undefined,
            'data-temp-id': temp_id || undefined
        });

        if (temp_id) mensajeElem.classList.add('mensajePendiente');

        if (esColabPrimerMensaje) {
            // Añadimos una clase identificadora al messageBlock
            messageBlock.classList.add('firstMessageOfThread');

            const userNameElem = crearElemento('span', 'userName', {textContent: userInfo.nombreUsuario});
            const avatarImg = crearElemento('img', 'avatarImage', {
                src: userInfo.imagenPerfil,
                alt: userInfo.nombreUsuario
            });
            messageBlock.appendChild(userNameElem);
            messageContainer.appendChild(avatarImg);
        }

        const messageTextElem = crearElemento('p', null, {textContent: mensajeTexto});
        mensajeElem.appendChild(messageTextElem);
        manejarAdjunto(adjunto, mensajeElem);

        messageContainer.appendChild(mensajeElem);
        messageBlock.appendChild(messageContainer);

        if (insertAtTop) {
            listaMensajes.insertBefore(messageBlock, listaMensajes.firstChild);
        } else {
            listaMensajes.appendChild(messageBlock);
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
        }
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

    function actualizarListaConversaciones(usuarioId, ultimoMensaje) {
        //console.log('actualizarListaConversaciones: Actualizando la lista de conversaciones.');

        const listaMensajes = document.querySelectorAll('.mensajes .mensaje');
        let conversacionActualizada = false;

        listaMensajes.forEach(mensaje => {
            const receptorId = mensaje.getAttribute('data-receptor');

            if (receptorId == usuarioId) {
                //console.log(`actualizarListaConversaciones: Actualizando último mensaje para usuario ${usuarioId}.`);
                const vistaPrevia = mensaje.querySelector('.vistaPrevia p');
                if (vistaPrevia) {
                    vistaPrevia.textContent = ultimoMensaje;
                }

                const tiempoMensajeDiv = mensaje.querySelector('.tiempoMensaje');
                if (tiempoMensajeDiv) {
                    const fechaActual = new Date();
                    tiempoMensajeDiv.setAttribute('data-fecha', fechaActual.toISOString());
                    const tiempoMensajeSpan = tiempoMensajeDiv.querySelector('span');
                    if (tiempoMensajeSpan) {
                        tiempoMensajeSpan.textContent = formatearTiempoRelativo(fechaActual);
                    }
                }
                conversacionActualizada = true;
            }
        });

        if (!conversacionActualizada) {
            //console.log('actualizarListaConversaciones: No se encontró la conversación, programando reinicio de chats.');
            setTimeout(() => {
                reiniciarChats();
                //console.log('actualizarListaConversaciones: Chats reiniciados.');
            }, 1000);
        }
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
                    }
                } else {
                    console.error('Error al reiniciar los chats:', response);
                }
            })
            .catch(error => {
                console.error('Error al reiniciar los chats:', error);
            });
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
                console.error('No se pudo obtener el token:', response.message);
                return null;
            }
        } catch (error) {
            console.error('Error al obtener el token:', error);
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
            console.error('Error en WebSocket:', error);
        };
        ws.onmessage = ({data}) => {
            const message = JSON.parse(data);
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

    function manejarMensajeWebSocket(data) {
        //console.log('manejarMensajeWebSocket: Recibido nuevo mensaje del WebSocket:', data);

        try {
            const parsedData = JSON.parse(data);
            //console.log('manejarMensajeWebSocket: Mensaje parseado correctamente:', parsedData);

            const msgEmisor = String(parsedData.emisor);
            const msgReceptor = parsedData.receptor;
            const msgMensaje = parsedData.mensaje;
            const msgConversacionId = parsedData.conversacion_id;
            const msgAdjunto = parsedData.adjunto || null;
            const tempId = parsedData.temp_id || null;

            //console.log('manejarMensajeWebSocket:', {msgEmisor, msgReceptor, msgMensaje, msgConversacionId});

            // Obtener el ID del usuario actual
            const currentUserId = String(emisor);

            let receptorIds;
            try {
                // Intentamos parsear msgReceptor
                receptorIds = JSON.parse(msgReceptor);

                // Si el resultado no es un array, lo convertimos en uno
                if (!Array.isArray(receptorIds)) {
                    receptorIds = [String(receptorIds)];
                } else {
                    // Convertimos todos los elementos a strings
                    receptorIds = receptorIds.map(id => String(id));
                }
            } catch (e) {
                // Si falla el parseo, asumimos que es un único ID y lo colocamos en un array
                receptorIds = [String(msgReceptor)];
            }

            // Comprobar si el mensaje es para nosotros
            if (receptorIds.includes(currentUserId)) {
                //console.log('manejarMensajeWebSocket: El mensaje es para nosotros.');
                //console.log('Datos del mensaje:', {
                //    msgConversacionId,
                //    msgEmisor,
                //    msgReceptor,
                //    currentUserId
                //});

                let chatWindow;

                if (msgConversacionId && msgConversacionId !== 'null') {
                    // Mensaje grupal o con conversacion_id
                    //console.log(`Buscando ventana de chat con data-conversacion-id="${msgConversacionId}"`);
                    chatWindow = document.querySelector(`.bloqueChatColab[data-conversacion-id="${msgConversacionId}"]`);
                } else {
                    // Mensaje uno a uno, usar el ID del emisor o receptor para encontrar la ventana de chat
                    const contactoId = msgEmisor === currentUserId ? msgReceptor : msgEmisor;
                    //console.log(`Buscando ventana de chat con data-user-id="${contactoId}"`);
                    chatWindow = document.querySelector(`.bloqueChat[data-user-id="${contactoId}"]`);

                    //console.log('Actualizando lista de conversaciones.');
                    actualizarListaConversaciones(msgConversacionId || contactoId, msgMensaje);
                }

                if (chatWindow) {
                    //console.log('Ventana de chat encontrada:', chatWindow);

                    const listaMensajes = chatWindow.querySelector('.listaMensajes');
                    const fechaActual = new Date();

                    if (msgEmisor === currentUserId) {
                        // El mensaje fue enviado por nosotros, lo añadimos a la derecha
                        //console.log('El mensaje fue enviado por nosotros. Añadiendo a la derecha.');
                        agregarMensajeAlChat(msgMensaje, 'mensajeDerecha', fechaActual, listaMensajes, null, false, msgAdjunto, tempId);
                    } else {
                        // El mensaje fue enviado por otro participante, lo añadimos a la izquierda
                        //console.log('El mensaje fue enviado por otro participante. Añadiendo a la izquierda.');
                        agregarMensajeAlChat(msgMensaje, 'mensajeIzquierda', fechaActual, listaMensajes, null, false, msgAdjunto, tempId, msgEmisor);
                    }

                    // Actualizar la lista de conversaciones
                    //console.log('Actualizando lista de conversaciones.');
                    actualizarListaConversaciones(msgConversacionId || contactoId, msgMensaje);
                } else {
                    //console.log(`manejarMensajeWebSocket: No se encontró la ventana de chat para conversacion_id: ${msgConversacionId} o usuario: ${msgEmisor}`);
                }
            } else if (msgEmisor === currentUserId) {
                // El mensaje fue enviado por nosotros pero quizás a otra conversación
                //console.log('manejarMensajeWebSocket: Es una confirmación de recepción de nuestro mensaje.');

                let chatWindow;

                if (msgConversacionId && msgConversacionId !== 'null') {
                    chatWindow = document.querySelector(`.bloqueChatColab[data-conversacion-id="${msgConversacionId}"]`);
                } else {
                    // Mensaje uno a uno
                    chatWindow = document.querySelector(`.bloqueChat[data-user-id="${msgReceptor}"]`);
                }

                if (chatWindow) {
                    const listaMensajes = chatWindow.querySelector('.listaMensajes');
                    const fechaActual = new Date();

                    // Añadir el mensaje a la derecha
                    agregarMensajeAlChat(msgMensaje, 'mensajeDerecha', fechaActual, listaMensajes, null, false, msgAdjunto, tempId);

                    // Actualizar la lista de conversaciones
                    actualizarListaConversaciones(msgConversacionId || msgReceptor, msgMensaje);
                } else {
                    //console.log(`manejarMensajeWebSocket: No se encontró la ventana de chat para conversacion_id: ${msgConversacionId} o usuario: ${msgReceptor}`);
                }
            } else {
                //console.log('manejarMensajeWebSocket: El mensaje no es para nosotros.');
            }
        } catch (error) {
            console.error('Error al manejar el mensaje de WebSocket:', error);
        }
    }

    function enviarMensajeWs(receptor, mensaje, adjunto = null, metadata = null, conversacion_id = null, listaMensajes = null) {
        const temp_id = Date.now();

        const receptorFinal = typeof receptor === 'string' ? receptor : typeof receptor === 'object' ? JSON.stringify(receptor) : null;

        if (!receptorFinal) {
            return console.error('Formato de receptor no válido.');
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
            agregarMensajeAlChat(mensaje, 'mensajeDerecha', new Date(), listaMensajes, null, false, adjunto, temp_id);
        } else {
            console.error('enviarMensajeWs: WebSocket no conectado, mensaje no enviado.');
            alert('No se puede enviar el mensaje, por favor, reinicia la página.');
        }
    }

    function confirmarMensaje(message) {
        const conversacionId = message.original_message.conversacion_id;
        let listaMensajes;

        if (conversacionId) {
            const bloqueChatColab = document.querySelector(`.bloqueChatColab[data-conversacion-id="${conversacionId}"]`);
            if (!bloqueChatColab) {
                return console.warn(`No se encontró el bloqueChatColab con conversacion_id ${conversacionId}.`);
            }
            listaMensajes = bloqueChatColab.querySelector('.listaMensajes');
        } else {
            listaMensajes = document.querySelector('.listaMensajes');
        }

        const mensajeElemento = listaMensajes.querySelector(`[data-temp-id="${message.original_message.temp_id}"]`);

        if (mensajeElemento) {
            mensajeElemento.classList.replace('mensajePendiente', 'mensajeEnviado');
        } else {
            console.warn(`No se encontró el mensaje con ID temporal ${message.original_message.temp_id}.`);
        }
    }

    function manejarError(message) {
        const listaMensajes = document.querySelector('.listaMensajes');
        const mensajeElemento = listaMensajes.querySelector(`[data-temp-id="${message.original_message.temp_id}"]`);

        if (mensajeElemento) {
            console.error(`manejarError: Error en el mensaje con ID temporal ${message.original_message.temp_id}.`);
            mensajeElemento.classList.add('mensajeError');
        } else {
            console.warn(`manejarError: No se encontró el elemento del mensaje con ID temporal ${message.original_message.temp_id}.`);
        }
    }

    // FUNCIONES PARA ENVIAR MENSAJE

    function msSetup() {
        document.addEventListener('click', event => {
            if (event.target.matches('.enviarMensaje')) {
                enviarMensaje();
            }
        });

        const mensajeInput = document.querySelector('.mensajeContenido');
        mensajeInput.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.altKey) {
                event.preventDefault();
                enviarMensaje();
            }
        });

        function enviarMensaje() {
            if (subidaChatProgreso) {
                return alert('Por favor espera a que se complete la subida del archivo.');
            }
            const mensaje = mensajeInput.value.trim();
            if (!mensaje) return;
            ocultarPreviews();
            let adjunto = archivoChatId || archivoChatUrl ? {archivoChatId, archivoChatUrl} : null;
            archivoChatId = archivoChatUrl = null;
            enviarMensajeWs(receptor, mensaje, adjunto);
            mensajeInput.value = '';
            actualizarListaConversaciones(receptor, `Tu: ${mensaje}`);
        }
    }

    function msColabSetup() {
        document.addEventListener('click', event => {
            if (event.target.matches('.enviarMensajeColab')) {
                enviarMensajeColab(event.target);
            }
        });

        const mensajeInput = document.querySelector('.mensajeContenidoColab');
        mensajeInput.addEventListener('keydown', event => {
            if (event.key === 'Enter' && !event.altKey) {
                event.preventDefault();
                enviarMensajeColab(document.querySelector('.enviarMensajeColab'));
            }
        });

        function enviarMensajeColab(button) {
            if (subidaChatProgreso) {
                return alert('Por favor espera a que se complete la subida del archivo.');
            }

            const bloqueChat = button.closest('.bloqueChatColab');
            const mensajeInput = bloqueChat.querySelector('.mensajeContenidoColab');
            const mensaje = mensajeInput.value.trim();

            if (!mensaje) {
                return alert('Por favor, ingresa un mensaje.');
            }

            const conversacion_id = button.getAttribute('data-conversacion-id');
            const participantes = JSON.parse(bloqueChat.getAttribute('data-participantes'));
            const metadata = 'colab';
            const listaMensajes = bloqueChat.querySelector('.listaMensajes');

            let adjunto = null;
            if (archivoChatId || archivoChatUrl) {
                adjunto = {archivoChatId, archivoChatUrl};
                archivoChatId = archivoChatUrl = null;
            }

            enviarMensajeWs(participantes, mensaje, adjunto, metadata, conversacion_id, listaMensajes);

            mensajeInput.value = '';
        }
    }

    // FUNCIONES PARA ADJUNTAR ARCHIVOS

    function ocultarPreviews() {
        const previewChatAudio = document.getElementById('previewChatAudio');
        const previewChatImagen = document.getElementById('previewChatImagen');
        const previewChatArchivo = document.getElementById('previewChatArchivo');
        const cancelUploadButton = document.getElementById('cancelUploadButton');
        previewChatAudio.style.display = 'none';
        previewChatImagen.style.display = 'none';
        previewChatArchivo.style.display = 'none';
        cancelUploadButton.style.display = 'none';
    }

    function subidaArchivosChat() {
        const ids = ['enviarAdjunto', 'bloqueChat', 'previewChatAudio', 'previewChatArchivo', 'previewChatImagen', 'cancelUploadButton'];
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

        const {enviarAdjunto, bloqueChat, previewChatArchivo, previewChatAudio, previewChatImagen, cancelUploadButton} = elements;

        cancelUploadButton.addEventListener('click', () => {
            subidaChatProgreso = false;
            archivoChatId = null;
            archivoChatUrl = null;
            ocultarPreviews();
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
            ocultarPreviews(); // Ocultar otros previews

            try {
                //console.log('Cargando archivo de audio...');
                previewChatAudio.style.display = 'block';
                cancelUploadButton.style.display = 'block';
                const progressBarId = waveAudio(file);
                //console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                //console.log('Audio cargado con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;
                subidaChatProgreso = false;
            } catch (error) {
                console.error('Error al cargar el audio:', error);
                subidaChatProgreso = false;
            }
        };

        const subidaChatImagen = async file => {
            //console.log('Iniciando subida de imagen:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            ocultarPreviews(); // Ocultar otros previews
            updateChatPreviewImagen(file);

            try {
                //console.log('Cargando archivo de imagen...');
                previewChatImagen.style.display = 'block';
                cancelUploadButton.style.display = 'block';
                const progressBarId = `progress-${Date.now()}`;
                //console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                //console.log('Imagen cargada con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;

                subidaChatProgreso = false;
            } catch (error) {
                console.error('Error al cargar la imagen:', error);
                subidaChatProgreso = false;
            }
        };

        const subidaChatArchivo = async file => {
            //console.log('Iniciando subida de archivo:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            cancelUploadButton.style.display = 'block';
            ocultarPreviews(); // Ocultar otros previews
            previewChatArchivo.style.display = 'block';
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
                console.error('Error al cargar el archivo:', error);
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
                previewChatImagen.style.display = 'block';
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
        formData.append('action', 'file_upload');
        formData.append('file', file);
        formData.append('file_hash', await generateFileHash(file)); // Asumiendo que ya tienes esta función

        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', my_ajax_object.ajax_url, true);

            // Actualización de la barra de progreso
            xhr.upload.onprogress = e => {
                if (e.lengthComputable) {
                    const progressBar = document.getElementById(progressBarId);
                    const progressPercent = (e.loaded / e.total) * 100;
                    if (progressBar) progressBar.style.width = `${progressPercent}%`;
                }
            };

            // Manejo de la respuesta del servidor
            xhr.onload = () => {
                if (xhr.status === 200) {
                    try {
                        const result = JSON.parse(xhr.responseText);
                        if (result.success) {
                            resolve(result.data); // Devuelve la data en caso de éxito
                        } else {
                            reject(new Error('Error en la respuesta del servidor'));
                        }
                    } catch (error) {
                        reject(error); // Error al parsear la respuesta
                    }
                } else {
                    reject(new Error(`Error en la carga del archivo. Status: ${xhr.status}`));
                }
            };

            // Manejo de errores de conexión
            xhr.onerror = () => {
                reject(new Error('Error en la conexión con el servidor'));
            };

            // Enviar solicitud AJAX
            try {
                xhr.send(formData);
            } catch (error) {
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
                    return console.error('Error al obtener más mensajes.');
                }
                const mensajes = data.data.mensajes.reverse();
                let fechaAnterior = null;

                mensajes.forEach(mensaje => {
                    agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, true, mensaje.adjunto);
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
                    return console.error('Error al obtener más mensajes.');
                }
    
                // Antes de agregar los nuevos mensajes, limpiar la información de usuario de los mensajes que ya no son primeros
                const mensajesAnteriores = listaMensajes.querySelectorAll('.firstMessageOfThread');
                mensajesAnteriores.forEach(mensaje => {
                    mensaje.classList.remove('firstMessageOfThread');
    
                    const userNameElem = mensaje.querySelector('.userName');
                    const avatarImg = mensaje.querySelector('.avatarImage');
                    if (userNameElem) userNameElem.remove();
                    if (avatarImg) avatarImg.remove();
                });
    
                // Invertimos los mensajes para procesarlos del más antiguo al más nuevo
                let mensajes = data.data.mensajes.reverse();
                const remitentesUnicos = [...new Set(mensajes.map(m => m.remitente))];
                const userInfos = await obtenerInfoUsuarios(remitentesUnicos);
                
                let fechaAnterior = null,
                    prevEmisor = null;
    
                for (let i = 0; i < mensajes.length; i++) {
                    const mensaje = mensajes[i];
                    const esNuevoHilo = prevEmisor !== mensaje.remitente;
                    prevEmisor = mensaje.remitente;
    
                    const userInfo = userInfos.get(mensaje.remitente);
                    agregarMensajeAlChat(
                        mensaje.mensaje,
                        mensaje.clase,
                        mensaje.fecha,
                        listaMensajes,
                        fechaAnterior,
                        true,
                        mensaje.adjunto,
                        null,
                        mensaje.remitente,
                        esNuevoHilo,
                        userInfo,
                        'Colab'
                    );
    
                    fechaAnterior = new Date(mensaje.fecha);
                }
    
                // Después de agregar mensajes, ajustar el scroll si es necesario
                // No es necesario ajustar en este caso si insertamos al final
            }
        });
    }

    init();
}
