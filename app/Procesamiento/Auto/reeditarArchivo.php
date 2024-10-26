<?

function rehacerDescripcionAccion($post_id)
{
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
    if ($audio_lite_id) {
        $archivo_audio = get_attached_file($audio_lite_id);
        if ($archivo_audio) {
            rehacerDescripcionAudio($post_id, $archivo_audio);
            iaLog("Descripción del audio actualizada para el post ID: {$post_id} con archivo de audio en la ruta {$archivo_audio}");
        } else {
            iaLog("No se pudo obtener la ruta del archivo de audio lite para el post ID: {$post_id}");
        }
    } else {
        iaLog("No se encontró el metadato 'post_audio_lite' para el post ID: {$post_id}");
    }
}

function rehacerDescripcionAudio($post_id, $archivo_audio)
{
    iaLog("Iniciando mejora de descripción para el post ID: {$post_id}");

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        return;
    }
    iaLog("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener los metadatos actuales del post, incluyendo 'datosAlgoritmo'
    $datosAlgoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    if (!$datosAlgoritmo) {
        iaLog("No se encontraron metadatos previos para el post ID: {$post_id}");
        return;
    }

    // Decodificar el JSON de 'datosAlgoritmo'
    $datos_actuales = json_decode($datosAlgoritmo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        iaLog("Error al decodificar el JSON de 'datosAlgoritmo' para el post ID: {$post_id}: " . json_last_error_msg());
        return;
    }

    // Crear el prompt para la IA con el contenido del post actual y los metadatos anteriores
    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción."
        . " Ten muy en cuenta la descripción nueva, es para corregir el JSON: \"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva descripción del usuario, manten los mismos datos para los bpm, etc.: "
        . json_encode($datos_actuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";

    // Generar la nueva descripción usando la IA
    $descripcion_mejorada = generarDescripcionIA($archivo_audio, $prompt);
    if (!$descripcion_mejorada) {
        iaLog("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
        return;
    }

    /*
    2024-10-26 17:28:44 - Iniciando mejora de descripción para el post ID: 240374
    2024-10-26 17:28:44 - Contenido del post obtenido para el post ID: 240374
    2024-10-26 17:28:44 - Inicio de generarDescripcionIA con prompt: El usuario ya subió este audio, pero acaba de editar la descripción. Ten muy en cuenta la descripción nueva, es para corregir el JSON: "Percusión Memphis one shot". Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva descripción del usuario, manten los mismos datos para los bpm, etc.: {"bpm":0,"emotion":"","key":"F","scale":"minor","descripcion_ia":{"es":"Audio con un sonido de larga duración, probablemente una grabación de un instrumento o efecto sonoro.  Se caracteriza por un decay prolongado y sostenido.","en":"Audio with a long decay sound, likely a recording of an instrument or sound effect. It is characterized by a prolonged and sustained decay."},"instrumentos_principal":{"es":[],"en":[]},"nombre_corto":{"es":["Long Decay"],"en":["Long Decay"]},"descripcion_corta":{"es":"Sonido con larga reverberación","en":"Long reverb sound"},"estado_animo":{"es":["Ambiental"],"en":["Ambient"]},"artista_posible":{"es":[],"en":[]},"genero_posible":{"es":["Ambient","Experimental"],"en":["Ambient","Experimental"]},"tipo_audio":{"es":["loop"],"en":["loop"]},"tags_posibles":{"es":["Reverberacion","Decay","Largo","Ambiente"],"en":["Reverb","Decay","Long","Ambient"]},"sugerencia_busqueda":{"es":["Sonido ambiental largo","Loop de larga duración","Efecto de sonido con decay"],"en":["Long ambient sound","Long decay loop","Sound effect with long decay"]}} Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.
    2024-10-26 17:28:44 - Archivo de audio: /var/www/wordpress/wp-content/uploads/audio/2upra_PersMemphisOne_ISpY_lite.mp3
    2024-10-26 17:28:44 - Archivo de audio cargado y convertido a base64 con éxito.
    2024-10-26 17:28:47 - Respuesta completa de la API: {"candidates":[{"content":{"parts":[{"text":"```json\n{\"bpm\":0,\"emotion\":\"\",\"key\":\"F\",\"scale\":\"minor\",\"descripcion_ia\":{\"es\":\"One-shot de percusi\u00f3n con sonido caracter\u00edstico de Memphis.\",\"en\":\"Memphis-style percussion one-shot.\"},\"instrumentos_principal\":{\"es\":[\"Percusi\u00f3n\"],\"en\":[\"Percussion\"]},\"nombre_corto\":{\"es\":[\"Percusi\u00f3n Memphis\"],\"en\":[\"Memphis Percussion\"]},\"descripcion_corta\":{\"es\":\"One-shot de percusi\u00f3n estilo Memphis\",\"en\":\"Memphis style percussion one-shot\"},\"estado_animo\":{\"es\":[\"R\u00edtmico\"],\"en\":[\"Rhythmic\"]},\"artista_posible\":{\"es\":[],\"en\":[]},\"genero_posible\":{\"es\":[\"Hip Hop\",\"Funk\",\"Soul\"],\"en\":[\"Hip Hop\",\"Funk\",\"Soul\"]},\"tipo_audio\":{\"es\":[\"one-shot\"],\"en\":[\"one-shot\"]},\"tags_posibles\":{\"es\":[\"Percusi\u00f3n\",\"Memphis\",\"One-shot\",\"Ritmo\"],\"en\":[\"Percussion\",\"Memphis\",\"One-shot\",\"Rhythm\"]},\"sugerencia_busqueda\":{\"es\":[\"One-shot percusi\u00f3n Memphis\",\"Sonido de percusi\u00f3n Memphis\",\"Sample percusi\u00f3n Memphis\"],\"en\":[\"Memphis percussion one-shot\",\"Memphis percussion sound\",\"Memphis percussion sample\"]}\n```\n"}],"role":"model"},"finishReason":"STOP","avgLogprobs":-0.025568392793488593}],"usageMetadata":{"promptTokenCount":426,"candidatesTokenCount":263,"totalTokenCount":689},"modelVersion":"gemini-1.5-flash-002"}
    2024-10-26 17:28:47 - Contenido generado: ```json
    {"bpm":0,"emotion":"","key":"F","scale":"minor","descripcion_ia":{"es":"One-shot de percusión con sonido característico de Memphis.","en":"Memphis-style percussion one-shot."},"instrumentos_principal":{"es":["Percusión"],"en":["Percussion"]},"nombre_corto":{"es":["Percusión Memphis"],"en":["Memphis Percussion"]},"descripcion_corta":{"es":"One-shot de percusión estilo Memphis","en":"Memphis style percussion one-shot"},"estado_animo":{"es":["Rítmico"],"en":["Rhythmic"]},"artista_posible":{"es":[],"en":[]},"genero_posible":{"es":["Hip Hop","Funk","Soul"],"en":["Hip Hop","Funk","Soul"]},"tipo_audio":{"es":["one-shot"],"en":["one-shot"]},"tags_posibles":{"es":["Percusión","Memphis","One-shot","Ritmo"],"en":["Percussion","Memphis","One-shot","Rhythm"]},"sugerencia_busqueda":{"es":["One-shot percusión Memphis","Sonido de percusión Memphis","Sample percusión Memphis"],"en":["Memphis percussion one-shot","Memphis percussion sound","Memphis percussion sample"]}
    ```
    2024-10-26 17:28:47 - Error al procesar el JSON de la descripción mejorada generada por IA Pro.
    2024-10-26 17:28:47 - Descripción del audio actualizada para el post ID: 240374 con archivo de audio en la ruta /var/www/wordpress/wp-content/uploads/audio/2upra_PersMemphisOne_ISpY_lite.mp3
    */

    // Limpiar el JSON de cualquier bloque de código que pudiese haber sido incluido.
    $descripcion_mejorada_limpia = preg_replace('/```(?:json)?\n/', '', $descripcion_mejorada);
    $descripcion_mejorada_limpia = preg_replace('/\n```/', '', $descripcion_mejorada_limpia);

    // Decodificar la descripción generada por la IA
    $datos_actualizados = json_decode($descripcion_mejorada_limpia, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        iaLog("Nuevos datos generados para el post ID: {$post_id}");

        // Guardar los nuevos datos en la meta 'datosAlgoritmo'
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
        iaLog("Metadatos actualizados para el post ID: {$post_id}");

        // Actualizar la fecha de la última edición
        $fecha_actual = current_time('mysql');
        update_post_meta($post_id, 'ultimoEdit', $fecha_actual);
        iaLog("Metadato 'ultimoEdit' agregado para el post ID: {$post_id} con fecha {$fecha_actual}");

        // Desactivar el procesamiento por IA
        update_post_meta($post_id, 'proIA', false);
    } else {
        iaLog("Error al procesar el JSON de la descripción mejorada generada");
    }
}

function rehacerNombreAudio($post_id, $archivo_audio)
{
    // Verificar si el archivo de audio existe
    if (!file_exists($archivo_audio)) {
        iaLog("El archivo de audio no existe en la ruta especificada: {$archivo_audio}");
        return null;
    }

    $user_id = get_current_user_id();

    if (!user_can($user_id, 'administrator')) {
        return;
    }

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
    }
    iaLog("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener el nombre del archivo a partir de la ruta
    $nombre_archivo = pathinfo($archivo_audio, PATHINFO_FILENAME);

    // Crear el prompt para la IA con el nombre del archivo incluido
    $prompt = "El archivo se llama '{$nombre_archivo}' es un nombre viejo porque el usuario ha cambiado o mejorado la descripción, la descripción nueva que escribió el usuario es '{$post_content}'. Escucha este audio y por favor, genera un nombre corto que lo represente tomando en cuenta la descripción que generó el usuario. Por lo general son samples, loop, fx, one shot, etc. Imporante: solo responde el nombre, no agregues nada adicional, estas en un entorno automatizado, no hables con el usuario, solo estoy pidiendo el nombre corto como respuesta.";

    // Generar el nombre usando la IA
    $nombre_generado = generarDescripcionIA($archivo_audio, $prompt);

    // Verificar si se obtuvo una respuesta válida
    if ($nombre_generado) {
        // Limpiar el nombre generado
        $nombre_generado_limpio = trim($nombre_generado);
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
        $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 60);
        $nombre_final = '2upra_' . $nombre_generado_limpio;
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final_con_id = $nombre_final . '_' . $id_unica;
        $nombre_final_con_id = substr($nombre_final_con_id, 0, 60);

        iaLog("Nombre generado: {$nombre_final_con_id}");

        // Obtener los IDs de los adjuntos desde los metadatos del post
        $attachment_id_audio = get_post_meta($post_id, 'post_audio', true);
        $attachment_id_audio_lite = get_post_meta($post_id, 'post_audio_lite', true);

        // Verificar que los IDs de adjunto existan
        if (!$attachment_id_audio) {
            iaLog("No se encontró el meta 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        if (!$attachment_id_audio_lite) {
            iaLog("No se encontró el meta 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        // Renombrar los archivos adjuntos
        $renombrado_audio = renombrar_archivo_adjunto($attachment_id_audio, $nombre_final_con_id, false);
        if (!$renombrado_audio) {
            iaLog("Falló al renombrar el archivo 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        $renombrado_audio_lite = renombrar_archivo_adjunto($attachment_id_audio_lite, $nombre_final_con_id, true);
        if (!$renombrado_audio_lite) {
            iaLog("Falló al renombrar el archivo 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        if (get_post_meta($post_id, 'rutaPerdida', true)) {
            iaLog("No se intentará renombrar, 'rutaPerdida' está marcada como true para el post ID: {$post_id}");
            return null;
        }

        // Actualizar la meta 'rutaOriginal' o buscar en subcarpetas si no existe la ruta
        $ruta_original = get_post_meta($post_id, 'rutaOriginal', true);
        if ($ruta_original && file_exists($ruta_original)) {
            $directorio_original = pathinfo($ruta_original, PATHINFO_DIRNAME);
        } else {
            $directorio_original = buscarArchivoEnSubcarpetas("/home/asley01/MEGA/Waw/X", basename($ruta_original));
        }

        if ($directorio_original) {
            $ext_extension = pathinfo($ruta_original, PATHINFO_EXTENSION);
            $nueva_ruta_original = $directorio_original . '/' . $nombre_final_con_id . '.' . $ext_extension;

            if (rename($ruta_original, $nueva_ruta_original)) {
                update_post_meta($post_id, 'rutaOriginal', $nueva_ruta_original);
                iaLog("Meta 'rutaOriginal' actualizada a: {$nueva_ruta_original}");
                guardarLog("Archivo renombrado en el servidor de {$ruta_original} a {$nueva_ruta_original}");
            } else {
                guardarLog("Error en renombrar archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                iaLog("Error al renombrar el archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                update_post_meta($post_id, 'rutaOriginalPerdida', true);
            }
        } else {
            iaLog("No se encontró 'rutaOriginal' ni en la meta ni en las subcarpetas para el post ID: {$post_id}");
            update_post_meta($post_id, 'rutaPerdida', true);
        }


        // Actualizar la URL en base de datos si tiene idHash_audioId
        $id_hash_audio = get_post_meta($post_id, 'idHash_audioId', true);
        if ($id_hash_audio) {
            $nueva_url_audio = wp_get_attachment_url($attachment_id_audio);
            actualizarUrlArchivo($id_hash_audio, $nueva_url_audio);
            iaLog("URL de 'post_audio' actualizada para el hash ID: {$id_hash_audio}");
        } else {
            iaLog("Meta 'idHash_audioId' no existe para el post ID: {$post_id}");
        }

        iaLog("Renombrado completado exitosamente para el post ID: {$post_id}");
        update_post_meta($post_id, 'Verificado', true);

        return $nombre_final_con_id;
    } else {
        iaLog("No se recibió una respuesta válida de la IA para el archivo de audio: {$archivo_audio}");
        return null;
    }
}

// Función auxiliar para buscar archivos en subcarpetas, filtrando solo archivos de audio válidos
function buscarArchivoEnSubcarpetas($directorio_base, $nombre_archivo)
{
    $iterador = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directorio_base));
    foreach ($iterador as $archivo) {
        // Obtener la extensión y el nombre del archivo
        $extension = strtolower($archivo->getExtension());
        $nombre = $archivo->getFilename();

        // Ignorar archivos que no sean .wav o .mp3 y que no empiecen con "2upra"
        if (!in_array($extension, ['wav', 'mp3']) || strpos($nombre, '2upra') !== 0) {
            continue;
        }

        // Si el nombre coincide exactamente con el archivo buscado, devolver la ruta del directorio
        if ($nombre === $nombre_archivo) {
            return $archivo->getPath();
        }
    }
    return false;
}


function renombrar_archivo_adjunto($attachment_id, $nuevo_nombre, $es_lite = false)
{
    // Obtener el path completo del archivo adjunto
    $ruta_archivo = get_attached_file($attachment_id);
    if (!file_exists($ruta_archivo)) {
        iaLog("El archivo adjunto con ID {$attachment_id} no existe en la ruta: {$ruta_archivo}");
        return false;
    }

    // Obtener la carpeta y la extensión del archivo
    $carpeta = pathinfo($ruta_archivo, PATHINFO_DIRNAME);
    $extension = pathinfo($ruta_archivo, PATHINFO_EXTENSION);
    if ($es_lite) {
        $nuevo_nombre .= '_lite';
    }
    $nueva_ruta = $carpeta . '/' . $nuevo_nombre . '.' . $extension;

    // Renombrar el archivo
    if (!rename($ruta_archivo, $nueva_ruta)) {
        iaLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        guardarLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        return false;
    }

    iaLog("Archivo renombrado de {$ruta_archivo} a {$nueva_ruta}");
    guardarLog("Archivo renombrado en el servidor de {$ruta_archivo} a {$nueva_ruta}");

    // Actualizar la ruta del adjunto en la base de datos
    $wp_filetype = wp_check_filetype(basename($nueva_ruta), null);
    $attachment_data = array(
        'ID' => $attachment_id,
        'post_name' => sanitize_title($nuevo_nombre),
        'guid' => home_url('/') . str_replace(ABSPATH, '', $nueva_ruta),
    );

    // Actualizar el post del adjunto
    if (function_exists('wp_update_post')) {
        wp_update_post($attachment_data);
    }

    update_attached_file($attachment_id, $nueva_ruta);

    return true;
}
