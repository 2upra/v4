<?


/**
 * Función para generar publicaciones múltiples a partir de un post con la meta 'multiple' establecida en true.
 *
 * @return void
 */
function generar_publicaciones_multiples() {
    // Registro de inicio de la función
    error_log('generar_publicaciones_multiples() - Inicio de la función.');

    // Buscar todos los social_post con multiple en 1 o true
    $args = array(
        'post_type'      => 'social_post',
        'posts_per_page' => 1, // Procesar solo 1 post por ejecución
        'meta_query'     => array(
            array(
                'key'   => 'multiple',
                'value' => '1',
            ),
        ),
        'orderby'        => 'ID',  // Ordenar por ID ascendente
        'order'          => 'ASC',
    );
    $query = new WP_Query($args);

    error_log('generar_publicaciones_multiples() - Consulta realizada: ' . var_export($args, true));

    if ($query->have_posts()) {
        error_log('generar_publicaciones_multiples() - Se encontraron posts con la meta "multiple".');

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);

            error_log('generar_publicaciones_multiples() - Procesando post ID: ' . $post_id . ', Autor ID: ' . $author_id);

            // Copiar metas si existen
            $paraColab = get_post_meta($post_id, 'paraColab', true);
            $paraDescarga = get_post_meta($post_id, 'paraDescarga', true);
            $artista = get_post_meta($post_id, 'artista', true);
            $fan = get_post_meta($post_id, 'fan', true);
            $rola = get_post_meta($post_id, 'rola', true);
            $sample = get_post_meta($post_id, 'sample', true);
            $tagsUsuario = get_post_meta($post_id, 'tagsUsuario', true);

            error_log('generar_publicaciones_multiples() - Valores de metas: paraColab: ' . $paraColab . ', paraDescarga: ' . $paraDescarga . ', artista: ' . $artista . ', fan: ' . $fan . ', rola: ' . $rola . ', sample: ' . $sample . ', tagsUsuario: ' . $tagsUsuario);

            $multiples_audios_encontrados = false;
            $ids_nuevos_posts = array(); // Array para almacenar los IDs de los nuevos posts

            // Iterar sobre los posibles audios múltiples
            for ($i = 2; $i <= 30; $i++) {
                $audio_lite_meta_key = 'post_audio_lite_' . $i;
                $audio_meta_key = 'post_audio' . $i;
                $idHash_audioId_key = 'idHash_audioId' . $i;

                $ruta_audio_lite = get_post_meta($post_id, $audio_lite_meta_key, true);
                $audio_id_hash = get_post_meta($post_id, $idHash_audioId_key, true);
                $audio_id = get_post_meta($post_id, $audio_meta_key, true);
                error_log('generar_publicaciones_multiples() - Iteración: ' . $i . ', ruta_audio_lite: ' . $ruta_audio_lite . ', audio_id_hash: ' . $audio_id_hash. ', audio_id: ' . $audio_id);

                // Si existe el audio lite, generar un nuevo post
                if (! empty($ruta_audio_lite) && ! empty($audio_id_hash) && !empty($audio_id)) {
                    $multiples_audios_encontrados = true;

                    // Obtener la ruta del archivo en el servidor
                    $upload_dir = wp_upload_dir();
                    $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);
                    $ruta_original = null; //la ruta original se puede omitir

                    error_log('generar_publicaciones_multiples() - Ruta del archivo en el servidor: ' . $ruta_servidor);

                    // Crear el nuevo post usando crearAutPost
                    $nuevo_post_id = crearAutPost($ruta_original, $ruta_servidor, $audio_id_hash, $author_id, $post_id);
                    error_log('generar_publicaciones_multiples() - Resultado de crearAutPost: ' . var_export($nuevo_post_id, true));

                    // Copiar metas al nuevo post si existen y se creó el post
                    if (! is_wp_error($nuevo_post_id) && $nuevo_post_id) {
                        // Guardar el ID del nuevo post en el array
                        $ids_nuevos_posts[] = $nuevo_post_id;
                        error_log('generar_publicaciones_multiples() - Nuevo post creado con ID: ' . $nuevo_post_id);

                        if (! empty($paraColab)) {
                            update_post_meta($nuevo_post_id, 'paraColab', $paraColab);
                            error_log('generar_publicaciones_multiples() - Meta paraColab copiada.');
                        }
                        if (! empty($paraDescarga)) {
                            update_post_meta($nuevo_post_id, 'paraDescarga', $paraDescarga);
                            error_log('generar_publicaciones_multiples() - Meta paraDescarga copiada.');
                        }
                        if (! empty($artista)) {
                            update_post_meta($nuevo_post_id, 'artista', $artista);
                            error_log('generar_publicaciones_multiples() - Meta artista copiada.');
                        }
                        if (! empty($fan)) {
                            update_post_meta($nuevo_post_id, 'fan', $fan);
                            error_log('generar_publicaciones_multiples() - Meta fan copiada.');
                        }
                        if (! empty($rola)) {
                            update_post_meta($nuevo_post_id, 'rola', $rola);
                            error_log('generar_publicaciones_multiples() - Meta rola copiada.');
                        }
                        if (! empty($sample)) {
                            update_post_meta($nuevo_post_id, 'sample', $sample);
                            error_log('generar_publicaciones_multiples() - Meta sample copiada.');
                        }
                        if (! empty($tagsUsuario)) {
                            update_post_meta($nuevo_post_id, 'tagsUsuario', $tagsUsuario);
                            error_log('generar_publicaciones_multiples() - Meta tagsUsuario copiada.');
                        }
                        if (! empty($audio_id)) {
                            update_post_meta($nuevo_post_id, 'post_audio', $audio_id);
                            error_log('generar_publicaciones_multiples() - Meta post_audio copiada.');
                        }

                        // Pausa de 2 segundos entre cada creación de post
                        sleep(2);
                    } else {
                        error_log('generar_publicaciones_multiples() - Error al crear el nuevo post.');
                    }
                } else{
                    error_log('generar_publicaciones_multiples() - No se encontraron datos suficientes para crear un nuevo post en la iteración: ' . $i);
                }
            }

            // Si no se encontraron múltiples audios, eliminar la meta 'multiple'
            if (! $multiples_audios_encontrados) {
                delete_post_meta($post_id, 'multiple');
                error_log('generar_publicaciones_multiples() - No se encontraron múltiples audios para el post ID: ' . $post_id . '. Se eliminó la meta "multiple".');
            } else {
                // Si se encontraron múltiples audios, guardar los IDs de los nuevos posts en el post original
                update_post_meta($post_id, 'posts_generados', $ids_nuevos_posts);
                error_log('generar_publicaciones_multiples() - Se encontraron múltiples audios para el post ID: ' . $post_id . '. IDs de los nuevos posts guardados: ' . implode(', ', $ids_nuevos_posts));
            }
        }
    } else {
        error_log('generar_publicaciones_multiples() - No se encontraron posts con la meta "multiple".');
    }
    wp_reset_postdata();
    error_log('generar_publicaciones_multiples() - Fin de la función.');
}

/**
 * Programa la ejecución de la función generar_publicaciones_multiples() cada 30 segundos.
 *
 * @return void
 */
function programar_generacion_publicaciones() {
    if (! wp_next_scheduled ( 'generar_publicaciones_multiples_evento' )) {
        wp_schedule_event(time(), 'cada_treinta_segundos', 'generar_publicaciones_multiples_evento');
        error_log('programar_generacion_publicaciones() - Evento programado.');
    } else {
        error_log('programar_generacion_publicaciones() - El evento ya está programado.');
    }
}
add_action('wp', 'programar_generacion_publicaciones');
add_action('generar_publicaciones_multiples_evento', 'generar_publicaciones_multiples');

/**
 * Define el intervalo personalizado de 30 segundos.
 *
 * @param array $schedules Los intervalos de tiempo programados.
 *
 * @return array Los intervalos de tiempo programados con el nuevo intervalo de 30 segundos.
 */
function agregar_intervalo_treinta_segundos( $schedules ) {
    $schedules['cada_treinta_segundos'] = array(
        'interval' => 30,
        'display'  => esc_html__( 'Cada 30 segundos' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'agregar_intervalo_treinta_segundos' );

