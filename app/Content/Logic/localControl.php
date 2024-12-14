<?php

// Función para registrar el endpoint de la API
function registrar_mi_api_endpoint() {
    register_rest_route('mi-api/v1', '/ultimos-posts', array(
        'methods' => 'GET',
        'callback' => 'mi_api_get_ultimos_posts',
        'permission_callback' => '__return_true', // Permite el acceso público
    ));
}
add_action('rest_api_init', 'registrar_mi_api_endpoint');


// Función para obtener los últimos posts y gestionar la sincronización
function mi_api_get_ultimos_posts() {
    if (LOCAL) {
        // Entorno local: Obtiene los posts de 2upra.com y los guarda localmente
        $response = wp_remote_get('https://2upra.com/wp-json/mi-api/v1/ultimos-posts');

        if (is_wp_error($response)) {
            error_log('Error al conectar con el servidor remoto: ' . $response->get_error_message());
            return new WP_Error('error_conexion', 'No se pudo conectar con el servidor remoto', array('status' => 500));
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (!is_array($data)) {
            error_log('Error al decodificar la respuesta de la API remota.');
            return new WP_Error('error_decodificacion', 'Respuesta de la API remota no válida', array('status' => 500));
        }

        foreach ($data as $post_data) {
            $existing_post = get_posts(array(
                'post_type' => 'social_post',
                'meta_query' => array(
                    array(
                        'key' => 'external_id',
                        'value' => $post_data['id'],
                        'compare' => '='
                    )
                )
            ));

            if (empty($existing_post)) {
                $new_post_id = wp_insert_post(array(
                    'post_title'   => $post_data['title'],
                    'post_content' => $post_data['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'social_post',
                    'post_author'  => 44,
                ));

                if (is_wp_error($new_post_id)) {
                    error_log('Error al insertar el post: ' . $new_post_id->get_error_message());
                } else {
                    update_post_meta($new_post_id, 'external_id', $post_data['id']);
                    error_log('Post insertado con ID: ' . $new_post_id);
                }
            } else {
                error_log('El post con ID externo ' . $post_data['id'] . ' ya existe en la base de datos.');
            }
        }

        return rest_ensure_response($data);
    } else {
        // Entorno de producción: Obtiene los posts locales (previamente sincronizados) y los devuelve
        // También sincroniza con la API remota y guarda los metadatos

        $user = get_user_by('id', 44);
        if (!$user) {
            $user_id = wp_create_user('usuario44', 'contrasena', 'correo@ejemplo.com');

            if (is_wp_error($user_id)) {
                error_log('Error al crear el usuario: ' . $user_id->get_error_message());
            } else {
                global $wpdb;
                $wpdb->update($wpdb->users, array('ID' => 44), array('ID' => $user_id));
                $user = new WP_User(44);
                $user->set_role('author');
                error_log('Usuario creado y asignado con ID: 44');
            }
        }

        $author_id = 44;

        // Obtener datos de la API remota
        $response = wp_remote_get('https://2upra.com/wp-json/mi-api/v1/ultimos-posts');

        if (is_wp_error($response)) {
            error_log('Error al conectar con el servidor remoto: ' . $response->get_error_message());
            return new WP_Error('error_conexion', 'No se pudo conectar con el servidor remoto', array('status' => 500));
        }

        $body = wp_remote_retrieve_body($response);
        $remote_posts = json_decode($body, true);

        if (!is_array($remote_posts)) {
            error_log('Error al decodificar la respuesta de la API remota.');
            return new WP_Error('error_decodificacion', 'Respuesta de la API remota no válida', array('status' => 500));
        }

        // Sincronizar los posts locales con los remotos
        foreach ($remote_posts as $remote_post) {
            $existing_post = get_posts(array(
                'post_type' => 'social_post',
                'meta_query' => array(
                    array(
                        'key' => 'external_id',
                        'value' => $remote_post['id'],
                        'compare' => '='
                    )
                )
            ));

            if (empty($existing_post)) {
                // Crear el post si no existe
                $new_post_id = wp_insert_post(array(
                    'post_title'   => $remote_post['title'],
                    'post_content' => $remote_post['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'social_post',
                    'post_author'  => $author_id,
                ));

                if (is_wp_error($new_post_id)) {
                    error_log('Error al insertar el post: ' . $new_post_id->get_error_message());
                } else {
                    error_log('Post insertado con ID: ' . $new_post_id);

                    // Guardar el ID externo como metadato
                    update_post_meta($new_post_id, 'external_id', $remote_post['id']);

                    // Guardar los metadatos del post remoto
                    if (isset($remote_post['metadata']) && is_array($remote_post['metadata'])) {
                        foreach ($remote_post['metadata'] as $meta_key => $meta_value) {
                            if (is_array($meta_value)) {
                                update_post_meta($new_post_id, $meta_key, $meta_value);
                            } else {
                                update_post_meta($new_post_id, $meta_key, $meta_value);
                            }
                        }
                    }
                }
            } else {
                // Actualizar el post existente
                $existing_post_id = $existing_post[0]->ID;

                $update_result = wp_update_post(array(
                    'ID'           => $existing_post_id,
                    'post_title'   => $remote_post['title'],
                    'post_content' => $remote_post['content'],
                    'post_author'  => $author_id,
                ));

                if (is_wp_error($update_result)) {
                    error_log('Error al actualizar el post: ' . $update_result->get_error_message());
                } else {
                    error_log('Post actualizado con ID: ' . $existing_post_id);

                    // Actualizar metadatos
                    if (isset($remote_post['metadata']) && is_array($remote_post['metadata'])) {
                        foreach ($remote_post['metadata'] as $meta_key => $meta_value) {
                            if (is_array($meta_value)) {
                                update_post_meta($existing_post_id, $meta_key, $meta_value);
                            } else {
                                update_post_meta($existing_post_id, $meta_key, $meta_value);
                            }
                        }
                    }
                }
            }
        }

        // Obtener y devolver los posts locales
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
}

// Función para registrar los metadatos en la API REST
function registrar_metadatos_en_rest_api() {
    register_rest_field(
        'social_post',
        'metadata',
        array(
            'get_callback'    => function ($post) {
                return get_post_meta($post['id']);
            },
            'update_callback' => null,
            'schema'          => null,
        )
    );
}
add_action('rest_api_init', 'registrar_metadatos_en_rest_api');