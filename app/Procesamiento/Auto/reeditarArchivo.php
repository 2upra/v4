<?

function rehacerDescripcionAccion($post_id)
{
    // Obtener el ID del audio lite del metadato 'post_audio_lite'
    $audio_lite_id = get_post_meta($post_id, 'post_audio_lite', true);

    // Verificar si existe el ID
    if ($audio_lite_id) {
        // Obtener la ruta completa del archivo adjunto (audio lite)
        $archivo_audio = get_attached_file($audio_lite_id);

        if ($archivo_audio) {
            // Llamar a la función para rehacer la descripción con el archivo de audio
            rehacerDescripcionAudio($post_id, $archivo_audio);
            rehacerNombreAudio($post_id, $archivo_audio);

            iaLog("Descripción del audio actualizada para el post ID: {$post_id} con archivo de audio en la ruta {$archivo_audio}");
        } else {
            iaLog("No se pudo obtener la ruta del archivo de audio lite para el post ID: {$post_id}");
        }
    } else {
        iaLog("No se encontró el metadato 'post_audio_lite' para el post ID: {$post_id}");
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

function rehacerDescripcionAudio($post_id, $archivo_audio)
{
    iaLog("Iniciando mejora de descripción para el post ID: {$post_id}");
    $post_content = get_post_field('post_content', $post_id);
    if (!$post_content) {
        iaLog("No se pudo obtener el contenido del post ID: {$post_id}");
        return;
    }
    iaLog("Contenido del post obtenido para el post ID: {$post_id}");

    // Obtener los tags manuales del usuario desde la meta 'tagsUsuario'
    $tags_usuario_str = get_post_meta($post_id, 'tagsUsuario', true);
    if (!$tags_usuario_str) {
        iaLog("No se encontraron tagsUsuario para el post ID: {$post_id}");
        $tags_usuario = [];
    } else {
        $tags_usuario = array_map('trim', explode(',', $tags_usuario_str));
        $tags_usuario = array_filter($tags_usuario); // Eliminar posibles elementos vacíos
        iaLog("TagsUsuario obtenidos y procesados para el post ID: {$post_id}");
    }
    // Convertir el array de tags en una cadena separada por comas para incluir en el prompt
    $tags_usuario_formateados = implode(', ', $tags_usuario);
    iaLog("TagsUsuario formateados: {$tags_usuario_formateados}");


    $prompt = "El usuario ya subió este audio, pero acaba de editar la descripción o lo acaba de publicar ahora mismo. "
        . "Ten muy en cuenta la descripcion. descripción:\"{$post_content}\". "
        . "Por favor, determina una descripción del audio utilizando el siguiente formato JSON, estos son datos de ejemplo sin relacion alguna con el caso actual!!: : "
        . '{"Descripcion":{"es":"(aqui iría una descripcion tuya del audio muy detallada)", "en":"(aqui en ingles)"},'
        . '"Instrumentos posibles":{"es":["Piano", "Guitarra"], "en":["Piano", "Guitar"]},'
        . '"Estado de animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"Genero posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"Artista posible":{"es":["Freddie Dredd, Flume"], "en":["Freddie Dredd, Flume"]},'
        . '"Tipo de audio":{"es":["aqui necesito que puedas determinar si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"Tags posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"Sugerencia de busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop o un one shot o un sample, usa tags de una palabra. Optimiza el SEO con sugerencias de búsqueda relevantes.";

    $descripcion_mejorada = generarDescripcionIA($archivo_audio, $prompt);

    if ($descripcion_mejorada) {
        iaLog("Descripción mejorada generada correctamente para el post ID: {$post_id}");
        // Limpiar y procesar la nueva descripción generada
        $descripcion_procesada = json_decode(trim($descripcion_mejorada, "```json \n"), true);

        if ($descripcion_procesada) {
            iaLog("Descripción JSON procesada correctamente para el post ID: {$post_id}");

            // Obtener el metadato 'datosAlgoritmo' existente
            $datos_algoritmo = get_post_meta($post_id, 'datosAlgoritmo', true);
            if ($datos_algoritmo) {
                $datos_algoritmo = json_decode($datos_algoritmo, true);
                iaLog("DatosAlgoritmo existentes obtenidos para el post ID: {$post_id}");
            } else {
                $datos_algoritmo = [];
                iaLog("No se encontraron datosAlgoritmo existentes para el post ID: {$post_id}");
            }

            // Preservar los datos esenciales
            $datos_preservados = [
                'tags' => $datos_algoritmo['tags'] ?? [],
                'bpm' => $datos_algoritmo['bpm'] ?? null,
                'key' => $datos_algoritmo['key'] ?? null,
                'scale' => $datos_algoritmo['scale'] ?? null,
                'autor' => $datos_algoritmo['autor'] ?? []
            ];
            iaLog("Datos preservados para el post ID: {$post_id}");

            // Agregar la nueva descripción IA mejorada con traducciones
            $nuevos_datos = [
                'descripcion_ia_pro' => [
                    'es' => $descripcion_procesada['Descripcion']['es'] ?? '',
                    'en' => $descripcion_procesada['Descripcion']['en'] ?? ''
                ],
                'instrumentos_posibles' => [
                    'es' => $descripcion_procesada['Instrumentos posibles']['es'] ?? [],
                    'en' => $descripcion_procesada['Instrumentos posibles']['en'] ?? []
                ],
                'estado_animo' => [
                    'es' => $descripcion_procesada['Estado de animo']['es'] ?? [],
                    'en' => $descripcion_procesada['Estado de animo']['en'] ?? []
                ],
                'artista_posible' => [
                    'es' => $descripcion_procesada['Artista posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Artista posible']['en'] ?? []
                ],
                'genero_posible' => [
                    'es' => $descripcion_procesada['Genero posible']['es'] ?? [],
                    'en' => $descripcion_procesada['Genero posible']['en'] ?? []
                ],
                'tipo_audio' => [
                    'es' => $descripcion_procesada['Tipo de audio']['es'] ?? [],
                    'en' => $descripcion_procesada['Tipo de audio']['en'] ?? []
                ],
                'tags_posibles' => [
                    'es' => $descripcion_procesada['Tags posibles']['es'] ?? [],
                    'en' => $descripcion_procesada['Tags posibles']['en'] ?? []
                ],
                'sugerencia_busqueda' => [
                    'es' => $descripcion_procesada['Sugerencia de busqueda']['es'] ?? [],
                    'en' => $descripcion_procesada['Sugerencia de busqueda']['en'] ?? []
                ]
            ];
            iaLog("Nuevos datos generados para el post ID: {$post_id}");

            // Combinar los datos preservados con los nuevos
            $datos_actualizados = array_merge($datos_preservados, $nuevos_datos);

            // Guardar los metadatos actualizados
            update_post_meta($post_id, 'datosAlgoritmo', json_encode($datos_actualizados, JSON_UNESCAPED_UNICODE));
            iaLog("Metadatos actualizados para el post ID: {$post_id}");

            // Agregar el metadato 'ultimoEdit' con la fecha actual
            $fecha_actual = current_time('mysql'); // Formato de fecha de WordPress
            update_post_meta($post_id, 'ultimoEdit', $fecha_actual);

            iaLog("Metadato 'ultimoEdit' agregado para el post ID: {$post_id} con fecha {$fecha_actual}");

            // Marcar el post como IA Pro
            update_post_meta($post_id, 'proIA', false);
        } else {
            iaLog("Error al procesar el JSON de la descripción mejorada generada por IA Pro.");
        }
    } else {
        iaLog("No se pudo generar la descripción mejorada para el post ID: {$post_id}");
    }
}