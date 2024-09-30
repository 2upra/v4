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
        abrirConversacion();
        manejarScroll();
        connectWebSocket();
        setupEnviarMensajeHandler();
    }

    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                conversacion = item.getAttribute('data-conversacion');
                receptor = item.getAttribute('data-receptor');
                currentPage = 1;

                try {
                    const data = await enviarAjax('obtenerChat', { conversacion, page: currentPage });
                    if (data?.success) {
                        mostrarMensajes(data.data.mensajes);
                        document.querySelector('.bloqueChat').style.display = 'block';
                        manejarScroll();
                        const listaMensajes = document.querySelector('.listaMensajes');
                        listaMensajes.scrollTop = listaMensajes.scrollHeight;
                    } else {
                        alert(data.message || 'Error desconocido al obtener los mensajes.');
                    }
                } catch (error) {
                    alert('Ha ocurrido un error al intentar abrir la conversación.');
                }
            });
        });
    }
    actualizarTiemposRelativos();
    function actualizarTiemposRelativos() {
        const actualizarElementosFecha = (selector) => {
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

            for (let i = startIndex; (insertAtTop ? i < children.length : i >= 0); i += searchOrder) {
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

    function connectWebSocket() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => {
            ws.send(JSON.stringify({ emisor }));
            pingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({ type: 'ping' }));
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
        ws.onmessage = ({ data }) => {
            const message = JSON.parse(data);
            if (message.type === 'pong') {
                // Respuesta al ping, no se requiere acción
            } else if (message.type === 'set_emisor') {
                ws.send(JSON.stringify({ emisor }));
            } else {
                manejarMensajeWebSocket(JSON.stringify(message));
            }
        };
    }

    function agregarMensajeAlChat(mensajeTexto, clase, fecha, listaMensajes = document.querySelector('.listaMensajes'), fechaAnterior = null, insertAtTop = false) {
        const fechaMensaje = new Date(fecha);

        if (!fechaAnterior) {
            let lastElement = null;
            const children = Array.from(listaMensajes.children);
            const searchOrder = insertAtTop ? 1 : -1;
            const startIndex = insertAtTop ? 0 : children.length - 1;

            for (let i = startIndex; (insertAtTop ? i < children.length : i >= 0); i += searchOrder) {
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

    function manejarMensajeWebSocket(data) {
        try {
            const { emisor: msgEmisor, receptor: msgReceptor, mensaje: msgMensaje } = JSON.parse(data);

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
                    // Selecciona el contenedor donde se muestran las conversaciones
                    const chatListContainer = document.querySelector('.mensajes');
                    if (chatListContainer) {
                        // Actualiza el contenido HTML con las nuevas conversaciones
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
        const messageData = { emisor, receptor, mensaje, adjunto, metadata };

        if (ws?.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(messageData));
        } else {
            console.error('WebSocket no está conectado, no se puede enviar el mensaje');
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
                const data = await enviarAjax('obtenerChat', { conversacion, page: currentPage });
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