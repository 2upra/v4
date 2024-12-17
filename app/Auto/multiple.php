<?

function generar_publicaciones_multiples()
{
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
        'orderby' => 'ID',  // Ordenar por ID ascendente
        'order'   => 'ASC',
    );
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);

            // Copiar metas si existen
            $paraColab = get_post_meta($post_id, 'paraColab', true);
            $paraDescarga = get_post_meta($post_id, 'paraDescarga', true);
            $artista = get_post_meta($post_id, 'artista', true);
            $fan = get_post_meta($post_id, 'fan', true);
            $rola = get_post_meta($post_id, 'rola', true);
            $sample = get_post_meta($post_id, 'sample', true);
            $tagsUsuario = get_post_meta($post_id, 'tagsUsuario', true);

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

                // Si existe el audio lite, generar un nuevo post
                if (! empty($ruta_audio_lite) && !empty($audio_id_hash)) {
                    $multiples_audios_encontrados = true;

                    // Obtener la ruta del archivo en el servidor
                    $upload_dir = wp_upload_dir();
                    $ruta_servidor = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ruta_audio_lite);
                    $ruta_original = null; //la ruta original se puede omitir

                    // Crear el nuevo post usando crearAutPost
                    $nuevo_post_id = crearAutPost($ruta_original, $ruta_servidor, $audio_id_hash, $author_id, $post_id);

                    // Copiar metas al nuevo post si existen y se creó el post
                    if (! is_wp_error($nuevo_post_id) && $nuevo_post_id) {
                        // Guardar el ID del nuevo post en el array
                        $ids_nuevos_posts[] = $nuevo_post_id;

                        if (! empty($paraColab)) {
                            update_post_meta($nuevo_post_id, 'paraColab', $paraColab);
                        }
                        if (! empty($paraDescarga)) {
                            update_post_meta($nuevo_post_id, 'paraDescarga', $paraDescarga);
                        }
                        if (! empty($artista)) {
                            update_post_meta($nuevo_post_id, 'artista', $artista);
                        }
                        if (! empty($fan)) {
                            update_post_meta($nuevo_post_id, 'fan', $fan);
                        }
                        if (! empty($rola)) {
                            update_post_meta($nuevo_post_id, 'rola', $rola);
                        }
                        if (! empty($sample)) {
                            update_post_meta($nuevo_post_id, 'sample', $sample);
                        }
                        if (! empty($tagsUsuario)) {
                            update_post_meta($nuevo_post_id, 'tagsUsuario', $tagsUsuario);
                        }
                        if (! empty($audio_id)) {
                            update_post_meta($nuevo_post_id, 'post_audio', $audio_id);
                        }

                        // Pausa de 2 segundos entre cada creación de post
                        sleep(2);
                    }
                }
            }

            // Si no se encontraron múltiples audios, eliminar la meta 'multiple'
            if (! $multiples_audios_encontrados) {
                delete_post_meta($post_id, 'multiple');
            } else {
                // Si se encontraron múltiples audios, guardar los IDs de los nuevos posts en el post original
                update_post_meta($post_id, 'posts_generados', $ids_nuevos_posts);
            }
        }
    }
    wp_reset_postdata();
}



function programar_generacion_publicaciones() {
    if (! wp_next_scheduled ( 'generar_publicaciones_multiples_evento' )) {
        wp_schedule_event(time(), 'cada_treinta_segundos', 'generar_publicaciones_multiples_evento');
    }
}
add_action('wp', 'programar_generacion_publicaciones'); 
add_action('generar_publicaciones_multiples_evento', 'generar_publicaciones_multiples');

// Define el intervalo personalizado de 30 segundos
function agregar_intervalo_treinta_segundos( $schedules ) {
    $schedules['cada_treinta_segundos'] = array(
        'interval' => 30,
        'display'  => esc_html__( 'Cada 30 segundos' ),
    );
    return $schedules;
}
add_filter( 'cron_schedules', 'agregar_intervalo_treinta_segundos' );