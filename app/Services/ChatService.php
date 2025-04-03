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
