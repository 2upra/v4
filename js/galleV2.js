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
        // Agregamos un log para verificar que se están obteniendo los elementos correctamente
        console.log('Iniciando abrirConversacion');

        document.querySelectorAll('.mensaje').forEach(item => {
            // Agregamos un log para cada item encontrado
            console.log('Elemento .mensaje encontrado:', item);

            item.addEventListener('click', async () => {
                const conversacion = item.getAttribute('data-conversacion');
                console.log('Conversación seleccionada:', conversacion); // Log para verificar que se obtiene el atributo correctamente

                currentPage = 1;
                try {
                    console.log('Enviando solicitud AJAX para obtener el chat con la conversación:', conversacion);

                    const data = await enviarAjax('obtenerChat', {
                        conversacion: conversacion,
                        page: currentPage
                    });

                    // Log para verificar la respuesta del servidor
                    console.log('Respuesta del servidor:', data);

                    if (data && data.success) {
                        console.log('Mensajes obtenidos con éxito:', data.mensajes);

                        const chatHtml = renderChat(data.mensajes, emisor);
                        const chatContainer = document.querySelector('.bloqueChat');
                        chatContainer.innerHTML = chatHtml;
                        chatContainer.style.display = 'block';
                    } else {
                        // Si no se obtuvo éxito en la respuesta, mostramos el mensaje de error
                        const errorMessage = data.message || 'Error desconocido al obtener los mensajes.';
                        console.error('No se pudieron obtener los mensajes:', errorMessage);
                        alert(errorMessage); // Para una mejor retroalimentación al usuario
                    }
                } catch (error) {
                    // Capturamos cualquier error en la solicitud AJAX
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

    function renderChat(mensajes, usuarioId) {
        let html = '';

        if (mensajes) {
            html += '<ul class="listaMensajes">';
            mensajes.forEach(mensaje => {
                const esRemitente = mensaje.remitente == usuarioId;
                const claseMensaje = esRemitente ? 'mensajeDerecha' : 'mensajeIzquierda';
                const imagenPerfil = !esRemitente ? imagenPerfil(mensaje.remitente) : null;
                const fechaRelativa = tiempoRelativo(mensaje.fecha);

                html += `<li class="mensaje ${claseMensaje}">`;
                if (!esRemitente) {
                    html += `
                        <div class="imagenMensaje">
                            <img src="${imagenPerfil}" alt="Imagen de perfil">
                        </div>`;
                }
                html += `
                    <div class="contenidoMensaje">
                        <p>${mensaje.mensaje}</p>
                        <span class="fechaMensaje">${fechaRelativa}</span>
                    </div>
                </li>`;
            });
            html += '</ul>';
            html += `
                <div>
                    <textarea class="mensajeContenido"></textarea>
                    <button class="enviarMensaje">Enviar</button>
                </div>
            `;
        } else {
            html += '<p>No hay mensajes en esta conversación.</p>';
        }

        return html;
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
