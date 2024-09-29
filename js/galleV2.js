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

    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                conversacion = item.getAttribute('data-conversacion');
                receptor = item.getAttribute('data-receptor');
                currentPage = 1;

                try {
                    const data = await enviarAjax('obtenerChat', {conversacion, page: currentPage});
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

    function actualizarTiemposRelativos() {
        const separadoresDeFecha = document.querySelectorAll('.fechaSeparador');
        separadoresDeFecha.forEach(separador => {
            const fechaMensaje = new Date(separador.getAttribute('data-fecha'));
            separador.textContent = formatearTiempoRelativo(fechaMensaje);
        });
    }

    // Actualizar cada minuto
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
        console.log('AGREGARMENSAJEALCHAT: Inicio de la función');
        const fechaMensaje = new Date(fecha);
        console.log('AGREGARMENSAJEALCHAT: Fecha del mensaje', fechaMensaje);
    
        if (!fechaAnterior) {
            console.log('AGREGARMENSAJEALCHAT: No hay fecha anterior proporcionada');
            let lastElement = null;
            if (insertAtTop) {
                console.log('AGREGARMENSAJEALCHAT: Insertando en la parte superior');
                for (let i = 0; i < listaMensajes.children.length; i++) {
                    const child = listaMensajes.children[i];
                    if (child.tagName.toLowerCase() === 'li' && (child.classList.contains('mensajeDerecha') || child.classList.contains('mensajeIzquierda'))) {
                        lastElement = child;
                        break;
                    }
                }
            } else {
                console.log('AGREGARMENSAJEALCHAT: Insertando en la parte inferior');
                for (let i = listaMensajes.children.length - 1; i >= 0; i--) {
                    const child = listaMensajes.children[i];
                    if (child.tagName.toLowerCase() === 'li' && (child.classList.contains('mensajeDerecha') || child.classList.contains('mensajeIzquierda'))) {
                        lastElement = child;
                        break;
                    }
                }
            }
            if (lastElement) {
                fechaAnterior = new Date(lastElement.getAttribute('data-fecha'));
                console.log('AGREGARMENSAJEALCHAT: Fecha del último elemento encontrado', fechaAnterior);
            } else {
                fechaAnterior = null;
                console.log('AGREGARMENSAJEALCHAT: No se encontró un elemento anterior');
            }
        }
    
        if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
            console.log('AGREGARMENSAJEALCHAT: Agregando separador de fecha');
            const divFecha = document.createElement('div');
            divFecha.textContent = formatearTiempoRelativo(fecha);
            divFecha.classList.add('fechaSeparador');
            divFecha.setAttribute('data-fecha', fechaMensaje.toISOString());
    
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
        console.log('AGREGARMENSAJEALCHAT: Creado elemento de lista', li);
    
        if (insertAtTop) {
            listaMensajes.insertBefore(li, listaMensajes.firstChild);
        } else {
            listaMensajes.appendChild(li);
        }
    
        if (!insertAtTop) {
            listaMensajes.scrollTop = listaMensajes.scrollHeight;
            console.log('AGREGARMENSAJEALCHAT: Desplazamiento ajustado al final');
        }
    
        console.log('AGREGARMENSAJEALCHAT: Fin de la función');
    }
    
    let pingInterval;

    galle();

    function galle() {
        abrirConversacion();
        manejarScroll();
        connectWebSocket();
        setupEnviarMensajeHandler();
    }

    function connectWebSocket() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => {
            ws.send(JSON.stringify({emisor}));
            pingInterval = setInterval(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({type: 'ping'}));
                }
            }, 30000);
        };
        ws.onclose = event => {
            clearInterval(pingInterval);
            setTimeout(connectWebSocket, 5000);
        };
        ws.onerror = error => console.error('Error en WebSocket:', error);
        ws.onmessage = ({data}) => {
            const message = JSON.parse(data);
            if (message.type === 'pong') {
                console.log('Pong recibido del servidor');
            } else if (message.type === 'set_emisor') {
                ws.send(JSON.stringify({emisor}));
            } else {
                manejarMensajeWebSocket(JSON.stringify(message));
            }
        };
    }

    // Estas funciones solo se activan para el usuario que reciban el mensaje.
    function manejarMensajeWebSocket(data) {
        console.log('MANEJARMENSAJEWEBSOCKET: Mensaje recibido a través de WebSocket:', data);
    
        try {
            const { emisor: msgEmisor, receptor: msgReceptor, mensaje: msgMensaje } = JSON.parse(data);
            console.log('MANEJARMENSAJEWEBSOCKET: Datos del mensaje parseado:', { msgEmisor, msgReceptor, msgMensaje });
    
            // Si eres el receptor del mensaje
            if (msgReceptor === emisor) {
                console.log('MANEJARMENSAJEWEBSOCKET: El mensaje es para este emisor:', emisor);
    
                if (msgEmisor === receptor) {
                    console.log('MANEJARMENSAJEWEBSOCKET: Receptor coincide, agregando mensaje al chat (izquierda).');
                    agregarMensajeAlChat(msgMensaje, 'mensajeIzquierda', new Date());
                }
    
                console.log('MANEJARMENSAJEWEBSOCKET: Actualizando lista de conversaciones para emisor:', msgEmisor);
                actualizarListaConversaciones(msgEmisor, msgMensaje);
    
            // Si eres el emisor del mensaje
            } else if (msgEmisor === emisor && msgReceptor === receptor) {
                console.log('MANEJARMENSAJEWEBSOCKET: El mensaje es de este emisor y receptor coincide, agregando mensaje al chat (derecha).');
                agregarMensajeAlChat(msgMensaje, 'mensajeDerecha', new Date());
    
                console.log('MANEJARMENSAJEWEBSOCKET: Actualizando lista de conversaciones para receptor:', msgReceptor);
                actualizarListaConversaciones(msgReceptor, msgMensaje);
            } else {
                console.log('MANEJARMENSAJEWEBSOCKET: El mensaje recibido no se ajusta a ninguna de las condiciones manejadas.');
            }
        } catch (error) {
            console.error('MANEJARMENSAJEWEBSOCKET: Error al manejar el mensaje de WebSocket:', error);
        }
    }
    
    function actualizarListaConversaciones(usuarioId, ultimoMensaje) {
        console.log('ACTUALIZARLISTACONVERSACIONES: Función llamada con:', { usuarioId, ultimoMensaje });
    
        const listaMensajes = document.querySelectorAll('.mensajes .mensaje');
        console.log('ACTUALIZARLISTACONVERSACIONES: Elementos seleccionados con querySelectorAll:', listaMensajes);
    
        let conversacionActualizada = false;
    
        listaMensajes.forEach(mensaje => {
            console.log('ACTUALIZARLISTACONVERSACIONES: Revisando mensaje:', mensaje);
    
            const receptorId = mensaje.getAttribute('data-receptor');
            console.log('ACTUALIZARLISTACONVERSACIONES: Receptor ID del mensaje:', receptorId);
    
            // Verifica si el receptor o emisor coincide con el id del usuario
            if (receptorId == usuarioId) {
                console.log('ACTUALIZARLISTACONVERSACIONES: Receptor ID coincide con Usuario ID:', receptorId);
    
                const vistaPrevia = mensaje.querySelector('.vistaPrevia p');
                if (vistaPrevia) {
                    console.log('ACTUALIZARLISTACONVERSACIONES: Vista previa encontrada:', vistaPrevia);
                    vistaPrevia.textContent = ultimoMensaje;
                } else {
                    console.log('ACTUALIZARLISTACONVERSACIONES: No se encontró vista previa en el mensaje.');
                }
    
                const fechaRelativa = formatearTiempoRelativo(new Date());
                console.log('ACTUALIZARLISTACONVERSACIONES: Fecha relativa calculada:', fechaRelativa);
    
                const tiempoMensaje = mensaje.querySelector('.tiempoMensaje span');
                if (tiempoMensaje) {
                    console.log('ACTUALIZARLISTACONVERSACIONES: Elemento tiempo encontrado:', tiempoMensaje);
                    tiempoMensaje.textContent = fechaRelativa;
                } else {
                    console.log('ACTUALIZARLISTACONVERSACIONES: No se encontró el elemento de tiempo en el mensaje.');
                }
    
                conversacionActualizada = true;
            } else {
                console.log('ACTUALIZARLISTACONVERSACIONES: Receptor ID no coincide con Usuario ID:', receptorId);
            }
        });
    
        if (!conversacionActualizada) {
            console.log('ACTUALIZARLISTACONVERSACIONES: No se actualizó ninguna conversación existente, agregando nueva conversación.');
            agregarNuevaConversacionALaLista(usuarioId, ultimoMensaje);
        }
    }

    function agregarNuevaConversacionALaLista(usuarioId, ultimoMensaje) {
        const listaMensajes = document.querySelector('.mensajes');

        // Crear los elementos necesarios
        const nuevoMensajeElemento = document.createElement('li');
        nuevoMensajeElemento.classList.add('mensaje');
        nuevoMensajeElemento.setAttribute('data-receptor', usuarioId);
        nuevoMensajeElemento.setAttribute('data-conversacion', '');

        const imagenMensaje = document.createElement('div');
        imagenMensaje.classList.add('imagenMensaje');
        const img = document.createElement('img');
        img.src = obtenerImagenPerfil(usuarioId);
        img.alt = 'Imagen de perfil';
        imagenMensaje.appendChild(img);

        const vistaPrevia = document.createElement('div');
        vistaPrevia.classList.add('vistaPrevia');
        const pMensaje = document.createElement('p');
        pMensaje.textContent = ultimoMensaje;
        vistaPrevia.appendChild(pMensaje);

        const tiempoMensaje = document.createElement('div');
        tiempoMensaje.classList.add('tiempoMensaje');
        const spanTiempo = document.createElement('span');
        spanTiempo.textContent = formatearTiempoRelativo(new Date());
        tiempoMensaje.appendChild(spanTiempo);

        // Agregar todos los elementos al mensaje
        nuevoMensajeElemento.appendChild(imagenMensaje);
        nuevoMensajeElemento.appendChild(vistaPrevia);
        nuevoMensajeElemento.appendChild(tiempoMensaje);

        // Agregar el nuevo mensaje al principio de la lista
        if (listaMensajes) {
            listaMensajes.insertBefore(nuevoMensajeElemento, listaMensajes.firstChild);
        }
    }
    function obtenerImagenPerfil(usuarioId) {
        return;
    }

    function enviarMensajeWs(receptor, mensaje, adjunto = null, metadata = null) {
        const messageData = {emisor, receptor, mensaje, adjunto, metadata};

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
            if (event.key === 'Enter') {
                if (!event.altKey) {
                    event.preventDefault();
                    enviarMensaje();
                }
            }
        });

        function enviarMensaje() {
            const mensaje = mensajeInput.value;
            if (mensaje.trim() !== '') {
                enviarMensajeWs(receptor, mensaje);
                agregarMensajeAlChat(mensaje, 'mensajeDerecha', new Date());
                mensajeInput.value = '';
            } else {
                console.warn('El mensaje está vacío y no será enviado');
            }
        }
    }

    function manejarScroll() {
        const listaMensajes = document.querySelector('.listaMensajes');
        let puedeDesplazar = true;

        listaMensajes?.addEventListener('scroll', async e => {
            if (e.target.scrollTop === 0) {
                if (!puedeDesplazar) {
                    return;
                }

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
                    primerMensaje && primerMensaje.scrollIntoView();
                }
            }
        });
    }
}
