jQuery(document).ready(function ($) {
    window.manejoDeLogs = function () {
        var isAdmin = $("#user_is_admin").val() === "true";

        if (!isAdmin || typeof ajax_object === "undefined" || !ajax_object.ajaxurl) {
            return;
        }

        let intentosFallidos = 0;
        const MAX_INTENTOS = 3;
        let intervaloLogs;
        
        function actualizarLogs() {
            $.ajax({
                url: ajax_object.ajaxurl,
                method: "POST",
                data: {
                    action: "obtener_logs",
                },
                success: function (response) {
                    intentosFallidos = 0;
                    if (response.success) {
                        // Actualizar logs personalizados
                        var $customLogsList = $("#custom-logs-list");
                        if ($customLogsList.length && Array.isArray(response.data.custom_logs)) {
                            $customLogsList.empty();
                            response.data.custom_logs.reverse().forEach(function(log, index) {
                                $customLogsList.append("<li value='" + (index + 1) + "'>" + log + "</li>");
                            });
                        }

                        // Actualizar logs de WordPress
                        var $wpLogsList = $("#wp-logs-list");
                        if ($wpLogsList.length && Array.isArray(response.data.wp_logs)) {
                            $wpLogsList.empty();
                            response.data.wp_logs.reverse().forEach(function(log, index) {
                                $wpLogsList.append("<li value='" + (index + 1) + "'>" + log + "</li>");
                            });
                        }
                    }
                },
                error: function (jqXHR) {
                    if (jqXHR.status === 400) {
                        intentosFallidos++;
                        if (intentosFallidos >= MAX_INTENTOS) {
                            clearInterval(intervaloLogs);
                        }
                    }
                },
            });
        }

        function inicializar() {
            actualizarLogs();
            intervaloLogs = setInterval(actualizarLogs, 600000);
        }

        inicializar();
    };

    window.manejoDeLogs();
});