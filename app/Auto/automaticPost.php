<?

function autProcesarAudio($rutaOriginalOne)
{
    autLog("autProcesarAudio start");
    $file_id = obtenerFileIDPorURL($rutaOriginalOne);
    if ($file_id === false) {
        eliminarHash($file_id);
        autLog("File ID no encontrado: $rutaOriginalOne");
    }

    if (!file_exists($rutaOriginalOne)) {
        eliminarHash($file_id);
        autLog("Archivo original no encontrado: $rutaOriginalOne");
        return;
    }

    $fileSizeMB = filesize($rutaOriginalOne) / 1048576;
    if ($fileSizeMB < 0.01) {
        $motivoFallo = "Archivo demasiado pequeño o inválido (menos de 0.01 MB)";
        autLog($motivoFallo . ": $rutaOriginalOne");
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
        return;
    }

    $path_parts = pathinfo($rutaOriginalOne);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        $motivoFallo = "Directorio inválido: {$path_parts['dirname']}";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo);
        return;
    }

    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];
    $temp_path = "$directory/{$basename}_temp.$extension";
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -map_metadata -1 -map 0:a -c:a copy " . escapeshellarg($temp_path) . " -y";
    exec($comando_strip_metadata, $output_strip, $return_strip);

    if ($return_strip !== 0) {
        $motivoFallo = "Error al eliminar metadatos: " . implode(" | ", $output_strip);
        eliminarHash($file_id);
        autLog($motivoFallo);
        $temp_path = $rutaOriginalOne;
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Movido aquí para manejar fallo de metadatos
    }

    if (!rename($temp_path, $rutaOriginalOne)) {
        $motivoFallo = "Error al reemplazar archivo original";
        eliminarHash($file_id);
        autLog($motivoFallo);
        if (!copy($temp_path, $rutaOriginalOne)) {
            autLog("Error al copiar archivo temporal, no se pudo reemplazar el original");
            manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Movido aquí si falla la copia
        } else {
            unlink($temp_path);
        }
    }

    $rutaWpLiteDos = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -b:a 128k " . escapeshellarg($rutaWpLiteDos) . " -y";
    exec($comando_lite, $output_lite, $return_lite);

    if ($return_lite !== 0) {
        $motivoFallo = "Error al crear versión lite: " . implode(" | ", $output_lite);
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Manejar fallo en la creación del lite
    }

    if (!file_exists($rutaWpLiteDos)) {
        $motivoFallo = "El archivo lite no se creó: $rutaWpLiteDos";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Manejar si no se crea el lite
    }

    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            $motivoFallo = "No se pudo crear directorio audio/";
            eliminarHash($file_id);
            autLog($motivoFallo);
            manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Manejar fallo en la creación del directorio
        }
    }

    if (!is_writable($target_dir_audio)) {
        $motivoFallo = "Directorio audio/ sin permisos de escritura";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Manejar falta de permisos
    }

    $rutaWpLiteOne = $target_dir_audio . "{$basename}_lite.mp3";

    if (!copy($rutaWpLiteDos, $rutaWpLiteOne)) {
        $motivoFallo = "Error al copiar archivo lite: " . error_get_last()['message'];
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Manejar error en la copia del lite
    }

    unlink($rutaWpLiteDos);

    if (!file_exists($rutaWpLiteOne)) {
        $motivoFallo = "Archivo lite no existe después de copiar: $rutaWpLiteOne";
        eliminarHash($file_id);
        autLog($motivoFallo);
        manejarArchivoFallido($rutaOriginalOne, $motivoFallo); // Manejar si el lite no existe después de copiar
    }

    chmod($rutaWpLiteOne, 0644);

    autLog("autProcesarAudio end");
    crearAutPost($rutaOriginalOne, $rutaWpLiteOne, $file_id);
}

function manejarArchivoFallido($rutaArchivo, $motivo)
{
    $directorioVerificar = "/home/asley01/MEGA/Waw/Verificar/";
    if (!file_exists($directorioVerificar)) {
        mkdir($directorioVerificar, 0777, true); // Crear el directorio si no existe
    }

    $nombreArchivo = basename($rutaArchivo);
    $nuevoDestino = $directorioVerificar . $nombreArchivo;

    if (rename($rutaArchivo, $nuevoDestino)) {
        // Crear un archivo de texto explicando el fallo
        $archivoTexto = $directorioVerificar . $nombreArchivo . ".txt";
        file_put_contents($archivoTexto, "Fallo al procesar el archivo: $nombreArchivo\nMotivo: $motivo");
    } else {
        autLog("Error al mover el archivo a $directorioVerificar");
    }
}

/*

[06-Nov-2024 01:07:00 UTC] PHP Warning:  Array to string conversion in /var/www/wordpress/wp-content/themes/2upra3v/app/Auto/automaticPost.php on line 109


*/


function automaticAudio($rutaArchivo, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
    autLog("automaticAudio end");
    $resultados = procesarArchivoAudioPython($rutaArchivo);

    if ($resultados) {
        echo "BPM: " . ($resultados['bpm'] ?? '') . "\n";
        echo "Emotion: " . ($resultados['emotion'] ?? '') . "\n";
        echo "Key: " . ($resultados['key'] ?? '') . "\n";
        echo "Scale: " . ($resultados['scale'] ?? '') . "\n";
        echo "Pitch: " . ($resultados['pitch'] ?? '') . "\n";
    } else {
        echo "Error procesando el archivo de audio.";
    }


    $informacion_archivo = '';
    if ($nombre_archivo) {
        $informacion_archivo .= "Archivo (IMPORTANCIA ALTA): '{$nombre_archivo}'\n";
    }
    if ($carpeta) {
        $informacion_archivo .= "Carpeta (IMPORTANCIA MEDIA): '{$carpeta}'\n";
    }
    if ($carpeta_abuela) {
        $informacion_archivo .= "Carpeta abuela (IMPORTANCIA BAJA): '{$carpeta_abuela}'\n";
    }
    if ($rutaArchivo) {
        $informacion_archivo .= "Ruta completa (PUEDE AYUDAR SI EL RESTO DE INFORMACIONES NO ES CLARA): '{$rutaArchivo}'\n";
    }

    $prompt = "Este audio fue subido automáticamente. Información:"
        . "{$informacion_archivo}"
        . "Por favor, determina una descripción precisa del audio utilizando el siguiente formato JSON. La información como el nombre y las carpetas son información super relevante para completar el JSON. Por favor, ignora cualquier nombre comercial, dominio, redes sociales o información no relevante que pueda contener el nombre o las carpetas. También ignora la palabra 'lite' o '2upra'. El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, que sea corta pero sin perder detalles importantes. Importante por no digas nada sobre las carpetas o donde esta ubicado el archivo, solo es una guia para entender de que trata el audio no hay que comentarlo, si archivo tiene un nombre claro, hay que tenerlo en cuenta, y luego el resto. Con los artistas posible siempre piensa en uno o varios que tengan la vibra de la descripción que la gente pueda relacionar con el audio. No uses palabras como 'Repetitive', 'Energetic', 'Powerful' en la descripcion corta. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
        . '{"descripcion_ia":{"es":"(aquí iría una descripción tuya del audio muy detallada)", "en":"(aquí en inglés)"},'
        . '"instrumentos_principal":{"es":["Piano"], "en":["Piano"]},'
        . '"nombre_corto":{"es":["(maximo 3 palabras)"], "en":["Kick Vitagen"]},'
        . '"descripcion_corta":{"es":["(entre 4 a 6 palabras)"], "en":["(en ingles)"]},'
        . '"estado_animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"genero_posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"artista_posible":{"es":["Freddie Dredd", "Flume"], "en":["Freddie Dredd", "Flume"]},'
        . '"tipo_audio":{"es":["determina si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"tags_posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"sugerencia_busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . "Te dejo una guía interesante de tags que puedes usar, por favor, usa solo los que realmente describan el audio: "
        . "Tipo y Formato: Acoustic, Chord, Down Sweep/Fall, Dry, Harmony, Loop, Melody, Mixed, Monophonic, One Shot, Polyphonic, Processed, Progression, Riser/Sweep, Short, Wet. "
        . "Timbre y Tono: Bassy, Boomy, Breathy, Bright, Buzzy, Clean, Coarse/Harsh, Cold, Dark, Delicate, Detuned, Dissonant, Distorted, Exotic, Fat, Full, Glitchy, Granular, Gloomy, Hard, High, Hollow, Low, Metallic, Muffled, Muted, Narrow, Noisy, Round, Sharp, Shimmering, Sizzling, Smooth, Soft, Piercing, Thin, Tinny, Warm, Wide, Wooden. "
        . "Género: Ambient, Breaks, Chillout, Chiptune, Cinematic, Classical, Acid House, Deep House, Disco, Drum & Bass, Dubstep, Ethnic/World, Electro House, Electro, Electro Swing, Folk/Country, Funk/Soul, Jazz, Jungle, House, Hip Hop, Latin/Afro Cuban, Minimal House, Nu Disco, R&B, Reggae/Dub, Reggaeton, Rock, Pop, Progressive House, Synthwave, Tech House, Techno, Trance, Trap, Vocals, Phonk, Memphis. "
        . "Estilo y Técnica: Arpeggiated, Decaying, Echoing, Long Release, Legato, Glissando/Glide, Pad, Percussive, Pitch Bend, Plucked, Pulsating, Punchy, Randomized, Slow Attack, Sweep/Filter Mod, Staccato/Stabs, Stuttered/Gated, Straight, Sustained, Syncopated, Uptempo, Wobble, Vibrato. "
        . "Calidad y Tecnología: Analog, Compressed, Digital, Dynamic, Loud, Range, Female, Funky, Jazzy, Lo Fi, Male, Quiet, Vintage, Vinyl. "
        . "Estado de Ánimo: Aggressive, Angry, Bouncy, Calming, Carefree, Cheerful, Climactic, Cool, Dramatic, Elegant, Epic, Excited, Energetic, Fun, Futuristic, Gentle, Groovy, Happy, Haunting, Hypnotic, Industrial, Manic, Melancholic, Mellow, Mystical, Nervous, Passionate, Peaceful, Playful, Powerful, Rebellious, Reflective, Relaxing, Romantic, Rowdy, Sad, Sentimental, Sexy, Soothing, Sophisticated, Spacey, Suspenseful, Uplifting, Urgent, Weird."
        . " Es crucial determinar si es un loop, un one shot o un sample. Usa tags de una palabra y optimiza el SEO con sugerencias de búsqueda relevantes. Sé muy detallado sin perder precisión. Aunque te pido en español y en ingles, hay algunas palabras que son mejor mantenerlas en ingles cuando en español son muy frecuentes, por ejemplo, kick, snare, cowbell, etc. Ignora '/home/asley01/MEGA/Waw/Kits' no es relevante, el resto de la ruta si.";

    $descripcion = generarDescripcionIA($rutaArchivo, $prompt);
    autLog("Descripcion generada");
    if ($descripcion) {
        // Convertir a UTF-8
        $descripcion_utf8 = mb_convert_encoding($descripcion, 'UTF-8', 'auto');
        $descripcion_procesada = json_decode(trim($descripcion_utf8, "```json \n"), true, 512, JSON_UNESCAPED_UNICODE);

        // Comprobar que la decodificación JSON fue exitosa y que el campo 'descripcion_ia' existe
        if (!$descripcion_procesada || !isset($descripcion_procesada['descripcion_ia']) || !is_array($descripcion_procesada['descripcion_ia'])) {
            iaLog("Error: La descripción procesada no tiene el formato esperado.");
            return false; // Retornar false en caso de error de formato
        }

        // Crear los nuevos datos con la estructura correcta
        $nuevos_datos = [
            'descripcion_ia' => [
                'es' => $descripcion_procesada['descripcion_ia']['es'] ?? '',
                'en' => $descripcion_procesada['descripcion_ia']['en'] ?? ''
            ],
            'instrumentos_principal' => [
                'es' => $descripcion_procesada['instrumentos_principal']['es'] ?? [],
                'en' => $descripcion_procesada['instrumentos_principal']['en'] ?? []
            ],
            'nombre_corto' => [
                'es' => $descripcion_procesada['nombre_corto']['es'] ?? '',
                'en' => $descripcion_procesada['nombre_corto']['en'] ?? ''
            ],
            'descripcion_corta' => [
                'es' => $descripcion_procesada['descripcion_corta']['es'] ?? '',
                'en' => $descripcion_procesada['descripcion_corta']['en'] ?? ''
            ],
            'estado_animo' => [
                'es' => $descripcion_procesada['estado_animo']['es'] ?? [],
                'en' => $descripcion_procesada['estado_animo']['en'] ?? []
            ],
            'artista_posible' => [
                'es' => $descripcion_procesada['artista_posible']['es'] ?? [],
                'en' => $descripcion_procesada['artista_posible']['en'] ?? []
            ],
            'genero_posible' => [
                'es' => $descripcion_procesada['genero_posible']['es'] ?? [],
                'en' => $descripcion_procesada['genero_posible']['en'] ?? []
            ],
            'tipo_audio' => [
                'es' => $descripcion_procesada['tipo_audio']['es'] ?? '',
                'en' => $descripcion_procesada['tipo_audio']['en'] ?? ''
            ],
            'tags_posibles' => [
                'es' => $descripcion_procesada['tags_posibles']['es'] ?? [],
                'en' => $descripcion_procesada['tags_posibles']['en'] ?? []
            ],
            'sugerencia_busqueda' => [
                'es' => $descripcion_procesada['sugerencia_busqueda']['es'] ?? [],
                'en' => $descripcion_procesada['sugerencia_busqueda']['en'] ?? []
            ]
        ];

        //autLog("Descripción del audio guardada para el post ID: {$nombre_archivo}");
    } else {
        // Si no se generó ninguna descripción, retornar false
        iaLog("Error: No se pudo generar la descripción.");
        return false;
    }

    $nuevos_datos_algoritmo = isset($nuevos_datos) ? [
        'bpm' => $resultados['bpm'] ?? '',
        'emotion' => $resultados['emotion'] ?? '',
        'key' => $resultados['key'] ?? '',
        'scale' => $resultados['scale'] ?? '',

        'descripcion_ia' => $nuevos_datos['descripcion_ia'],
        'instrumentos_principal' => $nuevos_datos['instrumentos_principal'],
        'nombre_corto' => $nuevos_datos['nombre_corto'],
        'descripcion_corta' => $nuevos_datos['descripcion_corta'],
        'estado_animo' => $nuevos_datos['estado_animo'],
        'artista_posible' => $nuevos_datos['artista_posible'],
        'genero_posible' => $nuevos_datos['genero_posible'],
        'tipo_audio' => $nuevos_datos['tipo_audio'],
        'tags_posibles' => $nuevos_datos['tags_posibles'],
        'sugerencia_busqueda' => $nuevos_datos['sugerencia_busqueda']
    ] : [];
    autLog("automaticAudio end");
    return $nuevos_datos_algoritmo;
}

/*
esta funcion funciona en otra parte y perfecta, no hay que alterar su funcionamiento original pero creo que puede servir para este nuevo proposito tmambien

La ruta wp lite, necesita la ruta del audo en el serviidor
la ruta original se puede omitir, el file_id corresponde a idHash_audioId2 o idHash_audioId3 
es decir que post_audio_lite_2 tendra su idHash_audioId2 y asi sucesivamente por cada audio
tambien por cada audio hay un post_audio2, post_audio3, se debe conservar
el autor id será el autor original

y las metas de paraColab, paraDescarga, artista, fan, rola, sample, tagsUsuario del post original necesito que se copien si es que existe
*/


function crearAutPost($rutaOriginal = null, $rutaWpLite = null, $file_id = null, $autor_id = null, $post_original = null)
{
    error_log("[crearAutPost] Inicio crearAutPost con rutaOriginal: $rutaOriginal, rutaWpLite: $rutaWpLite, file_id: $file_id");

    if ($autor_id === null) {
        $autor_id = 44;
    }
    $nombre_archivo = pathinfo($rutaOriginal, PATHINFO_FILENAME);
    $carpeta = basename(dirname($rutaOriginal));
    $carpeta_abuela = basename(dirname(dirname($rutaOriginal)));
    $datosAlgoritmo = automaticAudio($rutaWpLite, $nombre_archivo, $carpeta, $carpeta_abuela);

    if (!$datosAlgoritmo) {
        error_log("[crearAutPost] Error: automaticAudio falló para rutaWpLite: $rutaWpLite");
        eliminarHash($file_id);
        return;
    }

    $descripcion_corta_es = $datosAlgoritmo['descripcion_corta']['en'] ?? '';
    $nombre_generado = $datosAlgoritmo['nombre_corto']['en'] ?? '';

    if (is_array($nombre_generado)) {
        $nombre_generado = $nombre_generado[0] ?? '';
    }

    if ($nombre_generado) {
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- áéíóúÁÉÍÓÚñÑ]/u', '', trim($nombre_generado));
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final = substr($nombre_generado_limpio . '_' . $id_unica . '_2upra', 0, 60);
    } else {
        error_log("[crearAutPost] Error: nombre_generado está vacío para rutaOriginal: $rutaOriginal");
        eliminarHash($file_id);
        return;
    }

    $extension_original = pathinfo($rutaOriginal, PATHINFO_EXTENSION);
    $nuevaRutaOriginal = dirname($rutaOriginal) . '/' . $nombre_final . '.' . $extension_original;
    if (!file_exists($rutaOriginal) || (file_exists($nuevaRutaOriginal) && !unlink($nuevaRutaOriginal))) {
        error_log("[crearAutPost] Error: No se puede renombrar el archivo original $rutaOriginal a $nuevaRutaOriginal");
        eliminarHash($file_id);
        return;
    }
    if (!rename($rutaOriginal, $nuevaRutaOriginal)) {
        error_log("[crearAutPost] Error: Fallo al renombrar $rutaOriginal a $nuevaRutaOriginal");
        eliminarHash($file_id);
        return;
    }

    if (!file_exists($rutaWpLite)) {
        error_log("[crearAutPost] Error: rutaWpLite $rutaWpLite no existe");
        return;
    }
    $extension_lite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
    $nuevo_nombre_lite = dirname($rutaWpLite) . '/' . $nombre_final . '_lite.' . $extension_lite;
    if (file_exists($nuevo_nombre_lite) && !unlink($nuevo_nombre_lite)) {
        error_log("[crearAutPost] Error: No se puede eliminar el archivo existente $nuevo_nombre_lite");
        eliminarHash($file_id);
        return;
    }
    if (!rename($rutaWpLite, $nuevo_nombre_lite)) {
        error_log("[crearAutPost] Error: Fallo al renombrar $rutaWpLite a $nuevo_nombre_lite");
        eliminarHash($file_id);
        return;
    }

    if (is_array($descripcion_corta_es)) {
        $descripcion_corta_es = $descripcion_corta_es[0] ?? '';
    }

    $titulo = mb_substr($descripcion_corta_es, 0, 60);
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $descripcion_corta_es,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);
    if (is_wp_error($post_id)) {
        error_log("[crearAutPost] Error: No se pudo crear el post. Error: " . $post_id->get_error_message());
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return;
    }

    update_post_meta($post_id, 'rutaOriginal', $nuevaRutaOriginal);
    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);
    update_post_meta($post_id, 'postAut', true);

    $audio_original_id = adjuntarArchivoAut($nuevaRutaOriginal, $post_id, $file_id);
    if (is_wp_error($audio_original_id)) {
        error_log("[crearAutPost] Error: Fallo al adjuntar el archivo original. Error: " . $audio_original_id->get_error_message());
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return $audio_original_id;
    }

    if (file_exists($nuevaRutaOriginal)) {
        unlink($nuevaRutaOriginal);
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        error_log("[crearAutPost] Error: Fallo al adjuntar el archivo lite. Error: " . $audio_lite_id->get_error_message());
        wp_delete_post($post_id, true);
        eliminarHash($file_id);
        return $audio_lite_id;
    }

    // Metadatos del post
    $existing_meta = get_post_meta($post_id);

    if (!isset($existing_meta['post_audio'])) {
        update_post_meta($post_id, 'post_audio', $audio_original_id);
    }
    if (!isset($existing_meta['post_audio_lite'])) {
        update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    }
    if (!isset($existing_meta['paraDescarga'])) {
        update_post_meta($post_id, 'paraDescarga', true);
    }
    if (!isset($existing_meta['nombreOriginal'])) {
        update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
    }
    if (!isset($existing_meta['carpetaOriginal'])) {
        update_post_meta($post_id, 'carpetaOriginal', $carpeta);
    }
    if (!isset($existing_meta['carpetaAbuelaOriginal'])) {
        update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);
    }
    if (!isset($existing_meta['audio_bpm'])) {
        update_post_meta($post_id, 'audio_bpm', $datosAlgoritmo['bpm'] ?? null);
    }
    if (!isset($existing_meta['audio_key'])) {
        update_post_meta($post_id, 'audio_key', $datosAlgoritmo['key'] ?? null);
    }
    if (!isset($existing_meta['audio_scale'])) {
        update_post_meta($post_id, 'audio_scale', $datosAlgoritmo['scale'] ?? null);
    }
    if (!isset($existing_meta['datosAlgoritmo'])) {
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE));
    }

    error_log("[crearAutPost] crearAutPost end con post_id: $post_id");
    return $post_id;
}


function adjuntarArchivoAut($archivo, $post_id, $file_id = null)
{

    if ($file_id !== null) {
        update_post_meta($post_id, 'idHash_audioId', $file_id);
    }

    // Variables para manejo de archivos temporales
    $es_url = filter_var($archivo, FILTER_VALIDATE_URL);
    $archivo_temp = '';

    if ($es_url) {
        // Descargar el archivo desde la URL a una ubicación temporal
        $temp_dir = sys_get_temp_dir();
        $nombre_archivo = basename(parse_url($archivo, PHP_URL_PATH));
        $archivo_temp = tempnam($temp_dir, 'upload_') . '_' . sanitize_file_name($nombre_archivo);

        $contenido = @file_get_contents($archivo);
        if ($contenido === false) {
            return new WP_Error('error_descarga', 'No se pudo descargar el archivo desde la URL: ' . esc_html($archivo));
        }

        // Guardar el contenido descargado en el archivo temporal
        if (file_put_contents($archivo_temp, $contenido) === false) {
            return new WP_Error('error_guardar_temporal', 'No se pudo guardar el archivo descargado en: ' . esc_html($archivo_temp));
        }

        $archivo_procesar = $archivo_temp;
    } else {
        // Asumir que es una ruta de archivo local
        $archivo_procesar = $archivo;
    }

    // Verificar si el archivo existe
    if (!file_exists($archivo_procesar)) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        return new WP_Error('archivo_no_encontrado', 'El archivo especificado no existe: ' . esc_html($archivo));
    }

    // Obtener información de la ruta de uploads
    $wp_upload_dir = wp_upload_dir();

    // Determinar si el archivo está dentro del directorio de uploads
    $ruta_relativa = '';
    if (strpos(realpath($archivo_procesar), realpath($wp_upload_dir['basedir'])) === 0) {
        // Archivo está dentro de uploads
        $ruta_relativa = str_replace(realpath($wp_upload_dir['basedir']) . DIRECTORY_SEPARATOR, '', realpath($archivo_procesar));
    }

    // Si el archivo no está en uploads, copiarlo al directorio de uploads
    if (empty($ruta_relativa)) {
        $filename = basename($archivo_procesar);
        $filename = sanitize_file_name($filename);
        $unique_filename = wp_unique_filename($wp_upload_dir['path'], $filename);
        $destino = trailingslashit($wp_upload_dir['path']) . $unique_filename;

        if (!copy($archivo_procesar, $destino)) {
            // Eliminar el archivo temporal si existe
            if ($es_url && file_exists($archivo_temp)) {
                @unlink($archivo_temp);
            }
            return new WP_Error('error_copia_archivo', 'No se pudo copiar el archivo al directorio de cargas.');
        }
    } else {
        // El archivo ya está en uploads
        $unique_filename = basename($archivo_procesar);
        $destino = $archivo_procesar;
    }

    // Obtener el tipo de archivo
    $filetype = wp_check_filetype($unique_filename, null);
    if (!$filetype['type']) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        // Si el archivo fue copiado a uploads, eliminarlo
        if (!empty($ruta_relativa)) {
            @unlink($destino);
        }
        return new WP_Error('tipo_archivo_no_soportado', 'El tipo de archivo no es soportado: ' . esc_html($unique_filename));
    }

    // Preparar los datos del adjunto
    $attachment = [
        'guid'           => $wp_upload_dir['url'] . '/' . $unique_filename,
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name(pathinfo($unique_filename, PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    // Insertar el adjunto en la base de datos
    $attach_id = wp_insert_attachment($attachment, $destino, $post_id);

    if (is_wp_error($attach_id)) {
        // Eliminar el archivo temporal si existe
        if ($es_url && file_exists($archivo_temp)) {
            @unlink($archivo_temp);
        }
        // Si el archivo fue copiado a uploads, eliminarlo
        if (!empty($ruta_relativa)) {
            @unlink($destino);
        }
        return $attach_id;
    }

    // Generar y actualizar los metadatos del adjunto
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $destino);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Obtener la URL del adjunto
    $adjunto_url = wp_get_attachment_url($attach_id);

    // Si se proporcionó un file_id, actualizar la URL usando actualizarUrlArchivo
    if (! is_null($file_id)) {
        // Asegúrate de que la función actualizarUrlArchivo esté definida
        if (function_exists('actualizarUrlArchivo')) {
            $resultado_actualizacion = actualizarUrlArchivo($file_id, $adjunto_url);

            // Verificar si la actualización fue exitosa
            if (is_wp_error($resultado_actualizacion)) {

                wp_delete_attachment($attach_id, true);
                return new WP_Error('actualizacion_url_fallida', 'No se pudo actualizar la URL del archivo: ' . $resultado_actualizacion->get_error_message());
            }
        } else {
            // Manejar el caso donde actualizarUrlArchivo no está definida
            wp_delete_attachment($attach_id, true);
            return new WP_Error('funcion_no_definida', 'La función actualizarUrlArchivo no está definida.');
        }
    }

    // Eliminar el archivo temporal si existe
    if ($es_url && file_exists($archivo_temp)) {
        @unlink($archivo_temp);
    }

    return $attach_id;
}


function buscar_archivo_recursivo($dir, $filename)
{
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            $result = buscar_archivo_recursivo($path, $filename);
            if ($result !== false) {
                return $result;
            }
        } elseif ($file === $filename) {
            return $path;
        }
    }
    return false;
}


/*
function actualizar_metas_posts_social() {
    // Define los argumentos para la consulta de posts
    $args = array(
        'post_type'      => 'social_post',
        'meta_key'       => 'postAut',
        'meta_value'     => '1',
        'posts_per_page' => -1, // Obtener todos los posts
        'fields'         => 'ids', // Solo IDs para eficiencia
    );

    $query = new WP_Query($args);

    if ( !$query->have_posts() ) {
        //autLog('No se encontraron posts de tipo social_post con postAut=1.');
        return;
    }

    // Directorio base donde se buscarán los archivos originales
    $directorio_base = '/home/asley01/MEGA/Waw/X/';

    foreach ( $query->posts as $post_id ) {
        // Verificar si los metadatos ya existen
        $rutaOriginal = get_post_meta( $post_id, 'rutaOriginal', true );
        $rutaLiteOriginal = get_post_meta( $post_id, 'rutaLiteOriginal', true );
        $idHash_audioId = get_post_meta( $post_id, 'idHash_audioId', true );

        // Solo procesar si al menos uno de los metadatos está vacío
        if ( $rutaOriginal && $rutaLiteOriginal && $idHash_audioId ) {
            continue; // Todos los metadatos están presentes, omitir
        }

        // Obtener el ID de adjunto de post_audio
        $post_audio_id = get_post_meta( $post_id, 'post_audio', true );
        if ( !$post_audio_id ) {
            //autLog("Post ID $post_id: No se encontró 'post_audio'.");
        }

        // Obtener el ID de adjunto de post_audio_lite
        $post_audio_lite_id = get_post_meta( $post_id, 'post_audio_lite', true );
        if ( !$post_audio_lite_id ) {
            //autLog("Post ID $post_id: No se encontró 'post_audio_lite'.");
        }

        // Actualizar 'rutaOriginal' si falta
        if ( !$rutaOriginal && $post_audio_id ) {
            $adjunto = get_post( $post_audio_id );
            if ( $adjunto ) {
                $filename = basename( get_attached_file( $post_audio_id ) );
                $ruta_completa = buscar_archivo_recursivo( $directorio_base, $filename );
                if ( $ruta_completa ) {
                    update_post_meta( $post_id, 'rutaOriginal', $ruta_completa );
                } else {
                    //autLog("Post ID $post_id: No se encontró el archivo original '$filename'.");
                }
            } else {
                //autLog("Post ID $post_id: No se encontró el adjunto con ID $post_audio_id.");
            }
        }

        // Actualizar 'rutaLiteOriginal' si falta
        if ( !$rutaLiteOriginal && $post_audio_lite_id ) {
            $adjunto_lite = get_post( $post_audio_lite_id );
            if ( $adjunto_lite ) {
                $ruta_lite = get_attached_file( $post_audio_lite_id );
                if ( $ruta_lite ) {
                    update_post_meta( $post_id, 'rutaLiteOriginal', $ruta_lite );
                } else {
                    //autLog("Post ID $post_id: No se pudo obtener la ruta de 'post_audio_lite'.");
                }
            } else {
                //autLog("Post ID $post_id: No se encontró el adjunto lite con ID $post_audio_lite_id.");
            }
        }

        // Actualizar 'idHash_audioId' si falta
        if ( !$idHash_audioId && $post_audio_id ) {
            $adjunto_url = wp_get_attachment_url( $post_audio_id );
            if ( $adjunto_url ) {
                $file_id = obtenerFileIDPorURL( $adjunto_url ); 
                if ( $file_id ) {
                    update_post_meta( $post_id, 'idHash_audioId', $file_id );
                } else {
                    //autLog("Post ID $post_id: No se pudo obtener 'idHash_audioId' para la URL '$adjunto_url'.");
                }
            } else {
                //autLog("Post ID $post_id: No se pudo obtener la URL del adjunto con ID $post_audio_id.");
            }
        }
    }

    //autLog('Actualización de metadatos de posts social_post completada.');
}

ejecutar_actualizar_metas_posts_social_una_vez();

function ejecutar_actualizar_metas_posts_social_una_vez() {
    if ( get_option( 'actualizar_metas_posts_social_realizado' ) ) {
        return;
    }
    actualizar_metas_posts_social();
    update_option( 'actualizar_metas_posts_social_realizado', 1 );
}

*/