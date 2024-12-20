<?

function rehacerDescripcionAccion($post_id)
{
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
    if ($audio_lite_id) {
        $archivo_audio = get_attached_file($audio_lite_id);
        if ($archivo_audio) {
            rehacerDescripcionAudio($post_id, $archivo_audio);
            //error_log("Descripción del audio actualizada para el post ID: {$post_id} con archivo de audio en la ruta {$archivo_audio}");
        } else {
            //error_log("No se pudo obtener la ruta del archivo de audio lite para el post ID: {$post_id}");
        }
    } else {
        //error_log("No se encontró el metadato 'post_audio_lite' para el post ID: {$post_id}");
    }
}

function rehacerJsonPost($post_id, $descripcion)
{
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);
    if ($audio_lite_id) {
        $archivo_audio = get_attached_file($audio_lite_id);
        if ($archivo_audio) {
            rehacerJson($post_id, $archivo_audio, $descripcion);
            //error_log("Descripción del audio actualizada para el post ID: {$post_id} con archivo de audio en la ruta {$archivo_audio}");
        } else {
            //error_log("No se pudo obtener la ruta del archivo de audio lite para el post ID: {$post_id}");
        }
    } else {
        //error_log("No se encontró el metadato 'post_audio_lite' para el post ID: {$post_id}");
    }
}
/*
como se puede arreglar
[20-Dec-2024 10:46:55 UTC] rehacerJson: Error: $datosAlgoritmo no es una cadena JSON para el post ID: 242704. Tipo: array[20-Dec-2024 10:46:55 UTC] Descripción del audio actualizada para el post ID: 242704 con archivo de audio en la ruta /var/www/wordpress/wp-content/uploads/2024/10/Bee-Loop_7U3a_2upra_optimizado.mp3
*/

function rehacerJson($post_id, $archivo_audio, $descripcion)
{
    //error_log("Iniciando reajusteJson para el post ID: {$post_id}");

    // Obtener el contenido del post
    //error_log("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener los metadatos actuales del post, incluyendo 'datosAlgoritmo'
    $datosAlgoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    if (!$datosAlgoritmo) {
        //error_log("No se encontraron metadatos previos para el post ID: {$post_id}");
        return;
    }

    // **Verificar si es un array y convertirlo a JSON si es necesario**
    if (is_array($datosAlgoritmo)) {
        //error_log("rehacerJson: \$datosAlgoritmo es un array, intentando convertirlo a JSON para el post ID: {$post_id}");
        $datosAlgoritmo = json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE);
        if (json_last_error() !== JSON_ERROR_NONE) {
            //error_log("rehacerJson: Error al convertir el array a JSON para el post ID: {$post_id}: " . json_last_error_msg());
            return;
        }
    } elseif (!is_string($datosAlgoritmo)) {
        //error_log("rehacerJson: Error: \$datosAlgoritmo no es una cadena JSON ni un array para el post ID: {$post_id}. Tipo: " . gettype($datosAlgoritmo));
        return;
    }

    // Decodificar el JSON de 'datosAlgoritmo'
    $datos_actuales = json_decode($datosAlgoritmo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        //error_log("rehacerJson: Error al decodificar el JSON de 'datosAlgoritmo' para el post ID: {$post_id}: " . json_last_error_msg());
        return;
    }

    // Crear el prompt para la IA con el contenido del post actual y los metadatos anteriores
    $prompt = "El usuario ya subió este audio, pero esta pidiendo corregir los tags o informacion del siguiente json"
        . " Este es el mensaje, usa la informacion para corregir el JSON: \"{$descripcion}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva indicacion del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datos_actuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. No cambies las cosas si el usuario no lo pidio, sigue sus instrucciones. Muchas veces el usuario no se explicará bien, hay que intuir que hay que ajustar del json, generalmente es para cambiar uno o dos tags. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";

    // Generar la nueva descripción usando la IA
    $descripcion_mejorada = generarDescripcionIA($archivo_audio, $prompt);
    if (!$descripcion_mejorada) {
        //error_log("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
        return;
    }

    // Limpiar el JSON de cualquier bloque de código que pudiese haber sido incluido.
    $descripcion_mejorada_limpia = preg_replace('/```(?:json)?\n/', '', $descripcion_mejorada);
    $descripcion_mejorada_limpia = preg_replace('/\n```/', '', $descripcion_mejorada_limpia);

    // Decodificar la descripción generada por la IA
    $datos_actualizados = json_decode($descripcion_mejorada_limpia, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        //error_log("Nuevos datos generados para el post ID: {$post_id}");

        // Guardar los nuevos datos en la meta 'datosAlgoritmo'
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
        //error_log("Metadatos actualizados para el post ID: {$post_id}");

        // Actualizar la fecha de la última edición
        $fecha_actual = current_time('mysql');
        update_post_meta($post_id, 'ultimoEdit', $fecha_actual);
        //error_log("Metadato 'ultimoEdit' agregado para el post ID: {$post_id} con fecha {$fecha_actual}");

        // Desactivar el procesamiento por IA
        update_post_meta($post_id, 'proIA', false);
    } else {
        //error_log("Error al procesar el JSON de la descripción mejorada generada");
    }
}

function rehacerDescripcionAudio($post_id, $archivo_audio)
{
    //error_log("rehacerDescripcionAudio: Iniciando mejora de descripción para el post ID: {$post_id}");

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        //error_log("No se pudo obtener el contenido del post ID: {$post_id}");
        return;
    }
    //error_log("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener los metadatos actuales del post, incluyendo 'datosAlgoritmo'
    $datosAlgoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
    if (!$datosAlgoritmo) {
        //error_log("No se encontraron metadatos previos para el post ID: {$post_id}");
        return;
    }

    // Decodificar el JSON de 'datosAlgoritmo'
    $datos_actuales = json_decode($datosAlgoritmo, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        //error_log("Error al decodificar el JSON de 'datosAlgoritmo' para el post ID: {$post_id}: " . json_last_error_msg());
        return;
    }

    // Crear el prompt para la IA con el contenido del post actual y los metadatos anteriores
    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción porque hay un dato incorrecto o un fallo en el json por ejemplo, no es un sample sino un one shot y viceversa, corrije cualquier cosa."
        . " Ten muy en cuenta la descripción nueva, es para corregir el JSON: \"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, este es el JSON del post anterior, modifícalo según la nueva descripción del usuario y corrije cualquier cosa, manten los mismos datos para los bpm, etc.: "
        . json_encode($datos_actuales, JSON_UNESCAPED_UNICODE)
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";

    // Generar la nueva descripción usando la IA
    $descripcion_mejorada = generarDescripcionIA($archivo_audio, $prompt);
    if (!$descripcion_mejorada) {
        //error_log("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
        return;
    }

    // Limpiar el JSON de cualquier bloque de código que pudiese haber sido incluido.
    $descripcion_mejorada_limpia = preg_replace('/```(?:json)?\n/', '', $descripcion_mejorada);
    $descripcion_mejorada_limpia = preg_replace('/\n```/', '', $descripcion_mejorada_limpia);

    // Decodificar la descripción generada por la IA
    $datos_actualizados = json_decode($descripcion_mejorada_limpia, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        //error_log("Nuevos datos generados para el post ID: {$post_id}");

        // Guardar los nuevos datos en la meta 'datosAlgoritmo'
        update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
        //error_log("Metadatos actualizados para el post ID: {$post_id}");

        // Actualizar la fecha de la última edición
        $fecha_actual = current_time('mysql');
        update_post_meta($post_id, 'ultimoEdit', $fecha_actual);
        //error_log("Metadato 'ultimoEdit' agregado para el post ID: {$post_id} con fecha {$fecha_actual}");

        // Desactivar el procesamiento por IA
        update_post_meta($post_id, 'proIA', false);
    } else {
        //error_log("Error al procesar el JSON de la descripción mejorada generada");
    }
}

function rehacerNombreAudio($post_id, $archivo_audio)
{
    // Verificar si el archivo de audio existe
    if (!file_exists($archivo_audio)) {
        //error_log("El archivo de audio no existe en la ruta especificada: {$archivo_audio}");
        return null;
    }

    $user_id = get_current_user_id();

    if (!user_can($user_id, 'administrator')) {
        return;
    }

    // Obtener el contenido del post
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        //error_log("No se pudo obtener el contenido del post ID: {$post_id}");
    }
    //error_log("Contenido del post obtenido para el post ID: {$post_id}");

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

        //error_log("Nombre generado: {$nombre_final_con_id}");

        // Obtener los IDs de los adjuntos desde los metadatos del post
        $attachment_id_audio = get_post_meta($post_id, 'post_audio', true);
        $attachment_id_audio_lite = get_post_meta($post_id, 'post_audio_lite', true);

        // Verificar que los IDs de adjunto existan
        if (!$attachment_id_audio) {
            //error_log("No se encontró el meta 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        if (!$attachment_id_audio_lite) {
            //error_log("No se encontró el meta 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        // Renombrar los archivos adjuntos
        $renombrado_audio = renombrar_archivo_adjunto($attachment_id_audio, $nombre_final_con_id, false);
        if (!$renombrado_audio) {
            //error_log("Falló al renombrar el archivo 'post_audio' para el post ID: {$post_id}");
            return null;
        }

        $renombrado_audio_lite = renombrar_archivo_adjunto($attachment_id_audio_lite, $nombre_final_con_id, true);
        if (!$renombrado_audio_lite) {
            //error_log("Falló al renombrar el archivo 'post_audio_lite' para el post ID: {$post_id}");
            return null;
        }

        if (get_post_meta($post_id, 'rutaPerdida', true)) {
            //error_log("No se intentará renombrar, 'rutaPerdida' está marcada como true para el post ID: {$post_id}");
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
                //error_log("Meta 'rutaOriginal' actualizada a: {$nueva_ruta_original}");
                guardarLog("Archivo renombrado en el servidor de {$ruta_original} a {$nueva_ruta_original}");
            } else {
                guardarLog("Error en renombrar archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                //error_log("Error al renombrar el archivo en el servidor de {$ruta_original} a {$nueva_ruta_original}");
                update_post_meta($post_id, 'rutaOriginalPerdida', true);
            }
        } else {
            //error_log("No se encontró 'rutaOriginal' ni en la meta ni en las subcarpetas para el post ID: {$post_id}");
            update_post_meta($post_id, 'rutaPerdida', true);
        }


        // Actualizar la URL en base de datos si tiene idHash_audioId
        $id_hash_audio = get_post_meta($post_id, 'idHash_audioId', true);
        if ($id_hash_audio) {
            $nueva_url_audio = wp_get_attachment_url($attachment_id_audio);
            actualizarUrlArchivo($id_hash_audio, $nueva_url_audio);
            //error_log("URL de 'post_audio' actualizada para el hash ID: {$id_hash_audio}");
        } else {
            //error_log("Meta 'idHash_audioId' no existe para el post ID: {$post_id}");
        }

        //error_log("Renombrado completado exitosamente para el post ID: {$post_id}");
        update_post_meta($post_id, 'Verificado', true);

        return $nombre_final_con_id;
    } else {
        //error_log("No se recibió una respuesta válida de la IA para el archivo de audio: {$archivo_audio}");
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
        //error_log("El archivo adjunto con ID {$attachment_id} no existe en la ruta: {$ruta_archivo}");
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
        //error_log("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        guardarLog("Error al renombrar el archivo de {$ruta_archivo} a {$nueva_ruta}");
        return false;
    }

    //error_log("Archivo renombrado de {$ruta_archivo} a {$nueva_ruta}");
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
