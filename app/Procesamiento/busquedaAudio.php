<?

add_action('init', 'iniciar_cron_procesamiento_audios');
function iniciar_cron_procesamiento_audios()
{
    if (!wp_next_scheduled('procesar_audio1_cron_event')) {
        wp_schedule_event(time(), 'cadaDosMinutos', 'procesar_audio1_cron_event');
        //guardarLog("Cron de procesamiento de audios programado para cada 2 minutos.");
    }
}

add_filter('cron_schedules', 'definir_cron_cada_dos_minutos');
function definir_cron_cada_dos_minutos($schedules)
{
    if (!isset($schedules['cadaDosMinutos'])) {
        $schedules['cadaDosMinutos'] = array(
            'interval' => 260,
            'display'  => __('Cada 2 minutos')
        );
    }
    return $schedules;
}
add_action('procesar_audio1_cron_event', 'procesarAudios');

function procesarAudios()
{
    automaticPost("procesarAudios llamado");
    $directorio_audios = '/home/asley01/MEGA/Waw/X';
    $lock_file = '/tmp/procesar_audios.lock';

    // Intentar crear y obtener un candado exclusivo
    $fp = fopen($lock_file, 'c');
    if ($fp === false) {
        automaticPost("Error al abrir el archivo de bloqueo: $lock_file.");
        return;
    }
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        automaticPost("No se pudo obtener el bloqueo exclusivo: otro proceso está en ejecución.");
        return;
    }

    try {
        automaticPost("Bloqueo obtenido, iniciando el procesamiento de audios.");
        $audio_info = buscarUnAudioValido($directorio_audios);
        if ($audio_info) {
            automaticPost("Audio válido encontrado: " . $audio_info['ruta']);
            autRevisarAudio($audio_info['ruta'], $audio_info['hash']);
        } else {
            automaticPost("No se encontró ningún audio válido en el directorio: $directorio_audios.");
        }
    } catch (Exception $e) {
        automaticPost("Error durante el procesamiento de audios: " . $e->getMessage());
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
        automaticPost("Bloqueo liberado, proceso finalizado.");
        
        // Verificar si el lock_file actual es el que se creó
        if (file_exists($lock_file)) {
            unlink($lock_file);
            automaticPost("Archivo de bloqueo eliminado: $lock_file.");
        }
    }
}


// Paso 2 - Buscar y retornar un solo audio válido
function buscarUnAudioValido($directorio)
{
    $extensiones_permitidas = ['wav', 'mp3'];
    $archivos_encontrados = 0;
    $archivos_evaluados = 0;
    $archivos_validos = 0;

    if (!is_dir($directorio) || !is_readable($directorio)) {
        automaticPost("[buscarUnAudioValido] Error: El directorio no existe o no es accesible: {$directorio}");
        return null;
    }

    automaticPost("[buscarUnAudioValido] Iniciando la búsqueda en el directorio: {$directorio}");

    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $archivo) {
            if ($archivo->isFile()) {
                $archivos_encontrados++;
                $nombreArchivo = $archivo->getFilename();
                $rutaArchivo = $archivo->getPathname();

                // Omitir archivos que empiezan por "2upra_"
                if (strpos($nombreArchivo, '2upra_') === 0) {
                    automaticPost("[buscarUnAudioValido] Omitiendo archivo por prefijo '2upra_': {$rutaArchivo}");
                    continue;
                }

                $ext = strtolower($archivo->getExtension());

                // Solo procesar si la extensión está permitida
                if (!in_array($ext, $extensiones_permitidas, true)) {
                    automaticPost("[buscarUnAudioValido] Omitiendo archivo por extensión no permitida ({$ext}): {$rutaArchivo}");
                    continue;
                }

                // Verificar si el archivo es legible
                if (!is_readable($rutaArchivo)) {
                    automaticPost("[buscarUnAudioValido] Omitiendo archivo no legible: {$rutaArchivo}");
                    continue;
                }

                $archivos_evaluados++;
                $hash = hash_file('sha256', $rutaArchivo);

                if (!$hash) {
                    automaticPost("[buscarUnAudioValido] No se pudo calcular el hash para el archivo: {$rutaArchivo}");
                    continue;
                }

                $debeProcesarse = debeProcesarse($rutaArchivo, $hash);
                if ($debeProcesarse) {
                    $archivos_validos++;
                    automaticPost("[buscarUnAudioValido] Archivo válido encontrado: {$rutaArchivo}");
                    return ['ruta' => $rutaArchivo, 'hash' => $hash];
                } else {
                    automaticPost("[buscarUnAudioValido] El archivo no necesita ser procesado: {$rutaArchivo}");
                }
            }
        }

        automaticPost("[buscarUnAudioValido] Búsqueda completa. Archivos encontrados: {$archivos_encontrados}, Evaluados: {$archivos_evaluados}, Válidos: {$archivos_validos}.");
    } catch (Exception $e) {
        automaticPost("[buscarUnAudioValido] Excepción al iterar directorios: " . $e->getMessage());
    }

    return null;
}

// Paso 3 - Verificar si el archivo debe ser procesado
function debeProcesarse($ruta_archivo, $file_hash)
{
    try {
        if (!file_exists($ruta_archivo)) {
            automaticPost("[debeProcesarse] Error: El archivo no existe: {$ruta_archivo}");
            return false;
        }

        if (!$file_hash) {
            automaticPost("[debeProcesarse] Error: Hash inexistente para el archivo: {$ruta_archivo}");
            return false;
        }

        $hash_obtenido = obtenerHash($file_hash);
        $hash_verificado = verificarCargaArchivoPorHash($file_hash);

        automaticPost("[debeProcesarse] Resultado de obtenerHash para {$ruta_archivo}: " . ($hash_obtenido ? 'Existe' : 'No existe'));
        automaticPost("[debeProcesarse] Resultado de verificarCargaArchivoPorHash para {$ruta_archivo}: " . ($hash_verificado ? 'Existe' : 'No existe'));

        if ($hash_obtenido || $hash_verificado) {
            automaticPost("[debeProcesarse] El archivo ya ha sido procesado previamente: {$ruta_archivo}");
            return false;
        }

        automaticPost("[debeProcesarse] El archivo está listo para ser procesado: {$ruta_archivo}");
        return true;
    } catch (Exception $e) {
        automaticPost("[debeProcesarse] Excepción capturada: " . $e->getMessage());
        return false;
    }
}

// Paso 4 - Revisar y procesar el audio automáticamente
function autRevisarAudio($audio, $file_hash)
{
    if (!file_exists($audio)) {
        automaticPost("[autRevisarAudio] Error: El archivo de audio no existe: {$audio}");
        return;
    }

    $upload_dir = wp_upload_dir();
    $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $audio);
    $user_id = 44;

    automaticPost("[autRevisarAudio] Intentando guardar el hash para el archivo: {$audio} con hash: {$file_hash}");
    if (!guardarHash($file_hash, $file_url, 'confirmed', $user_id)) {
        automaticPost("[autRevisarAudio] Error: No se pudo guardar el hash en la base de datos para el archivo: {$audio}");
        return;
    }

    automaticPost("[autRevisarAudio] Hash guardado exitosamente. Iniciando procesamiento del audio.");
    autProcesarAudio($audio);
    automaticPost("[autRevisarAudio] Procesamiento del audio completado para: {$audio}");
}