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

    init();

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
                        // Ajustar el scroll al final de la lista de mensajes
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

        if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
            const divFecha = document.createElement('div');
            divFecha.textContent = formatearTiempoRelativo(fecha);
            divFecha.classList.add('fechaSeparador');

            if (insertAtTop) {
                listaMensajes.insertBefore(divFecha, listaMensajes.firstChild);
            } else {
                listaMensajes.appendChild(divFecha);
            }
        }

        const li = document.createElement('li');
        li.textContent = mensajeTexto;
        li.classList.add(clase);
        li.setAttribute('data-fecha', fechaMensaje.toISOString());

        if (insertAtTop) {
            listaMensajes.insertBefore(li, listaMensajes.firstChild);
        } else {
            listaMensajes.appendChild(li);
        }

        if (!insertAtTop) {
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
        }
    }

    function connectWebSocket() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => console.log('WebSocket conectado');
        ws.onclose = () => setTimeout(connectWebSocket, 5000);
        ws.onerror = error => console.error('Error en WebSocket:', error);
        ws.onmessage = ({ data }) => manejarMensajeWebSocket(data);
    }

    function manejarMensajeWebSocket(data) {
        try {
            const { emisor: msgEmisor, receptor: msgReceptor, mensaje: msgMensaje } = JSON.parse(data);

            if (msgReceptor == emisor) {
                // Mensaje recibido por el usuario actual
                if (msgEmisor == receptor) {
                    // La conversación con el emisor está abierta
                    agregarMensajeAlChat(msgMensaje, 'mensajeIzquierda', new Date());
                } else {
                    // La conversación no está abierta, actualizar la lista o notificar al usuario
                    actualizarListaConversaciones(msgEmisor, msgMensaje);
                }
            } else if (msgEmisor == emisor && msgReceptor == receptor) {
                // Mensaje enviado por el usuario actual
                agregarMensajeAlChat(msgMensaje, 'mensajeDerecha', new Date());
            }
        } catch (error) {
            console.error('Error al manejar el mensaje de WebSocket:', error);
        }
    }

    function actualizarListaConversaciones(emisorMensaje, ultimoMensaje) {
        // Implementar lógica para actualizar la lista de conversaciones
        // Por ejemplo, mostrar una notificación o agregar la conversación a la lista
        alert(`Nuevo mensaje de ${emisorMensaje}: ${ultimoMensaje}`);
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
                const mensajeInput = document.querySelector('.mensajeContenido');
                const mensaje = mensajeInput.value;

                if (mensaje.trim() !== '') {
                    enviarMensajeWs(receptor, mensaje);
                    agregarMensajeAlChat(mensaje, 'mensajeDerecha', new Date());
                    mensajeInput.value = '';
                } else {
                    console.warn('El mensaje está vacío y no será enviado');
                }
            }
        });
    }

    function manejarScroll() {
        const listaMensajes = document.querySelector('.listaMensajes');
        listaMensajes?.addEventListener('scroll', async e => {
            if (e.target.scrollTop === 0) {
                currentPage++;
                const data = await enviarAjax('obtenerChat', { conversacion, page: currentPage });
                if (data?.success) {
                    const mensajes = data.data.mensajes;
                    let fechaAnterior = null;
                    mensajes.reverse().forEach(mensaje => {
                        agregarMensajeAlChat(mensaje.mensaje, mensaje.clase, mensaje.fecha, listaMensajes, fechaAnterior, true);
                        fechaAnterior = new Date(mensaje.fecha);
                    });
                    // Mantener la posición del scroll después de agregar mensajes al principio
                    const primerMensaje = listaMensajes.querySelector('li');
                    primerMensaje && primerMensaje.scrollIntoView();
                }
            }
        });
    }
}