<?

function autProcesarAudio($audio_path)
{
    autLog("--Inicio de la función autProcesarAudio.--");

    // Verificar si el archivo existe
    if (!file_exists($audio_path)) {
        autLog("Archivo no encontrado: $audio_path");
        return;
    }

    // Obtener partes del path
    $path_parts = pathinfo($audio_path);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        autLog("Directorio inválido: {$path_parts['dirname']}");
        return;
    }
    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];

    autLog("Ruta inicial: $audio_path, Directorio: $directory, Basename: $basename, Extensión: $extension");

    // Obtener ID del archivo por la ruta directa
    $file_id = obtenerFileIDPorURL($audio_path);
    if ($file_id === false) {
        autLog("File ID no encontrado para la ruta: $audio_path");
        return;
    } else {
        autLog("File ID obtenido: $file_id");
    }

    // Ruta temporal para eliminar metadatos
    $temp_path = "$directory/{$basename}_temp.$extension";

    // 1. Eliminar metadatos con ffmpeg
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -map_metadata -1 -c copy " . escapeshellarg($temp_path) . " -y";
    autLog("Comando para eliminar metadatos: $comando_strip_metadata");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        autLog("Error al eliminar metadatos: " . implode(" | ", $output_strip));
        return;
    }

    // Reemplazar archivo original
    if (!rename($temp_path, $audio_path)) {
        autLog("No se pudo reemplazar el archivo original.");
        return;
    }
    autLog("Metadatos eliminados del archivo: $audio_path");

    // 2. Crear versión lite en MP3 a 128 kbps
    $lite_path = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($audio_path) . " -b:a 128k " . escapeshellarg($lite_path) . " -y";
    autLog("Comando para crear versión lite: $comando_lite");
    exec($comando_lite, $output_lite, $return_lite);
    if ($return_lite !== 0) {
        autLog("Error al crear versión lite: " . implode(" | ", $output_lite));
        return;
    }

    $nombre_limpio = pathinfo($lite_path, PATHINFO_FILENAME);
    $carpeta = basename(dirname($lite_path));
    $carpeta_abuela = basename(dirname(dirname($lite_path)));

    // 6. Mover el archivo lite al directorio de uploads
    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    // Crear directorio 'audio' si no existe
    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            autLog("No se pudo crear el directorio de uploads/audio.");

            return;
        }
    }

    $target_path_lite = $target_dir_audio . "{$nombre_limpio}_lite.mp3";

    // Mover archivo lite
    if (!rename($lite_path, $target_path_lite)) {
        autLog("No se pudo mover el archivo lite al directorio de uploads.");
        return;
    }
    autLog("Archivo lite movido al directorio de uploads: $target_path_lite");


    // 7. Enviar rutas a crearAutPost
    autLog("Enviando rutas a crearAutPost: Original - $audio_path, Lite - $target_path_lite");
    crearAutPost($audio_path, $target_path_lite, $file_id, $lite_path);
    autLog("Archivos enviados a crearAutPost.");

    autLog("--Fin de la función autProcesarAudio.--");
}


function automaticAudio($rutaArchivo, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
    $resultados = procesarArchivoAudioPython($rutaArchivo);

    if ($resultados) {
        echo "BPM: " . ($resultados['bpm'] ?? '') . "\n";
        echo "Emotion: " . ($resultados['emotion'] ?? '') . "\n";
        echo "Key: " . ($resultados['key'] ?? '') . "\n";
        echo "Scale: " . ($resultados['scale'] ?? '') . "\n";
    } else {
        echo "Error procesando el archivo de audio.";
    }

    $informacion_archivo = '';
    if ($nombre_archivo) {
        $informacion_archivo .= "Archivo: '{$nombre_archivo}'\n";
    }
    if ($carpeta) {
        $informacion_archivo .= "Carpeta: '{$carpeta}'\n";
    }
    if ($carpeta_abuela) {
        $informacion_archivo .= "Carpeta abuela: '{$carpeta_abuela}'\n";
    }

    $prompt = "Este audio fue subido automáticamente. "
        . "{$informacion_archivo}"
        . "Por favor, determina una descripción precisa del audio utilizando el siguiente formato JSON. La información como el nombre y las carpetas son información super relevante para completar el JSON. Por favor, ignora cualquier nombre comercial, dominio, redes sociales o información no relevante que pueda contener el nombre o las carpetas. También ignora la palabra 'lite' o '2upra'. El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, hazla muy corta y breve ejemplo 'Memphis snare acustico', 'Sample vitagen piano', etc entre 3 a 5 palabras. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
        . '{"descripcion_ia":{"es":"(aquí iría una descripción tuya del audio muy detallada)", "en":"(aquí en inglés)"},'
        . '"instrumentos_principal":{"es":["Piano"], "en":["Piano"]},'
        . '"nombre_corto":{"es":["(maximo 3 palabras)"], "en":["Kick Vitagen"]},'
        . '"descripcion_corta":{"es":["(entre 3 a 5 palabras)"], "en":["(en ingles)"]},'
        . '"estado_animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"genero_posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"artista_posible":{"es":["Freddie Dredd", "Flume"], "en":["Freddie Dredd", "Flume"]},'
        . '"tipo_audio":{"es":["determina si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"tags_posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"sugerencia_busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop, un one shot o un sample. Usa tags de una palabra y optimiza el SEO con sugerencias de búsqueda relevantes. Sé muy detallado sin perder precisión.";

    $descripcion = generarDescripcionIA($rutaArchivo, $prompt);

    if ($descripcion) {
        // Procesar el JSON eliminando caracteres innecesarios
        $descripcion_procesada = json_decode(trim($descripcion, "```json \n"), true);

        if (isset($descripcion_procesada['descripcion_ia']) && is_array($descripcion_procesada['descripcion_ia'])) {
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

            autLog("Descripción del audio guardada para el post ID: {$nombre_archivo}");
        }
    }

    // Verificar que $nuevos_datos esté definido antes de usarlo
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

    return $nuevos_datos_algoritmo;
}

function crearAutPost($nuevo_nombre_original, $nuevo_nombre_lite, $file_id, $lite_path)
{
    autLog("Iniciando crearAutPost con nuevo_nombre_original: $nuevo_nombre_original, nuevo_nombre_lite: $nuevo_nombre_lite, file_id: $file_id, lite_path: $lite_path");

    $autor_id = 44;
    $nombre_archivo = pathinfo($lite_path, PATHINFO_FILENAME);
    $carpeta = basename(dirname($lite_path));
    $carpeta_abuela = basename(dirname(dirname($lite_path)));

    autLog("Nombre archivo: $nombre_archivo, Carpeta: $carpeta, Carpeta abuela: $carpeta_abuela");

    $datosAlgoritmo = automaticAudio($nuevo_nombre_lite, $nombre_archivo, $carpeta, $carpeta_abuela);

    if (!$datosAlgoritmo) {
        autLog("Error al obtener datos del algoritmo.");
        return;
    }

    $descripcion_corta_es = $datosAlgoritmo['descripcion_ia']['es'] ?? '';
    $nombre_generado = $datosAlgoritmo['nombre_corto']['en'] ?? '';

    // Verificar si $nombre_generado es un array
    if (is_array($nombre_generado)) {
        autLog("Nombre generado es un array: " . print_r($nombre_generado, true));
        $nombre_generado = $nombre_generado[0] ?? ''; 
    }

    autLog("Descripción corta ES: $descripcion_corta_es, Nombre generado: $nombre_generado");

    if ($nombre_generado) {
        $nombre_generado_limpio = trim($nombre_generado);
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- ]/', '', $nombre_generado_limpio);
        $nombre_generado_limpio = substr($nombre_generado_limpio, 0, 70);
        $nombre_final = $nombre_generado_limpio . '_2upra';
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final_con_id = $nombre_final . '_' . $id_unica;
        $nombre_final_con_id = substr($nombre_final_con_id, 0, 60);

        autLog("Nombre final generado: $nombre_final_con_id");
    } else {
        autLog("Error en la generación del nombre en crearAutPost");
        eliminarHash($file_id);
        return;
    }

    $titulo = mb_substr($descripcion_corta_es, 0, 60);
    $contenido = $descripcion_corta_es;

    autLog("Título generado: $titulo, Contenido generado: $contenido");

    // Crear el post
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $contenido,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];

    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) {
        autLog("Error al crear el post: " . $post_id->get_error_message());
        return $post_id;
    }

    autLog("Post creado con ID: $post_id");

    update_post_meta($post_id, 'rutaOriginal', $nuevo_nombre_original);
    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);

    $nuevo_nombre_original = "$directory/$nombre_final_con_id.$extension";
    autLog("Nuevo nombre original: $nuevo_nombre_original");

    if (!rename($audio_path, $nuevo_nombre_original)) {
        autLog("No se pudo renombrar el archivo original.");
        return;
    }

    // Renombrar archivo lite
    $nuevo_nombre_lite = "$directory/{$nombre_final_con_id}_lite.mp3";
    autLog("Nuevo nombre lite: $nuevo_nombre_lite");

    if (!rename($lite_path, $nuevo_nombre_lite)) {
        autLog("No se pudo renombrar el archivo lite.");
        return;
    }

    update_post_meta($post_id, 'postAut', true);

    $audio_original_id = adjuntarArchivoAut($nuevo_nombre_original, $post_id, $file_id);
    if (is_wp_error($audio_original_id)) {
        autLog("Error al adjuntar archivo original: " . $audio_original_id->get_error_message());
        wp_delete_post($post_id, true);
        return $audio_original_id;
    }

    autLog("Archivo original adjuntado con ID: $audio_original_id");

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) {
        autLog("Error al adjuntar archivo lite: " . $audio_lite_id->get_error_message());
        return $audio_lite_id;
    }

    autLog("Archivo lite adjuntado con ID: $audio_lite_id");

    update_post_meta($post_id, 'post_audio', $audio_original_id);
    update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    update_post_meta($post_id, 'paraDescarga', true);

    // INFORMACIÓN NUEVA PARA CARPETAS Y FUNCIONALIDAD DE KITS
    update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
    update_post_meta($post_id, 'carpetaOriginal', $carpeta);
    update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);

    autLog("Metadatos del post actualizados.");

    // Agregar los datos del algoritmo como meta con los datos JSON de $datosAlgoritmo
    update_post_meta($post_id, 'datosAlgoritmo', json_encode($datosAlgoritmo));

    autLog("Datos del algoritmo guardados en el post.");

    // Devolver el ID del post
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
        autLog('No se encontraron posts de tipo social_post con postAut=1.');
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
            autLog("Post ID $post_id: No se encontró 'post_audio'.");
        }

        // Obtener el ID de adjunto de post_audio_lite
        $post_audio_lite_id = get_post_meta( $post_id, 'post_audio_lite', true );
        if ( !$post_audio_lite_id ) {
            autLog("Post ID $post_id: No se encontró 'post_audio_lite'.");
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
                    autLog("Post ID $post_id: No se encontró el archivo original '$filename'.");
                }
            } else {
                autLog("Post ID $post_id: No se encontró el adjunto con ID $post_audio_id.");
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
                    autLog("Post ID $post_id: No se pudo obtener la ruta de 'post_audio_lite'.");
                }
            } else {
                autLog("Post ID $post_id: No se encontró el adjunto lite con ID $post_audio_lite_id.");
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
                    autLog("Post ID $post_id: No se pudo obtener 'idHash_audioId' para la URL '$adjunto_url'.");
                }
            } else {
                autLog("Post ID $post_id: No se pudo obtener la URL del adjunto con ID $post_audio_id.");
            }
        }
    }

    autLog('Actualización de metadatos de posts social_post completada.');
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