<?
/*
add_action('init', 'iniciar_cron_procesamiento_audios');
function iniciar_cron_procesamiento_audios()
{
    if (!wp_next_scheduled('cada55seg')) {
        wp_schedule_event(time(), 'cada55segundos', 'cada55seg');
        autLog("Cron de procesamiento de audios programado para cada 55 segundos.");
    }
}

add_filter('cron_schedules', 'definir_cron_cada_55_segundos');
function definir_cron_cada_55_segundos($schedules)
{
    if (!isset($schedules['cada55segundos'])) {
        $schedules['cada55segundos'] = array(
            'interval' => 55, // 55 segundos
            'display'  => __('Cada 55 segundos')
        );
    }
    return $schedules;
}

// Conecta el evento cron a la función de procesamiento de audios
add_action('cada55seg', 'procesarAudios');


*/
function procesarAudios()
{

    if (!defined('LOCAL') || (defined('LOCAL') && LOCAL === true)) {
        return;
    }

    autLog("procesarAudios start ");
    $directorio_audios = '/home/asley01/MEGA/Waw/Kits/';
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
    $max_intentos = 100;
    $max_intentos_hash = 3;
    $carpeta_protegida = '/home/asley01/MEGA/Waw/Kits/'; // Ruta de la carpeta que no se debe borrar

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
                    $archivos[] = $file->getPathname();
                } else {
                    try {
                        unlink($file->getPathname());
                        autLog("Archivo eliminado por extensión no permitida: " . $file->getPathname());
                    } catch (Exception $e) {
                        autLog("Error al eliminar archivo no permitido: " . $e->getMessage());
                    }
                }
            }
        }
        
        foreach ($subcarpetas as $subcarpeta) {
          if ($subcarpeta !== $carpeta_protegida) { // Verificar si la carpeta es la protegida
            $carpeta_vacia = !(new FilesystemIterator($subcarpeta))->valid();
                if ($carpeta_vacia) {
                    try {
                        rmdir($subcarpeta);
                        autLog("Carpeta vacía eliminada: " . $subcarpeta);
                    } catch (Exception $e) {
                        autLog("Error al eliminar carpeta vacía: " . $e->getMessage());
                    }
                }
            }
        }

        if (empty($archivos)) {
            return buscarUnAudioValido($directorio, $intentos + 1);
        }

        $archivo_seleccionado = $archivos[array_rand($archivos)];
        $hash = null;
        $intentos_hash = 0;

        while ($intentos_hash < $max_intentos_hash && !$hash) {
            $hash = recalcularHash($archivo_seleccionado);
            $intentos_hash++;
            if (!$hash) {
                autLog("Error: No se pudo calcular el hash del archivo '$archivo_seleccionado'. Intento $intentos_hash.");
            }
        }

        if (!$hash) {
            autLog("Error: No se pudo calcular el hash después de $max_intentos_hash intentos. Eliminando archivo.");
            unlink($archivo_seleccionado);
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
            // Note: sonHashesSimilaresAut is now in FileHashService.php
            if (function_exists('sonHashesSimilaresAut') && sonHashesSimilaresAut($file_hash, $hash_existente['file_hash'])) {
                autLog("debeProcesarse: Se encontró un hash similar en la base de datos");

                if ($hash_verificado && file_exists($ruta_archivo)) {
                    $eliminado = unlink($ruta_archivo);
                    autLog("debeProcesarse: Eliminación del archivo: " . ($eliminado ? "EXITOSA" : "FALLIDA") . " - Ruta: $ruta_archivo");
                    $hash_eliminado = eliminarPorHash($file_hash);
                    autLog("debeProcesarse: Eliminación del hash: " . ($hash_eliminado ? "EXITOSA" : "FALLIDA") . " - Hash: $file_hash");
                } else {
                    // Mover el archivo y crear un archivo de texto con la razón
                    $nueva_ruta = "/home/asley01/MEGA/Waw/Verificar/" . basename($ruta_archivo);
                    if (rename($ruta_archivo, $nueva_ruta)) {
                        $razon_no_eliminar = "Razón: No pasó la verificación de carga.\nHash del archivo: $file_hash\nRuta original: $ruta_archivo\nFecha: " . date("Y-m-d H:i:s");
                        file_put_contents($nueva_ruta . "_razon.txt", $razon_no_eliminar);
                        autLog("debeProcesarse: Archivo movido a $nueva_ruta y motivo de no eliminación registrado.");
                    } else {
                        autLog("debeProcesarse: No se pudo mover el archivo a la ruta de verificación: $nueva_ruta");
                    }
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
        '\\.(' . $extensiones_regex . ')$'
    );
    return $wpdb->get_results($query, ARRAY_A);
}

//esto ya no se puede usar, pues los archivos ya no son accesibles desde la web, pero lo que puede hacer es ver si el archivo existe 
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

// Refactor(Org): Moved function sonHashesSimilaresAut to app/Services/FileHashService.php

// Refactor(Org): Moved function obtenerHash to app/Services/FileHashService.php

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
