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
    let mensaje = null;
    let receptor = null;
    let metadata = null;
    let conversacion = null;
    let ws;
    let currentPage = 1;
    abrirConversacion();
    manejarScroll();

    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                conversacion = item.getAttribute('data-conversacion');
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
                                divFecha.textContent = formatearTiempoRelativo(mensaje.fecha);
                                divFecha.classList.add('fechaSeparador');
                                listaMensajes.appendChild(divFecha);
                            }
                            const li = document.createElement('li');
                            li.textContent = mensaje.mensaje;
                            li.classList.add(mensaje.clase);
                            listaMensajes.appendChild(li);
                            fechaAnterior = fechaMensaje;
                        });
    
                        bloqueChat.style.display = 'block';
                        manejarScroll();
                        listaMensajes.scrollTop = listaMensajes.scrollHeight;
    
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
            console.log('Datos recibidos:', data);
            
            try {
                const {emisor: msgEmisor, receptor: msgReceptor, mensaje: msgMensaje, adjunto, metadata} = JSON.parse(data);
                console.log('Mensaje desestructurado:', msgEmisor, msgReceptor, msgMensaje, adjunto, metadata);
        
                // Verificar si los emisor y receptor coinciden con nuestra lógica
                if ((msgEmisor == emisor && msgReceptor == receptor) || (msgEmisor == receptor && msgReceptor == emisor)) {
                    console.log('El mensaje es relevante para la conversación actual.');
        
                    const listaMensajes = document.querySelector('.listaMensajes');
        
                    // Verificar que el elemento existe
                    if (listaMensajes) {
                        console.log('Elemento .listaMensajes encontrado.');
        
                        const li = document.createElement('li');
                        li.textContent = msgMensaje;
                        li.classList.add(msgEmisor === emisor ? 'mensajeDerecha' : 'mensajeIzquierda');
        
                        // Verificar si la clase correcta se añade al elemento
                        console.log('Clase añadida al mensaje:', li.className);
        
                        listaMensajes.appendChild(li);
                        listaMensajes.scrollTop = listaMensajes.scrollHeight;
                        console.log('Mensaje añadido a la lista y desplazamiento ajustado.');
                    } else {
                        console.log('Elemento .listaMensajes no encontrado. Asegúrate de que el DOM está correctamente estructurado.');
                    }
                } else {
                    console.log('El mensaje no es relevante para la conversación actual. Emisor y receptor no coinciden.');
                }
            } catch (error) {
                console.error('Error al analizar el mensaje JSON o desestructurar:', error);
            }
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
            const mensajeInput = document.querySelector('.mensajeContenido');
            const mensaje = mensajeInput.value;
            if (mensaje.trim() !== '') {
                enviarMensajeWs(receptor, mensaje);
                mensajeInput.value = ''; // Limpiar el campo de entrada
            } else {
                console.warn('El mensaje está vacío y no será enviado');
            }
        }
    });

    function manejarScroll() {
        const listaMensajes = document.querySelector('.listaMensajes');
        if (listaMensajes) {
            listaMensajes.addEventListener('scroll', async e => {
                const {scrollTop} = e.target;
                if (scrollTop === 0) {
                    currentPage++;
                    const data = await enviarAjax('obtenerChat', {
                        conversacion: conversacion,
                        page: currentPage
                    });
                    if (data && data.success) {
                        const mensajes = data.data.mensajes;
                        let fechaAnterior = null;
                        mensajes.reverse().forEach(mensaje => { // Invertir para mantener el orden correcto
                            const fechaMensaje = new Date(mensaje.fecha);
                            if (!fechaAnterior || fechaMensaje - fechaAnterior >= 3 * 60 * 1000) {
                                const divFecha = document.createElement('div');
                                divFecha.textContent = formatearTiempoRelativo(mensaje.fecha); // Formatear la fecha
                                divFecha.classList.add('fechaSeparador');
                                listaMensajes.insertBefore(divFecha, listaMensajes.firstChild);
                            }
                            // Crear el mensaje
                            const li = document.createElement('li');
                            li.textContent = mensaje.mensaje;
                            li.classList.add(mensaje.clase);
                            listaMensajes.insertBefore(li, listaMensajes.firstChild);
                            fechaAnterior = fechaMensaje;
                        });
    
                        // Opcional: Ajustar el scroll para mantener la posición después de cargar más mensajes
                        listaMensajes.scrollTop = listaMensajes.scrollHeight / currentPage;
                    }
                }
            });
        }
    }
}
