<?php
function shortcode_mostrar_logs_para_admin() {
    if (current_user_can('administrator')) {
        ob_start();
        ?>
        <div id="admin-logs" class="admin-logs1">
            <h3>Logs Personalizados</h3>
            <ol reversed id="custom-logs-list"></ol>
        </div>
        <?php
        return ob_get_clean();
    }
    return '';
}
add_shortcode('mostrar_logs_para_admin', 'shortcode_mostrar_logs_para_admin');

function shortcode_mostrar_logs_para_admin_w() {
    if (current_user_can('administrator')) {
        ob_start();
        ?>
        <div id="admin-logs-w" class="admin-logs1-w">
            <h3>Logs de WordPress</h3>
            <ol reversed id="wp-logs-list"></ol>
        </div>
        <?php
        return ob_get_clean();
    }
    return '';
}
add_shortcode('mostrar_logs_para_admin_w', 'shortcode_mostrar_logs_para_admin_w');


function obtener_logs_ajax() {
    if (current_user_can('administrator')) {
        // Limitar el número de logs personalizados y verificar su validez
        $custom_logs = get_option('wanlog_logs', []);
        if (!is_array($custom_logs)) {
            $custom_logs = [];
        }
        $custom_logs = array_slice($custom_logs, -100);

        // Obtener logs de WordPress si el archivo existe
        $wp_logs = [];
        $log_file = WP_CONTENT_DIR . '/debug.log';

        if (file_exists($log_file) && is_readable($log_file)) {
            $wp_logs = tail($log_file, 100);
        }

        // Combinar los logs
        wp_send_json_success([
            'custom_logs' => $custom_logs,
            'wp_logs' => $wp_logs
        ]);
    } else {
        wp_send_json_error('No tienes permisos para ver los logs');
    }
}
add_action('wp_ajax_obtener_logs', 'obtener_logs_ajax');

function tail($filename, $lines = 100, $buffer = 4096) {
    $f = fopen($filename, "rb");
    if (!$f) return []; // Si no se puede abrir el archivo, retornar un array vacío

    fseek($f, 0, SEEK_END);
    $position = ftell($f);
    $output = '';
    $lines--; // Ajustar por la última línea

    while ($position > 0 && $lines >= 0) {
        $seek = min($position, $buffer);
        $position -= $seek;
        fseek($f, $position);

        $chunk = fread($f, $seek);
        $output = $chunk . $output;

        $lines -= substr_count($chunk, "\n");
    }

    fclose($f);

    // Retornar solo las últimas $lines líneas en caso de que haya leído más
    return array_slice(explode("\n", trim($output)), -($lines + 1));
}



/* EL JS

jQuery(document).ready(function ($) {
    window.manejoDeLogs = function () {
        var isAdmin = $("#user_is_admin").val() === "true";

        if (
            !isAdmin ||
            typeof ajax_object === "undefined" ||
            !ajax_object.ajaxurl
        ) {
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
                        var logsHtml = "";

                        // Función para generar HTML de logs numerados inversamente
                        function generarLogsHtml(logs, titulo) {
                            var html = "<h3>" + titulo + "</h3><ol reversed>";
                            for (var i = logs.length - 1; i >= 0; i--) {
                                html +=
                                    "<li value='" +
                                    (logs.length - i) +
                                    "'>" +
                                    logs[i] +
                                    "</li>";
                            }
                            html += "</ol>";
                            return html;
                        }

                        // Logs personalizados
                        if (Array.isArray(response.data.custom_logs)) {
                            logsHtml += generarLogsHtml(
                                response.data.custom_logs,
                                "Logs Personalizados"
                            );
                        }

                        // Logs de WordPress
                        if (Array.isArray(response.data.wp_logs)) {
                            logsHtml += generarLogsHtml(
                                response.data.wp_logs,
                                "Logs de WordPress"
                            );
                        }

                        var $adminLogs = $("#admin-logs");
                        if ($adminLogs.length) {
                            $adminLogs.html(logsHtml);
                            $adminLogs.scrollTop($adminLogs[0].scrollHeight);
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
            intervaloLogs = setInterval(actualizarLogs, 5000);
        }

        inicializar();
    };

    window.manejoDeLogs();
});
 */

function logs() {
    wp_enqueue_script('logs', get_template_directory_uri() . '/js/logs.js', array('jquery'), '1.1.7', true);
    wp_localize_script('logs', 'ajax_object', array('ajaxurl' => admin_url('admin-ajax.php')));
}
add_action('wp_enqueue_scripts', 'logs');