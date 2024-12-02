function iniciarCargaNotificaciones() {
    let paginaActual = 2,
        cargando = false;

    const marcarNotificacionVista = id =>
        fetch(ajaxurl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'marcar_notificacion_vista', notificacionId: id})
        })
            .then(res => (res.ok ? console.log('Notificación vista') : console.error('Error marcando vista')))
            .catch(err => console.error('Error de red', err));

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

    // Función para observar notificaciones nuevas o existentes
    const observarNotificaciones = () => {
        document.querySelectorAll('.notificacion-item:not([data-observado="true"])').forEach(el => {
            el.dataset.observado = 'true'; // Marca como observado
            observer.observe(el);
        });
    };

    // Observar notificaciones iniciales
    observarNotificaciones();

    window.addEventListener('scroll', () => {
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 200 && !cargando) {
            cargando = true;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'cargar_notificaciones', pagina: paginaActual++})
            })
                .then(res => res.text())
                .then(data => {
                    if (data) {
                        const lista = document.querySelector('.notificaciones-lista');
                        lista.insertAdjacentHTML('beforeend', data);
                        observarNotificaciones(); // Observar nuevas notificaciones
                        cargando = false;
                    }
                })
                .catch(err => console.error('Error cargando notificaciones', err));
        }
    });
}
