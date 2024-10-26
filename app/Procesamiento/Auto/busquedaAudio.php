<?

add_action('init', 'iniciar_cron_procesamiento_audios');
function iniciar_cron_procesamiento_audios()
{
    if (!wp_next_scheduled('audio60')) {
        wp_schedule_event(time(), 'cadaDosMinutos', 'audio60');
        //autLog("Cron de procesamiento de audios programado para cada 2 minutos.");
    }
}

add_filter('cron_schedules', 'definir_cron_cada_dos_minutos');
function definir_cron_cada_dos_minutos($schedules)
{
    if (!isset($schedules['cadaDosMinutos'])) {
        $schedules['cadaDosMinutos'] = array(
            'interval' => 60,
            'display'  => __('Cada 2 minutos')
        );
    }
    return $schedules;
}
add_action('audio60', 'procesarAudios');



function procesarAudios() {
    $directorio_audios = '/home/asley01/MEGA/Waw/Kits';
    $lock_file = '/tmp/procesar_audios.lock';
    $max_reintentos = 5;
    $espera_segundos = 5;
    
    $fp = fopen($lock_file, 'c');
    if ($fp === false) {
        // No se pudo abrir el archivo de bloqueo, se sale de la función.
        return;
    }

    // Intentar adquirir el bloqueo hasta un número máximo de reintentos.
    $intentos = 0;
    while (!flock($fp, LOCK_EX | LOCK_NB)) {
        $intentos++;
        if ($intentos >= $max_reintentos) {
            // Si se alcanzó el número máximo de intentos, salir.
            fclose($fp);
            return;
        }
        // Esperar 5 segundos antes de reintentar.
        sleep($espera_segundos);
    }

    try {
        $inicio = microtime(true);

        // Buscar un audio válido y procesarlo
        $audio_info = buscarUnAudioValido($directorio_audios);
        if ($audio_info) {
            $tiempo = microtime(true) - $inicio;
            autLog("Tiempo de búsqueda: " . number_format($tiempo, 2) . " segundos");
            autRevisarAudio($audio_info['ruta'], $audio_info['hash']);
        }
    } finally {
        // Liberar el bloqueo y cerrar el archivo de bloqueo.
        flock($fp, LOCK_UN);
        fclose($fp);
        // Eliminar el archivo de bloqueo si existe.
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    }
}

function buscarUnAudioValido($directorio) {
    $extensiones_permitidas = ['wav', 'mp3'];

    if (!is_dir($directorio) || !is_readable($directorio)) {
        shell_exec('sudo /bin/chmod -R 770 /home/asley01/MEGA/Waw/Kits/ 2>&1');
        return null;
    }

    try {
        $subcarpetas = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                $subcarpetas[] = $item->getPathname();
            }
        }

        if (empty($subcarpetas)) {
            $subcarpetas[] = $directorio;
        }

        $carpeta_seleccionada = $subcarpetas[array_rand($subcarpetas)];

        $archivos = [];
        $dir_iterator = new DirectoryIterator($carpeta_seleccionada);
        foreach ($dir_iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensiones_permitidas, true)) {
                    $archivos[] = $file->getPathname();
                }
            }
        }

        if (empty($archivos)) {
            return buscarUnAudioValido($directorio);
        }

        $archivo_seleccionado = $archivos[array_rand($archivos)];
        $hash = hash_file('sha256', $archivo_seleccionado);

        if (!$hash) {
            return buscarUnAudioValido($directorio);
        }

        if (debeProcesarse($archivo_seleccionado, $hash)) {
            return ['ruta' => $archivo_seleccionado, 'hash' => $hash];
        } else {
            return buscarUnAudioValido($directorio);
        }
    } catch (Exception $e) {
        shell_exec('sudo /bin/chmod -R 770 /home/asley01/MEGA/Waw/Kits/ 2>&1');
        return null;
    }

    return null;
}

function debeProcesarse($ruta_archivo, $file_hash) {
    try {
        if (!file_exists($ruta_archivo) || !$file_hash) {
            return false;
        }

        $hash_obtenido = obtenerHash($file_hash);
        $hash_verificado = verificarCargaArchivoPorHash($file_hash);

        if ($hash_obtenido || $hash_verificado) {
            return false;
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

function autRevisarAudio($audio, $file_hash) {
    if (!file_exists($audio)) {
        return;
    }

    $upload_dir = wp_upload_dir();
    $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $audio);
    $user_id = 44;

    if (!guardarHash($file_hash, $file_url, $user_id, 'confirmed')) {
        return;
    }

    autProcesarAudio($audio);
}