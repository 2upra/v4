function like() {
    let ultimoDobleClic = 0; // Variable para el doble clic
    const retrasoEntreClics = 500; // 500 ms de retraso
    animacionLike();
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
                evento.preventDefault(); // Prevenir acción por defecto del doble clic
                botonLike.click(); // Simular clic en el botón de like directamente
            } else {
                console.log('No se encontró el ID de la publicación o el botón de "Me gusta" en el doble clic.');
            }
        }
    });
    // Delegación de eventos para clics en botones de interacción
    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('[data-like_type][data-post_id]');
        if (boton) {
            manejarClicEnBoton(evento, boton);
        }
    });

    async function manejarClicEnBoton(evento, boton) {
        evento.preventDefault();

        const idPublicacion = parseInt(boton.dataset.post_id, 10);
        const tipoInteraccion = boton.dataset.like_type;

        console.log(`Clic en botón: ID de publicación ${idPublicacion}, Tipo de interacción: ${tipoInteraccion}`);

        if (!idPublicacion || !tipoInteraccion) {
            console.log('Datos incompletos: ID de publicación o tipo de interacción no encontrados.');
            return;
        }

        if (boton.dataset.requestRunning === 'true') {
            console.log('Solicitud en curso, ignorando clic.');
            return;
        }

        if (!navigator.onLine) {
            console.log('No hay conexión a internet.');
            alert('No hay conexión a internet. Verifica tu conexión e inténtalo de nuevo.');
            return;
        }

        boton.dataset.requestRunning = 'true';
        const añadiendoInteraccion = !boton.classList.contains('liked');

        // Actualización optimista
        const contenedor = boton.closest('.botonlike-container');
        if (!contenedor) {
            console.error('No se encontró el contenedor del botón.');
            boton.dataset.requestRunning = 'false';
            return;
        }

        const contadorElement = contenedor.querySelector(`.${tipoInteraccion}-count`);
        if (!contadorElement) {
            console.error(`No se encontró el contador para el tipo de interacción: ${tipoInteraccion}`);
            boton.dataset.requestRunning = 'false';
            return;
        }

        const contadorActual = parseInt(contadorElement.textContent, 10);
        const nuevoContador = añadiendoInteraccion ? contadorActual + 1 : contadorActual - 1;

        console.log(`Actualización optimista: ${tipoInteraccion} de ${contadorActual} a ${nuevoContador}`);
        actualizarIUInteraccion(boton, añadiendoInteraccion, tipoInteraccion, nuevoContador);

        const datos = {
            post_id: idPublicacion,
            like_type: tipoInteraccion,
            like_state: añadiendoInteraccion,
            nonce: boton.dataset.nonce
        };

        try {
            console.log('Enviando solicitud AJAX:', datos);
            const respuesta = await enviarAjax('like', datos);
            console.log('Respuesta AJAX recibida:', respuesta);

            if (respuesta.success) {
                console.log(`Interacción "${tipoInteraccion}" ${añadiendoInteraccion ? 'añadida' : 'quitada'} en la publicación ${idPublicacion}.`);
                // Verificar si la respuesta del servidor coincide con la actualización optimista
                if (respuesta.counts && respuesta.counts[tipoInteraccion] !== undefined && respuesta.counts[tipoInteraccion] !== nuevoContador) {
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
            console.log('Solicitud finalizada.');
        }
    }

    function actualizarIUInteraccion(boton, añadiendo, tipo, contador) {
        console.log(`Actualizando UI para ${tipo}: añadiendo=${añadiendo}, contador=${contador}`);
        actualizarEstadoBoton(boton, añadiendo, tipo);
        const contenedor = boton.closest('.botonlike-container');
        if (contenedor) {
            actualizarContador(contenedor, tipo, contador);
        } else {
            console.error('No se encontró el contenedor para actualizar la UI.');
        }
    }

    function revertirIUInteraccion(boton, añadiendo, tipo, contador) {
        console.log(`Revirtiendo UI para ${tipo}: añadiendo=${añadiendo}, contador=${contador}`);
        actualizarEstadoBoton(boton, añadiendo, tipo);
        const contenedor = boton.closest('.botonlike-container');
        if (contenedor) {
            actualizarContador(contenedor, tipo, contador);
        } else {
            console.error('No se encontró el contenedor para revertir la UI.');
        }
    }

    function actualizarEstadoBoton(boton, activo, tipo) {
        console.log(`Actualizando estado del botón ${tipo}: activo=${activo}`);
        const claseActivo = tipo + '-active';
        if (activo) {
            boton.classList.add(claseActivo);
            boton.classList.add('liked');
        } else {
            boton.classList.remove(claseActivo);
            boton.classList.remove('liked');
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
        console.log(`Actualizando contador de ${tipo} a ${contador}`);
        const claseContador = `${tipo}-count`;
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
        let isHoveringContainer = false;
        let isHoveringExtras = false;
        let delayHide = 200; // Tiempo en milisegundos que los botones permanecerán visibles

        const showExtras = () => {
            //console.log('showExtras: Mostrando botones extras');
            clearTimeout(timeoutId);
            container.classList.add('active');
        };

        const hideExtras = (delay = 0) => {
            //console.log(`hideExtras: Ocultando botones extras en ${delay}ms`);
            timeoutId = setTimeout(() => {
                //console.log('hideExtras: Timeout expirado');
                if (!isHoveringContainer && !isHoveringExtras) {
                    //console.log('hideExtras: Ocultando botones extras porque no hay hover en container ni extras');
                    container.classList.remove('active');
                } else {
                    //console.log('hideExtras: No se ocultan los botones extras porque hay hover en container o extras');
                }
            }, delay);
        };

        const handleMouseEnterContainer = () => {
            isHoveringContainer = true;
            //console.log('handleMouseEnterContainer: Mouse entró en el contenedor');
            clearTimeout(timeoutId);
            showExtras();
        };

        const handleMouseLeaveContainer = () => {
            isHoveringContainer = false;
            //console.log('handleMouseLeaveContainer: Mouse salió del contenedor');
            hideExtras(delayHide);
        };

        const handleMouseEnterExtras = () => {
            isHoveringExtras = true;
            //console.log('handleMouseEnterExtras: Mouse entró en botones extras');
            clearTimeout(timeoutId);
        };

        const handleMouseLeaveExtras = () => {
            isHoveringExtras = false;
            //console.log('handleMouseLeaveExtras: Mouse salió de botones extras');
            hideExtras(delayHide);
        };

        container.addEventListener('mouseenter', handleMouseEnterContainer);
        container.addEventListener('mouseleave', handleMouseLeaveContainer);

        botonesExtras.addEventListener('mouseenter', handleMouseEnterExtras);
        botonesExtras.addEventListener('mouseleave', handleMouseLeaveExtras);

        // Manejo de eventos táctiles (sin cambios significativos aquí)
        container.addEventListener('touchstart', () => {
            //console.log('touchstart en container');
            clearTimeout(timeoutId);
            containers.forEach(c => c !== container && c.classList.remove('active'));
            timeoutId = setTimeout(showExtras, 500);
        });

        container.addEventListener('touchend', () => {
            //console.log('touchend en container');
            hideExtras(delayHide);
        });

        botonesExtras.addEventListener('touchstart', event => {
            //console.log('touchstart en botonesExtras');
            //event.stopPropagation();
        });

        botonLike.addEventListener('touchstart', event => {
            console.log('touchstart en botonLike');
            if (container.classList.contains('active')) {
                event.preventDefault();
            }
        });
    });
}
