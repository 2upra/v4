function iniciarCargaNotificaciones() {
    let paginaActual = 2,
        cargando = false;
    const listaNotificaciones = document.querySelector('.notificaciones-lista.modal');

    if(!listaNotificaciones){
        console.error('No se encontró el elemento .notificaciones-lista.modal');
        return;
    }

    const marcarNotificacionVista = id => {
        return fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({action: 'marcar_notificacion_vista', notificacionId: id})
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
        {root: null, rootMargin: '0px', threshold: 0.1}
    );

    const observarNotificaciones = () => {
        listaNotificaciones.querySelectorAll('.notificacion-item:not([data-observado="true"])').forEach(el => {
            el.dataset.observado = 'true';
            observer.observe(el);
        });
    };

    observarNotificaciones();

    listaNotificaciones.addEventListener('scroll', () => {
       
        if (listaNotificaciones.scrollHeight - (listaNotificaciones.scrollTop + listaNotificaciones.clientHeight)  <= 200 && !cargando) {
            cargando = true;
           
            fetch(ajaxUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({action: 'cargar_notificaciones', pagina: paginaActual})
            })
                .then(res => {
                   
                    if (!res.ok) {
                        console.error('Respuesta del servidor no fue OK:', res.statusText);
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return res.text();
                })
                .then(data => {
                    if (data) {
                        listaNotificaciones.insertAdjacentHTML('beforeend', data);
                        observarNotificaciones();
                        paginaActual++;
                       
                    } else {
                        
                    }
                    cargando = false;
                    
                })
                .catch(err => {
                    console.error('Error cargando notificaciones:', err);
                    cargando = false;
                   
                });
        }
    });
}