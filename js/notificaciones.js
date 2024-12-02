function iniciarCargaNotificaciones() {
    let paginaActual = 2,
        cargando = false;

    const marcarNotificacionVista = id => {
        return fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'marcar_notificacion_vista', notificacionId: id})
        })
            .then(res => {
                if (!res.ok) {
                    console.error('Error al marcar la notificación como vista');
                }
            })
            .catch(err => console.error('Error de red al marcar notificación vista', err));
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
        {root: null, rootMargin: '0px', threshold: 0.1}
    );

    const observarNotificaciones = () => {
        document.querySelectorAll('.notificacion-item:not([data-observado="true"])').forEach(el => {
            el.dataset.observado = 'true';
            observer.observe(el);
        });
    };

    observarNotificaciones();

    window.addEventListener('scroll', () => {
        console.log('Evento scroll detectado.');
        console.log('Scroll Y:', window.scrollY);
        console.log('Window Height:', window.innerHeight);
        console.log('Document Height:', document.documentElement.scrollHeight);
        console.log('Cargando:', cargando);

        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 200 && !cargando) {
            console.log('Condición para cargar más notificaciones cumplida.');
            cargando = true;
            console.log('Cargando página:', paginaActual);
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'cargar_notificaciones', pagina: paginaActual})
            })
                .then(res => {
                    console.log('Respuesta recibida del servidor:', res.status);
                    if (!res.ok) {
                        console.error('Respuesta del servidor no fue OK:', res.statusText);
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return res.text();
                })
                .then(data => {
                    if (data) {
                        console.log('Datos recibidos para la página', paginaActual - 1, ':', data);
                        const lista = document.querySelector('.notificaciones-lista');
                        lista.insertAdjacentHTML('beforeend', data);
                        observarNotificaciones();
                        paginaActual++;
                        console.log('Página actual incrementada a:', paginaActual);
                    } else {
                        console.log('No se recibieron datos para la página', paginaActual - 1);
                    }
                    cargando = false;
                    console.log('Estado de carga restablecido a:', cargando);
                })
                .catch(err => {
                    console.error('Error cargando notificaciones:', err);
                    cargando = false;
                    console.log('Estado de carga restablecido a (error):', cargando);
                });
        } else {
            console.log('Condición para cargar más notificaciones no cumplida.');
        }
    });
}