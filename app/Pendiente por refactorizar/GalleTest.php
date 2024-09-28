<?php

function procesarMensajeTest() {
    $emisor = 1; // ID del emisor
    $receptor = 44; // ID del receptor
    
    // Obtener la ID actual del mensaje
    $mensaje_id = get_option('mensaje_test_id', 0);
    $mensaje_id++;
    
    $mensaje = "Este es un mensaje de prueba #" . $mensaje_id;
    $adjunto = null; // Sin adjunto
    $metadata = null; // Sin metadata

    chatLog("Enviando mensaje: $mensaje de $emisor a $receptor");
    $mensajeGuardadoID = guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);
    
    if ($mensajeGuardadoID !== false) {
        chatLog("Mensaje guardado exitosamente con ID: $mensajeGuardadoID");
        // Actualizar la ID del mensaje
        update_option('mensaje_test_id', $mensaje_id);
    } else {
        chatLog("Error al guardar el mensaje");
    }
}

function programar_mensaje_test() {
    if (!wp_next_scheduled('enviar_mensaje_test')) {
        wp_schedule_event(time(), 'minutely', 'enviar_mensaje_test');
    }
}

function enviar_mensaje_test() {
    procesarMensajeTest();
}

// Agregar el intervalo de un minuto si no existe
function agregar_intervalo_minutely($schedules) {
    if (!isset($schedules['minutely'])) {
        $schedules['minutely'] = array(
            'interval' => 60,
            'display' => __('Una vez por minuto')
        );
    }
    return $schedules;
}

// Hooks
add_action('init', 'programar_mensaje_test');
add_action('enviar_mensaje_test', 'enviar_mensaje_test');
add_filter('cron_schedules', 'agregar_intervalo_minutely');

// Asegúrate de que el evento se elimine cuando se desactive el plugin
register_deactivation_hook(__FILE__, 'desactivar_mensaje_test');

function desactivar_mensaje_test() {
    $timestamp = wp_next_scheduled('enviar_mensaje_test');
    wp_unschedule_event($timestamp, 'enviar_mensaje_test');
}