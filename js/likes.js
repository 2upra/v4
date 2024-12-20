function like() {
    let ultimoClic = 0;
    const retrasoEntreClics = 500; // 500 ms de retraso
    animacionLike();

    // Delegación de eventos para clics en botones de interacción
    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('[data-like_type][data-post_id]');
        if (boton) {
            manejarClicEnBoton(evento, boton);
        }
    });

    // Manejar doble clic o doble toque en los elementos <li>
    let ultimoToque = 0;
    const retrasoEntreToques = 300; // 300 ms para detectar doble toque

    document.addEventListener('touchstart', function (evento) {
        const elemento = evento.target.closest('.POST-nada'); // Ajusta el selector según sea necesario
        if (elemento) {
            const ahora = Date.now();
            if (ahora - ultimoToque < retrasoEntreToques) {
                evento.preventDefault(); // Prevenir el evento de clic
                manejarDobleToque(elemento);
            }
            ultimoToque = ahora;
        }
    });

    document.addEventListener('dblclick', function (evento) {
        const elemento = evento.target.closest('.POST-nada'); // Ajusta el selector según sea necesario
        if (elemento) {
            manejarDobleClic(elemento);
        }
    });

    function manejarDobleClic(elemento) {
        const idPost = elemento.getAttribute('id-post');
        if (idPost) {
            const botonLike = document.querySelector(`.botonlike[data-post_id="${idPost}"][data-like_type="like"]`);
            if (botonLike) {
                manejarClicEnBoton(new Event('dblclick'), botonLike); // Simular un clic en el botón de like
            }
        }
    }

    function manejarDobleToque(elemento) {
        const idPost = elemento.getAttribute('id-post');
        if (idPost) {
            const botonLike = document.querySelector(`.botonlike[data-post_id="${idPost}"][data-like_type="like"]`);
            if (botonLike) {
                manejarClicEnBoton(new Event('touchstart'), botonLike); // Simular un clic en el botón de like
            }
        }
    }

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
        const añadiendoInteraccion = !boton.classList.contains('liked');
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
        actualizarEstadoBoton(boton, añadiendo, tipo);
    }

    function revertirIUInteraccion(boton, añadiendo, tipo) {
        actualizarEstadoBoton(boton, añadiendo, tipo);
    }

    function actualizarEstadoBoton(boton, activo, tipo) {
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
    let touchstartTime = 0;
    let timeoutId = null;

    containers.forEach(container => {
        const botonesExtras = container.querySelector('.botones-extras');
        const botonLike = container.querySelector('.post-like-button');

        // Escritorio: mouseenter y mouseleave
        container.addEventListener('mouseenter', () => {
            clearTimeout(timeoutId);
            // Remover la clase 'active' de otros contenedores
            containers.forEach(c => {
                if (c !== container) {
                    c.classList.remove('active');
                }
            });
            // Añadir un retraso para mostrar los botones extras
            timeoutId = setTimeout(() => {
                container.classList.add('active');
            }, 300); // Ajusta el tiempo de retraso según sea necesario
        });

        container.addEventListener('mouseleave', () => {
            clearTimeout(timeoutId);
            // Añadir un retraso para ocultar los botones extras
            timeoutId = setTimeout(() => {
                container.classList.remove('active');
            }, 300); // Ajusta el tiempo de retraso según sea necesario
        });

        // Móvil: touchstart, touchend y detectar pulsación larga
        container.addEventListener('touchstart', event => {
            touchstartTime = Date.now();
            clearTimeout(timeoutId);

            // Remover la clase 'active' de otros contenedores
            containers.forEach(c => {
                if (c !== container) {
                    c.classList.remove('active');
                }
            });

            // Iniciar un temporizador para la pulsación larga
            timeoutId = setTimeout(() => {
                container.classList.add('active');
            }, 500); // Ajusta el tiempo para la pulsación larga (e.g., 500ms)
        });

        container.addEventListener('touchend', event => {
            const duration = Date.now() - touchstartTime;
            clearTimeout(timeoutId);

            // Si la duración es menor que el tiempo de pulsación larga, es un toque corto
            if (duration < 500) {
                // Evitar que se propague el evento de clic al botón de like si ya se ha mostrado el menú
                if (container.classList.contains('active')) {
                    event.preventDefault();
                }
                container.classList.remove('active');
            }
        });

        // Evitar que el menú se oculte cuando se toca dentro de él
        botonesExtras.addEventListener('touchstart', event => {
            event.stopPropagation();
        });

        //Para evitar que active el boton like si ya se activo active
        botonLike.addEventListener('touchstart', event => {
            if (container.classList.contains('active')) {
                event.preventDefault();
            }
        });
    });
}
