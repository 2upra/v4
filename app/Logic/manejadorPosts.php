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

function obtenerDatosFeed($userId)
{
    global $wpdb;
    $table_likes = "{$wpdb->prefix}post_likes";
    $table_intereses = INTERES_TABLE;
    $siguiendo = (array) get_user_meta($userId, 'siguiendo', true);
    $interesesUsuario = $wpdb->get_results($wpdb->prepare(
        "SELECT interest, intensity FROM $table_intereses WHERE user_id = %d",
        $userId
    ), OBJECT_K);
    $vistas_posts = get_user_meta($userId, 'vistas_posts', true);

    $args = [
        'post_type'      => 'social_post',
        'posts_per_page' => 5000,
        'date_query'     => [
            'after' => date('Y-m-d', strtotime('-100 days'))
        ],
        'fields'         => 'ids',
        'no_found_rows'  => true,
    ];
    $posts_ids = get_posts($args);

    if (empty($posts_ids)) {
        return [];
    }
    $placeholders = implode(', ', array_fill(0, count($posts_ids), '%d'));
    $meta_keys = ['datosAlgoritmo', 'Verificado', 'postAut'];
    $meta_keys_placeholders = implode(',', array_fill(0, count($meta_keys), '%s'));
    $sql_meta = "
        SELECT post_id, meta_key, meta_value
        FROM {$wpdb->postmeta}
        WHERE meta_key IN ($meta_keys_placeholders) AND post_id IN ($placeholders)
    ";

    $prepared_sql_meta = $wpdb->prepare($sql_meta, array_merge($meta_keys, $posts_ids));
    $meta_results = $wpdb->get_results($prepared_sql_meta);
    $meta_data = [];
    foreach ($meta_results as $meta_row) {
        $meta_data[$meta_row->post_id][$meta_row->meta_key] = $meta_row->meta_value;
    }
    $sql_likes = "
        SELECT post_id, COUNT(*) as likes_count
        FROM $table_likes
        WHERE post_id IN ($placeholders)
        GROUP BY post_id
    ";

    $likes_results = $wpdb->get_results($wpdb->prepare($sql_likes, $posts_ids));
    $likes_by_post = [];
    foreach ($likes_results as $like_row) {
        $likes_by_post[$like_row->post_id] = $like_row->likes_count;
    }
    $sql_posts = "
        SELECT ID, post_author, post_date, post_content
        FROM {$wpdb->posts}
        WHERE ID IN ($placeholders)
    ";
    $posts_results = $wpdb->get_results($wpdb->prepare($sql_posts, $posts_ids), OBJECT_K);
    $post_content = [];
    foreach ($posts_results as $post) {
        $post_content[$post->ID] = $post->post_content;
    }

    return [
        'siguiendo'        => $siguiendo,
        'interesesUsuario' => $interesesUsuario,
        'posts_ids'        => $posts_ids,
        'likes_by_post'    => $likes_by_post,
        'meta_data'        => $meta_data,
        'author_results'   => $posts_results,
        'post_content'     => $post_content,
    ];
}

function calcularFeedPersonalizado($userId, $identifier = '', $similar_to = null)
{
    $datos = obtenerDatosFeedConCache($userId); #Aqui se obtiene obtenerDatosFeed
    if (empty($datos)) {
        return [];
    }
    $usuario = get_userdata($userId);
    if (!$usuario || !is_object($usuario)) {
        return [];
    }
    $posts_personalizados = [];
    $current_timestamp = current_time('timestamp');
    $vistas_posts_processed = obtenerYProcesarVistasPosts($userId);
    $esAdmin = in_array('administrator', (array)$usuario->roles);
    $decay_factors = [];
    foreach ($datos['author_results'] as $post_data) {
        $post_date = $post_data->post_date;
        $post_timestamp = is_string($post_date) ? strtotime($post_date) : $post_date; 
        $diasDesdePublicacion = floor(($current_timestamp - $post_timestamp) / (3600 * 24));
        if (!isset($decay_factors[$diasDesdePublicacion])) {
            $decay_factors[$diasDesdePublicacion] = getDecayFactor($diasDesdePublicacion);
        }
    }
    $posts_data = $datos['author_results'];
    $puntos_por_post = calcularPuntosPostBatch(
        $posts_data,
        $datos,
        $esAdmin,
        $vistas_posts_processed,
        $identifier,
        $similar_to,
        $current_timestamp,
        $userId,
        $decay_factors 
    );

    if (!empty($puntos_por_post)) {
        arsort($puntos_por_post);
    }
    return $puntos_por_post;
}

function ordenamientoQuery($query_args, $filtroTiempo, $current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts)
{
    global $wpdb;
    $likes_table = $wpdb->prefix . 'post_likes';

    // Asegúrate de que query_args sea un array
    if (!is_array($query_args)) {
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

            if (!empty($posts_with_likes)) {
                $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                if (!empty($post_ids)) {
                    $query_args['post__in'] = $post_ids;
                    $query_args['orderby'] = 'post__in';
                }
            } else {
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
            }
            break;

        default: // Feed personalizado
            $feed_result = obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);
            
            if (!empty($feed_result['post_ids'])) {
                $query_args['post__in'] = $feed_result['post_ids'];
                $query_args['orderby'] = 'post__in';
                
                if (!empty($feed_result['post_not_in'])) {
                    $query_args['post__not_in'] = $feed_result['post_not_in'];
                }
            } else {
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
            }
            break;
    }

    // Asegúrate de que siempre haya un orderby por defecto
    if (empty($query_args['orderby'])) {
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
    }

    return $query_args;
}

function procesarPublicaciones($query_args, $args, $is_ajax) {
    ob_start();
    $user_id = get_current_user_id();
    $cache_key = 'posts_count_' . md5(serialize($query_args)) . '_user_' . $user_id;
    $posts_count = 0;
    
    // Validaciones iniciales
    if (empty($query_args) || !is_array($query_args)) {
        error_log('Query args está vacío o no es un array en procesarPublicaciones');
        return '';
    }

    // Separar lógica de consulta para primeros 300 posts y el resto
    $query_args['no_found_rows'] = false;

    // Consulta sin caché para los primeros 300 posts
    $query_args['posts_per_page'] = 300;
    $query_recientes = new WP_Query($query_args);
    if (!is_a($query_recientes, 'WP_Query')) {
        error_log('Error al crear WP_Query para primeros 300 posts');
        return '';
    }

    // Consulta con caché para el resto de los posts
    $total_posts = get_transient($cache_key);
    if ($total_posts === false) {
        $query_args['offset'] = 300;
        $query_args['posts_per_page'] = -1; // Recuperar todos los posts restantes
        
        try {
            $query_resto = new WP_Query($query_args);
            if (!is_a($query_resto, 'WP_Query')) {
                error_log('Error al crear WP_Query para posts adicionales');
                return '';
            }
            $total_posts = $query_resto->found_posts + $query_recientes->found_posts;
            set_transient($cache_key, $total_posts, 12 * HOUR_IN_SECONDS);
        } catch (Exception $e) {
            error_log('Error en WP_Query: ' . $e->getMessage());
            return '';
        }
    }

    echo '<input type="hidden" class="total-posts total-posts-' . esc_attr($args['filtro']) . '" value="' . esc_attr($total_posts) . '" />';

    // Renderizar los primeros 300 posts
    if ($query_recientes->have_posts()) {
        renderizarPosts($query_recientes, $args, $is_ajax, $posts_count);
    }

    // Renderizar el resto de posts si la caché lo permite
    if ($query_resto && $query_resto->have_posts()) {
        renderizarPosts($query_resto, $args, $is_ajax, $posts_count);
    }

    wp_reset_postdata();
    return ob_get_clean();
}

function renderizarPosts($query, $args, $is_ajax, &$posts_count) {
    $filtro = !empty($args['filtro']) ? $args['filtro'] : $args['filtro'];
    $tipoPost = $args['post_type'];

    if (!wp_doing_ajax()) {
        $clase_extra = in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes']) ? 'clase-rolastatus' : 'clase-' . esc_attr($filtro);
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
}


function construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to)
{
    global $wpdb;
    $query_args = [
        'post_type' => $args['post_type'],
        'posts_per_page' => $posts,
        'paged' => $paged,
        'ignore_sticky_posts' => true,
        'suppress_filters' => false,
    ];
    if (!empty($identifier)) {
        $query_args = filtrarIdentifier($identifier, $query_args);
    }
    if ($args['post_type'] === 'social_post') {
        $query_args = ordenamientoQuery($query_args, $filtroTiempo, $current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);
    }
    return $query_args;
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
    
    //postLog("Total de posts personalizados encontrados: " . count($post_ids));
    
    return [
        'post_ids' => $post_ids,
        'post_not_in' => $post_not_in,
    ];
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