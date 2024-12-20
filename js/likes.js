function like() {
    let ultimoDobleClic = 0; // Variable para el doble clic
    const retrasoEntreClics = 500; // 500 ms de retraso

    // Delegación de eventos para doble clic en los elementos <li>
    document.addEventListener('dblclick', function (evento) {
        const elementoLi = evento.target.closest('li.EDYQHV');
        if (elementoLi) {
            const ahora = Date.now();
            if (ahora - ultimoDobleClic < retrasoEntreClics) {
                console.log('Doble clic demasiado rápido, ignorado.');
                return;
            }
            ultimoDobleClic = ahora;

            const idPublicacion = elementoLi.getAttribute('id-post');
            const botonLike = elementoLi.querySelector('.post-like-button');

            if (idPublicacion && botonLike) {
                console.log('Doble clic en post ID:', idPublicacion);
                // Simular clic en el botón de like
                manejarClicEnBoton(evento, botonLike);
            }
        }
    });

    // Delegación de eventos para clics en botones de interacción
    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('[data-like_type][data-post_id]');
        if (boton && !evento.simulated) {
            //  <-  Añade la condición !evento.simulated
            manejarClicEnBoton(evento, boton);
        }
    });

    async function manejarClicEnBoton(evento, boton) {
        evento.preventDefault();

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
        const añadiendoInteraccion = !boton.classList.contains('liked');

        // Actualización optimista
        const contenedor = boton.closest('.botonlike-container');
        const contadorActual = parseInt(contenedor.querySelector(`.${tipoInteraccion}-count`).textContent, 10);
        const nuevoContador = añadiendoInteraccion ? contadorActual + 1 : contadorActual - 1;
        actualizarIUInteraccion(boton, añadiendoInteraccion, tipoInteraccion, nuevoContador);

        const datos = {
            post_id: idPublicacion,
            like_type: tipoInteraccion,
            like_state: añadiendoInteraccion,
            nonce: boton.dataset.nonce
        };

        try {
            const respuesta = await enviarAjax('like', datos);

            if (respuesta.success) {
                console.log(`Interacción "${tipoInteraccion}" ${añadiendoInteraccion ? 'añadida' : 'quitada'} en la publicación ${idPublicacion}.`);
                // Verificar si la respuesta del servidor coincide con la actualización optimista
                if (respuesta.counts[tipoInteraccion] !== nuevoContador) {
                    console.warn('Desajuste entre la actualización optimista y la respuesta del servidor.');
                    actualizarContador(contenedor, tipoInteraccion, respuesta.counts[tipoInteraccion]);
                }
            } else {
                // Revertir actualización optimista en caso de error
                console.error('Error al procesar la interacción:', respuesta.error);
                revertirIUInteraccion(boton, !añadiendoInteraccion, tipoInteraccion, contadorActual);
                // Mostrar mensajes de error específicos
                if (respuesta.error === 'not_logged_in') {
                    alert('Debes estar logueado para realizar esta acción.');
                } else if (respuesta.error === 'invalid_nonce') {
                    alert('Nonce inválido. Por favor, recarga la página e inténtalo de nuevo.');
                } else if (respuesta.error === 'error_like_type') {
                    alert('Tipo de interacción inválido.');
                } else {
                    alert('Hubo un error al procesar tu solicitud.');
                }
            }
        } catch (error) {
            // Revertir actualización optimista en caso de error
            console.error('Error en la solicitud AJAX:', error);
            revertirIUInteraccion(boton, !añadiendoInteraccion, tipoInteraccion, contadorActual);
            alert('Hubo un error al procesar tu solicitud. Por favor, inténtalo de nuevo.');
        } finally {
            boton.dataset.requestRunning = 'false';
        }
    }

    function actualizarIUInteraccion(boton, añadiendo, tipo, contador) {
        actualizarEstadoBoton(boton, añadiendo, tipo);
        const contenedor = boton.closest('.botonlike-container');
        actualizarContador(contenedor, tipo, contador);
    }

    function revertirIUInteraccion(boton, añadiendo, tipo, contador) {
        actualizarEstadoBoton(boton, añadiendo, tipo);
        const contenedor = boton.closest('.botonlike-container');
        actualizarContador(contenedor, tipo, contador);
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

function animacionLike() {
    const containers = document.querySelectorAll('.botonlike-container');

    containers.forEach(container => {
        const botonesExtras = container.querySelector('.botones-extras');
        const botonLike = container.querySelector('.post-like-button');
        let timeoutId = null;

        // Función para mostrar los botones extras
        const showExtras = () => {
            clearTimeout(timeoutId); // Limpia cualquier temporizador previo
            containers.forEach(c => c !== container && c.classList.remove('active')); // Oculta otros contenedores activos
            container.classList.add('active'); // Activa el contenedor actual
        };

        // Función para ocultar los botones extras
        const hideExtras = () => {
            clearTimeout(timeoutId); // Limpia cualquier temporizador previo
            timeoutId = setTimeout(() => {
                container.classList.remove('active'); // Oculta después de un retraso
            }, 2000); // Tiempo de espera para ocultar
        };

        // Mostrar botones extras al entrar con el mouse en el contenedor principal
        container.addEventListener('mouseenter', showExtras);

        // Iniciar el temporizador para ocultar cuando el mouse salga del contenedor principal,
        // pero no si el mouse entra en los botones extras
        container.addEventListener('mouseleave', (event) => {
            if (!botonesExtras.contains(event.relatedTarget)) {
                hideExtras();
            }
        });

        // Cancelar la ocultación si el mouse entra en los botones extras
        botonesExtras.addEventListener('mouseenter', () => {
            clearTimeout(timeoutId); // Cancela el temporizador de ocultar
        });

        // Iniciar el temporizador para ocultar cuando el mouse salga de los botones extras
        botonesExtras.addEventListener('mouseleave', (event) => {
            if (!container.contains(event.relatedTarget)) {
                hideExtras();
            }
        });

        // Móvil: touchstart, touchend y detectar pulsación larga
        let touchstartTime = 0;
        container.addEventListener('touchstart', () => {
            touchstartTime = Date.now(); // Marca el tiempo de inicio del toque
            clearTimeout(timeoutId); // Limpia cualquier temporizador previo
            containers.forEach(c => c !== container && c.classList.remove('active')); // Oculta otros contenedores activos

            timeoutId = setTimeout(() => {
                showExtras(); // Muestra los extras si es una pulsación larga
            }, 500); // Define el tiempo para la pulsación larga
        });

        container.addEventListener('touchend', () => {
            const duration = Date.now() - touchstartTime; // Calcula la duración del toque
            clearTimeout(timeoutId); // Limpia cualquier temporizador previo

            if (duration < 500 && container.classList.contains('active')) {
                hideExtras(); // Oculta si es un toque corto
            }
        });

        // Evitar que el menú se oculte cuando se toca dentro de los botones extras
        botonesExtras.addEventListener('touchstart', event => {
            event.stopPropagation(); // Detiene la propagación para evitar conflictos
        });

        // Evitar que active el botón like si ya está activo
        botonLike.addEventListener('touchstart', event => {
            if (container.classList.contains('active')) {
                event.preventDefault(); // Evita el comportamiento predeterminado
            }
        });
    });
}