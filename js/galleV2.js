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

    function init() {
        manejarScroll();
        setupEnviarMensajeHandler();
        actualizarConexionEmisor();
        iniciarChat();
        clickMensaje();
    }

    function actualizarConexionEmisor() {
        const emisorId = galleV2.emisor;
        enviarAjax('actualizarConexion', {user_id: emisorId})
            .then(response => {
                if (response.success) {
                    console.log('Emisor actualizado como conectado.');
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
    }

    async function abrirConversacion({conversacion, receptor, imagenPerfil, nombreUsuario}) {
        try {
            let data = {success: true, data: {mensajes: []}}; // Inicialmente, datos vacíos para el caso de no tener conversación
            const currentPage = 1;

            if (conversacion) {
                // Si se recibió un ID de conversación, hacemos la petición con él
                data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});
            } else if (receptor) {
                // Si no hay conversación, pero sí receptor, hacemos la petición solo con el receptor
                data = await enviarAjax('obtenerChat', {receptor, page: currentPage});
            }

            // Si la petición fue exitosa o si no hay mensajes simplemente mostramos el chat vacío
            if (data?.success) {
                mostrarMensajes(data.data.mensajes); // Mostrar los mensajes obtenidos, si no hay, mostrará un chat vacío

                const bloqueChat = document.querySelector('.bloqueChat');
                bloqueChat.querySelector('.imagenMensaje img').src = imagenPerfil;
                bloqueChat.querySelector('.nombreConversacion p').textContent = nombreUsuario;
                bloqueChat.style.display = 'block'; // Mostrar la ventana del chat

                manejarScroll();

                const listaMensajes = document.querySelector('.listaMensajes');
                listaMensajes.scrollTop = listaMensajes.scrollHeight; // Desplazar al final del chat

                // Actualizar el estado de conexión del receptor
                await actualizarEstadoConexion(receptor, bloqueChat);
                setInterval(() => actualizarEstadoConexion(receptor, bloqueChat), 30000); // Actualizar cada 30 segundos
            } else {
                alert(data.message || 'Error desconocido al obtener los mensajes.');
            }
        } catch (error) {
            alert('Ha ocurrido un error al intentar abrir la conversación.');
        }
    }
    /*
no funciona bien, no obtiene los datos a pesar de que la funcion si hace su trabajo al parecer


2024-10-01 03:33:00 - Iniciando función infoUsuario.
2024-10-01 03:33:00 - Receptor recibido: 44
2024-10-01 03:33:00 - Imagen de perfil obtenida: https://i0.wp.com/2upra.com/wp-content/uploads/2024/05/perfildefault.jpg?quality=40&strip=all
2024-10-01 03:33:00 - Nombre de usuario obtenido: 2upra

parece que falla al mandar los datos porque no veo el log de respuesta enviada

function infoUsuario() {
    // Guardamos un log al iniciar la función
    chatLog('Iniciando función infoUsuario.');

    // Verificamos si el usuario está autenticado
    if (!is_user_logged_in()) {
        chatLog('Error: Usuario no autenticado.');
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die(); // Finalizamos la ejecución
    }

    // Obtenemos el ID del receptor desde la solicitud POST
    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;
    chatLog('Receptor recibido: ' . $receptor);

    // Verificamos si hay un receptor válido
    if ($receptor <= 0) {
        chatLog('Error: ID del receptor inválido.');
        wp_send_json_error(array('message' => 'ID del receptor inválido.'));
        wp_die(); // Finalizamos la ejecución
    }

    // Obtenemos la imagen de perfil y el nombre del usuario
    $imagenPerfil = imagenPerfil($receptor) ?: 'ruta_por_defecto.jpg'; // URL de una imagen por defecto
    $nombreUsuario = obtenerNombreUsuario($receptor) ?: 'Usuario Desconocido'; // Nombre por defecto

    // Guardamos los datos obtenidos en el log
    chatLog('Imagen de perfil obtenida: ' . $imagenPerfil);
    chatLog('Nombre de usuario obtenido: ' . $nombreUsuario);

    // Enviamos la respuesta con los datos obtenidos
    wp_send_json_success(array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario
    ));

    // Guardamos el log indicando que se envió la respuesta con éxito
    chatLog('Respuesta enviada con éxito para el receptor ID: ' . $receptor);

    wp_die(); // Finalizamos la ejecución
}



    */
    async function manejarClickEnMensaje(item) {
        item.addEventListener('click', async () => {
            let conversacion = item.getAttribute('data-conversacion');
            const receptor = item.getAttribute('data-receptor');
            let imagenPerfil = item.querySelector('.imagenMensaje img')?.src || null;
            let nombreUsuario = item.querySelector('.nombreUsuario strong')?.textContent || null;

            // Si no tenemos la imagen de perfil o el nombre, pedimos la información al servidor
            if (!imagenPerfil || !nombreUsuario) {
                try {
                    const data = await enviarAjax('infoUsuario', {receptor});

                    if (data?.success) {
                        imagenPerfil = data.imagenPerfil || 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/05/perfildefault.jpg?quality=40&strip=all';
                        nombreUsuario = data.nombreUsuario || 'Usuario Desconocido'; // Nombre por defecto si no se encuentra
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

            // Si aún no tenemos imagenPerfil o nombreUsuario, asignar valores por defecto para evitar errores
            imagenPerfil = imagenPerfil || 'https://i0.wp.com/2upra.com/wp-content/uploads/2024/05/perfildefault.jpg?quality=40&strip=all';
            nombreUsuario = nombreUsuario || 'Usuario Desconocido';

            // Abrir la conversación
            //actualizarConexionEmisor();
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

    setInterval(actualizarTiemposRelativos, 4000);

    function mostrarMensajes(mensajes) {
        const listaMensajes = document.querySelector('.listaMensajes');
        listaMensajes.innerHTML = '';
        let fechaAnterior = null;

        mensajes.forEach(mensaje => {
            agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, false);
            fechaAnterior = new Date(mensaje.fecha);
        });
    }

    function agregarMensajeAlChat(mensajeTexto, clase, fecha, listaMensajes = document.querySelector('.listaMensajes'), fechaAnterior = null, insertAtTop = false) {
        const fechaMensaje = new Date(fecha);

        if (!fechaAnterior) {
            let lastElement = null;
            const children = Array.from(listaMensajes.children);
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

        if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
            const divFecha = document.createElement('div');
            divFecha.textContent = formatearTiempoRelativo(fechaMensaje);
            divFecha.classList.add('fechaSeparador');
            divFecha.setAttribute('data-fecha', fechaMensaje.toISOString());

            insertAtTop ? listaMensajes.insertBefore(divFecha, listaMensajes.firstChild) : listaMensajes.appendChild(divFecha);
        }

        const li = document.createElement('li');
        li.textContent = mensajeTexto;
        li.classList.add(clase);
        li.setAttribute('data-fecha', fechaMensaje.toISOString());

        insertAtTop ? listaMensajes.insertBefore(li, listaMensajes.firstChild) : listaMensajes.appendChild(li);

        if (!insertAtTop) {
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
        }
    }

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

    function manejarMensajeWebSocket(data) {
        try {
            const {emisor: msgEmisor, receptor: msgReceptor, mensaje: msgMensaje} = JSON.parse(data);

            if (msgReceptor === emisor) {
                if (msgEmisor === receptor) {
                    agregarMensajeAlChat(msgMensaje, 'mensajeIzquierda', new Date());
                }
                actualizarListaConversaciones(msgEmisor, msgMensaje);
            } else if (msgEmisor === emisor && msgReceptor === receptor) {
                agregarMensajeAlChat(msgMensaje, 'mensajeDerecha', new Date());
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
            reiniciarChats();
        }
    }

    function reiniciarChats() {
        enviarAjax('reiniciarChats', {})
            .then(response => {
                if (response.success && response.data.html) {
                    const chatListContainer = document.querySelector('.mensajes');
                    if (chatListContainer) {
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

    function enviarMensajeWs(receptor, mensaje, adjunto = null, metadata = null) {
        const messageData = {emisor, receptor, mensaje, adjunto, metadata};

        if (ws?.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(messageData));
        } else {
            console.error('WebSocket no está conectado, no se puede enviar el mensaje');
            alert('No se puede enviar el mensaje, por favor, reinicia la pagina');
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
            const mensaje = mensajeInput.value;
            if (mensaje.trim() !== '') {
                enviarMensajeWs(receptor, mensaje);
                agregarMensajeAlChat(mensaje, 'mensajeDerecha', new Date());
                mensajeInput.value = '';
                const mensajeVistaPrevia = `Tu: ${mensaje}`;
                actualizarListaConversaciones(receptor, mensajeVistaPrevia);
            }
        }
    }
    function manejarScroll() {
        const listaMensajes = document.querySelector('.listaMensajes');
        let puedeDesplazar = true;

        listaMensajes?.addEventListener('scroll', async e => {
            if (e.target.scrollTop === 0 && puedeDesplazar) {
                puedeDesplazar = false;
                setTimeout(() => {
                    puedeDesplazar = true;
                }, 2000);

                currentPage++;
                const data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});
                if (data?.success) {
                    const mensajes = data.data.mensajes;
                    let fechaAnterior = null;
                    mensajes.reverse().forEach(mensaje => {
                        agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, true);
                        fechaAnterior = new Date(mensaje.fecha);
                    });
                    const primerMensaje = listaMensajes.querySelector('li');
                    if (primerMensaje) {
                        primerMensaje.scrollIntoView();
                    }
                }
            }
        });
    }

    init();
}
