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
    // Se llama a la función desde su nueva ubicación en PostCreationService
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


// Refactor(Org): Función adjuntarArchivoAut() movida a app/Services/Post/PostAttachmentService.php

// Refactor(Org): Función buscar_archivo_recursivo() movida a app/Utils/SystemUtils.php

// Refactor(Org): Función crearAutPost() movida a app/Services/Post/PostCreationService.php

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
