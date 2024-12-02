function iniciarCargaNotificaciones() {
    let paginaActual = 2,
        cargando = false;

    const marcarNotificacionVista = id => {
        console.log(`Marcando notificación vista, ID: ${id}`); // Log para verificar el ID de la notificación
        return fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'marcar_notificacion_vista', notificacionId: id})
        })
            .then(res => {
                if (res.ok) {
                    console.log('Notificación marcada como vista correctamente');
                } else {
                    console.error('Error al marcar la notificación como vista');
                }
            })
            .catch(err => console.error('Error de red al marcar notificación vista', err));
    };

    const observer = new IntersectionObserver(
        entradas => {
            console.log('IntersectionObserver activado'); // Log para verificar que el observer se activa
            entradas.forEach(e => {
                if (e.isIntersecting) {
                    console.log(`Elemento intersectado, data-notificacion-id: ${e.target.dataset.notificacionId}`); // Log del ID del elemento intersectado
                    const id = e.target.dataset.notificacionId;
                    if (id) {
                        marcarNotificacionVista(id);
                        observer.unobserve(e.target);
                        console.log(`Dejando de observar el elemento con ID: ${id}`); // Log para verificar que se deja de observar el elemento
                    }
                }
            });
        },
        {root: null, rootMargin: '0px', threshold: 0.1}
    );

    // Función para observar notificaciones nuevas o existentes
    const observarNotificaciones = () => {
        console.log('Observando nuevas notificaciones'); // Log para indicar que se están observando notificaciones
        document.querySelectorAll('.notificacion-item:not([data-observado="true"])').forEach(el => {
            console.log(`Observando elemento con ID: ${el.dataset.notificacionId}`); // Log para cada elemento que empieza a ser observado
            el.dataset.observado = 'true'; // Marca como observado
            observer.observe(el);
        });
    };

    // Observar notificaciones iniciales
    console.log('Iniciando observación de notificaciones iniciales'); // Log para el inicio del proceso
    observarNotificaciones();

    window.addEventListener('scroll', () => {
        console.log('Scroll detectado'); // Log para verificar que el evento de scroll se detecta
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 200 && !cargando) {
            console.log('Cargando más notificaciones...'); // Log para indicar que se va a cargar más notificaciones
            cargando = true;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'cargar_notificaciones', pagina: paginaActual++})
            })
                .then(res => res.text())
                .then(data => {
                    console.log('Notificaciones cargadas del servidor'); // Log para verificar que se obtuvieron datos del servidor
                    if (data) {
                        const lista = document.querySelector('.notificaciones-lista');
                        lista.insertAdjacentHTML('beforeend', data);
                        console.log('Notificaciones añadidas al DOM'); // Log para indicar que las notificaciones se agregaron al DOM
                        observarNotificaciones(); // Observar nuevas notificaciones
                        cargando = false;
                        console.log('Carga de notificaciones completada'); // Log para indicar que la carga terminó
                    }
                })
                .catch(err => {
                    console.error('Error cargando notificaciones', err); // Log en caso de error
                    cargando = false; // Restablecer el estado de carga para permitir reintentos
                });
        }
    });
}