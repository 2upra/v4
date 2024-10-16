<?

function subidaArchivo()
{
    guardarLog("INICIO subidaArchivo");
    $is_admin = current_user_can('administrator');
    $current_user_id = get_current_user_id(); // Obtener ID del usuario actual
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

    // Si el archivo ya existe en la base de datos
    if ($existing_file) {
        $file_id = $existing_file['id'];
        $file_url = $existing_file['file_url'];
        $owner_id = $existing_file['user_id']; // ID del usuario que subió el archivo

        // Verificar si el archivo realmente existe en el servidor
        $file_path = str_replace(get_site_url(), ABSPATH, $file_url); // Convertir URL a ruta absoluta
        if (!file_exists($file_path)) {
            guardarLog("El archivo no existe en el servidor: $file_url");

            // Si el archivo no existe en el servidor, permitir que se suba el archivo normalmente
            $movefile = wp_handle_upload($file, array('test_form' => false, 'unique_filename_callback' => 'nombreUnicoFile'));
            guardarLog("Resultado de wp_handle_upload: " . print_r($movefile, true));

            if ($movefile && !isset($movefile['error'])) {
                // Si el archivo estaba registrado con user_id = 0, actualizarlo con el user_id actual
                if ($owner_id == 0) {
                    guardarLog("Actualizando el user_id de $owner_id a $current_user_id para el archivo $file_id");
                    actualizarUrlArchivo($file_id, $movefile['url']); // Actualizar URL
                }
                guardarLog("Carga exitosa. URL del nuevo archivo: " . $movefile['url']);
                wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
            } else {
                guardarLog("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
                wp_send_json_error($movefile['error'] ?? 'Error desconocido');
            }

            return;
        }
        // Verificar si el archivo pertenece al usuario actual o si es administrador
        if ($owner_id != $current_user_id && !$is_admin) {
            guardarLog("El archivo no pertenece al usuario actual.");
            wp_send_json_error('No tienes permiso para reutilizar este archivo');
            return;
        }
        // Si el archivo está pendiente y el usuario no es administrador
        if ($existing_file['status'] === 'pending' && !$is_admin) {
            guardarLog("El archivo ya está pendiente, reutilizando: " . $existing_file['file_url']);
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
        // Si el archivo ya está confirmado, no es necesario volver a subirlo
        if ($existing_file['status'] === 'confirmed') {
            guardarLog("El archivo ya está confirmado, reutilizando: " . $file_url);
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
        // Si es administrador, permitir el uso del archivo sin eliminarlo
        if ($is_admin) {
            guardarLog("El usuario es administrador, reutilizando archivo existente: " . $file_url);
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
    }

    guardarLog("No se encontró un archivo existente con este hash o el archivo está pendiente.");

    // Manejar la nueva subida de archivo
    $movefile = wp_handle_upload($file, array('test_form' => false, 'unique_filename_callback' => 'nombreUnicoFile'));
    guardarLog("Resultado de wp_handle_upload: " . print_r($movefile, true));

    if ($movefile && !isset($movefile['error'])) {
        $file_id = guardarHash($file_hash, $movefile['url'], 'pending', $current_user_id);
        guardarLog("Carga exitosa. Hash guardado: $file_hash. URL del nuevo archivo: " . $movefile['url']);
        $file_path = $movefile['file']; // Ruta del archivo
        wp_schedule_single_event(time() + 5, 'antivirus', array($file_path, $file_id, $current_user_id)); 

        wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
    } else {
        guardarLog("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
        wp_send_json_error($movefile['error'] ?? 'Error desconocido');
    }

    guardarLog("FIN subidaArchivo");
}

function antivirus($file_path, $file_id, $current_user_id) {
    $command = escapeshellcmd("clamscan --infected --quiet " . $file_path);
    $output = shell_exec($command);

    if ($output) {
        unlink($file_path); // Elimina el archivo infectado
        guardarLog("Archivo infectado eliminado: $file_path");
        // Restringir al usuario que subió el archivo infectado
        restringir_usuario(array($current_user_id));
    } else {
        guardarLog("Archivo limpio confirmado: $file_path");
    }
}


// Programar la acción de WordPress
add_action('antivirus', 'antivirus', 10, 2);



function actualizarUrlArchivo($file_id, $new_url)
{
    global $wpdb;
    
    // Log del inicio de la operación
    guardarLog("Inicio de actualizarUrlArchivo para File ID: $file_id con nueva URL: $new_url");
    
    // Intentar actualizar la URL del archivo en la base de datos
    $resultado = $wpdb->update(
        "{$wpdb->prefix}file_hashes",
        array('file_url' => $new_url),  // Campos a actualizar
        array('id' => $file_id),        // Condición: ID del archivo
        array('%s'),                    // Formato del campo a actualizar
        array('%d')                     // Formato del campo condicional
    );

    if ($resultado !== false) {
        guardarLog("URL actualizada correctamente para File ID: $file_id");
    } else {
        guardarLog("Error al actualizar la URL para File ID: $file_id");
    }

    // Devolver el resultado de la actualización
    return $resultado;
}

function obtenerFileIDPorURL($url)
{
    global $wpdb;
    
    $file_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}file_hashes WHERE file_url = %s",
            $url
        )
    );

    if ($file_id !== null) {
        return (int) $file_id;
    } else {
        guardarLog("No se encontró File ID para la URL: $url");
        return false;
    }
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

function guardarHash($hash, $url, $status = 'pending', $user_id)
{
    global $wpdb;
    $wpdb->insert(
        "{$wpdb->prefix}file_hashes",
        array(
            'file_hash' => $hash, 
            'file_url' => $url, 
            'status' => $status,
            'user_id' => $user_id,  // Guardar el ID del usuario
            'upload_date' => current_time('mysql')
        ),
        array('%s', '%s', '%s', '%d', '%s')  // Añadir el formato de user_id
    );
    return $wpdb->insert_id;
}

function confirmarHashId($file_id)
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


function eliminarHash($file_hash)
{
    global $wpdb;
    $resultado = (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", array('file_hash' => $file_hash), array('%s'));
    
    if ($resultado) {
        guardarLog("Hash eliminado: $file_hash");
    } else {
        guardarLog("Error al eliminar el hash: $file_hash");
    }
    
    return $resultado;
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
