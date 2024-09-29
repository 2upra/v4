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
    const { emisor } = galleV2;
    let receptor = null, currentPage = 1, ws;

    abrirConversacion();
    conectarWebSocket();
    manejarScroll();

    function abrirConversacion() {
        document.querySelectorAll('.mensaje').forEach(item => {
            item.addEventListener('click', async () => {
                receptor = item.getAttribute('data-receptor');
                currentPage = 1;
                const conversacion = item.getAttribute('data-conversacion');
                const data = await enviarAjax('obtenerChat', { conversacion, page: currentPage });
                if (data?.success) {
                    renderMensajes(data.data.mensajes);
                    document.querySelector('.bloqueChat').style.display = 'block';
                } else alert(data.message || 'Error al obtener los mensajes.');
            });
        });
    }

    function renderMensajes(mensajes) {
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
        listaMensajes.scrollTop = listaMensajes.scrollHeight;
    }

    function conectarWebSocket() {
        ws = new WebSocket(wsUrl);
        ws.onopen = () => console.log('WebSocket conectado');
        ws.onclose = () => setTimeout(conectarWebSocket, 5000);
        ws.onerror = error => console.error('Error en WebSocket:', error);
        ws.onmessage = ({ data }) => {
            const mensaje = JSON.parse(data);
            console.log('Mensaje recibido de', mensaje.emisor, ':', mensaje.mensaje);
            if (mensaje.receptor === receptor || mensaje.emisor === receptor) {
                renderMensajes([mensaje]);
            }
        };
    }

    document.addEventListener('click', ({ target }) => {
        if (target.matches('.enviarMensaje')) {
            const mensaje = document.querySelector('.mensajeContenido').value.trim();
            if (mensaje) enviarMensajeWs(receptor, mensaje);
        }
    });

    function enviarMensajeWs(receptor, mensaje, adjunto = null, metadata = null) {
        if (ws?.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({ emisor, receptor, mensaje, adjunto, metadata }));
            console.log('Mensaje enviado:', mensaje);
        } else {
            console.error('WebSocket no está conectado.');
        }
    }

    function manejarScroll() {
        const listaMensajes = document.querySelector('.listaMensajes');
        if (listaMensajes) {
            listaMensajes.addEventListener('scroll', async ({ target }) => {
                if (target.scrollTop === 0) {
                    currentPage++;
                    const conversacionId = document.querySelector('.bloqueChat').getAttribute('data-conversacion');
                    const data = await enviarAjax('obtenerChat', { conversacionId, page: currentPage });
                    if (data?.success) {
                        renderMensajes(data.mensajes);
                    }
                }
            });
        }
    }
}
