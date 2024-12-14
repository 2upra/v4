<?php

add_action('rest_api_init', function () {
    register_rest_route('mi-api/v1', '/ultimos-posts', array(
        'methods' => 'GET',
        'callback' => 'mi_api_get_ultimos_posts',
    ));
});

function mi_api_get_ultimos_posts() {
    // Detectar si estamos en entorno local
    define('LOCAL', strpos(home_url(), 'localhost') !== false || strpos(home_url(), '.local') !== false);

    // Si estamos en local, hacer una petición a la API remota
    if (LOCAL) {
        $response = wp_remote_get('https://2upra.com/wp-json/mi-api/v1/ultimos-posts');

        // Comprobar si la respuesta es válida
        if (is_wp_error($response)) {
            return new WP_Error('error_conexion', 'No se pudo conectar con el servidor remoto', array('status' => 500));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Devolver los datos obtenidos de la API remota
        return rest_ensure_response($data);
    }

    // Si no estamos en local, obtener los datos de la base de datos local
    $user = get_user_by('id', 44);
    if (!$user) {
        $user_id = wp_create_user('usuario44', 'contrasena', 'correo@ejemplo.com'); 

        if (is_wp_error($user_id)) {
            error_log('Error al crear el usuario: ' . $user_id->get_error_message());
        } else {
            global $wpdb;
            $wpdb->update($wpdb->users, array('ID' => 44), array('ID' => $user_id));

            // Asignar un rol al usuario (ejemplo: 'author').
            $user = new WP_User(44);
            $user->set_role('author');
        }
    }

    // Obtener los posts del autor con ID 44
    $author_id = 44; // ID del autor

    $args = array(
        'post_type'      => 'social_post',
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'posts_per_page' => 20,
        'author'         => $author_id,
    );

    $query = new WP_Query($args);
    $posts = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();
            $post_data = array(
                'id'         => $post_id,
                'title'      => get_the_title(),
                'content'    => get_the_content(),
                'permalink'  => get_permalink(),
                'author'     => get_the_author(),
                'date'       => get_the_date('Y-m-d H:i:s'),
                'metadata'   => get_post_meta($post_id),
            );

            $posts[] = $post_data;
        }
        wp_reset_postdata();
    }

    return rest_ensure_response($posts);
}