<?php

// Archivo creado para contener la lógica de negocio para la sincronización de datos (ej: verificación de cambios).
// Refactor(Org): Crea archivo app/Services/SyncService.php para lógica de sincronización

class SyncService
{
    // TODO: Implementar la lógica de sincronización aquí.
}

// Refactor(Org): Función verificarCambiosAudios() movida desde app/Sync/api.php
function verificarCambiosAudios(WP_REST_Request $request)
{
    error_log('[verificarCambiosAudios] Inicio de la función');
    //aqui necesito que en caso de recibir 355, transformar a 1
    $user_id = $request->get_param('user_id');
    $force_sync = $request->get_param('force');

    if ($user_id == 355) {
        $user_id = 1;
    }

    $last_sync_timestamp = isset($_GET['last_sync']) ? intval($_GET['last_sync']) : 0;

    error_log("[verificarCambiosAudios] Parámetro recibido - user_id: {$user_id}");
    error_log("[verificarCambiosAudios] Parámetro recibido - last_sync_timestamp: {$last_sync_timestamp}");
    error_log("[verificarCambiosAudios] Parámetro recibido - force: {$force_sync}");

    global $wpdb;

    $descargas_timestamp = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value
        FROM {$wpdb->usermeta}
        WHERE user_id = %d AND meta_key = 'descargas_modificado'
    ", $user_id));
    $samples_timestamp = $wpdb->get_var($wpdb->prepare("
        SELECT meta_value
        FROM {$wpdb->usermeta}
        WHERE user_id = %d AND meta_key = 'samplesGuardados_modificado'
    ", $user_id));

    $descargas_timestamp = ($descargas_timestamp !== null) ? intval($descargas_timestamp) : 0;
    $samples_timestamp = ($samples_timestamp !== null) ? intval($samples_timestamp) : 0;

    error_log("[verificarCambiosAudios] Valor convertido - descargas_modificado: {$descargas_timestamp}");
    error_log("[verificarCambiosAudios] Valor convertido - samplesGuardados_modificado: {$samples_timestamp}");

    $response_data = [
        'descargas_modificado' => $descargas_timestamp,
        'samplesGuardados_modificado' => $samples_timestamp,
        'force_sync' => false, // Inicialmente asumimos que no se forzó la sincronización
    ];

    // Si se recibe el parámetro 'force' y es 'true', forzamos la sincronización
    if ($force_sync === 'true') {
        error_log("[verificarCambiosAudios] Se recibió el parámetro 'force=true'. Forzando la sincronización.");
        $response_data['descargas_modificado'] = time(); // Establecemos el timestamp actual para indicar cambio
        $response_data['samplesGuardados_modificado'] = time(); // Establecemos el timestamp actual para indicar cambio
        $response_data['force_sync'] = true; // Indicamos que se forzó la sincronización
    } else {
        // Lógica normal de comparación de timestamps si no se fuerza la sincronización
        if ($descargas_timestamp > $last_sync_timestamp || $samples_timestamp > $last_sync_timestamp) {
            error_log("[verificarCambiosAudios] Se detectaron cambios desde el último sync.");
        } else {
            error_log("[verificarCambiosAudios] No se detectaron cambios desde el último sync.");
        }
    }

    error_log("[verificarCambiosAudios] Datos de respuesta: " . json_encode($response_data));

    error_log('[verificarCambiosAudios] Fin de la función');
    return rest_ensure_response($response_data);
}
