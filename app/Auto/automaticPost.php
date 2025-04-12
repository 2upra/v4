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

// Refactor(Org): Función automaticAudio() movida a app/Services/AudioProcessingService.php



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