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

    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                const conversacion = item.getAttribute('data-conversacion');
                currentPage = 1;
                receptor = item.getAttribute('data-receptor');

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

                        mensajes.forEach(mensaje => {
                            const fechaMensaje = new Date(mensaje.fecha);

                            if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
                                const divFecha = document.createElement('div');
                                divFecha.textContent = formatearTiempoRelativo(mensaje.fecha); // Formatear la fecha
                                divFecha.classList.add('fechaSeparador');
                                listaMensajes.appendChild(divFecha);
                            }

                            // Crear el mensaje
                            const li = document.createElement('li');
                            li.textContent = mensaje.mensaje;
                            li.classList.add(mensaje.clase);
                            listaMensajes.appendChild(li);
                            fechaAnterior = fechaMensaje;
                        });

                        bloqueChat.style.display = 'block';
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
        console.log('Preparando para enviar un mensaje...'); 
        console.log('Datos del mensaje:', {receptor, mensaje, adjunto, metadata});

        const messageData = {
            emisor: emisor,
            receptor: receptor,
            mensaje: mensaje,
            adjunto: adjunto,
            metadata: metadata
        };

        if (ws && ws.readyState === WebSocket.OPEN) {
            console.log('WebSocket está abierto. Enviando mensaje...');
            ws.send(JSON.stringify(messageData));
            console.log('Mensaje enviado:', mensaje);
        } else {
            console.error('WebSocket no está conectado, no se puede enviar el mensaje');
        }
    };

    document.addEventListener('click', event => {
        if (event.target.matches('.enviarMensaje')) {
            console.log('Botón de enviar mensaje clicado'); // Log de clic en el botón
            const mensaje = document.querySelector('.mensajeContenido').value;
            if (mensaje.trim() !== '') {
                console.log('Contenido del mensaje:', mensaje); 
                console.log('Receptor ID:', receptor); // Log del receptor ID
                enviarMensajeWs(receptor, mensaje);
                document.querySelector('.mensajeContenido').value = '';
                console.log('Campo de entrada de mensaje limpiado'); 
            } else {
                console.warn('El mensaje está vacío y no será enviado');
            }
        }
    });
}
