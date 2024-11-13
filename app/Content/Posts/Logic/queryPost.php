<?

function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    try {
        $user_id = obtenerUserId($is_ajax);
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            error_log("[publicaciones] Advertencia: No se encontró ID de usuario");
        }

        $defaults = [
            'filtro' => '',
            'tab_id' => '',
            'posts' => 12,
            'exclude' => [],
            'post_type' => 'social_post',
            'similar_to' => null,
        ];
        $args = array_merge($defaults, $args);

        $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
        $output = procesarPublicaciones($query_args, $args, $is_ajax);

        if ($is_ajax) {
            echo $output;
            die();
        }
        return $output;
    } catch (Exception $e) {
        error_log("[publicaciones] Error crítico: " . $e->getMessage());
        return false;
    }
}

function configuracionQueryArgs($args, $paged, $user_id, $current_user_id)
{
    try {
        global $FALLBACK_USER_ID;
        if (!isset($FALLBACK_USER_ID)) {
            $FALLBACK_USER_ID = 44;
            error_log("[configuracionQueryArgs] Aviso: FALLBACK_USER_ID no definido, usando valor predeterminado: 44");
        }

        $is_authenticated = $current_user_id && $current_user_id != 0;
        $is_admin = current_user_can('administrator');

        if (!$is_authenticated) {
            error_log("[configuracionQueryArgs] Advertencia: Usuario no autenticado, utilizando FALLBACK_USER_ID");
            $current_user_id = $FALLBACK_USER_ID;
        }

        $identifier = $_POST['identifier'] ?? '';
        $posts = $args['posts'];
        $similar_to = $args['similar_to'] ?? null;
        $filtroTiempo = (int)get_user_meta($current_user_id, 'filtroTiempo', true);

        if ($filtroTiempo === false) {
            error_log("[configuracionQueryArgs] Error: No se pudo obtener filtroTiempo para el usuario ID: " . $current_user_id);
        }

        $query_args = construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to);
        $query_args = aplicarFiltrosUsuario($query_args, $current_user_id);
        $query_args = aplicarFiltroGlobal($query_args, $args, $current_user_id);

        return $query_args;
    } catch (Exception $e) {
        error_log("[configuracionQueryArgs] Error crítico: " . $e->getMessage());
        return false;
    }
}

function construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to)
{
    try {
        global $wpdb;
        if (!$wpdb) {
            error_log("[construirQueryArgs] Error crítico: No se pudo acceder a la base de datos wpdb");
            return false;
        }

        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'ignore_sticky_posts' => true,
            'suppress_filters' => false,
        ];

        if (!empty($identifier)) {
            $query_args = filtrarIdentifier($identifier, $query_args);
            if (!$query_args) {
                error_log("[construirQueryArgs] Error: Falló el filtrado por identifier: " . $identifier);
            }
        }

        if ($args['post_type'] === 'social_post') {
            $query_args = ordenamientoQuery($query_args, $filtroTiempo, $current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);
            if (!$query_args) {
                error_log("[construirQueryArgs] Error: Falló el ordenamiento de la consulta para post_type social_post");
            }
        }

        return $query_args;
    } catch (Exception $e) {
        error_log("[construirQueryArgs] Error crítico: " . $e->getMessage());
        return false;
    }
}


function ordenamientoQuery($query_args, $filtroTiempo, $current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts)
{
    try {
        global $wpdb;
        if (!$wpdb) {
            error_log("[ordenamientoQuery] Error crítico: No se pudo acceder a la base de datos wpdb");
            return false;
        }

        $likes_table = $wpdb->prefix . 'post_likes';

        // Validación de query_args
        if (!is_array($query_args)) {
            error_log("[ordenamientoQuery] Advertencia: query_args no es un array, inicializando array vacío");
            $query_args = array();
        }

        switch ($filtroTiempo) {
            case 1:
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;

            case 2: // Top semanal
            case 3: // Top mensual
                $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';

                $sql = "
                    SELECT p.ID, 
                           COUNT(pl.post_id) as like_count 
                    FROM {$wpdb->posts} p 
                    LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id 
                    WHERE p.post_type = 'social_post' 
                    AND p.post_status = 'publish'
                    AND p.post_date >= DATE_SUB(NOW(), INTERVAL $interval)  
                    AND pl.like_date >= DATE_SUB(NOW(), INTERVAL $interval) 
                    GROUP BY p.ID
                    HAVING like_count > 0
                    ORDER BY like_count DESC, p.post_date DESC
                ";

                $posts_with_likes = $wpdb->get_results($sql, ARRAY_A);

                if ($wpdb->last_error) {
                    error_log("[ordenamientoQuery] Error: Fallo en consulta de likes: " . $wpdb->last_error);
                }

                if (!empty($posts_with_likes)) {
                    $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                    if (!empty($post_ids)) {
                        $query_args['post__in'] = $post_ids;
                        $query_args['orderby'] = 'post__in';
                    } else {
                        error_log("[ordenamientoQuery] Aviso: No se encontraron IDs de posts con likes");
                    }
                } else {
                    error_log("[ordenamientoQuery] Aviso: No se encontraron posts con likes para el período " . $interval);
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;

            default: // Feed personalizado
                $feed_result = obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);

                if (!empty($feed_result['post_ids'])) {
                    $query_args['post__in'] = $feed_result['post_ids'];
                    $query_args['orderby'] = 'post__in';

                    if (count($feed_result['post_ids']) > 2500) {
                        error_log("[ordenamientoQuery] Aviso: Limitando resultados a 2500 posts");
                        $feed_result['post_ids'] = array_slice($feed_result['post_ids'], 0, 2500);
                    }

                    if (!empty($feed_result['post_not_in'])) {
                        $query_args['post__not_in'] = $feed_result['post_not_in'];
                    }
                } else {
                    error_log("[ordenamientoQuery] Aviso: Feed personalizado vacío, usando ordenamiento por fecha");
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;
        }

        // Validación final de orderby
        if (empty($query_args['orderby'])) {
            error_log("[ordenamientoQuery] Aviso: No se estableció orderby, usando valores por defecto");
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        }

        return $query_args;
    } catch (Exception $e) {
        error_log("[ordenamientoQuery] Error crítico: " . $e->getMessage());
        return false;
    }
}


function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();
    $user_id = get_current_user_id();
    $cache_key = 'posts_count_' . md5(serialize($query_args)) . '_user_' . $user_id;
    $posts_count = 0;

    // Verificar que query_args no esté vacío
    if (empty($query_args)) {
        error_log('Query args está vacío en procesarPublicaciones');
        return '';
    }

    // Asegurarse de que query_args sea un array
    if (!is_array($query_args)) {
        error_log('Query args no es un array en procesarPublicaciones');
        return '';
    }

    $total_posts = get_transient($cache_key);
    if ($total_posts === false) {
        $query_args['no_found_rows'] = true;

        // Crear la consulta con manejo de errores
        try {
            $query = new WP_Query($query_args);

            // Verificar si la consulta es válida
            if (!is_a($query, 'WP_Query')) {
                error_log('Error al crear WP_Query');
                return '';
            }

            $total_posts = $query->found_posts;
            set_transient($cache_key, $total_posts, 12 * HOUR_IN_SECONDS);
        } catch (Exception $e) {
            error_log('Error en WP_Query: ' . $e->getMessage());
            return '';
        }
    } else {
        // Si usamos el caché, aún necesitamos crear la consulta
        $query = new WP_Query($query_args);
    }

    // Verificar que $query sea válido antes de continuar
    if (!is_object($query) || !method_exists($query, 'have_posts')) {
        error_log('Query inválido en procesarPublicaciones');
        return '';
    }

    echo '<input type="hidden" class="total-posts total-posts-' . esc_attr($args['filtro']) . '" value="' . esc_attr($total_posts) . '" />';
    $filtro = !empty($args['filtro']) ? $args['filtro'] : $args['filtro'];
    if ($query->have_posts()) {

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

        while ($query->have_posts()) {
            $query->the_post();
            $posts_count++;

            if ($tipoPost === 'social_post') {
                echo htmlPost($filtro);
            } elseif ($tipoPost === 'colab') {
                echo htmlColab($filtro);
            } else {
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





function filtrarIdentifier($identifier, $query_args)
{
    global $wpdb;

    $identifier = strtolower(trim($identifier));
    $terms = explode(' ', $identifier);
    $normalized_terms = array();
    foreach ($terms as $term) {
        $term = trim($term);
        if (empty($term)) continue;
        $normalized_terms[] = $term;
        if (substr($term, -1) === 's') {
            $normalized_terms[] = substr($term, 0, -1);
        } else {
            $normalized_terms[] = $term . 's';
        }
    }
    $normalized_terms = array_unique($normalized_terms);
    $query_args['s'] = $identifier;
    add_filter('posts_search', function ($search, $wp_query) use ($normalized_terms, $wpdb) {
        if (empty($normalized_terms)) {
            return $search;
        }

        $search = '';
        $search_conditions = array();
        $term_conditions = array();
        foreach ($normalized_terms as $term) {
            $like_term = '%' . $wpdb->esc_like($term) . '%';
            $term_conditions[] = $wpdb->prepare("
                {$wpdb->posts}.post_title LIKE %s OR
                {$wpdb->posts}.post_content LIKE %s OR
                EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                    AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                    AND {$wpdb->postmeta}.meta_value LIKE %s
                )
            ", $like_term, $like_term, $like_term);
        }

        if (!empty($term_conditions)) {
            $search .= ' AND (' . implode(' OR ', $term_conditions) . ')';
        }

        return $search;
    }, 10, 2);

    return $query_args;
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
    $similar_to = isset($_POST['similar_to']) ? intval($_POST['similar_to']) : null;

    publicaciones(
        array(
            'filtro' => $filtro,
            'post_type' => $tipoPost,
            'tab_id' => $tab_id,
            'user_id' => $user_id,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas,
            'similar_to' => $similar_to,
        ),
        true,
        $paged
    );
}
add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');

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