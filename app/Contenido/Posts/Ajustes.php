<?

/**
 * Actualiza títulos y slugs de los posts 'social_post' verificados.
 */
function actualizar_titulos_y_slugs_social_posts() {
    guardarLog('Iniciando la función actualizar_titulos_y_slugs_social_posts');

    // Verifica si la actualización ya se ha realizado
    if ( get_option( 'social_posts_actualizados' ) ) {
        guardarLog('Opción social_posts_actualizados encontrada. Saliendo de la función.');
        return;
    }

    // Argumentos para la consulta
    $args = array(
        'post_type'      => 'social_post',
        'meta_key'       => 'Verificado',
        'meta_value'     => 'true',
        'posts_per_page' => -1, // Obtener todos los posts
    );

    $query = new WP_Query( $args );
    guardarLog('Consulta WP_Query ejecutada con argumentos: ' . print_r($args, true));

    if ( $query->have_posts() ) {
        guardarLog('Posts encontrados: ' . $query->found_posts);
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id   = get_the_ID();
            $contenido = get_the_content();

            guardarLog("Procesando post ID: $post_id");

            // Limpiar el contenido para usarlo en el título y slug
            $nuevo_titulo = sanitize_text_field( $contenido );
            $nuevo_slug   = sanitize_title( $contenido );

            guardarLog("Nuevo título propuesto: $nuevo_titulo");
            guardarLog("Nuevo slug base propuesto: $nuevo_slug");

            // Obtener el slug actual
            $slug_actual = get_post_field( 'post_name', $post_id );
            guardarLog("Slug actual: $slug_actual");

            // Verificar si el slug actual ya tiene un sufijo numérico
            $slug_base = preg_replace('/-\d+$/', '', $slug_actual);
            if ( $slug_base === $slug_actual ) {
                // El slug actual no tiene sufijo numérico
                // Preparar el nuevo slug único
                $nuevo_slug_unico = wp_unique_post_slug( $nuevo_slug, $post_id, get_post_status( $post_id ), get_post_type( $post_id ), get_post_parent( $post_id ) );
                guardarLog("Nuevo slug único: $nuevo_slug_unico");
            } else {
                // El slug ya tiene un sufijo numérico, asumiendo que es único
                guardarLog("El slug ya tiene un sufijo numérico. No se actualizará el slug.");
                $nuevo_slug_unico = $slug_actual; // Mantener el slug actual
            }

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

                if ( is_wp_error( $resultado ) ) {
                    guardarLog("Error actualizando el post ID $post_id: " . $resultado->get_error_message());
                } else {
                    guardarLog("Post ID $post_id actualizado correctamente.");
                }
            } else {
                guardarLog("Post ID $post_id no requiere actualizaciones.");
            }
        }

        // Opcionalmente, establecer la opción para evitar futuras actualizaciones
        update_option( 'social_posts_actualizados', true );
        guardarLog('Opción social_posts_actualizados establecida como true.');
    } else {
        guardarLog('No se encontraron posts que cumplan con los criterios.');
    }

    // Restaurar los datos originales de la consulta
    wp_reset_postdata();
}
actualizar_titulos_y_slugs_social_posts();


function actualizar_titulo_slug_al_guardar( $post_id, $post, $update ) {
    guardarLog("Iniciando actualización al guardar para post ID: $post_id");

    // Verificar el tipo de post
    if ( $post->post_type !== 'social_post' ) {
        guardarLog("El post ID $post_id no es de tipo 'social_post'. Saliendo de la función.");
        return;
    }

    // Verificar si el usuario puede editar el post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        guardarLog("El usuario actual no tiene permisos para editar el post ID $post_id. Saliendo de la función.");
        return;
    }

    // Verificar la meta 'Verificado'
    $verificado = get_post_meta( $post_id, 'Verificado', true );
    guardarLog("Meta 'Verificado' para post ID $post_id: $verificado");

    if ( $verificado !== 'true' ) {
        guardarLog("El post ID $post_id no está verificado. Saliendo de la función.");
        return;
    }

    // Evitar loops infinitos
    remove_action( 'save_post', 'actualizar_titulo_slug_al_guardar', 10 );

    // Obtener el contenido
    $contenido = $post->post_content;
    guardarLog("Contenido del post ID $post_id: $contenido");

    // Generar el nuevo título y slug
    $nuevo_titulo = sanitize_text_field( $contenido );
    $nuevo_slug   = sanitize_title( $contenido );
    guardarLog("Nuevo título: $nuevo_titulo");
    guardarLog("Nuevo slug base: $nuevo_slug");

    // Asegurar que el slug sea único
    $nuevo_slug_unico = wp_unique_post_slug( $nuevo_slug, $post_id, $post->post_status, $post->post_type, $post->post_parent );
    guardarLog("Nuevo slug único: $nuevo_slug_unico");

    // Preparar los datos para actualizar
    $post_data = array(
        'ID'         => $post_id,
        'post_title' => $nuevo_titulo,
        'post_name'  => $nuevo_slug_unico,
    );

    // Actualizar el post
    $resultado = wp_update_post( $post_data, true );

    if ( is_wp_error( $resultado ) ) {
        guardarLog("Error actualizando el post ID $post_id: " . $resultado->get_error_message());
    } else {
        guardarLog("Post ID $post_id actualizado correctamente al guardar.");
    }

    // Re-agregar el hook
    add_action( 'save_post', 'actualizar_titulo_slug_al_guardar', 10, 3 );
}
add_action( 'save_post', 'actualizar_titulo_slug_al_guardar', 10, 3 );