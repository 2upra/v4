<?

function autProcesarAudio($rutaOriginalOne)
{
    guardarLog("Iniciando autProcesarAudio para el archivo: $rutaOriginalOne");

    // Obtener ID del archivo por la ruta directa
    $file_id = obtenerFileIDPorURL($rutaOriginalOne);
    if ($file_id === false) {
        guardarLog("Error: No se encontró el ID del archivo para $rutaOriginalOne");
        eliminarHash($file_id);
        return;
    }
    guardarLog("ID del archivo obtenido: $file_id");

    // Verificar si el archivo existe
    if (!file_exists($rutaOriginalOne)) {
        guardarLog("Error: El archivo no existe en la ruta: $rutaOriginalOne");
        eliminarHash($file_id);
        return;
    }

    // Obtener partes del path
    $path_parts = pathinfo($rutaOriginalOne);
    $directory = realpath($path_parts['dirname']);
    if ($directory === false) {
        guardarLog("Error: No se encontró el directorio para {$path_parts['dirname']}");
        eliminarHash($file_id);
        return;
    }
    $extension = strtolower($path_parts['extension']);
    $basename = $path_parts['filename'];
    guardarLog("Ruta inicial: $rutaOriginalOne, Directorio: $directory, Basename: $basename, Extensión: $extension");

    // Ruta temporal para eliminar metadatos
    $temp_path = "$directory/{$basename}_temp.$extension";

    // 1. Eliminar cualquier metadato y borrar imágenes adjuntas
    $comando_strip_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -map_metadata -1 -c copy " . escapeshellarg($temp_path) . " -y";
    guardarLog("Ejecutando comando para eliminar metadatos: $comando_strip_metadata");
    exec($comando_strip_metadata, $output_strip, $return_strip);
    if ($return_strip !== 0) {
        guardarLog("Error al eliminar metadatos: " . implode(" | ", $output_strip));
        eliminarHash($file_id);
        return;
    }
    guardarLog("Metadatos eliminados con éxito.");

    // 2. Reemplazar el archivo original con el archivo sin metadatos
    if (!rename($temp_path, $rutaOriginalOne)) {
        guardarLog("Error: No se pudo reemplazar el archivo original con el archivo sin metadatos.");
        eliminarHash($file_id);
        return;
    }
    guardarLog("Archivo original reemplazado con éxito por el archivo sin metadatos.");

    // 3. Agregar nueva imagen como metadato
    $rutaNuevaImagen = "/var/www/wordpress/wp-content/uploads/2024/10/temporal08_1730099605.jpg";
    $rutaFinalConImagen = "$directory/{$basename}_con_imagen.$extension";
    $comando_add_image_metadata = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -i " . escapeshellarg($rutaNuevaImagen) . " -map 0 -map 1 -c copy -metadata:s:v title='Album cover' -metadata:s:v comment='Cover (front)' " . escapeshellarg($rutaFinalConImagen) . " -y";
    guardarLog("Ejecutando comando para agregar imagen: $comando_add_image_metadata");
    exec($comando_add_image_metadata, $output_add_meta, $return_add_meta);
    if ($return_add_meta !== 0) {
        guardarLog("Error al agregar la imagen: " . implode(" | ", $output_add_meta));
        eliminarHash($file_id);
        return;
    }
    guardarLog("Imagen agregada exitosamente al archivo.");

    // 4. Reemplazar archivo original con el archivo que contiene la nueva imagen
    if (!rename($rutaFinalConImagen, $rutaOriginalOne)) {
        guardarLog("Error: No se pudo reemplazar el archivo original con el archivo que contiene la nueva imagen.");
        eliminarHash($file_id);
        return;
    }
    guardarLog("Archivo reemplazado exitosamente por el archivo con nueva imagen.");

    // 5. Crear versión lite en MP3 a 128 kbps
    $rutaWpLiteDos = "$directory/{$basename}_lite.mp3";
    $comando_lite = "/usr/bin/ffmpeg -i " . escapeshellarg($rutaOriginalOne) . " -b:a 128k " . escapeshellarg($rutaWpLiteDos) . " -y";
    guardarLog("Ejecutando comando para crear versión lite en MP3: $comando_lite");
    exec($comando_lite, $output_lite, $return_lite);
    if ($return_lite !== 0) {
        guardarLog("Error al crear versión lite: " . implode(" | ", $output_lite));
        eliminarHash($file_id);
        return;
    }
    guardarLog("Versión lite creada exitosamente.");

    // 6. Mover el archivo lite al directorio de uploads
    $uploads_dir = wp_upload_dir();
    $target_dir_audio = trailingslashit($uploads_dir['basedir']) . "audio/";

    // Crear directorio 'audio' si no existe
    if (!file_exists($target_dir_audio)) {
        if (!wp_mkdir_p($target_dir_audio)) {
            guardarLog("Error: No se pudo crear el directorio de uploads/audio.");
            eliminarHash($file_id);
            return;
        }
    }
    guardarLog("Directorio 'audio' verificado/creado exitosamente.");

    // Ruta final del archivo lite en el directorio de uploads
    $rutaWpLiteOne = $target_dir_audio . "{$basename}_lite.mp3";

    // Mover archivo lite
    if (!rename($rutaWpLiteDos, $rutaWpLiteOne)) {
        guardarLog("Error: No se pudo mover el archivo lite al directorio de uploads.");
        eliminarHash($file_id);
        return;
    }
    guardarLog("Archivo lite movido exitosamente al directorio de uploads: $rutaWpLiteOne");

    // Enviar rutas a crearAutPost
    guardarLog("Enviando rutas a crearAutPost: Original - $rutaOriginalOne, Lite - $rutaWpLiteOne");
    crearAutPost($rutaOriginalOne, $rutaWpLiteOne, $file_id);
    guardarLog("Archivos enviados a crearAutPost.");

    guardarLog("Finalizando autProcesarAudio.");
}


function automaticAudio($rutaArchivo, $nombre_archivo = null, $carpeta = null, $carpeta_abuela = null)
{
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
        . "Por favor, determina una descripción precisa del audio utilizando el siguiente formato JSON. La información como el nombre y las carpetas son información super relevante para completar el JSON. Por favor, ignora cualquier nombre comercial, dominio, redes sociales o información no relevante que pueda contener el nombre o las carpetas. También ignora la palabra 'lite' o '2upra'. El 'nombre_corto' es un nuevo nombre para el archivo, y la 'descripción corta' es para entender rápidamente qué es el audio, por favor, que sea corta pero sin perder detalles importantes. Importante por no digas nada sobre las carpetas o donde esta ubicado el archivo, solo es una guia para entender de que trata el audio no hay que comentarlo. Te incluyo la estructura JSON con datos de ejemplo, que son irrelevantes en este caso: "
        . '{"descripcion_ia":{"es":"(aquí iría una descripción tuya del audio muy detallada)", "en":"(aquí en inglés)"},'
        . '"instrumentos_principal":{"es":["Piano"], "en":["Piano"]},'
        . '"nombre_corto":{"es":["(maximo 3 palabras)"], "en":["Kick Vitagen"]},'
        . '"descripcion_corta":{"es":["(entre 4 a 8 palabras)"], "en":["(en ingles)"]},'
        . '"estado_animo":{"es":["Tranquilo"], "en":["Calm"]},'
        . '"genero_posible":{"es":["Hip hop"], "en":["Hip hop"]},'
        . '"artista_posible":{"es":["Freddie Dredd", "Flume"], "en":["Freddie Dredd", "Flume"]},'
        . '"tipo_audio":{"es":["determina si es un sample, un loop o un one shot"], "en":["Sample"]},'
        . '"tags_posibles":{"es":["Naturaleza", "phonk", "memphis", "oscuro"], "en":["Nature"]},'
        . '"sugerencia_busqueda":{"es":["Sonido relajante"], "en":["Relaxing sound"]}}.'
        . " Nota adicional: responde solo con la estructura JSON solicitada, mantén datos vacíos si no aplica. Es crucial determinar si es un loop, un one shot o un sample. Usa tags de una palabra y optimiza el SEO con sugerencias de búsqueda relevantes. Sé muy detallado sin perder precisión. Aunque te pido en español y en ingles, hay algunas palabras que son mejor mantenerlas en ingles cuando en español son muy frecuentes, por ejemplo, kick, snare, cowbell, etc.";

    $descripcion = generarDescripcionIA($rutaArchivo, $prompt);

    if ($descripcion) {
        // Convertir a UTF-8
        $descripcion_utf8 = mb_convert_encoding($descripcion, 'UTF-8', 'auto');
        $descripcion_procesada = json_decode(trim($descripcion_utf8, "```json \n"), true, 512, JSON_UNESCAPED_UNICODE);

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

            //autLog("Descripción del audio guardada para el post ID: {$nombre_archivo}");
        }
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

    return $nuevos_datos_algoritmo;
}

function crearAutPost($rutaOriginal, $rutaWpLite, $file_id)
{
    // Configuración de variables iniciales
    $autor_id = 44;
    $nombre_archivo = pathinfo($rutaOriginal, PATHINFO_FILENAME);
    $carpeta = basename(dirname($rutaOriginal));
    $carpeta_abuela = basename(dirname(dirname($rutaOriginal)));

    $datosAlgoritmo = automaticAudio($rutaWpLite, $nombre_archivo, $carpeta, $carpeta_abuela);
    if (!$datosAlgoritmo) {
        eliminarHash($file_id);
        return;
    }

    $descripcion_corta_es = $datosAlgoritmo['descripcion_corta']['en'] ?? '';
    $nombre_generado = $datosAlgoritmo['nombre_corto']['en'] ?? '';

    // Manejo de arrays en nombre generado
    if (is_array($nombre_generado)) {
        $nombre_generado = $nombre_generado[0] ?? '';
    }

    if ($nombre_generado) {
        $nombre_generado_limpio = preg_replace('/[^A-Za-z0-9\- áéíóúÁÉÍÓÚñÑ]/u', '', trim($nombre_generado));
        $id_unica = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        $nombre_final = substr($nombre_generado_limpio . '_' . $id_unica . '_2upra', 0, 60);
    } else {
        eliminarHash($file_id);
        return;
    }

    // Renombrar archivo original con verificación
    $extension_original = pathinfo($rutaOriginal, PATHINFO_EXTENSION);
    $nuevo_nombre_original = dirname($rutaOriginal) . '/' . $nombre_final . '.' . $extension_original;
    if (!file_exists($rutaOriginal) || (file_exists($nuevo_nombre_original) && !unlink($nuevo_nombre_original))) {
        return;
    }
    if (!rename($rutaOriginal, $nuevo_nombre_original)) {
        return;
    }

    // Renombrar archivo lite con verificación
    if (!file_exists($rutaWpLite)) return;
    $extension_lite = pathinfo($rutaWpLite, PATHINFO_EXTENSION);
    $nuevo_nombre_lite = dirname($rutaWpLite) . '/' . $nombre_final . '_lite.' . $extension_lite;
    if (file_exists($nuevo_nombre_lite) && !unlink($nuevo_nombre_lite)) {
        return;
    }
    if (!rename($rutaWpLite, $nuevo_nombre_lite)) {
        return;
    }

    // Asegurar que la descripción es una cadena
    if (is_array($descripcion_corta_es)) {
        $descripcion_corta_es = $descripcion_corta_es[0] ?? '';
    }

    // Crear el post
    $titulo = mb_substr($descripcion_corta_es, 0, 60);
    $post_data = [
        'post_title'    => $titulo,
        'post_content'  => $descripcion_corta_es,
        'post_status'   => 'publish',
        'post_author'   => $autor_id,
        'post_type'     => 'social_post',
    ];
    $post_id = wp_insert_post($post_data);

    if (is_wp_error($post_id)) return $post_id;

    update_post_meta($post_id, 'rutaOriginal', $nuevo_nombre_original);
    update_post_meta($post_id, 'rutaLiteOriginal', $nuevo_nombre_lite);
    update_post_meta($post_id, 'postAut', true);

    $audio_original_id = adjuntarArchivoAut($nuevo_nombre_original, $post_id, $file_id);
    if (is_wp_error($audio_original_id)) {
        wp_delete_post($post_id, true);
        return $audio_original_id;
    }

    $audio_lite_id = adjuntarArchivoAut($nuevo_nombre_lite, $post_id);
    if (is_wp_error($audio_lite_id)) return $audio_lite_id;

    // Metadatos del post
    update_post_meta($post_id, 'post_audio', $audio_original_id);
    update_post_meta($post_id, 'post_audio_lite', $audio_lite_id);
    update_post_meta($post_id, 'paraDescarga', true);
    update_post_meta($post_id, 'nombreOriginal', $nombre_archivo);
    update_post_meta($post_id, 'carpetaOriginal', $carpeta);
    update_post_meta($post_id, 'carpetaAbuelaOriginal', $carpeta_abuela);
    update_post_meta($post_id, 'audio_bpm', $datosAlgoritmo['bpm'] ?? null);
    update_post_meta($post_id, 'audio_key', $datosAlgoritmo['key'] ?? null);
    update_post_meta($post_id, 'audio_scale', $datosAlgoritmo['scale'] ?? null);
    update_post_meta($post_id, 'datosAlgoritmo', json_encode($datosAlgoritmo, JSON_UNESCAPED_UNICODE));

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