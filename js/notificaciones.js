function iniciarCargaNotificaciones() {
    let paginaActual = 2,
        cargando = false;
    const listaNotificaciones = document.querySelector('.notificaciones-lista.modal');

    if (!listaNotificaciones) return;

    const marcarNotificacionVista = id => {
        enviarAjax('marcar_notificacion_vista', { notificacionId: id })
            .then(response => {
                if (!response.success) console.error('Error al marcar la notificación como vista:', response.message);
            })
            .catch(err => console.error('Error de red al marcar notificación vista:', err));
    };

    const observer = new IntersectionObserver(
        entradas => {
            entradas.forEach(e => {
                if (e.isIntersecting) {
                    const id = e.target.dataset.notificacionId;
                    if (id) {
                        marcarNotificacionVista(id);
                        observer.unobserve(e.target);
                    }
                }
            });
        },
        { root: null, rootMargin: '0px', threshold: 0.1 }
    );

    const observarNotificaciones = () => {
        listaNotificaciones.querySelectorAll('.notificacion-item:not([data-observado="true"])').forEach(el => {
            el.dataset.observado = 'true';
            observer.observe(el);
        });
    };

    const cargarPaginaNotificaciones = (pagina) => {
        enviarAjax('cargar_notificaciones', { pagina })
            .then(data => {
                const textoNormalizado = data.replace(/\s+/g, ' ').trim();

                if (textoNormalizado.includes('No hay notificaciones disponibles')) {
                    cargando = true;
                    return;
                }

                if (pagina === 1) {
                    listaNotificaciones.innerHTML = data;
                    paginaActual = 2;
                } else {
                    listaNotificaciones.insertAdjacentHTML('beforeend', data);
                    paginaActual++;
                }

                observarNotificaciones();
                cargando = false;
            })
            .catch(() => {
                cargando = false;
            });
    };

    observarNotificaciones();

    listaNotificaciones.addEventListener('scroll', () => {
        if (listaNotificaciones.scrollHeight - (listaNotificaciones.scrollTop + listaNotificaciones.clientHeight) <= 200 && !cargando) {
            cargando = true;
            cargarPaginaNotificaciones(paginaActual);
        }
    });

    document.addEventListener("DOMContentLoaded", function() {
        async function verificarNotificaciones() {
            try {
                const response = await enviarAjax('verificar_notificaciones');
                
                if (response.hay_no_vistas) {
                    document.querySelector('#icono-notificaciones svg').setAttribute('fill', '#d43333');
                    cargarPaginaNotificaciones(1); // Recargar la página 1 si hay nuevas notificaciones
                } else {
                    document.querySelector('#icono-notificaciones svg').setAttribute('fill', 'currentColor');
                }

                verificarNotificaciones();
            } catch (error) {
                console.error('Error en la verificación de notificaciones:', error);
                setTimeout(verificarNotificaciones, 30000);
            }
        }
        verificarNotificaciones();
    });
}