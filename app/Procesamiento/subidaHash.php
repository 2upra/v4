<?

define('HASH_SCRIPT_PATH', '/var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/hashAudio.py');
define('PROCESO_DELAY', 500000); // 0.5 segundos en microsegundos
define('MAX_EXECUTION_TIME', 30); // 30 segundos por archivo
define('BATCH_SIZEHASH', 50); 
ini_set('memory_limit', '256M');
set_time_limit(0); 

if (!defined('HASH_SIMILARITY_THRESHOLD')) {
    define('HASH_SIMILARITY_THRESHOLD', 0.7);  // Ajusta el valor según tus necesidades
}
define('WRAPPER_SCRIPT_PATH', '/var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/process_audio.sh');


function sonHashesSimilares($hash1, $hash2, $umbral = HASH_SIMILARITY_THRESHOLD)
{
    if (empty($hash1) || empty($hash2)) {
        return false;
    }

    // Convertir hashes a valores binarios
    $bin1 = hex2bin($hash1);
    $bin2 = hex2bin($hash2);

    if ($bin1 === false || $bin2 === false) {
        return false;
    }

    // Calcular similitud usando distancia de Hamming
    $similitud = 1 - (count(array_diff_assoc(str_split($bin1), str_split($bin2))) / strlen($bin1));

    return $similitud >= $umbral;
}

function recalcularHash($audio_file_path) {
    try {
        // Verificaciones iniciales
        if (!filter_var($audio_file_path, FILTER_VALIDATE_URL)) {
            throw new Exception("URL inválida: " . $audio_file_path);
        }

        $upload_dir = wp_upload_dir();
        $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $audio_file_path);

        // Verificaciones de archivo
        if (!file_exists($file_path)) {
            throw new Exception("Archivo no encontrado: " . $file_path);
        }

        if (!is_readable($file_path)) {
            throw new Exception("No hay permisos de lectura para el archivo: " . $file_path);
        }

        // Verificar script wrapper
        if (!file_exists(WRAPPER_SCRIPT_PATH)) {
            throw new Exception("Script wrapper no encontrado en: " . WRAPPER_SCRIPT_PATH);
        }

        if (!is_executable(WRAPPER_SCRIPT_PATH)) {
            throw new Exception("Script wrapper no tiene permisos de ejecución: " . WRAPPER_SCRIPT_PATH);
        }

        // Ejecutar el comando
        $command = escapeshellarg(WRAPPER_SCRIPT_PATH) . ' ' . escapeshellarg($file_path);
        //guardarLog("Ejecutando comando: " . $command);

        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        
        $process = proc_open($command, $descriptorspec, $pipes);
        
        if (!is_resource($process)) {
            throw new Exception("No se pudo iniciar el proceso");
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        
        foreach ($pipes as $pipe) {
            fclose($pipe);
        }
        
        $return_value = proc_close($process);
        
        if ($return_value !== 0) {
            throw new Exception("Error en el proceso Python: " . $error);
        }
        
        $hash = trim($output);
        if (!preg_match('/^[a-f0-9]{64}$/', $hash)) {
            throw new Exception("Hash inválido generado: " . $output);
        }
        
        //guardarLog("Hash calculado correctamente: " . $hash);
        return $hash;

    } catch (Exception $e) {
        //guardarLog("Error en recalcularHash: " . $e->getMessage());
        return false;
    }
}



function actualizarHashesDeTodosLosAudios()
{
    global $wpdb;

    try {
        // Configurar timeout y modo de transacción
        $wpdb->query("SET innodb_lock_wait_timeout = 100");
        $wpdb->query("START TRANSACTION");

        // Consulta sin bloqueo explícito de tablas
        $query = $wpdb->prepare("
            SELECT fh.id, fh.file_url, fh.status, p.ID as post_id 
            FROM {$wpdb->prefix}file_hashes fh
            LEFT JOIN {$wpdb->posts} p ON p.guid = fh.file_url
            WHERE fh.file_url LIKE %s
            AND (fh.status IS NULL 
                 OR fh.status = 'pending' 
                 OR fh.status = 'error')
            ORDER BY fh.id DESC
            LIMIT %d",
            '%.wav',
            BATCH_SIZEHASH
        );

        $audios = $wpdb->get_results($query);

        if (empty($audios)) {
            $wpdb->query("COMMIT");
            return true;
        }

        foreach ($audios as $audio) {
            // Bloquear solo el registro actual
            $wpdb->query($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}file_hashes WHERE id = %d FOR UPDATE",
                $audio->id
            ));

            if ($audio->status !== 'pending') {
                actualizarEstadoArchivo($audio->id, 'pending');
            }

            $nuevo_hash = recalcularHash($audio->file_url);

            if (!$nuevo_hash) {
                actualizarEstadoArchivo($audio->id, 'error');
                continue;
            }

            // Verificar duplicados existentes
            $hash_existente = $wpdb->get_var($wpdb->prepare("
                SELECT id 
                FROM {$wpdb->prefix}file_hashes 
                WHERE file_hash = %s 
                AND id != %d
                LIMIT 1",
                $nuevo_hash,
                $audio->id
            ));

            if ($hash_existente) {
                actualizarEstadoArchivo($audio->id, 'duplicado');
                continue;
            }

            // Buscar similitudes solo en archivos confirmados
            $duplicados = $wpdb->get_results($wpdb->prepare("
                SELECT id, file_url, file_hash 
                FROM {$wpdb->prefix}file_hashes 
                WHERE id != %d 
                AND status = 'confirmed'
                AND file_url LIKE '%.wav'",
                $audio->id
            ));

            $es_duplicado = false;
            $duplicado_info = null;

            foreach ($duplicados as $posible_duplicado) {
                if (sonHashesSimilares($nuevo_hash, $posible_duplicado->file_hash)) {
                    $es_duplicado = true;
                    $duplicado_info = $posible_duplicado;
                    break;
                }
            }

            // Actualizar registro
            $resultado = $wpdb->update(
                "{$wpdb->prefix}file_hashes",
                [
                    'file_hash' => $nuevo_hash,
                    'status' => $es_duplicado ? 'duplicado' : 'confirmed'
                ],
                ['id' => $audio->id],
                ['%s', '%s'],
                ['%d']
            );

            if ($resultado === false) {
                throw new Exception("Error actualizando registro ID: {$audio->id}");
            }

            // Actualizar meta si es necesario
            if ($audio->post_id && $es_duplicado) {
                update_post_meta($audio->post_id, '_audio_duplicado', [
                    'duplicado_de' => $duplicado_info->file_url,
                    'hash' => $nuevo_hash,
                    'similitud' => true
                ]);
            }

            gc_collect_cycles();
            usleep(PROCESO_DELAY);
        }

        $wpdb->query("COMMIT");
        return true;

    } catch (Exception $e) {
        $wpdb->query("ROLLBACK");
        error_log("Error en actualizarHashesDeTodosLosAudios: " . $e->getMessage());
        return false;
    }
}
actualizarHashesDeTodosLosAudios();

function actualizarEstadoArchivo($id, $estado)
{
    global $wpdb;

    try {
        //guardarLog("Intentando actualizar estado del archivo ID: {$id} a {$estado}");
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

        guardarLog("Estado actualizado para ID {$id}: {$estado}");
        return true;
    } catch (Exception $e) {
        //guardarLog("Error en actualizarEstadoArchivo: " . $e->getMessage());
        return false;
    }
}

function subidaArchivo()
{
    //guardarLog("INICIO subidaArchivo");
    $is_admin = current_user_can('administrator');
    $current_user_id = get_current_user_id(); // Obtener ID del usuario actual
    $file = $_FILES['file'] ?? null;
    $file_hash = sanitize_text_field($_POST['file_hash'] ?? '');

    // Verificar si se proporcionó archivo y hash
    if (!$file || !$file_hash) {
        //guardarLog("No se proporcionó archivo o hash");
        wp_send_json_error('No se proporcionó archivo o hash');
        return;
    }

    //guardarLog("Hash recibido: $file_hash");
    $existing_file = obtenerHash($file_hash);

    // Si el archivo ya existe en la base de datos
    if ($existing_file) {
        $file_id = $existing_file['id'];
        $file_url = $existing_file['file_url'];
        $owner_id = $existing_file['user_id']; // ID del usuario que subió el archivo

        // Verificar si el archivo realmente existe en el servidor
        $file_path = str_replace(get_site_url(), ABSPATH, $file_url); // Convertir URL a ruta absoluta
        if (!file_exists($file_path)) {
            //guardarLog("El archivo no existe en el servidor: $file_url");

            // Si el archivo no existe en el servidor, permitir que se suba el archivo normalmente
            $movefile = wp_handle_upload($file, array('test_form' => false, 'unique_filename_callback' => 'nombreUnicoFile'));
            //guardarLog("Resultado de wp_handle_upload: " . print_r($movefile, true));

            if ($movefile && !isset($movefile['error'])) {
                // Si el archivo estaba registrado con user_id = 0, actualizarlo con el user_id actual
                if ($owner_id == 0) {
                    //guardarLog("Actualizando el user_id de $owner_id a $current_user_id para el archivo $file_id");
                    actualizarUrlArchivo($file_id, $movefile['url']); // Actualizar URL
                }
                //guardarLog("Carga exitosa. URL del nuevo archivo: " . $movefile['url']);
                wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
            } else {
                //guardarLog("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
                wp_send_json_error($movefile['error'] ?? 'Error desconocido');
            }

            return;
        }
        // Verificar si el archivo pertenece al usuario actual o si es administrador
        if ($owner_id != $current_user_id && !$is_admin) {
            //guardarLog("El archivo no pertenece al usuario actual.");
            wp_send_json_error('No tienes permiso para reutilizar este archivo');
            return;
        }
        // Si el archivo está pendiente y el usuario no es administrador
        if ($existing_file['status'] === 'pending' && !$is_admin) {
            //guardarLog("El archivo ya está pendiente, reutilizando: " . $existing_file['file_url']);
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
        // Si el archivo ya está confirmado, no es necesario volver a subirlo
        if ($existing_file['status'] === 'confirmed') {
            //guardarLog("El archivo ya está confirmado, reutilizando: " . $file_url);
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
        // Si es administrador, permitir el uso del archivo sin eliminarlo
        if ($is_admin) {
            //guardarLog("El usuario es administrador, reutilizando archivo existente: " . $file_url);
            wp_send_json_success(array('fileUrl' => $file_url, 'fileId' => $file_id));
            return;
        }
    }

    //guardarLog("No se encontró un archivo existente con este hash o el archivo está pendiente.");

    // Manejar la nueva subida de archivo
    $movefile = wp_handle_upload($file, array('test_form' => false, 'unique_filename_callback' => 'nombreUnicoFile'));
    //guardarLog("Resultado de wp_handle_upload: " . print_r($movefile, true));

    if ($movefile && !isset($movefile['error'])) {
        $file_id = guardarHash($file_hash, $movefile['url'], $current_user_id, 'pending');
        //guardarLog("Carga exitosa. Hash guardado: $file_hash. URL del nuevo archivo: " . $movefile['url']);
        $file_path = $movefile['file']; // Ruta del archivo
        wp_schedule_single_event(time() + 5, 'antivirus', array($file_path, $file_id, $current_user_id));

        wp_send_json_success(array('fileUrl' => $movefile['url'], 'fileId' => $file_id));
    } else {
        //guardarLog("Error en la carga: " . ($movefile['error'] ?? 'Error desconocido'));
        wp_send_json_error($movefile['error'] ?? 'Error desconocido');
    }

    //guardarLog("FIN subidaArchivo");
}

function antivirus($file_path, $file_id, $current_user_id)
{
    $command = escapeshellcmd("clamscan --infected --quiet " . $file_path);
    $output = shell_exec($command);

    if ($output) {
        unlink($file_path); // Elimina el archivo infectado
        //guardarLog("Archivo infectado eliminado: $file_path");
        // Restringir al usuario que subió el archivo infectado
        restringir_usuario(array($current_user_id));
    } else {
        //guardarLog("Archivo limpio confirmado: $file_path");
    }
}


// Programar la acción de WordPress
add_action('antivirus', 'antivirus', 10, 2);






/*
[26-Oct-2024 16:20:32 UTC] PHP Deprecated:  Optional parameter $status declared before required parameter $user_id is implicitly treated as a required parameter in /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/subidaHash.php on line 184
*/
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
            ////guardarLog("Error: el hash existe y no está en estado 'loss'.");
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
            ////guardarLog("Error al intentar guardar el hash nuevamente: " . $e->getMessage());
            return false;
        }
    }
}



function actualizarUrlArchivo($file_id, $new_url)
{
    global $wpdb;

    // Log del inicio de la operación
    //guardarLog("Inicio de actualizarUrlArchivo para File ID: $file_id con nueva URL: $new_url");

    // Intentar actualizar la URL del archivo en la base de datos
    $resultado = $wpdb->update(
        "{$wpdb->prefix}file_hashes",
        array('file_url' => $new_url),  // Campos a actualizar
        array('id' => $file_id),        // Condición: ID del archivo
        array('%s'),                    // Formato del campo a actualizar
        array('%d')                     // Formato del campo condicional
    );

    if ($resultado !== false) {
        //guardarLog("URL actualizada correctamente para File ID: $file_id");
    } else {
        //guardarLog("Error al actualizar la URL para File ID: $file_id");
    }

    // Devolver el resultado de la actualización
    return $resultado;
}





function nombreUnicoFile($dir, $name, $ext)
{
    return basename($name, $ext) . $ext;
}

add_action('wp_ajax_file_upload', 'subidaArchivo');



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



function eliminarHash($id)
{
    global $wpdb;
    $resultado = (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", array('id' => $id), array('%d'));
    if ($resultado) {
        //guardarLog("eliminarHash: Registro eliminado con ID: $id");
    } else {
        //guardarLog("eliminarHash: Error al eliminar el registro con ID: $id");
    }

    return $resultado;
}

function eliminarPorHash($file_hash)
{
    global $wpdb;
    $resultado = (bool) $wpdb->delete(
        "{$wpdb->prefix}file_hashes",
        array('file_hash' => $file_hash),
        array('%s')
    );

    if ($resultado) {
        //guardarLog("eliminarPorHash: Registro eliminado con hash: $file_hash");
    } else {
        //guardarLog("eliminarPorHash: Error al eliminar el registro con hash: $file_hash");
    }

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
        ////guardarLog("No se encontró File ID para la URL: $url");
        return false;
    }
}

// Ejecutar la función


/*
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
        //guardarLog("Archivo pendiente eliminado: " . $archivo['file_url']);
    }
}

if (!wp_next_scheduled('limpiar_archivos_pendientes')) {
    wp_schedule_event(time(), 'daily', 'limpiar_archivos_pendientes');
}
add_action('limpiar_archivos_pendientes', 'limpiarArchivosPendientes');
*/