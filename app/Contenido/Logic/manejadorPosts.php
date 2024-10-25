<?

function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    $user_id = obtenerUserId($is_ajax);
    $current_user_id = get_current_user_id();

    $defaults = [
        'filtro' => '',
        'tab_id' => '',
        'posts' => 12,
        'exclude' => [],
        'post_type' => 'social_post',
        'similar_to' => null, // Nuevo parámetro para posts similares
    ];
    $args = array_merge($defaults, $args);
    $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
    $output = procesarPublicaciones($query_args, $args, $is_ajax);

    if ($is_ajax) {
        echo $output;
        die();
    } else {
        return $output;
    }
}


function configuracionQueryArgs($args, $paged, $user_id, $current_user_id)
{
    $identifier = $_POST['identifier'] ?? '';
    $posts_per_page = $args['posts'];
    $similar_to = $args['similar_to'] ?? null;
    $post_not_in = $similar_to ? [$similar_to] : [];
    $cache_key = "feed_personalizado_{$current_user_id}";

    if ($args['post_type'] === 'social_post' && empty($identifier)) {
        // Obtener posts personalizados de caché o calcularlos
        $posts_personalizados = wp_cache_get($cache_key);
        
        if ($posts_personalizados === false) {
            $posts_personalizados = calcularFeedPersonalizado($current_user_id);
            wp_cache_set($cache_key, $posts_personalizados, '', HOUR_IN_SECONDS);
        }

        // Calcular offset y límite para la paginación
        $offset = ($paged - 1) * $posts_per_page;
        $post_ids = array_keys($posts_personalizados);

        // Eliminar posts excluidos
        if ($similar_to) {
            $post_ids = array_values(array_diff($post_ids, [$similar_to]));
        }

        // Asegurar que no haya duplicados
        $post_ids = array_unique($post_ids);

        // Obtener slice específico para esta página
        $paged_post_ids = array_slice($post_ids, $offset, $posts_per_page);

        if (empty($paged_post_ids)) {
            // Si no hay posts para esta página, devolver query que no retornará resultados
            return ['post__in' => [0]];
        }

        $query_args = [
            'post_type'      => $args['post_type'],
            'posts_per_page' => $posts_per_page,
            'post__in'       => $paged_post_ids,
            'orderby'        => 'post__in',
            'no_found_rows'  => true, // Optimización si no necesitas pagination links
            'meta_query'     => [],
        ];
    } else {
        // Query para posts con identifier
        $query_args = [
            'post_type'      => $args['post_type'],
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => [],
        ];

        if (!empty($identifier)) {
            $query_args['meta_query'][] = [
                'key'     => 'datosAlgoritmo',
                'value'   => $identifier,
                'compare' => 'LIKE',
            ];
        }

        if (!empty($post_not_in)) {
            $query_args['post__not_in'] = $post_not_in;
        }
    }

    // Manejar publicaciones similares
    if ($similar_to) {
        $query_args = configurarSimilarTo($query_args, $similar_to);
    }

    // Aplicar filtros adicionales
    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);

    return $query_args;
}

// Función auxiliar para verificar si la caché necesita actualización
function shouldUpdateCache($cache_key) {
    $last_update = wp_cache_get($cache_key . '_last_update');
    $current_time = time();
    
    if (!$last_update || ($current_time - $last_update) > HOUR_IN_SECONDS) {
        wp_cache_set($cache_key . '_last_update', $current_time);
        return true;
    }
    
    return false;
}

function configurarSimilarTo($query_args, $similar_to)
{
    // Obtener los datosAlgoritmo del post similar
    $datosAlgoritmo = get_post_meta($similar_to, 'datosAlgoritmo', true);
    if ($datosAlgoritmo) {
        $data = json_decode($datosAlgoritmo, true);

        $meta_queries = [];
        $relation = 'OR'; 

        // Coincidencia de Tags en ambos idiomas
        if (!empty($data['tags_posibles'])) {
            foreach (['es', 'en'] as $lang) {
                if (!empty($data['tags_posibles'][$lang]) && is_array($data['tags_posibles'][$lang])) {
                    foreach ($data['tags_posibles'][$lang] as $tag) {
                        $meta_queries[] = [
                            'key'     => 'datosAlgoritmo',
                            'value'   => '"' . $tag . '"',
                            'compare' => 'LIKE',
                        ];
                    }
                }
            }
        }


        // Función auxiliar para obtener datos de varias claves
        function get_meta_field($data, $keys, $lang) {
            foreach ($keys as $key) {
                if (isset($data[$key][$lang]) && is_array($data[$key][$lang])) {
                    return $data[$key][$lang];
                }
            }
            return []; // Devuelve un arreglo vacío si no se encuentra ninguna clave válida
        }

        if (!empty($data)) {
            // Definir las posibles claves para cada categoría
            $descripcion_keys = ['descripcion_ia_pro', 'descripcion_ia'];
            $estado_animo_keys = ['estado_animo']; // Asumo que 'estado_animo' es consistente
            $artista_posible_keys = ['artista_posible']; // Asumo que 'artista_posible' es consistente

            // Obtener los datos de descripción en ambos idiomas
            $descripcion_es = get_meta_field($data, $descripcion_keys, 'es');
            $descripcion_en = get_meta_field($data, $descripcion_keys, 'en');

            // Obtener los datos de estado_animo en ambos idiomas
            $estado_animo_es = get_meta_field($data, $estado_animo_keys, 'es');
            $estado_animo_en = get_meta_field($data, $estado_animo_keys, 'en');

            // Obtener los datos de artista_posible en ambos idiomas
            $artista_posible_es = get_meta_field($data, $artista_posible_keys, 'es');
            $artista_posible_en = get_meta_field($data, $artista_posible_keys, 'en');

            // Combinar todos los datos en una sola cadena
            $combined_data = implode(' ', array_merge(
                $descripcion_es,
                $descripcion_en,
                $estado_animo_es,
                $estado_animo_en,
                $artista_posible_es,
                $artista_posible_en
            ));

            // Asegurarse de que $combined_data no esté vacío antes de agregar a meta_queries
            if (!empty($combined_data)) {
                $meta_queries[] = [
                    'key'     => 'datosAlgoritmo',
                    'value'   => $combined_data,
                    'compare' => 'LIKE',
                ];
            }
        }

        if (!empty($meta_queries)) {
            // Ajustar la relación si hay múltiples condiciones
            if (count($meta_queries) > 1) {
                $meta_query = [
                    'relation' => $relation,
                ];
                // Agregar cada consulta individual
                foreach ($meta_queries as $mq) {
                    $meta_query[] = $mq;
                }
            } else {
                $meta_query = $meta_queries[0];
            }

            // Combinar las meta_queries existentes con las nuevas
            if (!empty($query_args['meta_query'])) {
                // Si 'meta_query' ya es una matriz con 'relation', necesitamos combinar correctamente
                if (isset($query_args['meta_query']['relation'])) {
                    // Añadir nuevas consultas manteniendo la relación existente
                    foreach ($meta_queries as $mq) {
                        $query_args['meta_query'][] = $mq;
                    }
                } else {
                    // Si 'meta_query' no tiene 'relation', añadir como condiciones OR
                    $query_args['meta_query'] = array_merge($query_args['meta_query'], $meta_queries);
                }
            } else {
                $query_args['meta_query'] = [$meta_query];
            }

            // Asegurarse de que $similar_to ya esté en post__not_in
            if (isset($query_args['post__not_in'])) {
                if (!in_array($similar_to, $query_args['post__not_in'])) {
                    $query_args['post__not_in'][] = $similar_to;
                }
            } else {
                $query_args['post__not_in'] = [$similar_to];
            }
        }
    }

    return $query_args;
}




function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();

    $query = new WP_Query($query_args);
    if ($query->have_posts()) {


        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];
        $tipoPost = $args['post_type'];
        
        if (!wp_doing_ajax()) {
            $clase_extra = 'clase-' . esc_attr($filtro);
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $clase_extra = 'clase-rolastatus';
            }

            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-posttype="' . esc_attr($tipoPost) . '" 
                  data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        // Itera sobre los resultados de la consulta
        while ($query->have_posts()) {
            $query->the_post();

            if ($tipoPost === 'social_post') {
                echo htmlPost($filtro);
            } 
            elseif ($tipoPost === 'colab') {
                echo htmlColab($filtro);
            }
            else {
                echo '<p>Tipo de publicación no reconocido.</p>';
            }
        }

        if (!wp_doing_ajax()) {
            echo '</ul>';
        }
    } else {
        echo nohayPost($filtro, $is_ajax);
    }

    wp_reset_postdata();
    return ob_get_clean();
}


function obtenerUserId($is_ajax)
{
    if ($is_ajax && isset($_POST['user_id'])) {
        return sanitize_text_field($_POST['user_id']);
    }

    $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    $indices = ['perfil', 'music', 'author', 'sello'];
    foreach ($indices as $index) {
        $pos = array_search($index, $url_segments);
        if ($pos !== false) {
            if ($index === 'sello') {
                return get_current_user_id();
            } elseif (isset($url_segments[$pos + 1])) {
                $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                if ($usuario) return $usuario->ID;
            }
            break;
        }
    }

    return null;
}


function publicacionAjax()
{
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $tipoPost = isset($_POST['posttype']) ? sanitize_text_field($_POST['posttype']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    $publicacionesCargadas = isset($_POST['cargadas']) && is_array($_POST['cargadas'])
        ? array_map('intval', $_POST['cargadas'])
        : array();
    $similar_to = isset($_POST['similar_to']) ? intval($_POST['similar_to']) : null; // Nuevo parámetro

    publicaciones(
        array(
            'filtro' => $filtro,
            'post_type' => $tipoPost,
            'tab_id' => $tab_id,
            'user_id' => $user_id,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas,
            'similar_to' => $similar_to, // Pasar el parámetro a la función
        ),
        true,
        $paged
    );
}

add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');

/*

TEST 

function publicar_en_threads($post_id) {
    // Obtener el contenido del post de WordPress
    $post = get_post($post_id);
    
    if (!$post) {
        return 'Post no encontrado.';
    }

    // Obtener el texto del post
    $texto = wp_strip_all_tags($post->post_content); // Limpiar HTML
    $titulo = get_the_title($post_id); // Título del post

    // Obtener la URL de la imagen destacada (si existe)
    $image_url = get_the_post_thumbnail_url($post_id, 'full');

    // Definir el tipo de media (texto o imagen)
    $media_type = $image_url ? 'IMAGE' : 'TEXT';

    // Access token y user ID (debes ajustarlo)
    $access_token = 'TU_ACCESS_TOKEN';
    $threads_user_id = 'TU_THREADS_USER_ID';

    // Construir la URL de la API para crear el contenedor de medios
    $url = "https://graph.threads.net/v1.0/{$threads_user_id}/threads?access_token={$access_token}";

    // Preparar los datos para la solicitud
    $data = array(
        'media_type' => $media_type,
        'text' => $titulo . "\n" . $texto,
    );

    // Si hay una imagen, agregarla a los datos
    if ($image_url) {
        $data['image_url'] = $image_url;
    }

    // Hacer la solicitud cURL para crear el contenedor de medios
    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => $data,
    ));

    if (is_wp_error($response)) {
        return 'Error en la solicitud: ' . $response->get_error_message();
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['id'])) {
        // Contenedor creado exitosamente
        $media_container_id = $body['id'];

        // Esperar 30 segundos antes de publicar el contenedor
        sleep(30);

        // Ahora publicar el contenedor
        $publish_url = "https://graph.threads.net/v1.0/{$threads_user_id}/threads_publish?access_token={$access_token}";
        $publish_data = array(
            'creation_id' => $media_container_id,
        );

        $publish_response = wp_remote_post($publish_url, array(
            'method' => 'POST',
            'body' => $publish_data,
        ));

        if (is_wp_error($publish_response)) {
            return 'Error en la publicación: ' . $publish_response->get_error_message();
        }

        $publish_body = json_decode(wp_remote_retrieve_body($publish_response), true);

        if (isset($publish_body['id'])) {
            return 'Publicación exitosa en Threads con ID: ' . $publish_body['id'];
        } else {
            return 'Error al publicar en Threads.';
        }
    } else {
        return 'Error al crear el contenedor de medios en Threads.';
    }
}
*/