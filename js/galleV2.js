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
        setupEnviarMensajeHandler();
        actualizarConexionEmisor();
        iniciarChat();
        clickMensaje();
        subidaArchivosChat();
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
                bloqueChat.querySelector('.imagenMensaje img').src = imagenPerfil;
                bloqueChat.querySelector('.nombreConversacion p').textContent = nombreUsuario;
                bloqueChat.style.display = 'block';

                manejarScroll(data.data.conversacion);

                const listaMensajes = document.querySelector('.listaMensajes');
                listaMensajes.scrollTop = listaMensajes.scrollHeight;

                // Actualizar estado de conexión del receptor
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
    
            botonMinimizar.addEventListener('click', (event) => {
                event.stopPropagation(); // Evita que el evento se propague al contenedor padre
                bloqueChat.classList.add('minimizado');
                
                // Oculta los elementos internos
                const elementosAOcultar = bloqueChat.querySelectorAll('.listaMensajes, .previewsChat, .chatEnvio');
                elementosAOcultar.forEach(elem => elem.style.display = 'none');
    
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
    
            bloqueChat.addEventListener('click', (event) => {
                if (bloqueChat.classList.contains('minimizado')) {
                    bloqueChat.classList.remove('minimizado');
                    
                    // Muestra los elementos internos
                    const elementosAMostrar = bloqueChat.querySelectorAll('.listaMensajes, .previewsChat, .chatEnvio');
                    elementosAMostrar.forEach(elem => elem.style.display = '');
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

    async function manejarClickEnMensaje(item) {
        item.addEventListener('click', async () => {
            console.log('Evento click detectado.');
            let conversacion = item.getAttribute('data-conversacion');
            receptor = item.getAttribute('data-receptor');
            let imagenPerfil = item.querySelector('.imagenMensaje img')?.src || null;
            let nombreUsuario = item.querySelector('.nombreUsuario strong')?.textContent || null;

            if (!imagenPerfil || !nombreUsuario) {
                console.log('No se tienen los datos, realizando solicitud AJAX para obtener información del servidor.');
                try {
                    const data = await enviarAjax('infoUsuario', {receptor});
                    console.log('Respuesta del servidor:', data);

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
            mensajes.forEach(item => manejarClickEnMensaje(item));
        }

        const botonesMensaje = document.querySelectorAll('.mensajeBoton');
        if (botonesMensaje.length > 0) {
            botonesMensaje.forEach(item => manejarClickEnMensaje(item));
        }
    }

    /*
     *   MANEJO DE BOTON PARA MOSTRAR LISTA DE CHATS Y CONVERSACION
     */

    /* 


    */

    /*
     *   FUNCIONES RELACIONADAS CON LA CARGAN LOS MENSAJES DE UNA CONVERSACION
     */

    function mostrarMensajes(mensajes) {
        const listaMensajes = document.querySelector('.listaMensajes');
        listaMensajes.innerHTML = '';
        let fechaAnterior = null;

        mensajes.forEach(mensaje => {
            // Mostrar el mensaje de texto junto con el adjunto si lo hay
            agregarMensajeAlChat(
                mensaje.mensaje,
                mensaje.clase,
                mensaje.fecha,
                listaMensajes,
                fechaAnterior,
                false,
                mensaje.adjunto // Pasamos el adjunto
            );

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
            } catch (error) {
                console.error('Error al insertar la fecha en listaMensajes:', error);
            }
        }
    }

    function agregarMensajeAlChat(mensajeTexto, clase, fecha, listaMensajes = document.querySelector('.listaMensajes'), fechaAnterior = null, insertAtTop = false, adjunto = null) {
        // Verifica si listaMensajes es un nodo DOM válido
        if (!listaMensajes || !(listaMensajes instanceof Element)) {
            console.error('Error: listaMensajes no es un elemento DOM válido o no se encontró. Valor recibido:', listaMensajes);
            return;
        }
    
        const fechaMensaje = new Date(fecha);
    
        if (!fechaAnterior) {
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
    
            fechaAnterior = lastElement ? new Date(lastElement.getAttribute('data-fecha')) : null;
        }
    
        // Lógica para manejar la fecha
        manejarFecha(fechaMensaje, fechaAnterior, listaMensajes, insertAtTop);
    
        // Crear el nuevo mensaje
        const li = document.createElement('li');
        li.textContent = mensajeTexto;
        li.classList.add(clase);
        li.setAttribute('data-fecha', fechaMensaje.toISOString());
    
        // Lógica para manejar el adjunto
        manejarAdjunto(adjunto, li);
    
        // Insertar el mensaje en la posición correcta
        if (insertAtTop) {
            listaMensajes.insertBefore(li, listaMensajes.firstChild);
        } else {
            listaMensajes.appendChild(li);
        }
    
        // Si no se está insertando al inicio, desplázate hacia abajo
        if (!insertAtTop) {
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
        }
    }

    function manejarMensajeWebSocket(data) {
        try {
            const { emisor: msgEmisor, receptor: msgReceptor, mensaje: msgMensaje } = JSON.parse(data);
            const listaMensajes = document.querySelector('.listaMensajes');
            const fechaActual = new Date();
            
            // Asegúrate de que emisor y receptor estén definidos
            if (msgReceptor === emisor) {
                if (msgEmisor === receptor) {
                    // Agregar mensaje desde el receptor (mensaje a la izquierda)
                    agregarMensajeAlChat(msgMensaje, 'mensajeIzquierda', fechaActual, listaMensajes);
                }
                actualizarListaConversaciones(msgEmisor, msgMensaje);
            } else if (msgEmisor === emisor && msgReceptor === receptor) {
                // Agregar mensaje desde el emisor (mensaje a la derecha)
                agregarMensajeAlChat(msgMensaje, 'mensajeDerecha', fechaActual, listaMensajes);
                actualizarListaConversaciones(msgReceptor, msgMensaje);
            }
        } catch (error) {
            console.error('Error al manejar el mensaje de WebSocket:', error);
        }
    }
    
    function actualizarListaConversaciones(usuarioId, ultimoMensaje) {
        const listaMensajes = document.querySelectorAll('.mensajes .mensaje');
        let conversacionActualizada = false;
    
        listaMensajes.forEach(mensaje => {
            const receptorId = mensaje.getAttribute('data-receptor');
    
            if (receptorId == usuarioId) {
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
            // Agregamos un pequeño retraso antes de reiniciar los chats
            setTimeout(() => {
                reiniciarChats();
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

    /*
     *   FUNCIONES RELACIONADAS ACTUALIZAR EL TIEMPO CADA MINUTO
     */

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

    /*
     *   FUNCION RELACIONADA VERIFICAR TOKEN
     */

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

    /*
     *   FUNCION PRINCIPAL PARA INICIAR CONEXION
     */

    async function iniciarChat() {
        token = await obtenerToken();
        if (token) {
            connectWebSocket();
        } else {
            console.error('No se pudo iniciar el chat sin un token válido');
        }
    }

    function connectWebSocket() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => {
            ws.send(
                JSON.stringify({
                    emisor,
                    type: 'auth',
                    token: token
                })
            );
            pingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({type: 'ping'}));
                }
            }, 30000);
        };
        ws.onclose = () => {
            clearInterval(pingInterval);
            console.log('Conexión cerrada. Reintentando en 5 segundos...');
            setTimeout(connectWebSocket, 5000);
        };
        ws.onerror = error => {
            console.error('Error en WebSocket:', error);
        };
        ws.onmessage = ({data}) => {
            const message = JSON.parse(data);
            if (message.type === 'pong') {
                console.log('Pong recibido');
            } else if (message.type === 'set_emisor') {
                ws.send(JSON.stringify({emisor}));
            } else {
                manejarMensajeWebSocket(JSON.stringify(message));
            }
        };
    }

    /*
     *   FUNCIONES PARA ENVIAR MENSAJE
     */

    /* 

    */

    function enviarMensajeWs(receptor, mensaje, adjunto = null, metadata = null) {
        const messageData = {emisor, receptor, mensaje, adjunto, metadata};

        if (ws?.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(messageData));
        } else {
            console.error('WebSocket no está conectado, no se puede enviar el mensaje');
            alert('No se puede enviar el mensaje, por favor, reinicia la página');
        }
    }

    function setupEnviarMensajeHandler() {
        
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
            const listaMensajes = document.querySelector('.listaMensajes');
            if (subidaChatProgreso === true) {
                // Verificamos si hay una subida en progreso
                alert('Por favor espera a que se complete la subida del archivo.');
                return; // Salimos de la función sin enviar el mensaje
            }

            const mensaje = mensajeInput.value;
            if (mensaje.trim() !== '') {
                ocultarPreviews();

                let adjunto = null;
                if (archivoChatId || archivoChatUrl) {
                    adjunto = {
                        archivoChatId: archivoChatId,
                        archivoChatUrl: archivoChatUrl
                    };
                    archivoChatId = null;
                    archivoChatUrl = null;
                }

                enviarMensajeWs(receptor, mensaje, adjunto);
                // agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, true);
                agregarMensajeAlChat(mensaje, 'mensajeDerecha', new Date(), listaMensajes, null, false, adjunto);
                mensajeInput.value = '';
                const mensajeVistaPrevia = `Tu: ${mensaje}`;
                actualizarListaConversaciones(receptor, mensajeVistaPrevia);
            }
        }
    }

    /*
     *   FUNCIONES PARA CARGAR MAS ADJUNTAR ARCHIVOS
     */

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
                console.log('El archivo no puede superar los 50 MB.');
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
            console.log('Iniciando subida de audio:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            ocultarPreviews(); // Ocultar otros previews

            try {
                console.log('Cargando archivo de audio...');
                previewChatAudio.style.display = 'block';
                cancelUploadButton.style.display = 'block';
                const progressBarId = waveAudio(file);
                console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                console.log('Audio cargado con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;
                subidaChatProgreso = false;
            } catch (error) {
                console.error('Error al cargar el audio:', error);
                subidaChatProgreso = false;
            }
        };

        const subidaChatImagen = async file => {
            console.log('Iniciando subida de imagen:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            ocultarPreviews(); // Ocultar otros previews
            updateChatPreviewImagen(file);

            try {
                console.log('Cargando archivo de imagen...');
                previewChatImagen.style.display = 'block';
                cancelUploadButton.style.display = 'block';
                const progressBarId = `progress-${Date.now()}`;
                console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                console.log('Imagen cargada con éxito:', fileId, 'URL:', fileUrl);

                archivoChatId = fileId;
                archivoChatUrl = fileUrl;

                subidaChatProgreso = false;
            } catch (error) {
                console.error('Error al cargar la imagen:', error);
                subidaChatProgreso = false;
            }
        };

        const subidaChatArchivo = async file => {
            console.log('Iniciando subida de archivo:', file.name, 'tamaño:', file.size, 'tipo:', file.type);
            subidaChatProgreso = true;
            cancelUploadButton.style.display = 'block';
            ocultarPreviews(); // Ocultar otros previews
            previewChatArchivo.style.display = 'block';
            previewChatArchivo.innerHTML = `
                <div class="file-name">${file.name}</div>
                <div id="barraProgresoFile" class="progress" style="width: 0%; height: 100%; background-color: #4CAF50; transition: width 0.3s;"></div>`;

            try {
                console.log('Cargando archivo...');
                const progressBarId = `progress-${Date.now()}`;
                console.log('Barra de progreso creada con ID:', progressBarId);

                const {fileUrl, fileId} = await subidaChatBackend(file, progressBarId);
                console.log('Archivo cargado con éxito:', fileId, 'URL:', fileUrl);

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

    /*
     *   FUNCION PARA CARGAR MAS MENSAJES
     */

    function manejarScroll(conversacion) {
        const listaMensajes = document.querySelector('.listaMensajes');
        let puedeDesplazar = true;
        currentPage = 1;

        // Verificar si la conversación es válida (no null ni undefined)
        if (!conversacion) {
            console.warn('ID de conversación no válida. No se cargará más historial.');
            return; // Salir de la función si no hay una conversación válida
        }

        listaMensajes?.addEventListener('scroll', async e => {
            if (e.target.scrollTop === 0 && puedeDesplazar) {
                puedeDesplazar = false;

                setTimeout(() => {
                    puedeDesplazar = true;
                }, 2000);

                currentPage++;

                // Realizar la solicitud AJAX solo si hay una conversación válida
                const data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});

                if (data?.success) {
                    const mensajes = data.data.mensajes;
                    let fechaAnterior = null;

                    mensajes.reverse().forEach(mensaje => {
                        agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, true, mensaje.adjunto);
                        fechaAnterior = new Date(mensaje.fecha);
                    });

                    const primerMensaje = listaMensajes.querySelector('li');
                    if (primerMensaje) {
                        primerMensaje.scrollIntoView();
                    }
                } else {
                    console.error('Error al obtener más mensajes.');
                }
            }
        });
    }

    init();
}
