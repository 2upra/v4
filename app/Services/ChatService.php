<?php

// Refactor: Función obtenerChats() movida desde app/Chat/renderListaChats.php
function obtenerChats($usuarioId, $pagina = 1, $resultadosPorPagina = 10)
{
    global $wpdb;
    $tablaConversacion = $wpdb->prefix . 'conversacion';
    $tablaMensajes = $wpdb->prefix . 'mensajes';

    // Calcular el offset para la paginación
    $offset = ($pagina - 1) * $resultadosPorPagina;

    // Obtener conversaciones que incluyan al usuario
    $query = $wpdb->prepare("
        SELECT id, participantes, fecha 
        FROM $tablaConversacion 
        WHERE JSON_CONTAINS(participantes, %s)
    ", json_encode($usuarioId));

    $conversaciones = $wpdb->get_results($query);

    if ($conversaciones) {
        foreach ($conversaciones as &$conversacion) {
            // Obtener el último mensaje de cada conversación incluyendo 'leido'
            $ultimoMensaje = $wpdb->get_row($wpdb->prepare("
                SELECT mensaje, fecha, emisor, COALESCE(leido, FALSE) AS leido
                FROM $tablaMensajes 
                WHERE conversacion = %d 
                ORDER BY fecha DESC
                LIMIT 1
            ", $conversacion->id));

            if ($ultimoMensaje) {
                // Limitar el mensaje a 32 caracteres
                if (mb_strlen($ultimoMensaje->mensaje) > 32) {
                    $ultimoMensaje->mensaje = mb_substr($ultimoMensaje->mensaje, 0, 32) . '...';
                }

                $conversacion->ultimoMensaje = $ultimoMensaje;
            } else {
                $conversacion->ultimoMensaje = null;
            }
        }

        // Ordenar las conversaciones por la fecha del último mensaje (descendente)
        usort($conversaciones, function ($a, $b) {
            $fechaA = isset($a->ultimoMensaje->fecha) ? strtotime($a->ultimoMensaje->fecha) : 0;
            $fechaB = isset($b->ultimoMensaje->fecha) ? strtotime($b->ultimoMensaje->fecha) : 0;
            return $fechaB - $fechaA;
        });

        // Aplicar paginación después de ordenar
        $conversaciones = array_slice($conversaciones, $offset, $resultadosPorPagina);
    }

    return $conversaciones;
}

// Refactor: Función infoUsuario() y su hook AJAX movidos desde app/Chat/auxiliares.php
function infoUsuario() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Usuario no autenticado.'));
        wp_die();
    }

    $receptor = isset($_POST['receptor']) ? intval($_POST['receptor']) : 0;

    if ($receptor <= 0) {
        wp_send_json_error(array('message' => 'ID del receptor inválido.'));
        wp_die();
    }

    // Asumiendo que UserUtils.php está incluido globalmente o donde se necesite
    // Si no, se necesitaría: require_once __DIR__ . '/../Utils/UserUtils.php';
    // Nota: Se asume que las funciones imagenPerfil() y obtenerNombreUsuario() están disponibles globalmente o en UserUtils.php
    $imagenPerfil = function_exists('imagenPerfil') ? imagenPerfil($receptor) : 'ruta_por_defecto.jpg';
    $nombreUsuario = function_exists('obtenerNombreUsuario') ? obtenerNombreUsuario($receptor) : 'Usuario Desconocido';

    if (ob_get_length()) {
        ob_end_clean();
    }

    wp_send_json_success(array(
        'imagenPerfil' => $imagenPerfil,
        'nombreUsuario' => $nombreUsuario
    ));

    wp_die();
}

add_action('wp_ajax_infoUsuario', 'infoUsuario');
