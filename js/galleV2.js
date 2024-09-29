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

    function abrirConversacion() {
        console.log('Iniciando abrirConversacion');
        document.querySelectorAll('.mensaje').forEach(item => {
            console.log('Elemento .mensaje encontrado:', item);
    
            item.addEventListener('click', async () => {
                const conversacion = item.getAttribute('data-conversacion');
                console.log('Conversación seleccionada:', conversacion);
                currentPage = 1;
                try {
                    console.log('Enviando solicitud AJAX para obtener el chat con la conversación:', conversacion);
    
                    const data = await enviarAjax('obtenerChat', {
                        conversacion: conversacion,
                        page: currentPage
                    });
                    console.log('Respuesta del servidor:', data);
    
                    if (data && data.success) {
                        const mensajes = data.data.mensajes;
                        console.log('Mensajes obtenidos con éxito:', mensajes);
    
                        // Seleccionamos el bloque de chat y la lista de mensajes
                        const bloqueChat = document.querySelector('.bloqueChat');
                        const listaMensajes = document.querySelector('.listaMensajes');
                        
                        // Borramos el contenido anterior de la lista de mensajes
                        listaMensajes.innerHTML = '';
    
                        // Renderizamos los nuevos mensajes
                        mensajes.forEach(mensaje => {
                            const li = document.createElement('li');
                            li.textContent = mensaje.contenido; // Asumiendo que 'contenido' es el campo del mensaje
                            listaMensajes.appendChild(li);
                        });
    
                        // Hacemos visible el bloque del chat
                        bloqueChat.style.display = 'block';
    
                        // Asegurarnos de que el bloque de chat esté visible
                        console.log('Mostrando el bloque del chat y los mensajes.');
    
                    } else {
                        const errorMessage = data.message || 'Error desconocido al obtener los mensajes.';
                        console.error('No se pudieron obtener los mensajes:', errorMessage);
                        alert(errorMessage);
                    }
                } catch (error) {
                    console.error('Error en la solicitud AJAX:', error);
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
