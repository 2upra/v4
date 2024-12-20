function like() {
    let ultimoClic = 0;
    const retrasoEntreClics = 500; // 500 ms de retraso

    // Delegación de eventos para clics en botones de interacción
    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('[data-like_type][data-post_id]');
        if (boton) {
            manejarClicEnBoton(evento, boton);
        }
    });

    async function manejarClicEnBoton(evento, boton) {
        evento.preventDefault();
        const ahora = Date.now();
        if (ahora - ultimoClic < retrasoEntreClics) {
            console.log('Clic ignorado por retraso.');
            return;
        }
        ultimoClic = ahora;

        const idPublicacion = parseInt(boton.dataset.post_id, 10);
        const tipoInteraccion = boton.dataset.like_type;

        if (!idPublicacion || !tipoInteraccion || boton.dataset.requestRunning === 'true') {
            console.log('Datos incompletos o solicitud en curso.');
            return;
        }

        if (!navigator.onLine) {
            alert('No hay conexión a internet. Verifica tu conexión e inténtalo de nuevo.');
            return;
        }

        boton.dataset.requestRunning = 'true';

        // Determinar si se está añadiendo o quitando la interacción
        const añadiendoInteraccion = !boton.classList.contains(tipoInteraccion + '-active');

        // Actualizar la UI inmediatamente
        actualizarIUInteraccion(boton, añadiendoInteraccion, tipoInteraccion);

        const datos = {
            post_id: idPublicacion,
            like_type: tipoInteraccion,
            like_state: añadiendoInteraccion, // true para añadir, false para quitar
            nonce: boton.dataset.nonce // Incluye el nonce para validación
        };

        try {
            const respuesta = await enviarAjax('like', datos); // Asumiendo que tienes una función enviarAjax

            if (respuesta.success) {
                console.log(`Interacción "${tipoInteraccion}" ${añadiendoInteraccion ? 'añadida' : 'quitada'} en la publicación ${idPublicacion}.`);
                const contenedor = boton.closest('.botonlike-container');
                actualizarTodosLosContadores(contenedor, idPublicacion, respuesta.counts);
                console.log('Contadores actualizados:', respuesta.counts);
            } else {
                // Manejar errores específicos
                console.error('Error al procesar la interacción:', respuesta.error);
                if (respuesta.error === 'not_logged_in') {
                    alert('Debes estar logueado para realizar esta acción.');
                } else if (respuesta.error === 'invalid_nonce') {
                    alert('Nonce inválido. Por favor, recarga la página e inténtalo de nuevo.');
                } else if (respuesta.error === 'error_like_type') {
                    alert('Tipo de interacción inválido.');
                } else {
                    alert('Hubo un error al procesar tu solicitud.');
                }
                revertirIUInteraccion(boton, !añadiendoInteraccion, tipoInteraccion);
            }
        } catch (error) {
            console.error('Error en la solicitud AJAX:', error);
            alert('Hubo un error al procesar tu solicitud. Por favor, inténtalo de nuevo.');
            revertirIUInteraccion(boton, !añadiendoInteraccion, tipoInteraccion);
        } finally {
            boton.dataset.requestRunning = 'false';
        }
    }

    function actualizarIUInteraccion(boton, añadiendo, tipo) {
        // No necesitas el contenedor aquí, el botón ya es el objetivo.

        // Actualiza el botón actual
        actualizarEstadoBoton(boton, añadiendo, tipo);

        // No necesitamos actualizar otros botones aquí. La clase 'liked'
        // se maneja directamente en actualizarEstadoBoton.

        // Registrar el estado actual del botón después de la actualización.
        console.log(`Estado actual del botón para la publicación ${boton.dataset.post_id}, tipo ${tipo}: ${boton.classList.contains(tipo + '-active') ? 'activo' : 'inactivo'}`);
        console.log(`Clase 'liked' del botón para la publicación ${boton.dataset.post_id}, tipo ${tipo}: ${boton.classList.contains('liked') ? 'presente' : 'ausente'}`);
    }

    function revertirIUInteraccion(boton, añadiendo, tipo) {
        // Revierte el botón actual
        actualizarEstadoBoton(boton, añadiendo, tipo);
    }

    function actualizarEstadoBoton(boton, activo, tipo) {
        const claseActivo = tipo + '-active';
        if (activo) {
            boton.classList.add(claseActivo);
            boton.classList.add('liked'); // Agregar la clase 'liked' al marcar
        } else {
            boton.classList.remove(claseActivo);
            boton.classList.remove('liked'); // Remover la clase 'liked' al desmarcar
        }
    }

    function actualizarTodosLosContadores(contenedor, idPublicacion, contadores) {
        if (!contenedor) {
            console.error('No se encontró el contenedor para actualizar contadores.');
            return;
        }
        console.log(`Actualizando contadores para la publicación ${idPublicacion}:`, contadores);
        actualizarContador(contenedor, 'like', contadores.like);
        actualizarContador(contenedor, 'favorito', contadores.favorito);
        actualizarContador(contenedor, 'no_me_gusta', contadores.no_me_gusta);
    }

    function actualizarContador(contenedor, tipo, contador) {
        let claseContador = '';
        if (tipo === 'like') {
            claseContador = 'like-count';
        } else if (tipo === 'favorito') {
            claseContador = 'favorite-count';
        } else if (tipo === 'no_me_gusta') {
            claseContador = 'dislike-count';
        }

        const spanContador = contenedor.querySelector(`.${claseContador}`);
        if (spanContador) {
            spanContador.textContent = contador;
            console.log(`Contador de ${tipo} actualizado a ${contador}.`);
        } else {
            console.error(`No se encontró el contador para ${tipo}.`);
        }
    }
}