<?
//Solo cargan la primera pagina, en total de post sale 12, no se donde esta el problema, el problema supongo que sucede en alguna parte de construirQueryArgs
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
        'similar_to' => null,
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
    global $FALLBACK_USER_ID;
    if (!isset($FALLBACK_USER_ID)) {
        $FALLBACK_USER_ID = 44;
    }

    $is_authenticated = $current_user_id && $current_user_id != 0;
    $is_admin = current_user_can('administrator');
    if (!$is_authenticated) {
        $current_user_id = $FALLBACK_USER_ID;
    }

    $identifier = $_POST['identifier'] ?? '';
    $posts = $args['posts'];
    $similar_to = $args['similar_to'] ?? null;
    $filtroTiempo = (int)get_user_meta($current_user_id, 'filtroTiempo', true);
    $query_args = construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to);
    $query_args = aplicarFiltrosUsuario($query_args, $current_user_id);
    $query_args = aplicarFiltroGlobal($query_args, $args, $current_user_id);
    return $query_args;
}

function construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to)
{
    global $wpdb;
    $likes_table = $wpdb->prefix . 'post_likes';

    // Configuración base con valores mínimos necesarios
    $query_args = [
        'post_type' => $args['post_type'],
        'posts_per_page' => $posts,
        'paged' => $paged,
        'ignore_sticky_posts' => true,
        'no_found_rows' => true, // Mejora el rendimiento cuando no necesitas pagination info
        'cache_results' => true,
        'update_post_meta_cache' => true, // Solo activar si necesitas meta datos
        'update_post_term_cache' => false, // Solo activar si necesitas términos
    ];

    // Optimización de búsqueda
    if (!empty($identifier)) {
        // Usar una única consulta JOIN en lugar de múltiples subconsultas
        add_filter('posts_where', function ($where) use ($identifier, $wpdb) {
            $terms = array_filter(explode(' ', $identifier));
            if (empty($terms)) return $where;

            $conditions = [];
            foreach ($terms as $term) {
                $like_term = '%' . $wpdb->esc_like($term) . '%';
                $conditions[] = $wpdb->prepare(
                    "({$wpdb->posts}.post_title LIKE %s OR 
                      {$wpdb->posts}.post_content LIKE %s OR 
                      pm.meta_value LIKE %s)",
                    $like_term,
                    $like_term,
                    $like_term
                );
            }

            return $where . " AND (" . implode(" OR ", $conditions) . ")";
        });

        add_filter('posts_join', function ($join) use ($wpdb) {
            return $join . " LEFT JOIN {$wpdb->postmeta} pm ON ({$wpdb->posts}.ID = pm.post_id AND pm.meta_key = 'datosAlgoritmo')";
        });

        add_filter('posts_distinct', function ($distinct) {
            return "DISTINCT";
        });
    }

    // Optimización de ordenamiento para social_post
    if ($args['post_type'] === 'social_post') {
        switch ($filtroTiempo) {
            case 1: // Posts recientes
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                break;

            case 2: // Top semanal
            case 3: // Top mensual
                $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';

                // Consulta optimizada con índices
                $sql = $wpdb->prepare("
                    SELECT p.ID
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$likes_table} pl ON p.ID = pl.post_id
                    WHERE p.post_type = %s 
                    AND p.post_status = 'publish'
                    AND p.post_date >= DATE_SUB(NOW(), INTERVAL {$interval})
                    AND (pl.like_date IS NULL OR pl.like_date >= DATE_SUB(NOW(), INTERVAL {$interval}))
                    GROUP BY p.ID
                    ORDER BY COUNT(pl.post_id) DESC, p.post_date DESC
                    LIMIT %d
                ", 'social_post', $posts * $paged);

                $post_ids = $wpdb->get_col($sql);

                if (!empty($post_ids)) {
                    $query_args['post__in'] = $post_ids;
                    $query_args['orderby'] = 'post__in';
                }
                break;

            default:
                // Feed personalizado
                $personalized_feed = obtenerFeedPersonalizado(
                    $current_user_id,
                    $identifier,
                    $similar_to,
                    $paged,
                    $is_admin,
                    $posts
                );

                if (!empty($personalized_feed['post_ids'])) {
                    $query_args['post__in'] = $personalized_feed['post_ids'];
                    $query_args['orderby'] = 'post__in';
                }
                break;
        }
    }

    return $query_args;
}


function obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts_per_page)
{
    $post_not_in = [];

    if ($similar_to) {
        $post_not_in[] = $similar_to;
        $cache_suffix = "_similar_" . $similar_to;
    } else {
        $cache_suffix = "";
    }

    $transient_key = $current_user_id == 44
        ? "feed_personalizado_anonymous_{$identifier}{$cache_suffix}"
        : "feed_personalizado_user_{$current_user_id}_{$identifier}{$cache_suffix}";
    $use_cache = !$is_admin;
    $cached_data = $use_cache ? get_transient($transient_key) : false;
    if ($cached_data) {
        $posts_personalizados = $cached_data['posts'];
    } else {
        if ($paged === 1) {
            $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
        } else {
            $posts_personalizados = get_option($transient_key . '_backup', []);
            if (empty($posts_personalizados)) {
                $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
            }
        }
        if ($use_cache) {
            $cache_data = ['posts' => $posts_personalizados, 'timestamp' => time()];
            $cache_time = $similar_to ? 3600 : 86400;
            set_transient($transient_key, $cache_data, $cache_time);
            update_option($transient_key . '_backup', $posts_personalizados);
        }
    }
    $post_ids = array_keys($posts_personalizados);
    if ($similar_to) {
        $post_ids = array_filter($post_ids, function ($post_id) use ($similar_to) {
            return $post_id != $similar_to;
        });
    }
    $post_ids = array_keys($posts_personalizados);
    if ($similar_to) {
        $post_ids = array_filter($post_ids, function ($post_id) use ($similar_to) {
            return $post_id != $similar_to;
        });
    }
    $post_ids = array_unique($post_ids);
    // No hacemos el slicing aquí
    // $offset = ($paged - 1) * $posts_per_page;
    // $current_page_ids = array_slice($post_ids, $offset, $posts_per_page);

    // Retornamos todos los IDs de posts
    return [
        'post_ids' => $post_ids,
        'post_not_in' => [],  // Ya no necesitamos post_not_in
    ];
}


function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();

    postLog("Query args: " . print_r($query_args, true));

    // Realiza una consulta sin paginación para obtener el total de publicaciones
    $total_query_args = $query_args;
    unset($total_query_args['posts_per_page']); // Para obtener el conteo total sin limitar el número de posts
    unset($total_query_args['paged']); // Quitamos la paginación para contar todo

    $total_query = new WP_Query($total_query_args);
    $total_posts = $total_query->found_posts;
    wp_reset_postdata();

    // Ahora realizamos la consulta paginada para las publicaciones que queremos mostrar
    $query = new WP_Query($query_args);
    $posts_count = 0;

    echo '<input type="hidden" class="total-posts total-posts-' . esc_attr($args['filtro']) . '" value="' . esc_attr($total_posts) . '" />';

    if ($query->have_posts()) {
        $filtro = !empty($args['filtro']) ? $args['filtro'] : $args['filtro'];
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

        postLog("FILTRO ENVIADO A htmlPost : $filtro");
        postLog("---------------------------------------");

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