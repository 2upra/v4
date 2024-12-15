<?

function procesarColeccion($postId, $userId, $sync = false)
{
    error_log("[procesarColeccion] Inicio de procesarColeccion. Post ID: " . $postId . ", User ID: " . $userId . ", Sync: " . ($sync ? 'true' : 'false'));

    $samples = get_post_meta($postId, 'samples', true);
    $numSamples = is_array($samples) ? count($samples) : 0;

    error_log("[procesarColeccion] Número de samples: " . $numSamples);

    if ($numSamples === 0) {
        error_log("[procesarColeccion] Error: No hay samples en esta colección.");
        return new WP_Error('no_samples', __('No hay samples en esta colección.', 'text-domain'));
    }

    if (!$sync) {
        $zipName = 'coleccion-' . $postId . '-' . $numSamples . '.zip';
        $upload_dir = wp_upload_dir();
        $zipPath = $upload_dir['path'] . '/' . $zipName;

        error_log("[procesarColeccion] Nombre del archivo ZIP: " . $zipName);
        error_log("[procesarColeccion] Ruta del archivo ZIP: " . $zipPath);

        // Buscar y eliminar ZIPs antiguos
        $files = glob($upload_dir['path'] . '/coleccion-' . $postId . '-*.zip');
        if ($files) {
            foreach ($files as $file) {
                if ($file !== $zipPath && file_exists($file)) {
                    unlink($file);
                    error_log("[procesarColeccion] Archivo ZIP antiguo eliminado: " . $file);
                }
            }
        }

        if (!is_dir($upload_dir['path']) || !is_writable($upload_dir['path'])) {
            error_log("[procesarColeccion] Error: El directorio de uploads no existe o no tiene permisos de escritura.");
            return new WP_Error('upload_dir_error', __('Error: El directorio de uploads no existe o no tiene permisos de escritura.', 'text-domain'));
        }
    }

    list($samplesDescargados, $samplesNoDescargados) = clasificarSamples($samples, $userId);
    $numSamplesNoDescargados = count($samplesNoDescargados);

    if (!$sync) {
        if (file_exists($zipPath)) {
            error_log("[procesarColeccion] El archivo ZIP ya existe.");
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
                if (file_exists($zipPath)) {
                    unlink($zipPath);
                }
                error_log("[procesarColeccion] Error al agregar archivos al ZIP.");
                return new WP_Error('add_file_error', __('Error al agregar archivo al ZIP.', 'text-domain'));
            }

            $zip->close();
            error_log("[procesarColeccion] Archivo ZIP cerrado.");
        }
    }

    error_log("[procesarColeccion] Samples no descargados: " . print_r($samplesNoDescargados, true));
    error_log("[procesarColeccion] Número de samples no descargados: " . $numSamplesNoDescargados);

    if ($numSamplesNoDescargados > 0) {
        $pinky = (int)get_user_meta($userId, 'pinky', true);
        error_log("[procesarColeccion] Pinkys del usuario: " . $pinky);

        if ($pinky < $numSamplesNoDescargados) {
            error_log("[procesarColeccion] Error: No tienes suficientes Pinkys. Requeridos: " . $numSamplesNoDescargados);
            if (!$sync && file_exists($zipPath)) {
                unlink($zipPath);
                error_log("[procesarColeccion] Archivo ZIP eliminado debido a la falta de Pinkys: " . $zipPath);
            }
            return new WP_Error('no_pinkys', __('No tienes suficientes Pinkys para esta descarga. Se requieren ' . $numSamplesNoDescargados . ' pinkys', 'text-domain'));
        }
        restarPinkys($userId, $numSamplesNoDescargados);
        error_log("[procesarColeccion] Pinkys restados: " . $numSamplesNoDescargados);
    }

    actualizarTimestampDescargas($userId);
    actualizarDescargas($userId, $samplesNoDescargados, $samplesDescargados);
    $totalDescargas = (int)get_post_meta($postId, 'totalDescargas', true);
    $totalDescargas++;
    update_post_meta($postId, 'totalDescargas', $totalDescargas);
    error_log("[procesarColeccion] Total de descargas del post actualizado: " . $totalDescargas);
    error_log("[procesarColeccion] Fin de procesarColeccion.");

    if (!$sync) {
        error_log("[procesarColeccion] Retornando ruta del ZIP: " . $zipPath);
        return $zipPath; // Devolvemos la ruta física
    } else {
        return true;
    }
}

function generarEnlaceDescargaColeccion($userID, $zipPath, $postId)
{
    $token = bin2hex(random_bytes(16));

    $token_data = array(
        'user_id' => $userID,
        'zip_path' => $zipPath, // Guarda la ruta física
        'post_id' => $postId,
        'time' => time(),
        'usos' => 0,
        'tipo' => 'coleccion'
    );

    error_log("--------------------------------------------------");
    error_log("[Inicio] Generando enlace de descarga de colección. UserID: " . $userID . ", ZipPath: " . $zipPath . ", Token: " . $token . ", Time: " . time());

    set_transient('descarga_token_' . $token, $token_data, HOUR_IN_SECONDS); // válido por 1 hora
    error_log("Token data set in transient: " . print_r($token_data, true));

    $enlaceDescarga = add_query_arg([
        'descarga_token' => $token,
        'tipo'          => 'coleccion'
    ], home_url());

    error_log("Enlace de descarga de colección generado: " . $enlaceDescarga);
    error_log("[Fin] Generando enlace de descarga de colección.");
    error_log("--------------------------------------------------");
    return $enlaceDescarga;
}

function descargaAudioColeccion()
{
    if (isset($_GET['descarga_token']) && isset($_GET['tipo']) && $_GET['tipo'] === 'coleccion') {
        $token = sanitize_text_field($_GET['descarga_token']);

        // Validación más estricta del token (recomendado)
        if (!ctype_xdigit($token)) {
            error_log("[Error] Descarga de colección: Token inválido. Token: " . $token);
            error_log("--------------------------------------------------");
            wp_die('El token de descarga no es válido.');
        }

        error_log("--------------------------------------------------");
        error_log("[Inicio] Intentando descargar colección con token: " . $token);
        error_log('User Agent: ' . $_SERVER['HTTP_USER_AGENT']);

        $token_data = get_transient('descarga_token_' . $token);

        if ($token_data) {
            error_log("Datos del token recuperados: " . print_r($token_data, true));

            $userID = get_current_user_id();
            error_log("UserID actual: " . $userID);
            error_log("UserID del token: " . $token_data['user_id']);

            // Desactivado temporalmente por problemas en Android. 
            /*
            if ($userID != $token_data['user_id']) {
                error_log("[Error] Descarga de colección: Usuario no autorizado. UserID: " . $userID . ", Token UserID: " . $token_data['user_id']);
                error_log("--------------------------------------------------");
                wp_die('No tienes permiso para descargar este archivo.');
            }
            */

            // Verificar el número de usos
            if ($token_data['usos'] >= 2) {
                error_log("[Error] Descarga de colección: Token ha excedido el número de usos permitidos. Usos: " . $token_data['usos']);
                delete_transient('descarga_token_' . $token);
                error_log("[Error] Token de descarga eliminado por exceder usos: " . $token);
                error_log("--------------------------------------------------");
                wp_die('El enlace de descarga ha excedido el número de usos permitidos.');
            }

            $zipPath = $token_data['zip_path']; // Ahora $zipPath es la ruta física correcta

            if ($zipPath && file_exists($zipPath) && is_readable($zipPath)) {
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
                    error_log("[Error] Descarga de colección: Error al abrir finfo.");
                    wp_die('Error al obtener información del archivo.');
                }
                $mime_type = finfo_file($finfo, $zipPath);
                if ($mime_type === false) {
                    error_log("[Error] Descarga de colección: Error al obtener el tipo MIME del archivo: " . $zipPath);
                    finfo_close($finfo);
                    wp_die('Error al obtener el tipo de archivo.');
                }
                finfo_close($finfo);
                error_log("Tipo MIME del archivo: " . $mime_type);

                $filename = basename($zipPath);
                error_log("Nombre del archivo: " . $filename);

                // Cabeceras HTTP
                header('Content-Type: ' . $mime_type);
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($zipPath));
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
                    $size = filesize($zipPath);
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
                $fp = fopen($zipPath, 'rb');
                if ($fp === false) {
                    error_log("[Error] Descarga de colección: Error al abrir el archivo: " . $zipPath);
                    wp_die('Error al abrir el archivo.');
                }
                fseek($fp, $range);
                error_log("Posición del puntero de archivo ajustada a: " . $range);

                error_log("Iniciando la transmisión del archivo...");
                while (!feof($fp)) {
                    $data = fread($fp, 8192);
                    if ($data === false) {
                        error_log("[Error] Descarga de colección: Error al leer el archivo: " . $zipPath);
                        fclose($fp);
                        wp_die('Error al leer el archivo.');
                    }
                    print($data);
                    flush();
                    if (connection_status() != 0) {
                        error_log("[Error] Descarga de colección: Conexión interrumpida. Estado: " . connection_status());
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

                error_log("[Fin] Descarga de colección completada.");
                error_log("--------------------------------------------------");
                exit;
            } else {
                error_log("[Error] Descarga de colección: El archivo no existe o no es accesible. Ruta: " . $zipPath);
                error_log("--------------------------------------------------");
                wp_die('El archivo no existe o no es accesible.');
            }
        }
        error_log("[Error] Descarga de colección: Token de descarga no válido o expirado. Token: " . $token);
        error_log("--------------------------------------------------");
        wp_die('El enlace de descarga no es válido o ha expirado.');
    }
}

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

    //error_log("[{$functionName}] Samples descargados para el usuario {$userId}: " . json_encode($samplesDescargados));
    //error_log("[{$functionName}] Samples no descargados para el usuario {$userId}: " . json_encode($samplesNoDescargados));

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
            //error_log("[{$functionName}] Contador de descargas incrementado para sample {$sampleId} (usuario {$userId}). Nuevo valor: {$descargasAnteriores[$sampleId]}");
        }
    }
}
add_action('template_redirect', 'descargaAudioColeccion');

function botonDescargaColec($postId, $sampleCount)
{
    ob_start();

    $userID = get_current_user_id();

    if ($userID) {
        $descargas_anteriores = get_user_meta($userID, 'descargas', true);
        $yaDescargado = isset($descargas_anteriores[$postId]);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';

?>
        <div class="ZAQIBB">
            <button class="icon-arrow-down botonprincipal <? echo esc_attr($claseExtra); ?>"
                data-post-id="<? echo esc_attr($postId); ?>"
                aria-label="Boton Descarga"
                id="download-button-<? echo esc_attr($postId); ?>"
                onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($userID); ?>', 'true', '<? echo esc_js($sampleCount); ?>')">
                <? echo $GLOBALS['descargaicono']; ?> Descargar
            </button>
        </div>
    <?
    } else {
    ?>
        <div class="ZAQIBB">
            <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                <? echo $GLOBALS['descargaicono']; ?>
            </button>
        </div>
    <?
    }

    return ob_get_clean();
}

function botonSincronizarColec($postId, $sampleCount)
{
    ob_start();

    $userID = get_current_user_id();

    if ($userID) {
        $descargas_anteriores = get_user_meta($userID, 'descargas', true);
        $yaDescargado = isset($descargas_anteriores[$postId]);
        $claseExtra = $yaDescargado ? 'yaDescargado' : '';

    ?>
        <div class="ZAQIBB">
            <button class="icon-arrow-down botonprincipal <? echo esc_attr($claseExtra); ?>"
                data-post-id="<? echo esc_attr($postId); ?>"
                aria-label="Boton Descarga"
                id="download-button-<? echo esc_attr($postId); ?>"
                aca necesito que envie otro valor en true de forma de segura, ahora procesarDescarga recibira otro valor
                onclick="return procesarDescarga('<? echo esc_js($postId); ?>', '<? echo esc_js($userID); ?>', 'true', '<? echo esc_js($sampleCount); ?>', 'true')">
                Sincronizar
            </button>
        </div>
    <?
    } else {
    ?>
        <div class="ZAQIBB">
            <button onclick="alert('Para descargar el archivo necesitas registrarte e iniciar sesión.');" class="icon-arrow-down" aria-label="Descargar">
                Sincronizar
            </button>
        </div>
<?
    }

    return ob_get_clean();
}
