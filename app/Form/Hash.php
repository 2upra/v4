<?php

function subidaArchivo()
{
    guardarLog("INICIO subidaArchivo");
    $is_admin = current_user_can('administrator');
    $file = $_FILES['file'] ?? null;
    $file_hash = sanitize_text_field($_POST['file_hash'] ?? '');

    // Verificar si se proporcionó archivo y hash
    if (!$file || !$file_hash) {
        guardarLog("No se proporcionó archivo o hash");
        wp_send_json_error('No se proporcionó archivo o hash');
        return;
    }

    guardarLog("Hash recibido: $file_hash");
    $existing_file = obtenerHash($file_hash);

    // Si el archivo ya existe
    if ($existing_file) {
        $file_id = $existing_file['id']; // Asumiendo que el ID está en el array de $existing_file

        // Si el archivo está pendiente y el usuario no es administrador
        if ($existing_file['status'] === 'pending' && !$is_admin) {
            guardarLog("El archivo ya está pendiente, reutilizando: " . $existing_file['file_url']);
            wp_send_json_success(array('fileUrl' => $existing_file['file_url'], 'fileId' => $file_id));
            return;
        }

        // Si el archivo está confirmado, no es necesario volver a subirlo
        if ($existing_file['status'] === 'confirmed') {
            guardarLog("El archivo ya está confirmado, reutilizando: " . $existing_file['file_url']);
            wp_send_json_success(array('fileUrl' => $existing_file['file_url'], 'fileId' => $file_id));
            return;
        }

        // Si es administrador, permitir el uso del archivo sin eliminarlo
        if ($is_admin) {
            guardarLog("El usuario es administrador, reutilizando archivo existente: " . $existing_file['file_url']);
            wp_send_json_success(array('fileUrl' => $existing_file['file_url'], 'fileId' => $file_id));
            return;
        }
    }

    guardarLog("No se encontró un archivo existente con este hash o el archivo está pendiente.");

    // Manejar la nueva subida de archivo
    $movefile = wp_handle_upload($file, array('test_form' => false, 'unique_filename_callback' => 'nombreUnicoFile'));
    guardarLog("Resultado de wp_handle_upload: " . print_r($movefile, true));

    if ($movefile && !isset($movefile['error'])) {
        $file_id = guardarHash($file_hash, $movefile['url'], 'pending');
        guardarLog("Carga exitosa. Hash guardado: $file_hash. URL del nuevo archivo: " . $movefile['url']);
        wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
    } else {
        guardarLog("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
        wp_send_json_error($movefile['error'] ?? 'Error desconocido');
    }

    guardarLog("FIN subidaArchivo");
}

function nombreUnicoFile($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

add_action('wp_ajax_file_upload', 'subidaArchivo');

function obtenerHash($file_hash)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1",
        $file_hash
    ), ARRAY_A);
}

function guardarHash($hash, $url, $status = 'pending')
{
    global $wpdb;
    $wpdb->insert(
        "{$wpdb->prefix}file_hashes",
        array(
            'file_hash' => $hash, 
            'file_url' => $url, 
            'status' => $status,
            'upload_date' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%s')
    );
    return $wpdb->insert_id;
}

function eliminarHash($file_hash)
{
    global $wpdb;
    return (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", array('file_hash' => $file_hash), array('%s'));
}

function confirmarArchivo($file_id)
{
    global $wpdb;
    return $wpdb->update(
        "{$wpdb->prefix}file_hashes",
        array('status' => 'confirmed'),
        array('id' => $file_id),
        array('%s'),
        array('%d')
    );
}

function limpiarArchivosPendientes()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'file_hashes';
    
    $archivos_pendientes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = 'pending' AND upload_date < %s",
            date('Y-m-d H:i:s', strtotime('-24 hours'))
        ),
        ARRAY_A
    );

    foreach ($archivos_pendientes as $archivo) {
        $file_path = str_replace(wp_get_upload_dir()['baseurl'], wp_get_upload_dir()['basedir'], $archivo['file_url']);
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        $wpdb->delete($table_name, array('id' => $archivo['id']));
        guardarLog("Archivo pendiente eliminado: " . $archivo['file_url']);
    }
}

if (!wp_next_scheduled('limpiar_archivos_pendientes')) {
    wp_schedule_event(time(), 'daily', 'limpiar_archivos_pendientes');
}
add_action('limpiar_archivos_pendientes', 'limpiarArchivosPendientes');
