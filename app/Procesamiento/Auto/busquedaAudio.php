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


function procesarAudios()
{
    $directorio_audios = '/home/asley01/MEGA/Waw/Kits';
    $lock_file = '/tmp/procesar_audios.lock';
    $max_reintentos = 5;
    $espera_segundos = 5;

    $fp = fopen($lock_file, 'c');
    if ($fp === false) {
        return;
    }
    $intentos = 0;
    while (!flock($fp, LOCK_EX | LOCK_NB)) {
        $intentos++;
        if ($intentos >= $max_reintentos) {
            fclose($fp);
            return;
        }
        sleep($espera_segundos);
    }

    try {
        $inicio = microtime(true);
        $audio_info = buscarUnAudioValido($directorio_audios);
        if ($audio_info) {
            $tiempo = microtime(true) - $inicio;
            autLog("Tiempo de búsqueda: " . number_format($tiempo, 2) . " segundos");
            enviarAudioaProcesar($audio_info['ruta'], $audio_info['hash']);
        }
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
        if (file_exists($lock_file)) {
            unlink($lock_file);
        }
    }
}

function buscarUnAudioValido($directorio, $intentos = 0)
{
    $max_intentos = 500; // Número máximo de intentos recursivos
    if ($intentos >= $max_intentos) {
        return null;
    }

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
                    $nombreArchivo = $file->getFilename();
                    // if (substr($nombreArchivo, -5) !== '2upra') {
                    $archivos[] = $file->getPathname();
                    // }
                }
            }
        }

        if (empty($archivos)) {
            return buscarUnAudioValido($directorio, $intentos + 1);
        }

        $archivo_seleccionado = $archivos[array_rand($archivos)];
        $hash = hash_file('sha256', $archivo_seleccionado);

        if (!$hash) {
            return buscarUnAudioValido($directorio, $intentos + 1);
        }

        if (debeProcesarse($archivo_seleccionado, $hash)) {
            return ['ruta' => $archivo_seleccionado, 'hash' => $hash];
        } else {
            return buscarUnAudioValido($directorio, $intentos + 1);
        }
    } catch (Exception $e) {
        shell_exec('sudo /bin/chmod -R 770 /home/asley01/MEGA/Waw/Kits/ 2>&1');
        return null;
    }

    return null;
}

function debeProcesarse($ruta_archivo, $file_hash)
{
    try {
        if (!file_exists($ruta_archivo)) {
            guardarLog("debeProcesarse: El archivo no existe en la ruta: $ruta_archivo");
            return false;
        }

        if (!$file_hash) {
            guardarLog("debeProcesarse: No se proporcionó un hash válido");
            return false;
        }

        $hash_obtenido = obtenerHash($file_hash);
        guardarLog("debeProcesarse: Hash obtenido: " . ($hash_obtenido ? "SI" : "NO") . " para hash: $file_hash");

        $hash_verificado = verificarCargaArchivoPorHash($file_hash);
        guardarLog("debeProcesarse: Hash verificado: " . ($hash_verificado ? "SI" : "NO") . " para hash: $file_hash");

        if ($hash_obtenido && $hash_verificado) {
            guardarLog("debeProcesarse: El archivo existe y está verificado. Procediendo a eliminar.");
            
            if (file_exists($ruta_archivo)) {
                $eliminado = unlink($ruta_archivo);
                guardarLog("debeProcesarse: Eliminación del archivo: " . ($eliminado ? "EXITOSA" : "FALLIDA") . " - Ruta: $ruta_archivo");
                
                $hash_eliminado = eliminarPorHash($file_hash);
                guardarLog("debeProcesarse: Eliminación del hash: " . ($hash_eliminado ? "EXITOSA" : "FALLIDA") . " - Hash: $file_hash");
            } else {
                guardarLog("debeProcesarse: El archivo ya no existe en la ruta: $ruta_archivo");
            }
            return false;
        }

        guardarLog("debeProcesarse: El archivo debe procesarse - Ruta: $ruta_archivo, Hash: $file_hash");
        return true;

    } catch (Exception $e) {
        guardarLog("debeProcesarse: Error - " . $e->getMessage());
        return false;
    }
}

function obtenerHash($file_hash)
{
    global $wpdb;
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}file_hashes WHERE file_hash = %s LIMIT 1",
        $file_hash
    ), ARRAY_A);
}

function enviarAudioaProcesar($audio, $file_hash)
{
    if (!file_exists($audio)) {
        return;
    }
    $user_id = 44;
    if (!guardarHash($file_hash, $audio, $user_id, 'confirmed')) {
        return;
    }

    autProcesarAudio($audio);
}
