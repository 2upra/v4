function galle() {
    const wsUrl = 'wss://2upra.com/ws';
    const emisor = galleV2.emisor;
    let mensaje = null;
    let receptor = null;
    let metadata = null;
    let conversacion = null;
    let ws;
    let currentPage = 1;
    abrirConversacion();
    manejarScroll();

    // Función para obtener la zona horaria del navegador
    function obtenerZonaHoraria() {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    }

    // Función para ajustar la fecha en base a la zona horaria del usuario
    function ajustarFechaALaZonaHoraria(fecha, zonaHoraria) {
        const fechaEnUTC = new Date(fecha);
        const opciones = {timeZone: zonaHoraria, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit', second: '2-digit'};
        const partes = new Intl.DateTimeFormat([], opciones).formatToParts(fechaEnUTC);

        const valores = {};
        partes.forEach(part => (valores[part.type] = part.value));

        return new Date(`${valores.year}-${valores.month}-${valores.day}T${valores.hour}:${valores.minute}:${valores.second}`);
    }

    // Función para calcular el tiempo relativo en JavaScript
    function formatearTiempoRelativo(fechaMensaje) {
        const ahora = new Date();
        const diferenciaSegundos = Math.floor((ahora - fechaMensaje) / 1000);

        if (diferenciaSegundos < 60) {
            return 'Justo ahora';
        } else if (diferenciaSegundos < 3600) {
            const minutos = Math.floor(diferenciaSegundos / 60);
            return minutos === 1 ? 'hace 1 minuto' : `hace ${minutos} minutos`;
        } else if (diferenciaSegundos < 86400) {
            const horas = Math.floor(diferenciaSegundos / 3600);
            return horas === 1 ? 'hace 1 hora' : `hace ${horas} horas`;
        } else if (diferenciaSegundos < 604800) {
            const dias = Math.floor(diferenciaSegundos / 86400);
            return dias === 1 ? 'hace 1 día' : `hace ${dias} días`;
        } else if (diferenciaSegundos < 2419200) {
            const semanas = Math.floor(diferenciaSegundos / 604800);
            return semanas === 1 ? 'hace 1 semana' : `hace ${semanas} semanas`;
        } else if (diferenciaSegundos < 29030400) {
            const meses = Math.floor(diferenciaSegundos / 2419200);
            return meses === 1 ? 'hace 1 mes' : `hace ${meses} meses`;
        } else {
            const años = Math.floor(diferenciaSegundos / 29030400);
            return años === 1 ? 'hace 1 año' : `hace ${años} años`;
        }
    }

    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                const conversacion = item.getAttribute('data-conversacion');
                currentPage = 1;

                try {
                    const data = await enviarAjax('obtenerChat', {
                        conversacion: conversacion,
                        page: currentPage
                    });

                    console.log(data);

                    if (data && data.success) {
                        const mensajes = data.data.mensajes;
                        const bloqueChat = document.querySelector('.bloqueChat');
                        const listaMensajes = document.querySelector('.listaMensajes');
                        listaMensajes.innerHTML = '';

                        let fechaAnterior = null;

                        // Obtener la zona horaria del cliente
                        const zonaHorariaUsuario = obtenerZonaHoraria();

                        mensajes.forEach(mensaje => {
                            const fechaMensaje = ajustarFechaALaZonaHoraria(mensaje.fecha, zonaHorariaUsuario);
                            if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
                                const divFecha = document.createElement('div');
                                divFecha.textContent = formatearTiempoRelativo(fechaMensaje); // Formatear la fecha
                                divFecha.classList.add('fechaSeparador');
                                listaMensajes.appendChild(divFecha);
                            }

                            // Crear el mensaje
                            const li = document.createElement('li');
                            li.textContent = mensaje.mensaje;
                            li.classList.add(mensaje.clase);
                            listaMensajes.appendChild(li);

                            // Actualizar la fecha anterior
                            fechaAnterior = fechaMensaje;
                        });

                        bloqueChat.style.display = 'block'; // Mostrar el bloque del chat
                    } else {
                        const errorMessage = data.message || 'Error desconocido al obtener los mensajes.';
                        alert(errorMessage);
                    }
                } catch (error) {
                    alert('Ha ocurrido un error al intentar abrir la conversación.');
                }
            });
        });
    }

    function manejarScroll() {
        const listaMensajes = document.querySelector('.listaMensajes');
        if (listaMensajes) {
            listaMensajes.addEventListener('scroll', async e => {
                const {scrollTop} = e.target;
                if (scrollTop === 0) {
                    currentPage++;
                    const conversacionId = document.querySelector('.bloqueChat').getAttribute('data-conversacion');
                    const data = await enviarAjax('obtenerChat', {
                        conversacionId: conversacionId,
                        page: currentPage
                    });

                    if (data && data.success) {
                        const chatHtml = renderChat(data.mensajes, emisor);
                        listaMensajes.innerHTML = chatHtml + listaMensajes.innerHTML;
                    }
                }
            });
        }
    }

    // WebSocket Connection
    const connectWebSocket = () => {
        ws = new WebSocket(wsUrl);

        ws.onopen = () => {
            console.log('WebSocket conectado');
        };

        ws.onclose = () => {
            console.log('WebSocket cerrado, reconectando...');
            setTimeout(connectWebSocket, 5000);
        };

        ws.onerror = error => {
            console.error('Error en WebSocket:', error);
        };

        ws.onmessage = ({data}) => {
            const {emisor, receptor, mensaje, adjunto, metadata} = JSON.parse(data);
            console.log('Mensaje recibido de', emisor, ':', mensaje);
        };
    };
    connectWebSocket();

    // Función para enviar un mensaje
    const enviarMensajeWs = (receptor, mensaje, adjunto = null, metadata = null) => {
        const messageData = {
            emisor: emisor,
            receptor: receptor,
            mensaje: mensaje,
            adjunto: adjunto,
            metadata: metadata
        };

        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify(messageData));
            console.log('Mensaje enviado:', mensaje);
        } else {
            console.error('WebSocket no está conectado, no se puede enviar el mensaje');
        }
    };

    document.addEventListener('click', event => {
        if (event.target.matches('.enviarMensaje')) {
            const mensaje = document.querySelector('.mensajeContenido').value;
            if (mensaje.trim() !== '') {
                const receptorId = document.querySelector('.bloqueChat').getAttribute('data-receptor');
                enviarMensajeWs(receptorId, mensaje);
                document.querySelector('.mensajeContenido').value = '';
            }
        }
    });
}
