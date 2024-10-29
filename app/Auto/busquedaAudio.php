<?

add_action('init', 'iniciar_cron_procesamiento_audios');
function iniciar_cron_procesamiento_audios()
{
    if (!wp_next_scheduled('sgasdgert54')) {
        wp_schedule_event(time(), 'cadaDosMinutos', 'sgasdgert54');
        autLog("Cron de procesamiento de audios programado para cada 2 minutos.");
    }
}

add_filter('cron_schedules', 'definir_cron_cada_dos_minutos');
function definir_cron_cada_dos_minutos($schedules)
{
    if (!isset($schedules['cadaDosMinutos'])) {
        $schedules['cadaDosMinutos'] = array(
            'interval' => 15,
            'display'  => __('Cada 2 minutos')
        );
    }
    return $schedules;
}

add_action('sgasdgert54', 'procesarAudios');


function procesarAudios()
{
    $directorio_audios = '/home/asley01/MEGA/Waw/Kits/MEMPHIS KIT/VOCAL MEMPHIS SAMPLE/';
    $lock_file = '/tmp/procesar_audios.lock';
    $max_reintentos = 5;
    $espera_segundos = 5;

    $fp = fopen($lock_file, 'c');
    if ($fp === false) {
        autLog("Error: No se pudo abrir el archivo de bloqueo.");
        return;
    }
    

    $intentos = 0;
    while (!flock($fp, LOCK_EX | LOCK_NB)) {
        $intentos++;
        if ($intentos >= $max_reintentos) {
            autLog("Error: No se pudo obtener el bloqueo después de $max_reintentos intentos.");
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
        } else {
            autLog("No se encontró un audio válido para procesar.");
        }
    } catch (Exception $e) {
        autLog("Error durante el procesamiento: " . $e->getMessage());
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
    $max_intentos = 50; // Número máximo de intentos recursivos
    if ($intentos >= $max_intentos) {
        autLog("Error: Se alcanzó el número máximo de intentos ($max_intentos) en buscarUnAudioValido.");
        return null;
    }

    $extensiones_permitidas = ['wav', 'mp3'];
    if (!is_dir($directorio) || !is_readable($directorio)) {
        autLog("Error: El directorio '$directorio' no existe o no es legible. Se intentará cambiar permisos.");
        $output = shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');
        autLog("Salida de permisos.sh: " . $output);
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
        $hash = recalcularHash($archivo_seleccionado);

        if (!$hash) {
            autLog("Error: No se pudo calcular el hash del archivo '$archivo_seleccionado'. Reintentando.");
            return buscarUnAudioValido($directorio, $intentos + 1);
        }

        if (debeProcesarse($archivo_seleccionado, $hash)) {
            return ['ruta' => $archivo_seleccionado, 'hash' => $hash];
        } else {
            return buscarUnAudioValido($directorio, $intentos + 1);
        }
    } catch (Exception $e) {
        autLog("Excepción: " . $e->getMessage() . " en buscarUnAudioValido.");
        $output = shell_exec('sudo /var/www/wordpress/wp-content/themes/2upra3v/app/Commands/permisos.sh 2>&1');
        autLog("Salida de permisos.sh: " . $output);
        return null;
    }

    return null;
}



function debeProcesarse($ruta_archivo, $file_hash)
{
    try {
        if (!file_exists($ruta_archivo)) {
            autLog("debeProcesarse: El archivo no existe en la ruta: $ruta_archivo");
            return false;
        }

        if (!$file_hash) {
            autLog("debeProcesarse: No se proporcionó un hash válido");
            return false;
        }

        // Filtrar hashes existentes solo para archivos WAV o MP3
        $hashes_existentes = obtenerHashesFiltrados(['wav', 'mp3']);
        $hash_verificado = verificarCargaArchivoPorHash($file_hash);
        autLog("debeProcesarse: Hash verificado: " . ($hash_verificado ? "SI" : "NO") . " para hash: $file_hash");

        // Verificar similitud con hashes existentes y condición de carga antes de eliminar
        foreach ($hashes_existentes as $hash_existente) {
            if (sonHashesSimilaresAut($file_hash, $hash_existente['file_hash'])) {
                autLog("debeProcesarse: Se encontró un hash similar en la base de datos");

                if ($hash_verificado && file_exists($ruta_archivo)) {
                    $eliminado = unlink($ruta_archivo);
                    autLog("debeProcesarse: Eliminación del archivo: " . ($eliminado ? "EXITOSA" : "FALLIDA") . " - Ruta: $ruta_archivo");
                    $hash_eliminado = eliminarPorHash($file_hash);
                    autLog("debeProcesarse: Eliminación del hash: " . ($hash_eliminado ? "EXITOSA" : "FALLIDA") . " - Hash: $file_hash");
                } else {
                    autLog("debeProcesarse: El archivo no se puede eliminar ya que no pasó la verificación de carga.");
                }
                return false;
            }
        }

        autLog("debeProcesarse: El archivo debe procesarse - Ruta: $ruta_archivo, Hash: $file_hash");
        return true;
    } catch (Exception $e) {
        autLog("debeProcesarse: Error - " . $e->getMessage());
        return false;
    }
}

function obtenerHashesFiltrados($extensiones)
{
    global $wpdb;
    $extensiones_regex = implode('|', array_map('preg_quote', $extensiones));
    $query = $wpdb->prepare(
        "SELECT file_hash FROM {$wpdb->prefix}file_hashes WHERE file_url REGEXP %s",
        '\.(' . $extensiones_regex . ')$'
    );
    return $wpdb->get_results($query, ARRAY_A);
}

function verificarCargaArchivoPorHash($file_hash)
{
    // Obtener los detalles del archivo usando el hash
    $archivo = obtenerHash($file_hash);

    if (!$archivo) {
        return false;
    }

    $file_id = $archivo['id'];
    $file_url = $archivo['file_url'];

    // Inicializar cURL para verificar la carga del archivo
    $ch = curl_init($file_url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Verificación de código de respuesta
    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        actualizarEstadoArchivo($file_id, 'loss');
        return false;
    }
}

function sonHashesSimilaresAut($hash1, $hash2, $umbral = 0.80)
{
    if (empty($hash1) || empty($hash2)) {
        return false;
    }

    $valores1 = array_map('hexdec', str_split($hash1, 2));
    $valores2 = array_map('hexdec', str_split($hash2, 2));

    if (count($valores1) !== count($valores2)) {
        return false;
    }

    $suma_diferencias_cuadradas = 0;
    $max_diferencia = 255;

    for ($i = 0; $i < count($valores1); $i++) {
        $diferencia = abs($valores1[$i] - $valores2[$i]);
        $suma_diferencias_cuadradas += pow($diferencia, 2);
    }

    $distancia = sqrt($suma_diferencias_cuadradas);
    $similitud = 1 - ($distancia / (sqrt(count($valores1)) * $max_diferencia));

    return $similitud >= $umbral;
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
