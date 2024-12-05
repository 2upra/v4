/*
tengo este error 

genericAjax.js?ver=3.0.16:743 Error en la solicitud AJAX: Objectaction: "marcar_notificacion_vista"ajaxUrl: "/wp-admin/admin-ajax.php"error: Error: HTTP error! status: 500 - 
    at enviarAjax (https://2upra.com/wp-content/themes/2upra3v/js/genericAjax.js?ver=3.0.16:726:19)requestData: {notificacionId: '322771'}notificacionId: "322771"[[Prototype]]: Object[[Prototype]]: Object
enviarAjax @ genericAjax.js?ver=3.0.16:743Understand this errorAI
notificaciones.js?ver=3.0.16:11 Error al marcar la notificación como vista: HTTP error! status: 500 - 

en el servidor


add_action('wp_ajax_marcar_notificacion_vista', 'marcarNotificacionVista');
function marcarNotificacionVista() {
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'No tienes permiso para realizar esta acción.'], 403);
    }

    $notificacionId = isset($_POST['notificacionId']) ? intval($_POST['notificacionId']) : 0;
    
    if ($notificacionId <= 0 || !get_post($notificacionId)) {
        wp_send_json_error(['message' => 'El ID de la notificación no es válido.'], 400);
    }

    $actualizado = update_post_meta($notificacionId, 'visto', 1);
    if ($actualizado === false) {
        wp_send_json_error(['message' => 'No se pudo actualizar la meta de la notificación.'], 500);
    }
    wp_send_json_success(['message' => 'Notificación marcada como vista.', 'notificacionId' => $notificacionId]);
}

en el cliente:

*/

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