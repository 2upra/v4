<?

function actualizar_titulos_y_slugs_social_posts() {
    // Verifica si la actualización ya se ha realizado
    if ( get_option( 'social_posts_actualizados' ) ) {
        return;
    }

    // Argumentos para la consulta
    $args = array(
        'post_type'  => 'social_post',
        'meta_key'   => 'Verificado',
        'meta_value' => 'true',
        'posts_per_page' => -1, // Obtener todos los posts
    );

    $query = new WP_Query( $args );

    if ( $query->have_posts() ) {
        while ( $query->have_posts() ) {
            $query->the_post();

            $post_id  = get_the_ID();
            $contenido = get_the_content();

            // Limpiar el contenido para usarlo en el título y slug
            $nuevo_titulo = sanitize_text_field( $contenido );
            $nuevo_slug   = sanitize_title( $contenido );

            // Asegurar que el slug sea único
            $nuevo_slug_unico = wp_unique_post_slug( $nuevo_slug, $post_id, get_post_status( $post_id ), get_post_type( $post_id ), get_post_parent( $post_id ) );

            // Preparar los datos para actualizar
            $post_data = array(
                'ID'         => $post_id,
                'post_title' => $nuevo_titulo,
                'post_name'  => $nuevo_slug_unico,
            );

            // Actualizar el post
            wp_update_post( $post_data );
        }
    }

    // Restaurar los datos originales de la consulta
    wp_reset_postdata();

    // Marcar como actualizado
    update_option( 'social_posts_actualizados', true );
}
add_action( 'init', 'actualizar_titulos_y_slugs_social_posts' );

function actualizar_titulo_slug_al_guardar( $post_id, $post, $update ) {
    // Verificar el tipo de post
    if ( $post->post_type !== 'social_post' ) {
        return;
    }

    // Verificar si el usuario puede editar el post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Verificar la meta 'Verificado'
    $verificado = get_post_meta( $post_id, 'Verificado', true );
    if ( $verificado !== 'true' ) {
        return;
    }

    // Evitar loops infinitos
    remove_action( 'save_post', 'actualizar_titulo_slug_al_guardar', 10 );

    // Obtener el contenido
    $contenido = $post->post_content;

    // Generar el nuevo título y slug
    $nuevo_titulo = sanitize_text_field( $contenido );
    $nuevo_slug   = sanitize_title( $contenido );

    // Asegurar que el slug sea único
    $nuevo_slug_unico = wp_unique_post_slug( $nuevo_slug, $post_id, $post->post_status, $post->post_type, $post->post_parent );

    // Preparar los datos para actualizar
    $post_data = array(
        'ID'         => $post_id,
        'post_title' => $nuevo_titulo,
        'post_name'  => $nuevo_slug_unico,
    );

    // Actualizar el post
    wp_update_post( $post_data );

    // Re-agregar el hook
    add_action( 'save_post', 'actualizar_titulo_slug_al_guardar', 10, 3 );
}
add_action( 'save_post', 'actualizar_titulo_slug_al_guardar', 10, 3 );