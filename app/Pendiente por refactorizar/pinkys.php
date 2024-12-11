<?

// Función para agregar pinkys al usuario
function agregarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales + $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

// Función para restar pinkys al usuario
function restarPinkys($userID, $cantidad)
{
    $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
    $nuevas_monedas = $monedas_actuales - $cantidad;
    update_user_meta($userID, 'pinky', $nuevas_monedas);
}

// Acción para restar pinkys al eliminar un post
add_action('before_delete_post', 'restarPinkysEliminacion');
function restarPinkysEliminacion($postID)
{
    $post = get_post($postID);
    $userID = $post->post_author;

    if ($userID) {
        restarPinkys($userID, 1);
    }
}
add_action('wp_ajax_procesarDescarga', 'procesarDescarga');


//aqui hay 2 problema con las colecciones, 1 una el zip da 404 y no veo que se este creando en el servidor, y 2, las colecciones deben descargarse tambien con generarEnlaceDescarga, por favor dame 
function procesarDescarga()
{
    $userId = get_current_user_id();
    error_log("Inicio del proceso de descarga. User ID: " . $userId);

    if (!$userId) {
        error_log("Error: Usuario no autorizado.");
        wp_send_json_error(['message' => 'No autorizado.']);
        return;
    }

    $postId = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $esColeccion = isset($_POST['coleccion']) && $_POST['coleccion'] === 'true';
    error_log("Post ID: " . $postId . ", esColeccion: " . ($esColeccion ? 'true' : 'false'));

    $post = get_post($postId);
    if (!$post || $post->post_status !== 'publish') {
        error_log("Error: Post no válido o no publicado. Post ID: " . $postId);
        wp_send_json_error(['message' => 'Post no válido.']);
        return;
    }

    if ($esColeccion) {
        error_log("Procesando colección. Post ID: " . $postId);
        $zipUrl = procesarColeccion($postId, $userId);
        if (is_wp_error($zipUrl)) {
            error_log("Error en procesarColeccion: " . $zipUrl->get_error_message());
            wp_send_json_error(['message' => $zipUrl->get_error_message()]);
            return;
        }

        // Generar enlace de descarga para la colección
        $downloadUrl = generarEnlaceDescargaColeccion($userId, $zipUrl, $postId);
        error_log("URL de descarga de colección generada: " . $downloadUrl);
    } else {
        error_log("Procesando descarga individual. Post ID: " . $postId);
        $audioId = get_post_meta($postId, 'post_audio', true);
        if (!$audioId) {
            error_log("Error: Audio no encontrado. Post ID: " . $postId);
            wp_send_json_error(['message' => 'Audio no encontrado.']);
            return;
        }

        $descargasAnteriores = get_user_meta($userId, 'descargas', true);
        error_log("Descargas anteriores: " . print_r($descargasAnteriores, true));

        if (!is_array($descargasAnteriores)) {
            $descargasAnteriores = [];
            error_log("Descargas anteriores no era un array, se inicializa como array vacío.");
        }

        $yaDescargado = isset($descargasAnteriores[$postId]);
        error_log("Ya descargado: " . ($yaDescargado ? 'true' : 'false'));

        if (!$yaDescargado) {
            $pinky = (int)get_user_meta($userId, 'pinky', true);
            error_log("Pinkys del usuario: " . $pinky);
            if ($pinky < 1) {
                error_log("Error: No hay suficientes Pinkys.");
                wp_send_json_error(['message' => 'No tienes suficientes Pinkys para esta descarga.']);
                return;
            }
            restarPinkys($userId, 1);
            error_log("Pinkys restados.");
        }

        if (!$yaDescargado) {
            $descargasAnteriores[$postId] = 1;
            error_log("Primera descarga, se agrega al registro.");
        } else {
            $descargasAnteriores[$postId]++;
            error_log("Descarga repetida, se incrementa el contador.");
        }

        update_user_meta($userId, 'descargas', $descargasAnteriores);
        error_log("Descargas del usuario actualizadas.");

        $totalDescargas = (int)get_post_meta($postId, 'totalDescargas', true);
        $totalDescargas++;
        update_post_meta($postId, 'totalDescargas', $totalDescargas);
        error_log("Total de descargas del post actualizado: " . $totalDescargas);

        $downloadUrl = generarEnlaceDescarga($userId, $audioId);
        error_log("URL de descarga generada: " . $downloadUrl);
    }

    actualizarTimestampDescargas($userId);
    error_log("Timestamp de descargas actualizado.");
    wp_send_json_success(['download_url' => $downloadUrl]);
    error_log("Fin del proceso de descarga.");
}



function procesarColeccion($postId, $userId) {
    error_log("[procesarColeccion] Inicio de procesarColeccion. Post ID: " . $postId . ", User ID: " . $userId);

    $samples = get_post_meta($postId, 'samples', true);
    $numSamples = is_array($samples) ? count($samples) : 0;

    error_log("[procesarColeccion] Samples obtenidos: " . print_r($samples, true));
    error_log("[procesarColeccion] Número de samples: " . $numSamples);

    if ($numSamples === 0) {
        error_log("[procesarColeccion] Error: No hay samples en esta colección.");
        return new WP_Error('no_samples', __('No hay samples en esta colección.', 'text-domain'));
    }

    $zipName = 'coleccion-' . $postId . '-' . $numSamples . '.zip';
    $upload_dir = wp_upload_dir();
    $zipPath = $upload_dir['path'] . '/' . $zipName;
    $zipUrl = $upload_dir['url'] . '/' . $zipName;

    error_log("[procesarColeccion] Nombre del archivo ZIP: " . $zipName);
    error_log("[procesarColeccion] Ruta del archivo ZIP: " . $zipPath);
    error_log("[procesarColeccion] URL del archivo ZIP: " . $zipUrl);

    if (!is_dir($upload_dir['path']) || !is_writable($upload_dir['path'])) {
        error_log("[procesarColeccion] Error: El directorio de uploads no existe o no tiene permisos de escritura.");
        return new WP_Error('upload_dir_error', __('Error: El directorio de uploads no existe o no tiene permisos de escritura.', 'text-domain'));
    }

    list($samplesDescargados, $samplesNoDescargados) = clasificarSamples($samples, $userId);
    $numSamplesNoDescargados = count($samplesNoDescargados);

    if (file_exists($zipPath)) {
        error_log("[procesarColeccion] El archivo ZIP ya existe.");
        error_log("[procesarColeccion] Samples descargados: " . print_r($samplesDescargados, true));
        error_log("[procesarColeccion] Samples no descargados: " . print_r($samplesNoDescargados, true));
        error_log("[procesarColeccion] Número de samples no descargados: " . $numSamplesNoDescargados);

        if ($numSamplesNoDescargados > 0) {
            $pinky = (int)get_user_meta($userId, 'pinky', true);
            error_log("[procesarColeccion] Pinkys del usuario: " . $pinky);

            if ($pinky < $numSamplesNoDescargados) {
                error_log("[procesarColeccion] Error: No tienes suficientes Pinkys. Requeridos: " . $numSamplesNoDescargados);
                return new WP_Error('no_pinkys', __('No tienes suficientes Pinkys para esta descarga. Se requieren ' . $numSamplesNoDescargados . ' pinkys', 'text-domain'));
            }
            restarPinkys($userId, $numSamplesNoDescargados);
             error_log("[procesarColeccion] Pinkys restados: " . $numSamplesNoDescargados);
        }
        
    } else {
        error_log("[procesarColeccion] El archivo ZIP no existe. Creando...");
        $zip = new ZipArchive();
        
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            error_log("[procesarColeccion] Error al crear el archivo ZIP.");
            return new WP_Error('zip_error', __('Error al crear el archivo ZIP.', 'text-domain'));
        }

        if (!agregarArchivosAlZip($zip, $samples)) {
            $zip->close();
            if(file_exists($zipPath)){
                unlink($zipPath);
            }
            error_log("[procesarColeccion] Error al agregar archivos al ZIP.");
            return new WP_Error('add_file_error', __('Error al agregar archivo al ZIP.', 'text-domain'));
        }
        
        $zip->close();
        error_log("[procesarColeccion] Archivo ZIP cerrado.");

        if ($numSamplesNoDescargados > 0) {
            $pinky = (int)get_user_meta($userId, 'pinky', true);
            error_log("[procesarColeccion] Pinkys del usuario: " . $pinky);
            
            if ($pinky < $numSamplesNoDescargados) {
                error_log("[procesarColeccion] Error: No tienes suficientes Pinkys. Requeridos: " . $numSamplesNoDescargados);
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                    error_log("[procesarColeccion] Archivo ZIP eliminado debido a la falta de Pinkys: " . $zipPath);
                }
                return new WP_Error('no_pinkys', __('No tienes suficientes Pinkys para esta descarga. Se requieren ' . $numSamplesNoDescargados . ' pinkys', 'text-domain'));
            }
             restarPinkys($userId, $numSamplesNoDescargados);
             error_log("[procesarColeccion] Pinkys restados: " . $numSamplesNoDescargados);
        }
    }

    actualizarDescargas($userId, $samplesNoDescargados, $samplesDescargados);
    $totalDescargas = (int)get_post_meta($postId, 'totalDescargas', true);
    $totalDescargas++;
    update_post_meta($postId, 'totalDescargas', $totalDescargas);
    error_log("[procesarColeccion] Total de descargas del post actualizado: " . $totalDescargas);
    error_log("[procesarColeccion] Fin de procesarColeccion. Retornando URL del ZIP: " . $zipUrl);
    return $zipUrl;
}


/*
[11-Dec-2024 08:21:05 UTC] [agregarArchivosAlZip] IDs de audio para sample 310675: "310676"
[11-Dec-2024 08:21:05 UTC] [agregarArchivosAlZip] Error: El valor de 'post_audio' para el sample 310675 no es un array.
[11-Dec-2024 08:21:05 UTC] [agregarArchivosAlZip] IDs de audio para sample 269818: "269819"
[11-Dec-2024 08:21:05 UTC] [agregarArchivosAlZip] Error: El valor de 'post_audio' para el sample 269818 no es un array.
[11-Dec-2024 08:21:05 UTC] [agregarArchivosAlZip] IDs de audio para sample 258647: "258648" 
arregla plis
*/


function agregarArchivosAlZip(ZipArchive &$zip, array $samples): bool
{
    $agregado = false;
    $functionName = __FUNCTION__;

    foreach ($samples as $sampleId) {
        $audioIds = get_post_meta($sampleId, 'post_audio', true);
        error_log("[{$functionName}] IDs de audio para sample {$sampleId}: " . json_encode($audioIds));

        // Convertir $audioIds a un array si no lo es
        if (!is_array($audioIds)) {
            if (is_string($audioIds) && !empty($audioIds)) {
                $audioIds = [$audioIds]; // Crea un array con el string como único elemento
            } else {
                error_log("[{$functionName}] Error: El valor de 'post_audio' para el sample {$sampleId} no es un array ni un string válido.");
                continue; // Salta a la siguiente iteración del bucle principal
            }
        }

        foreach ($audioIds as $audioId) {
            $audioFile = get_attached_file($audioId);
            error_log("[{$functionName}] Ruta del archivo de audio {$audioId}: " . ($audioFile ?: 'No disponible'));

            if (!$audioFile) {
                error_log("[{$functionName}] Error: No se pudo obtener la ruta del archivo de audio con ID: {$audioId}");
                continue; // Salta a la siguiente iteración del bucle de audioIds
            }

            if (!file_exists($audioFile)) {
                error_log("[{$functionName}] Error: El archivo de audio no existe: {$audioFile}");
                continue; // Salta a la siguiente iteración del bucle de audioIds
            }

            if ($zip->addFile($audioFile, basename($audioFile))) {
                error_log("[{$functionName}] Archivo agregado al ZIP: " . basename($audioFile));
                $agregado = true;
            } else {
                error_log("[{$functionName}] Error al agregar archivo al ZIP: " . basename($audioFile));
                return false; // Retorna falso inmediatamente en caso de error
            }
        }
    }

    return $agregado;
}

?>

function clasificarSamples(array $samples, int $userId): array
{
    $functionName = __FUNCTION__;
    $samplesDescargados = [];
    $samplesNoDescargados = [];

    foreach ($samples as $sampleId) {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];

        if (!is_array($descargasAnteriores)) {
            error_log("[{$functionName}] Error: El valor de 'descargas' para el usuario {$userId} no es un array.");
            $descargasAnteriores = [];
        }

        if (isset($descargasAnteriores[$sampleId])) {
            $samplesDescargados[] = $sampleId;
        } else {
            $samplesNoDescargados[] = $sampleId;
        }
    }

    error_log("[{$functionName}] Samples descargados para el usuario {$userId}: " . json_encode($samplesDescargados));
    error_log("[{$functionName}] Samples no descargados para el usuario {$userId}: " . json_encode($samplesNoDescargados));

    return [$samplesDescargados, $samplesNoDescargados];
}

function actualizarDescargas(int $userId, array $samplesNoDescargados, array $samplesDescargados): void
{
    $functionName = __FUNCTION__;

    foreach ($samplesNoDescargados as $sampleId) {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];

        if (!is_array($descargasAnteriores)) {
            error_log("[{$functionName}] Error: El valor de 'descargas' para el usuario {$userId} no es un array.");
            $descargasAnteriores = [];
        }

        $descargasAnteriores[$sampleId] = 1;
        update_user_meta($userId, 'descargas', $descargasAnteriores);
        error_log("[{$functionName}] Sample no descargado ({$sampleId}) agregado a descargas para el usuario {$userId}.");
    }

    foreach ($samplesDescargados as $sampleId) {
        $descargasAnteriores = get_user_meta($userId, 'descargas', true) ?: [];

        if (!is_array($descargasAnteriores)) {
            error_log("[{$functionName}] Error: El valor de 'descargas' para el usuario {$userId} no es un array.");
            $descargasAnteriores = [];
        }

        if (isset($descargasAnteriores[$sampleId])) {
            $descargasAnteriores[$sampleId]++;
            update_user_meta($userId, 'descargas', $descargasAnteriores);
            error_log("[{$functionName}] Contador de descargas incrementado para sample {$sampleId} (usuario {$userId}). Nuevo valor: {$descargasAnteriores[$sampleId]}");
        }
    }
}


function generarEnlaceDescargaColeccion($userID, $zipUrl, $postId) {
    $token = bin2hex(random_bytes(16));

    $token_data = array(
        'user_id' => $userID,
        'zip_url' => $zipUrl,
        'post_id' => $postId, // Podrías necesitar el post ID para luego en descargaAudioColeccion
        'time' => time(),
        'usos' => 0, // Inicializar el contador de usos
        'tipo' => 'coleccion' // Identificar que es una colección
    );

    error_log("--------------------------------------------------");
    error_log("[Inicio] Generando enlace de descarga de colección. UserID: " . $userID . ", ZipURL: " . $zipUrl . ", Token: " . $token . ", Time: " . time());

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    error_log("Token data set in transient: " . print_r($token_data, true));

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());

    error_log("Enlace de descarga de colección generado: " . $enlaceDescarga);
    error_log("[Fin] Generando enlace de descarga de colección.");
    error_log("--------------------------------------------------");
    return $enlaceDescarga;
}

function generarEnlaceDescarga($userID, $audioID)
{
    $token = bin2hex(random_bytes(16));

    $token_data = array(
        'user_id' => $userID,
        'audio_id' => $audioID,
        'time' => time(),
        'usos' => 0, // Inicializar el contador de usos
    );

    error_log("--------------------------------------------------");
    error_log("[Inicio] Generando enlace de descarga. UserID: " . $userID . ", AudioID: " . $audioID . ", Token: " . $token . ", Time: " . time());

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    error_log("Token data set in transient: " . print_r($token_data, true));

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
    ], home_url());

    error_log("Enlace de descarga generado: " . $enlaceDescarga);
    error_log("[Fin] Generando enlace de descarga.");
    error_log("--------------------------------------------------");
    return $enlaceDescarga;
}

add_action('wp_ajax_descargar_audio', 'procesarDescarga');
add_action('template_redirect', 'descargaAudio');

function descargaAudio()
{
    //(codigo omitido)
    if (isset($_GET['descarga_token'])) {
        $token = sanitize_text_field($_GET['descarga_token']);

        error_log("--------------------------------------------------");
        error_log("[Inicio] Intentando descargar con token: " . $token);
        error_log('User Agent: ' . $_SERVER['HTTP_USER_AGENT']);
        error_log('Request Headers: ' . print_r(getallheaders(), true));

        $token_data = get_transient('descarga_token_' . $token);

        if ($token_data) {
            error_log("Datos del token recuperados: " . print_r($token_data, true));

            $userID = get_current_user_id();
            error_log("UserID actual: " . $userID);
            error_log("UserID del token: " . $token_data['user_id']);

            // desactivado temporalmente porque en andorid no se envia correctamente el id
            /*if ($userID != $token_data['user_id']) {
                error_log("[Error] Descarga de audio: Usuario no autorizado. UserID: " . $userID . ", Token UserID: " . $token_data['user_id']);
                error_log("--------------------------------------------------");
                wp_die('No tienes permiso para descargar este archivo.');
            } */

            // Verificar el número de usos
            if ($token_data['usos'] >= 2) {
                error_log("[Error] Descarga de audio: Token ha excedido el número de usos permitidos. Usos: " . $token_data['usos']);
                delete_transient('descarga_token_' . $token);
                error_log("[Error] Token de descarga eliminado por exceder usos: " . $token);
                error_log("--------------------------------------------------");
                wp_die('El enlace de descarga ha excedido el número de usos permitidos.');
            }

            $audioID = $token_data['audio_id'];
            $audio_path = get_attached_file($audioID);

            if ($audio_path && file_exists($audio_path) && is_readable($audio_path)) {
                // Limpiar cualquier salida previa
                while (ob_get_level()) {
                    ob_end_clean();
                }

                // Configuración del servidor
                ini_set('zlib.output_compression', 'Off');
                ini_set('output_buffering', 'Off');
                set_time_limit(0);
                error_log("Configuración del servidor ajustada para la descarga.");

                // Obtener el tipo MIME y el nombre del archivo
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo === false) {
                    error_log("[Error] Descarga de audio: Error al abrir finfo.");
                    wp_die('Error al obtener información del archivo.');
                }
                $mime_type = finfo_file($finfo, $audio_path);
                if ($mime_type === false) {
                    error_log("[Error] Descarga de audio: Error al obtener el tipo MIME del archivo: " . $audio_path);
                    finfo_close($finfo);
                    wp_die('Error al obtener el tipo de archivo.');
                }
                finfo_close($finfo);
                error_log("Tipo MIME del archivo: " . $mime_type);

                $filename = basename($audio_path);
                error_log("Nombre del archivo: " . $filename);

                // Cabeceras HTTP
                header('Content-Type: ' . $mime_type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($audio_path));
                header('Accept-Ranges: bytes');
                header('Cache-Control: no-cache, must-revalidate');
                header('Pragma: no-cache');
                header('Expires: 0');
                error_log("Cabeceras HTTP configuradas correctamente.");

                // Manejo de rangos para descarga parcial
                if (isset($_SERVER['HTTP_RANGE'])) {
                    error_log("Solicitud de rango recibida: " . $_SERVER['HTTP_RANGE']);
                    list($a, $range) = explode("=", $_SERVER['HTTP_RANGE'], 2);
                    list($range) = explode(",", $range, 2);
                    list($range, $range_end) = explode("-", $range);
                    $range = intval($range);
                    $size = filesize($audio_path);
                    $range_end = ($range_end) ? intval($range_end) : $size - 1;

                    header('HTTP/1.1 206 Partial Content');
                    header("Content-Range: bytes $range-$range_end/$size");
                    header('Content-Length: ' . ($range_end - $range + 1));
                    error_log("Respondiendo con contenido parcial. Rango: $range-$range_end");
                } else {
                    $range = 0;
                    error_log("No se solicitó rango. Se enviará el archivo completo.");
                }

                // Abrir y enviar el archivo
                $fp = fopen($audio_path, 'rb');
                if ($fp === false) {
                    error_log("[Error] Descarga de audio: Error al abrir el archivo: " . $audio_path);
                    wp_die('Error al abrir el archivo.');
                }
                fseek($fp, $range);
                error_log("Posición del puntero de archivo ajustada a: " . $range);

                error_log("Iniciando la transmisión del archivo...");
                while (!feof($fp)) {
                    $data = fread($fp, 8192);
                    if ($data === false) {
                        error_log("[Error] Descarga de audio: Error al leer el archivo: " . $audio_path);
                        fclose($fp);
                        wp_die('Error al leer el archivo.');
                    }
                    print($data);
                    flush();
                    if (connection_status() != 0) {
                        error_log("[Error] Descarga de audio: Conexión interrumpida. Estado: " . connection_status());
                        fclose($fp);
                        exit;
                    }
                }

                fclose($fp);
                error_log("Transmisión del archivo finalizada con éxito.");

                // Incrementar el contador de usos y actualizar el token
                $token_data['usos']++;
                set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS);
                error_log("Contador de usos incrementado a: " . $token_data['usos']);

                // Eliminar el token si ha alcanzado el límite de usos
                if ($token_data['usos'] >= 3) {
                    delete_transient('descarga_token_' . $token);
                    error_log("[OK] Token de descarga eliminado después de 3 usos: " . $token);
                }

                error_log("[Fin] Descarga de audio completada.");
                error_log("--------------------------------------------------");
                exit;
            } else {
                error_log("[Error] Descarga de audio: El archivo no existe o no es accesible. Ruta: " . $audio_path);
                error_log("--------------------------------------------------");
                wp_die('El archivo no existe o no es accesible.');
            }
        } else {
            error_log("[Error] Descarga de audio: Token de descarga no válido o expirado. Token: " . $token);
            error_log("--------------------------------------------------");
            wp_die('El enlace de descarga no es válido o ha expirado.');
        }
    }
}



/*
async function procesarDescarga(postId, usuarioId) {
    console.log('Iniciando procesarDescarga', postId, usuarioId);

    const confirmed = await new Promise(resolve => {
        const confirmBox = confirm('Esta descarga costará 1 Pinky. ¿Deseas continuar?');
        resolve(confirmBox);
    });

    if (!confirmed) {
        console.log('Descarga cancelada por el usuario.');
        return false;
    }

    try {
        const data = {
            post_id: postId 
        };

        // Enviar la solicitud AJAX
        const responseData = await enviarAjax('procesarDescarga', data);
        console.log('Datos de respuesta:', responseData);

        // Verificar si la respuesta fue exitosa
        if (responseData.success) {
            // Acceder a la propiedad download_url dentro de responseData.data
            if (responseData.data && responseData.data.download_url) {
                console.log('Descarga autorizada, iniciando descarga');
                window.location.href = responseData.data.download_url;  // Redirige a la URL de descarga
            } else {
                console.error('Error: download_url no está definido en la respuesta.');
                alert('Hubo un problema obteniendo el enlace de descarga.');
            }
        } else {
            console.log('No hay suficientes pinkys o error en la descarga.');
            alert(responseData.message || 'No tienes suficientes pinkys');
        }
    } catch (error) {
        console.error('Error en la solicitud:', error);
        alert('Ocurrió un error al procesar la descarga. Por favor, intenta de nuevo.');
    }

    return false;
}
*/

// Función para generar el botón de descarga
function botonDescarga($postId)
{
    ob_start();
    $paraDescarga = get_post_meta($postId, 'paraDescarga', true);
    $userId = get_current_user_id();

    if ($paraDescarga == '1') {
        if ($userId) {
            $descargasAnteriores = get_user_meta($userId, 'descargas', true);
            $yaDescargado = isset($descargasAnteriores[$postId]);
            $claseExtra = $yaDescargado ? 'yaDescargado' : '';
            $esColeccion = get_post_type($postId) === 'colecciones' ? 'true' : 'false';

            // Error log para postId 320353
            if ($postId == 320353) {
                error_log("botonDescarga - Post ID: 320353");
                error_log("botonDescarga - get_post_type(320353): " . get_post_type($postId));
                error_log("botonDescarga - esColeccion: " . $esColeccion);
            }

?>
            <div class="ZAQIBB">
                <button class="icon-arrow-down <?php echo esc_attr($claseExtra); ?>"
                    data-post-id="<?php echo esc_attr($postId); ?>"
                    aria-label="Boton Descarga"
                    id="download-button-<?php echo esc_attr($postId); ?>"
                    onclick="return procesarDescarga('<?php echo esc_js($postId); ?>', '<?php echo esc_js($userId); ?>', '<?php echo $esColeccion; ?>')">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
        <?php
        } else {
        ?>
            <div class="ZAQIBB">
                <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                    <?php echo $GLOBALS['descargaicono']; ?>
                </button>
            </div>
    <?php
        }
    }
    return ob_get_clean();
}
// Agregar pinkys al registrarse un nuevo usuario
function pinkysRegistro($user_id)
{
    $pinkys_iniciales = 10;
    update_user_meta($user_id, 'pinky', $pinkys_iniciales);
}
add_action('user_register', 'pinkysRegistro');

// Función para restablecer los pinkys semanalmente
function restablecerPinkys()
{
    $usuarios_query = new WP_User_Query(array(
        'fields' => 'ID',
    ));

    if (!empty($usuarios_query->results)) {
        foreach ($usuarios_query->results as $userID) {
            $monedas_actuales = (int) get_user_meta($userID, 'pinky', true);
            if ($monedas_actuales < 10) {
                update_user_meta($userID, 'pinky', 10);
            }
        }
    }
}
add_action('restablecer_pinkys_semanal', 'restablecerPinkys');

// Programar el evento semanal para restablecer los pinkys
if (!wp_next_scheduled('restablecer_pinkys_semanal')) {
    wp_schedule_event(time(), 'weekly', 'restablecer_pinkys_semanal');
}


function botonDescargaPrueba()
{
    ob_start();
    ?>
    <div class="ZAQIBB ASDGD8">
        <button aria-label="Descarga ejemplo">
            <? echo $GLOBALS['descargaicono']; ?>
        </button>
    </div>
<?
    return ob_get_clean();
}
