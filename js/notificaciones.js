jQuery(document).ready(function(jQuery) {
    if (jQuery(".icono-notificaciones").length > 0) {
        var tiempoDeEspera = 5000;
        var maxTiempoDeEspera = 30000;
        var incrementoTiempo = 5000;
        var intervalo;

        function detenerPolling() {
            if (intervalo) clearInterval(intervalo);
        }

        function iniciarPolling() {
            detenerPolling(); 
            intervalo = setInterval(function() {
                actualizarIconoNotificaciones();
                tiempoDeEspera = Math.min(tiempoDeEspera + incrementoTiempo, maxTiempoDeEspera);
            }, tiempoDeEspera);
        }

        function resetearTiempoDeEspera() {
            tiempoDeEspera = 5000;
            iniciarPolling();
        }

        actualizarIconoNotificaciones();
        iniciarPolling();

        jQuery(document).off('mousemove keydown click').on('mousemove keydown click', resetearTiempoDeEspera);

        jQuery(".icono-notificaciones").click(function(event){
            event.stopPropagation(); 
            jQuery(".notificaciones-container").toggle(); 

            if (jQuery(".notificaciones-container").is(":visible")) {
                jQuery.ajax({
                    url: datosNotificaciones.ajaxurl,
                    type: 'POST',
                    data: {
                        'action': 'cargar_notificaciones',
                        'usuario_id': datosNotificaciones.usuarioID
                    },
                    success: function(data) {
                        jQuery(".notificaciones-container").html(data);
                        jQuery.ajax({
                            url: datosNotificaciones.ajaxurl,
                            type: 'POST',
                            data: {
                                'action': 'marcar_como_leidas',
                                'usuario_id': datosNotificaciones.usuarioID
                            },
                            success: function() {
                                jQuery(".icono-notificaciones").removeClass('tiene-notificaciones');
                            }
                        });
                    }
                });
            } else {
                actualizarIconoNotificaciones();
            }
        });

        var estadoNotificaciones = false; 
        function actualizarIconoNotificaciones() {
            jQuery.ajax({
                url: datosNotificaciones.ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    'action': 'verificar_notificaciones',
                    'usuario_id': datosNotificaciones.usuarioID
                },
                success: function(respuesta) {
                    if(respuesta.tiene_notificaciones && !estadoNotificaciones) {
                        jQuery(".icono-notificaciones").addClass('tiene-notificaciones');
                        estadoNotificaciones = true; 
                    } else if(!respuesta.tiene_notificaciones && estadoNotificaciones) {
                        jQuery(".icono-notificaciones").removeClass('tiene-notificaciones');
                        estadoNotificaciones = false;
                    }
                }
            });
        }
    }


    var manejarClicFueraNotificaciones = function(event) {
        if (!jQuery(event.target).closest(".icono-notificaciones, .notificaciones-container").length) {
            jQuery(".notificaciones-container").hide();
        }
    };

    jQuery(document).off('click', manejarClicFueraNotificaciones).on('click', manejarClicFueraNotificaciones);

    var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
    jQuery.ajax({
        url: datosNotificaciones.ajaxurl, 
        type: 'POST',
        data: 'action=ajustar_zona_horaria&timezone=' + timezone,
        success: function(response) {
        },
        error: function(response) {
        }
    });
});