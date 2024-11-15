<?
/*
function procesarMensajeTest() {
    $emisor = 1; // ID del emisor
    $receptor = 2; // ID del receptor
    $mensaje_id = get_option('mensaje_test_id', 0);
    $mensaje_id++;
    $mensaje = "Este es un mensaje de prueba con un texto muy largo porque como es una prueba hay que verificar ciertas cosas blablabla #" . $mensaje_id;
    $adjunto = null; // Sin adjunto
    $metadata = null; // Sin metadata
    chatLog("Enviando mensaje: $mensaje de $emisor a $receptor");
    $mensajeGuardadoID = guardarMensaje($emisor, $receptor, $mensaje, $adjunto, $metadata);
    
    if ($mensajeGuardadoID !== false) {
        chatLog("Mensaje guardado exitosamente con ID: $mensajeGuardadoID");
        update_option('mensaje_test_id', $mensaje_id);
    } else {
        chatLog("Error al guardar el mensaje");
    }
}

function programarMensaje() {
    if (!wp_next_scheduled('enviarMensajeTest')) {
        wp_schedule_event(time(), 'minutely', 'enviarMensajeTest');
    }
}

function enviarMensajeTest() {
    procesarMensajeTest();
}

// Agregar el intervalo de un minuto si no existe
function intervaloMinuto($schedules) {
    if (!isset($schedules['minutely'])) {
        $schedules['minutely'] = array(
            'interval' => 60,
            'display' => __('Una vez por minuto')
        );
    }
    return $schedules;
}

add_action('init', 'programarMensaje');
add_action('enviarMensajeTest', 'enviarMensajeTest');
add_filter('cron_schedules', 'intervaloMinuto');
register_deactivation_hook(__FILE__, 'desactivarTestMensaje');

function desactivarTestMensaje() {
    $timestamp = wp_next_scheduled('enviarMensajeTest');
    wp_unschedule_event($timestamp, 'enviarMensajeTest');
}

*/