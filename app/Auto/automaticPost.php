<?



// Refactor(Org): Función manejarArchivoFallido() movida a app/Utils/SystemUtils.php

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
