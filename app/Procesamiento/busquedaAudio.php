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

/*
Por si se bloquean
sudo chmod -R o+rx /home/asley01/MEGA/Waw/X/
sudo chown -R asley01:www-data /home/asley01/MEGA/Waw/X/
sudo chmod -R g+rx /home/asley01/MEGA/Waw/X/
*/


// Paso 1 - Ejecuta cada 4 minutos, envía un solo audio válido para autProcesarAudio
function procesarAudios()
{
    autLog("procesarAudios llamado");
    $directorio_audios = '/home/asley01/MEGA/Waw/X';
    $lock_file = '/tmp/procesar_audios.lock';

    // Intentar crear y obtener un candado exclusivo
    $fp = fopen($lock_file, 'c');
    if ($fp === false) {
        autLog("Error al abrir el archivo de bloqueo: $lock_file.");
        return;
    }
    if (!flock($fp, LOCK_EX | LOCK_NB)) {
        autLog("No se pudo obtener el bloqueo exclusivo: otro proceso está en ejecución.");
        return;
    }

    try {
        autLog("Bloqueo obtenido, iniciando el procesamiento de audios.");
        $audio_info = buscarUnAudioValido($directorio_audios);
        if ($audio_info) {
            autLog("Audio válido encontrado: " . $audio_info['ruta']);
            autRevisarAudio($audio_info['ruta'], $audio_info['hash']);
        } else {
            autLog("No se encontró ningún audio válido en el directorio: $directorio_audios.");
        }
    } catch (Exception $e) {
        autLog("Error durante el procesamiento de audios: " . $e->getMessage());
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
        autLog("Bloqueo liberado, proceso finalizado.");
        
        // Verificar si el lock_file actual es el que se creó
        if (file_exists($lock_file)) {
            unlink($lock_file);
            autLog("Archivo de bloqueo eliminado: $lock_file.");
        }
    }
}

function buscarUnAudioValido($directorio, $intentos_recursion = 3) {
    $extensiones_permitidas = ['wav', 'mp3'];
    
    // Verificar si el directorio existe y es accesible
    if (!is_dir($directorio) || !is_readable($directorio)) {
        autLog("[buscarUnAudioValido] Error: El directorio no existe o no es accesible: {$directorio}");
        
        // Aplicar comandos de permisos si el directorio no es accesible
        autLog("[buscarUnAudioValido] Intentando corregir permisos...");
        $comando1 = "sudo chmod -R o+rx {$directorio}";
        $comando2 = "sudo chown -R asley01:www-data {$directorio}";
        $comando3 = "sudo chmod -R g+rx {$directorio}";
        exec($comando1, $output1, $return_var1);
        exec($comando2, $output2, $return_var2);
        exec($comando3, $output3, $return_var3);

        // Verificar si los comandos se ejecutaron correctamente
        if ($return_var1 !== 0 || $return_var2 !== 0 || $return_var3 !== 0) {
            autLog("[buscarUnAudioValido] Error: No se pudieron cambiar los permisos del directorio: {$directorio}");
            return null;
        }

        // Verificar nuevamente si ahora es accesible
        if (!is_readable($directorio)) {
            autLog("[buscarUnAudioValido] Error: No se pudo corregir los permisos para el directorio: {$directorio}");
            return null;
        }
    }

    autLog("[buscarUnAudioValido] Iniciando la búsqueda en el directorio: {$directorio}");

    try {
        // Obtener todas las subcarpetas
        $subcarpetas = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && is_readable($item->getPathname())) {
                $subcarpetas[] = $item->getPathname();
            } else {
                autLog("[buscarUnAudioValido] No se puede acceder a la carpeta: {$item->getPathname()}, omitiendo.");
            }
        }

        // Si no hay subcarpetas, usar el directorio principal
        if (empty($subcarpetas)) {
            $subcarpetas[] = $directorio;
        }

        // Seleccionar una subcarpeta aleatoria
        $carpeta_seleccionada = $subcarpetas[array_rand($subcarpetas)];
        autLog("[buscarUnAudioValido] Carpeta seleccionada aleatoriamente: {$carpeta_seleccionada}");

        // Obtener todos los archivos de la carpeta seleccionada
        $archivos = [];
        $dir_iterator = new DirectoryIterator($carpeta_seleccionada);
        foreach ($dir_iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower($file->getExtension());
                if (in_array($ext, $extensiones_permitidas, true)) {
                    $nombreArchivo = $file->getFilename();
                    if (strpos($nombreArchivo, '2upra_') !== 0) {
                        $archivos[] = $file->getPathname();
                    }
                }
            }
        }

        // Si no hay archivos válidos en esta carpeta, intentar con otra
        if (empty($archivos)) {
            autLog("[buscarUnAudioValido] No se encontraron archivos válidos en la carpeta seleccionada");

            // Verificar si hemos agotado los intentos de recursión
            if ($intentos_recursion > 0) {
                return buscarUnAudioValido($directorio, $intentos_recursion - 1); // Recursión limitada
            } else {
                autLog("[buscarUnAudioValido] Se alcanzó el límite de intentos de búsqueda.");
                return null;
            }
        }

        // Seleccionar un archivo aleatorio
        $archivo_seleccionado = $archivos[array_rand($archivos)];
        $hash = hash_file('sha256', $archivo_seleccionado);

        if (!$hash) {
            autLog("[buscarUnAudioValido] No se pudo calcular el hash para el archivo: {$archivo_seleccionado}");

            // Verificar si hemos agotado los intentos de recursión
            if ($intentos_recursion > 0) {
                return buscarUnAudioValido($directorio, $intentos_recursion - 1); // Intentar con otro archivo
            } else {
                autLog("[buscarUnAudioValido] Se alcanzó el límite de intentos de búsqueda.");
                return null;
            }
        }

        // Verificar si debe procesarse
        if (debeProcesarse($archivo_seleccionado, $hash)) {
            autLog("[buscarUnAudioValido] Archivo válido encontrado: {$archivo_seleccionado}");
            return ['ruta' => $archivo_seleccionado, 'hash' => $hash];
        } else {
            autLog("[buscarUnAudioValido] El archivo no necesita ser procesado, buscando otro...");

            // Verificar si hemos agotado los intentos de recursión
            if ($intentos_recursion > 0) {
                return buscarUnAudioValido($directorio, $intentos_recursion - 1); // Intentar con otro archivo
            } else {
                autLog("[buscarUnAudioValido] Se alcanzó el límite de intentos de búsqueda.");
                return null;
            }
        }

    } catch (Exception $e) {
        autLog("[buscarUnAudioValido] Excepción al iterar directorios: " . $e->getMessage());
        return null;
    }

    return null;
}


// Paso 3 - Verificar si el archivo debe ser procesado
function debeProcesarse($ruta_archivo, $file_hash)
{
    try {
        if (!file_exists($ruta_archivo)) {
            autLog("[debeProcesarse] Error: El archivo no existe: {$ruta_archivo}");
            return false;
        }

        if (!$file_hash) {
            autLog("[debeProcesarse] Error: Hash inexistente para el archivo: {$ruta_archivo}");
            return false;
        }

        $hash_obtenido = obtenerHash($file_hash);
        $hash_verificado = verificarCargaArchivoPorHash($file_hash);

        autLog("[debeProcesarse] Resultado de obtenerHash para {$ruta_archivo}: " . ($hash_obtenido ? 'Existe' : 'No existe'));
        autLog("[debeProcesarse] Resultado de verificarCargaArchivoPorHash para {$ruta_archivo}: " . ($hash_verificado ? 'Existe' : 'No existe'));

        if ($hash_obtenido || $hash_verificado) {
            autLog("[debeProcesarse] El archivo ya ha sido procesado previamente: {$ruta_archivo}");
            return false;
        }

        autLog("[debeProcesarse] El archivo está listo para ser procesado: {$ruta_archivo}");
        return true;
    } catch (Exception $e) {
        autLog("[debeProcesarse] Excepción capturada: " . $e->getMessage());
        return false;
    }
}

// Paso 4 - Revisar y procesar el audio automáticamente
function autRevisarAudio($audio, $file_hash)
{
    if (!file_exists($audio)) {
        autLog("[autRevisarAudio] Error: El archivo de audio no existe: {$audio}");
        return;
    }

    $upload_dir = wp_upload_dir();
    $file_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $audio);
    $user_id = 44;

    autLog("[autRevisarAudio] Intentando guardar el hash para el archivo: {$audio} con hash: {$file_hash}");
    if (!guardarHash($file_hash, $file_url, 'confirmed', $user_id)) {
        autLog("[autRevisarAudio] Error: No se pudo guardar el hash en la base de datos para el archivo: {$audio}");
        return;
    }

    autLog("[autRevisarAudio] Hash guardado exitosamente. Iniciando procesamiento del audio.");
    autProcesarAudio($audio);
    autLog("[autRevisarAudio] Procesamiento del audio completado para: {$audio}");
}