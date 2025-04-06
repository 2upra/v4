<?php
// Refactor(Org): Moved function guardarHash from HashUtils.php

function guardarHash($hash, $url, $user_id, $status = 'pending')
{
    global $wpdb;

    try {
        $wpdb->insert(
            "{$wpdb->prefix}file_hashes",
            array(
                'file_hash' => $hash,
                'file_url' => $url,
                'status' => $status,
                'user_id' => $user_id,
                'upload_date' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        return $wpdb->insert_id;
    } catch (Exception $e) {
        // Obtener el registro existente para verificar su estado
        $registro_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s",
            $hash
        ), ARRAY_A);

        // Si el estado es 'loss', eliminar el registro
        if ($registro_existente && $registro_existente['status'] === 'loss') {
            $wpdb->delete("{$wpdb->prefix}file_hashes", array('file_hash' => $hash), array('%s'));
        } else {
            //////guardarLog("Error: el hash existe y no está en estado 'loss'.");
            return false;
        }

        // Reintentar la inserción después de borrar el registro en estado 'loss'
        try {
            $wpdb->insert(
                "{$wpdb->prefix}file_hashes",
                array(
                    'file_hash' => $hash,
                    'file_url' => $url,
                    'status' => $status,
                    'user_id' => $user_id,
                    'upload_date' => current_time('mysql')
                ),
                array('%s', '%s', '%s', '%d', '%s')
            );
            return $wpdb->insert_id;
        } catch (Exception $e) {
            //////guardarLog("Error al intentar guardar el hash nuevamente: " . $e->getMessage());
            return false;
        }
    }
}

// Refactor(Org): Moved function actualizarEstadoArchivo from app/Utils/HashUtils.php
function actualizarEstadoArchivo($id, $estado)
{
    global $wpdb;

    try {
        ////guardarLog("Intentando actualizar estado del archivo ID: {$id} a {$estado}");
        $actualizado = $wpdb->update(
            "{$wpdb->prefix}file_hashes",
            ['status' => $estado],
            ['id' => $id],
            ['%s'],
            ['%d']
        );

        if ($actualizado === false) {
            throw new Exception("Error al actualizar estado para ID: " . $id);
        }

        ////guardarLog("Estado actualizado para ID {$id}: {$estado}");
        return true;
    } catch (Exception $e) {
        ////guardarLog("Error en actualizarEstadoArchivo: " . $e->getMessage());
        return false;
    }
}

// Refactor(Org): Moved function confirmarHashId from app/Utils/HashUtils.php
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

// Refactor(Org): Moved function obtenerHash from app/Auto/busquedaAudio.php
function obtenerHash($file_hash)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1",
        $file_hash
    ), ARRAY_A);
}

// Refactor(Org): Moved function handle_recalcular_hash() and its hook from app/Utils/HashUtils.php
function handle_recalcular_hash()
{
    try {
        if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(['message' => 'No se pudo subir el archivo o está corrupto.']);
        }
        $audio_file = $_FILES['audio_file'];
        $allowed_mime_types = ['audio/mpeg', 'audio/wav'];
        if (!in_array($audio_file['type'], $allowed_mime_types)) {
            wp_send_json_error(['message' => 'Tipo de archivo no permitido.']);
        }
        $upload_dir = wp_upload_dir();
        $temp_file_path = $upload_dir['path'] . '/' . basename($audio_file['name']);
        if (!move_uploaded_file($audio_file['tmp_name'], $temp_file_path)) {
            wp_send_json_error(['message' => 'Error al mover el archivo subido.']);
        }
        // Assuming recalcularHash is available globally or included/required
        $hash = recalcularHash($temp_file_path);
        if ($hash === false) {
            wp_send_json_error(['message' => 'Error al generar el hash del archivo.']);
        }
        unlink($temp_file_path);
        wp_send_json_success(['hash' => $hash]);
    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}
add_action('wp_ajax_recalcularHash', 'handle_recalcular_hash');

// Refactor(Org): Moved function actualizarUrlArchivo from app/Utils/HashUtils.php
function actualizarUrlArchivo($file_id, $new_url)
{
    global $wpdb;

    // Log del inicio de la operación
    ////guardarLog("Inicio de actualizarUrlArchivo para File ID: $file_id con nueva URL: $new_url");

    // Intentar actualizar la URL del archivo en la base de datos
    $resultado = $wpdb->update(
        "{$wpdb->prefix}file_hashes",
        array('file_url' => $new_url),  // Campos a actualizar
        array('id' => $file_id),        // Condición: ID del archivo
        array('%s'),                    // Formato del campo a actualizar
        array('%d')                     // Formato del campo condicional
    );

    if ($resultado !== false) {
        ////guardarLog("URL actualizada correctamente para File ID: $file_id");
    } else {
        ////guardarLog("Error al actualizar la URL para File ID: $file_id");
    }

    // Devolver el resultado de la actualización
    return $resultado;
}

// Refactor(Org): Moved function subidaArchivo() and its hook from app/Utils/HashUtils.php
//tengo una duda, en el caso de administrador, subo una imagen qeu se llama imagen.jpg, y la url de la imagen es asi https://2upra.com/wp-content/uploads/2024/12/imagen-1.jpg, y en guardo en la tabla de hash asi: https://2upra.com/wp-content/uploads/2024/12/imagen-1.jpg, o sea obviamente hay una diferencia entre imagen.jpg y imagen-1.jpg y a hay que averiguar si sucede en el caso de no administrador. Tambien he visto que en algunas opcaciones se borran las imagenes.
function subidaArchivo() {
    $is_admin = current_user_can('administrator');
    $current_user_id = get_current_user_id(); // Obtener ID del usuario actual
    $file = $_FILES['file'] ?? null;

    $file_hash = sanitize_text_field($_POST['file_hash'] ?? '');
    $is_chat = filter_var($_POST['chat'] ?? false, FILTER_VALIDATE_BOOLEAN); // Detectar si se recibe el parámetro 'chat'

    // Verificar si se proporcionó archivo y hash
    if (!$file || !$file_hash) {
        wp_send_json_error('No se proporcionó archivo o hash');
        return;
    }
    
    // Nota: La función obtenerHash() se llama aquí pero no fue movida a este archivo.
    // Asegúrate de que obtenerHash() esté disponible globalmente o ajústalo.
    $existing_file = obtenerHash($file_hash); 

    // Si el archivo ya existe en la base de datos
    if ($existing_file) {
        $file_id = $existing_file['id'];
        $file_url = $existing_file['file_url'];
        $owner_id = $existing_file['user_id']; // ID del usuario que subió el archivo

        // Verificar si el archivo realmente existe en el servidor
        $file_path = str_replace(get_site_url(), ABSPATH, $file_url); // Convertir URL a ruta absoluta
        if (!file_exists($file_path)) {
            // Si el archivo no existe en el servidor, permitir que se suba el archivo normalmente
            $upload_dir = wp_upload_dir();
            $custom_dir = $is_chat ? $upload_dir['basedir'] . '/chat_uploads' : $upload_dir['path']; // Usar ruta predeterminada si no es chat

            if (!file_exists($custom_dir)) {
                mkdir($custom_dir, 0755, true); // Crear la carpeta si no existe
            }

            // Filtro para establecer el directorio de subida personalizado solo si es chat
            if ($is_chat) {
                add_filter('upload_dir', function($dirs) use ($custom_dir) {
                    $dirs['path'] = $custom_dir;
                    $dirs['url'] = str_replace($dirs['basedir'], $dirs['baseurl'], $custom_dir);
                    $dirs['subdir'] = ''; // No necesitamos subdirectorios adicionales
                    return $dirs;
                });
            }

            $movefile = wp_handle_upload($file, array(
                'test_form' => false,
                'unique_filename_callback' => 'nombreUnicoFile', // Note: This function is now in FileUtils.php
            ));

            // Eliminar el filtro después de la carga si se aplicó
            if ($is_chat) {
                remove_filter('upload_dir', '__return_false');
            }

            if ($movefile && !isset($movefile['error'])) {
                // Si el archivo estaba registrado con user_id = 0, actualizarlo con el user_id actual
                if ($owner_id == 0) {
                    actualizarUrlArchivo($file_id, $movefile['url']); // Actualizar URL
                }
                wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
            } else {
                wp_send_json_error($movefile['error'] ?? 'Error desconocido');
            }

            return;
        }
        // Verificar si el archivo pertenece al usuario actual o si es administrador
        if ($owner_id != $current_user_id && !$is_admin) {
            wp_send_json_error('No tienes permiso para reutilizar este archivo');
            return;
        }
        // Si el archivo está pendiente y el usuario no es administrador
        if ($existing_file['status'] === 'pending' && !$is_admin) {
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
        // Si el archivo ya está confirmado, no es necesario volver a subirlo
        if ($existing_file['status'] === 'confirmed') {
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
        // Si es administrador, permitir el uso del archivo sin eliminarlo
        if ($is_admin) {
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
    }

    // Manejar la nueva subida de archivo
    $upload_dir = wp_upload_dir();
    $custom_dir = $is_chat ? $upload_dir['basedir'] . '/chat_uploads' : $upload_dir['path']; // Usar ruta predeterminada si no es chat

    if (!file_exists($custom_dir)) {
        mkdir($custom_dir, 0755, true); // Crear la carpeta si no existe
    }

    // Filtro para establecer el directorio de subida personalizado solo si es chat
    if ($is_chat) {
        add_filter('upload_dir', function($dirs) use ($custom_dir) {
            $dirs['path'] = $custom_dir;
            $dirs['url'] = str_replace($dirs['basedir'], $dirs['baseurl'], $custom_dir);
            $dirs['subdir'] = ''; // No necesitamos subdirectorios adicionales
            return $dirs;
        });
    }

    $movefile = wp_handle_upload($file, array(
        'test_form' => false,
        'unique_filename_callback' => 'nombreUnicoFile', // Note: This function is now in FileUtils.php
    ));

    // Eliminar el filtro después de la carga si se aplicó
    if ($is_chat) {
        remove_filter('upload_dir', '__return_false');
    }

    if ($movefile && !isset($movefile['error'])) {
        $file_id = guardarHash($file_hash, $movefile['url'], $current_user_id, 'pending');
        wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
    } else {
        wp_send_json_error($movefile['error'] ?? 'Error desconocido');
    }
}

add_action('wp_ajax_file_upload', 'subidaArchivo');

// Refactor(Org): Moved function eliminarPorHash from app/Utils/HashUtils.php
function eliminarPorHash($file_hash)
{
    global $wpdb;
    $resultado = (bool) $wpdb->delete(
        "{$wpdb->prefix}file_hashes",
        array('file_hash' => $file_hash),
        array('%s')
    );

    if ($resultado) {
        ////guardarLog("eliminarPorHash: Registro eliminado con hash: $file_hash");
    } else {
        ////guardarLog("eliminarPorHash: Error al eliminar el registro con hash: $file_hash");
    }

    return $resultado;
}

// Refactor(Org): Moved function eliminarHash from app/Utils/HashUtils.php
function eliminarHash($id)
{
    global $wpdb;
    $resultado = (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", array('id' => $id), array('%d'));
    if ($resultado) {
        ////guardarLog("eliminarHash: Registro eliminado con ID: $id");
    } else {
        ////guardarLog("eliminarHash: Error al eliminar el registro con ID: $id");
    }

    return $resultado;
}

// Refactor(Org): Moved function obtenerFileIDPorURL from app/Utils/HashUtils.php
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
        //////guardarLog("No se encontró File ID para la URL: $url");
        return false;
    }
}

// Refactor(Org): Moved function limpiarArchivosPendientes() and its hook/schedule from app/Utils/HashUtils.php
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
        ////guardarLog("Archivo pendiente eliminado: " . $archivo['file_url']);
    }
}

if (!wp_next_scheduled('limpiar_archivos_pendientes')) {
    wp_schedule_event(time(), 'daily', 'limpiar_archivos_pendientes');
}
add_action('limpiar_archivos_pendientes', 'limpiarArchivosPendientes');

?>
