<?

/*
import os
import librosa
import numpy as np
import hashlib

print("Inicio del script")  # Confirmación de que el script comienza

def calcular_hash_audio(audio_path):
    try:
        print(f"Procesando archivo: {audio_path}")  # Depuración de ruta de archivo
        
        # Verificar si el archivo existe
        if not os.path.exists(audio_path):
            print(f"Archivo no encontrado: {audio_path}")
            return None

        # Intentar cargar el archivo
        try:
            y, sr = librosa.load(audio_path, sr=None)
            print("Archivo cargado exitosamente")  # Confirmación de carga
        except Exception as load_error:
            print(f"Error cargando el archivo de audio: {audio_path}, {load_error}")
            return None

        # Confirmación de inicio del procesamiento de espectrograma
        print("Iniciando cálculo de espectrograma")
        mel_spectrogram = librosa.feature.melspectrogram(y=y, sr=sr)
        print("Espectrograma calculado")  # Confirmación de espectrograma
        
        log_mel_spectrogram = librosa.power_to_db(mel_spectrogram, ref=np.max)
        print("Espectrograma logarítmico calculado")  # Confirmación de espectrograma logarítmico
        
        mel_bytes = log_mel_spectrogram.tobytes()
        print("Espectrograma convertido a bytes")  # Confirmación de conversión a bytes
        
        hash_obj = hashlib.sha256(mel_bytes)
        print("Hash generado exitosamente")  # Confirmación de hash
        
        return hash_obj.hexdigest()

    except Exception as e:
        print(f"Error en el procesamiento de hash para el archivo {audio_path}: {e}")
        return None



*/

function recalcularHash($audio_file_path) {
    // Verificar si la URL es válida
    if (!filter_var($audio_file_path, FILTER_VALIDATE_URL)) {
        guardarLog("URL inválida: " . $audio_file_path);
        return false;
    }

    // Convertir URL a ruta del sistema de archivos
    $upload_dir = wp_upload_dir();
    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $audio_file_path);

    // Verificar si el archivo existe físicamente
    if (!file_exists($file_path)) {
        guardarLog("Archivo no encontrado: " . $file_path);
        return false;
    }

    // Crear y ejecutar el comando
    $command = escapeshellcmd("python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/hashAudio.py") . ' ' . escapeshellarg($file_path);
    guardarLog("Ejecutando comando: " . $command);

    // Ejecutar el comando
    $output = shell_exec($command . ' 2>&1');
    
    // Limpiar la salida y verificar que sea un hash válido
    $hash = trim($output);
    
    // Verificar que el resultado sea un hash SHA-256 válido (64 caracteres hexadecimales)
    if (preg_match('/^[a-f0-9]{64}$/', $hash)) {
        guardarLog("Hash calculado correctamente: " . $hash);
        return $hash;
    } else {
        guardarLog("Error al calcular el hash. Salida: " . $output);
        return false;
    }
}

/*
2024-10-27 22:46:01 - Audio ID: 11842 marcado como pendiente.
2024-10-27 22:46:01 - Ejecutando comando: python3 /var/www/wordpress/wp-content/themes/2upra3v/app/Procesamiento/hashAudio.py '/var/www/wordpress/wp-content/uploads/2024/10/Memphis-Kick_BJ03_2upra.wav'
2024-10-27 22:46:01 - Resultado del comando: Inicio del script
2024-10-27 22:46:01 - Hash calculado para el audio ID: 11842 - Duplicado: Sí
2024-10-27 22:46:01 - Audio ID: 11842 actualizado a estado: duplicado
2024-10-27 22:46:01 - Duplicado encontrado - Original: https://2upra.com/wp-content/uploads/2024/10/Memphis-Snare_G0Wx_2upra.wav, Duplicado: https://2upra.com/wp-content/uploads/2024/10/Memphis-Kick_BJ03_2upra.wav

ves que esta mal, tampoco marca el estado duplicado en la base de datus y se queda en bucle e incluso en la base datos en un hash vi que guardo "Inicio del script" en vez de hash, añade un log para ver que hash guarda y arregla todo
*/

function actualizarHashesDeTodosLosAudios() {
    global $wpdb;

    // Obtener solo registros que necesitan procesamiento
    $audios = $wpdb->get_results("
        SELECT fh.id, fh.file_url, fh.status, p.ID as post_id 
        FROM {$wpdb->prefix}file_hashes fh
        LEFT JOIN {$wpdb->posts} p ON p.guid = fh.file_url
        WHERE fh.file_url LIKE '%.wav'
        AND (fh.status IS NULL 
             OR fh.status = 'pending' 
             OR fh.status = 'error')
        ORDER BY fh.id DESC
    ");

    guardarLog("Audios a procesar: " . count($audios));

    foreach ($audios as $audio) {
        guardarLog("Procesando Audio ID: " . $audio->id . " (Estado actual: " . $audio->status . ")");

        // Marcar como pending solo si no está ya en ese estado
        if ($audio->status !== 'pending') {
            actualizarEstadoArchivo($audio->id, 'pending');
            guardarLog("Audio ID: " . $audio->id . " marcado como pendiente.");
        }

        // Recalcular el hash
        $nuevo_hash = recalcularHash($audio->file_url);
        
        if (!$nuevo_hash) {
            guardarLog("Error al calcular hash para Audio ID: " . $audio->id);
            actualizarEstadoArchivo($audio->id, 'error');
            continue;
        }

        // Verificar duplicados solo contra archivos confirmados
        $duplicado = $wpdb->get_row($wpdb->prepare("
            SELECT id, file_url 
            FROM {$wpdb->prefix}file_hashes 
            WHERE file_hash = %s 
            AND id != %d 
            AND status = 'confirmed'
            LIMIT 1
        ", $nuevo_hash, $audio->id));

        $nuevo_estado = $duplicado ? 'duplicado' : 'confirmed';
        
        // Actualizar hash y estado
        $actualizado = $wpdb->update(
            "{$wpdb->prefix}file_hashes",
            array(
                'file_hash' => $nuevo_hash,
                'status' => $nuevo_estado
            ),
            array('id' => $audio->id),
            array('%s', '%s'),
            array('%d')
        );

        guardarLog("Audio ID: " . $audio->id . " - Hash: " . $nuevo_hash . " - Estado: " . $nuevo_estado);

        if ($actualizado === false) {
            guardarLog("Error al actualizar en base de datos Audio ID: " . $audio->id);
            continue;
        }

        // Actualizar meta si es duplicado
        if ($audio->post_id && $duplicado) {
            update_post_meta($audio->post_id, '_audio_duplicado', array(
                'duplicado_de' => $duplicado->file_url,
                'hash' => $nuevo_hash
            ));
            guardarLog("Duplicado encontrado - Original: {$duplicado->file_url}, Duplicado: {$audio->file_url}");
        }

        // Delay para no sobrecargar
        usleep(500000); // 0.5 segundos
    }
    
    guardarLog("Proceso completado");
}

#actualizarHashesDeTodosLosAudios();

//tengo esta funcion que tal vez sirva de referencia
function actualizarEstadoArchivo($file_id, $status)
{
    global $wpdb;
    $wpdb->update(
        "{$wpdb->prefix}file_hashes",
        array('status' => $status), // Nuevo estado
        array('id' => $file_id), // Condición de ID
        array('%s'), // Formato del estado
        array('%d')  // Formato del ID
    );
}


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
        $file_id = guardarHash($file_hash, $movefile['url'], $current_user_id, 'pending');
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

function verificarCargaArchivoPorHash($file_hash)
{
    // Obtener los detalles del archivo usando el hash
    $archivo = obtenerHash($file_hash);
    
    if (!$archivo) {
        //guardarLog("No se encontró ningún archivo con el hash: $file_hash");
        return false;
    }
    
    $file_id = $archivo['id'];
    $file_url = $archivo['file_url'];
    
    //guardarLog("Iniciando verificación de carga para File ID: $file_id con URL: $file_url");
    
    // Inicializar cURL
    $ch = curl_init($file_url);
    
    // Configurar opciones de cURL para realizar una solicitud HEAD
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Tiempo de espera de 10 segundos
    
    // Ejecutar la solicitud
    curl_exec($ch);
    
    // Obtener el código de respuesta HTTP
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Cerrar la sesión de cURL
    curl_close($ch);
    
    // Verificar el código de respuesta
    if ($http_code >= 200 && $http_code < 300) {
        //guardarLog("El archivo con File ID: $file_id se cargó correctamente. Código HTTP: $http_code");
        
        return true;
    } else {
        // Actualizar el estado a 'loss' si no se pudo cargar el archivo
        actualizarEstadoArchivo($file_id, 'loss');
        
        //guardarLog("Error al cargar el archivo con File ID: $file_id. Código HTTP: $http_code");
        return false;
    }
}




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
            //guardarLog("Error: el hash existe y no está en estado 'loss'.");
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
            //guardarLog("Error al intentar guardar el hash nuevamente: " . $e->getMessage());
            return false;
        }
    }
}



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



function eliminarHash($id) {
    global $wpdb;
    $resultado = (bool) $wpdb->delete("{$wpdb->prefix}file_hashes", array('id' => $id), array('%d'));
    if ($resultado) {
        guardarLog("eliminarHash: Registro eliminado con ID: $id");
    } else {
        guardarLog("eliminarHash: Error al eliminar el registro con ID: $id");
    }
    
    return $resultado;
}

function eliminarPorHash($file_hash) {
    global $wpdb;
    $resultado = (bool) $wpdb->delete(
        "{$wpdb->prefix}file_hashes", 
        array('file_hash' => $file_hash), 
        array('%s')
    );
    
    if ($resultado) {
        guardarLog("eliminarPorHash: Registro eliminado con hash: $file_hash");
    } else {
        guardarLog("eliminarPorHash: Error al eliminar el registro con hash: $file_hash");
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
        //guardarLog("No se encontró File ID para la URL: $url");
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
        guardarLog("Archivo pendiente eliminado: " . $archivo['file_url']);
    }
}

if (!wp_next_scheduled('limpiar_archivos_pendientes')) {
    wp_schedule_event(time(), 'daily', 'limpiar_archivos_pendientes');
}
add_action('limpiar_archivos_pendientes', 'limpiarArchivosPendientes');
*/