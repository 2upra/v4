document.addEventListener("DOMContentLoaded", function() {
    const iconoNotificaciones = document.querySelector(".icono-notificaciones");
    if (iconoNotificaciones) {
        let tiempoDeEspera = 5000;
        const maxTiempoDeEspera = 30000;
        const incrementoTiempo = 5000;
        let intervalo;
        let estadoNotificaciones = false;

        function detenerPolling() {
            if (intervalo) clearInterval(intervalo);
        }

        function iniciarPolling() {
            detenerPolling(); 
            intervalo = setInterval(() => {
                actualizarIconoNotificaciones();
                tiempoDeEspera = Math.min(tiempoDeEspera + incrementoTiempo, maxTiempoDeEspera);
            }, tiempoDeEspera);
        }

        function resetearTiempoDeEspera() {
            tiempoDeEspera = 5000;
            iniciarPolling();
        }

        function actualizarIconoNotificaciones() {
            enviarAjax('verificar_notificaciones', { usuario_id: datosNotificaciones.usuarioID })
                .then(respuesta => {
                    if (respuesta.tiene_notificaciones && !estadoNotificaciones) {
                        iconoNotificaciones.classList.add('tiene-notificaciones');
                        estadoNotificaciones = true; 
                    } else if (!respuesta.tiene_notificaciones && estadoNotificaciones) {
                        iconoNotificaciones.classList.remove('tiene-notificaciones');
                        estadoNotificaciones = false;
                    }
                });
        }

        iconoNotificaciones.addEventListener("click", (event) => {
            event.stopPropagation();
            const notificacionesContainer = document.querySelector(".notificaciones-container");
            notificacionesContainer.classList.toggle("visible");

            if (notificacionesContainer.classList.contains("visible")) {
                enviarAjax('cargar_notificaciones', { usuario_id: datosNotificaciones.usuarioID })
                    .then(data => {
                        notificacionesContainer.innerHTML = data;
                        return enviarAjax('marcar_como_leidas', { usuario_id: datosNotificaciones.usuarioID });
                    })
                    .then(() => {
                        iconoNotificaciones.classList.remove('tiene-notificaciones');
                    });
            } else {
                actualizarIconoNotificaciones();
            }
        });

        document.addEventListener('mousemove', resetearTiempoDeEspera);
        document.addEventListener('keydown', resetearTiempoDeEspera);
        document.addEventListener('click', resetearTiempoDeEspera);

        document.addEventListener("click", (event) => {
            if (!event.target.closest(".icono-notificaciones, .notificaciones-container")) {
                document.querySelector(".notificaciones-container").classList.remove("visible");
            }
        });

        actualizarIconoNotificaciones();
        iniciarPolling();
        /*
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        enviarAjax('ajustar_zona_horaria', { timezone: timezone }); */
    }
});
