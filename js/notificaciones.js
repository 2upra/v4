function iniciarCargaNotificaciones() {
    let paginaActual = 1;
    let cargando = false;

    window.addEventListener('scroll', function () {
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 200 && !cargando) {
            cargando = true;
            paginaActual++;
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'cargar_notificaciones',
                    pagina: paginaActual
                })
            })
                .then(response => response.text())
                .then(data => {
                    if (data) {
                        document.querySelector('.notificaciones-lista').insertAdjacentHTML('beforeend', data);
                        cargando = false;
                    } else {
                        cargando = true;
                    }
                })
                .catch(error => {
                    console.log('Error al cargar las notificaciones.', error);
                });
        }
    });
}
