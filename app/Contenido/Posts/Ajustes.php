<?

function registrarCambioSlug($old_slug, $post_id, $new_slug) {
    $log_file = get_stylesheet_directory() . '/cambiosSlug.log';
    $date = current_time('Y-m-d H:i:s');
    $log_entry = sprintf("[%s] Post ID: %d | Slug Anterior: %s | Nuevo Slug: %s\n", $date, $post_id, $old_slug, $new_slug);
    
    // Asegurarse de que el archivo es escribible
    if (is_writable($log_file)) {
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    } else {
        error_log("No se puede escribir en el archivo de log: $log_file");
    }
}
function actualizar_titulos_y_slugs_social_posts() {
    // Argumentos para la consulta
    $args = array(
        'post_type'      => 'social_post',
        'meta_key'       => 'Verificado',
        'meta_value'     => '1',
        'posts_per_page' => -1, // Obtener todos los posts
        // Desactivar temporalmente la verificación de la fecha en 'ultima_actualizacion_slug'
        /*
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'     => 'ultima_actualizacion_slug',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key'     => 'ultima_actualizacion_slug',
                'value'   => date('Y-m-d H:i:s', strtotime('-7 days')),
                'compare' => '<',
                'type'    => 'DATETIME',
            ),
        ),
        */
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id   = get_the_ID();
            // Extraer contenido sin etiquetas HTML
            $contenido = wp_strip_all_tags( get_the_content() );

            // Limpiar el contenido para usarlo en el título y slug
            $nuevo_titulo = sanitize_text_field( $contenido );
            $nuevo_slug   = sanitize_title( $contenido );

            // Verificar que el contenido no esté vacío
            if ( empty( $nuevo_titulo ) || empty( $nuevo_slug ) ) {
                continue; // Saltar a la siguiente iteración
            }

            // Obtener el slug actual
            $slug_actual = get_post_field( 'post_name', $post_id );

            // Preparar el nuevo slug único
            $nuevo_slug_unico = wp_unique_post_slug( $nuevo_slug, $post_id, get_post_status( $post_id ), get_post_type( $post_id ), get_post_parent( $post_id ) );

            // Preparar los datos para actualizar
            $post_data = array(
                'ID'         => $post_id,
                'post_title' => $nuevo_titulo,
                'post_name'  => $nuevo_slug_unico,
            );

            // Comparar el título actual con el propuesto
            $titulo_actual = get_post_field( 'post_title', $post_id );
            $actualizar_titulo = ( $titulo_actual !== $nuevo_titulo );

            // Comparar el slug actual con el propuesto
            $actualizar_slug = ( $slug_actual !== $nuevo_slug_unico );

            if ( $actualizar_titulo || $actualizar_slug ) {
                // Actualizar el post solo si el título o slug son diferentes
                $resultado = wp_update_post( $post_data, true );

                if ( ! is_wp_error( $resultado ) ) {
                    // Actualizar la meta 'ultima_actualizacion_slug' con la fecha actual
                    update_post_meta( $post_id, 'ultima_actualizacion_slug', current_time( 'mysql' ) );

                    // Si se actualizó el slug, registrar el cambio
                    if ( $actualizar_slug ) {
                        registrarCambioSlug($slug_actual, $post_id, $nuevo_slug_unico);
                    }
                }
            }
        }
    }

    // Restaurar los datos originales de la consulta
    wp_reset_postdata();
}

actualizar_titulos_y_slugs_social_posts();
