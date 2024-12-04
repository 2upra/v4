function iniciarCargaNotificaciones() {
    let paginaActual = 2,
        cargando = false;
    const listaNotificaciones = document.querySelector('.notificaciones-lista.modal');

    if (!listaNotificaciones) {
        console.error('No se encontró el elemento .notificaciones-lista.modal');
        return;
    }

    const marcarNotificacionVista = id => {
        return fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'marcar_notificacion_vista', notificacionId: id })
        })
            .then(res => {
                if (!res.ok) {
                    console.error('Error al marcar la notificación como vista:', res.statusText);
                }
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

    observarNotificaciones();

    listaNotificaciones.addEventListener('scroll', () => {
        if (listaNotificaciones.scrollHeight - (listaNotificaciones.scrollTop + listaNotificaciones.clientHeight) <= 200 && !cargando) {
            cargando = true;

            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'cargar_notificaciones', pagina: paginaActual })
            })
                .then(res => {
                    if (!res.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return res.text();
                })
                .then(data => {
                    // Normalizamos el texto para evitar problemas con espacios o saltos de línea
                    const textoNormalizado = data.replace(/\s+/g, ' ').trim();

                    // Verificamos de forma más flexible si no hay notificaciones
                    if (textoNormalizado.includes('No hay notificaciones disponibles')) {
                        cargando = true; // Detenemos la carga adicional
                        return;
                    }

                    if (data) {
                        listaNotificaciones.insertAdjacentHTML('beforeend', data);
                        observarNotificaciones();
                        paginaActual++;
                    }
                    cargando = false;
                })
                .catch(err => {
                    cargando = false;
                });
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    // Función para verificar notificaciones no leídas usando long polling
    async function verificarNotificaciones() {
        try {
            const response = await enviarAjax('verificar_notificaciones');
            
            if (response.hay_no_vistas) {
                // Cambiar el color del ícono a rojo (#d43333)
                document.querySelector('#icono-notificaciones svg').setAttribute('fill', '#d43333');
            } else {
                // Cambiar el color de vuelta a su color por defecto (currentColor)
                document.querySelector('#icono-notificaciones svg').setAttribute('fill', 'currentColor');
            }

            // Iniciar otra verificación después de que se reciba una respuesta
            verificarNotificaciones();

        } catch (error) {
            console.error('Error en la verificación de notificaciones:', error);

            // En caso de error, esperar unos segundos antes de intentar de nuevo
            setTimeout(verificarNotificaciones, 30000);
        }
    }

    // Iniciar la verificación de notificaciones al cargar la página
    verificarNotificaciones();
});