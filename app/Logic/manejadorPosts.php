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
    $query_args = [
        'post_type' => $args['post_type'],
        'posts_per_page' => $posts,
        'paged' => $paged,
        'ignore_sticky_posts' => true,
        'suppress_filters' => false,
    ];

    postLog("Iniciando construirQueryArgs con filtroTiempo: $filtroTiempo");

    // Aplicar filtro de identificador
    if (!empty($identifier)) {
        $query_args = filtrarIdentifier($identifier, $query_args);
    }

    // Aplicar ordenamiento
    if ($args['post_type'] === 'social_post') {
        $query_args = ordenamientoQuery($query_args, $filtroTiempo, $current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);
    }

    postLog("Query args finales: " . print_r($query_args, true));
    return $query_args;
}

//porque si busco drum, arroja 1500 resultados, y si busco drums, arroja 3000, no debería importar la palabra exacta que se usa, pero veo que tiene mucha relevancia, puede hacer esto mas flexible, ya instale nlp-tools, por favor, aplicalo aca (manten el codigo funcional)
function filtrarIdentifier($identifier, $query_args)
{
    global $wpdb;
    
    // Procesamiento inicial del identificador
    $identifier = strtolower(trim($identifier));
    
    // Cargar el stemmer para español
    $template_dir = get_template_directory();
    error_log("Template directory: " . $template_dir);
    error_log("Attempting to load: " . $template_dir . '/vendor/autoload.php');
    
    if (file_exists($template_dir . '/vendor/autoload.php')) {
        error_log("Autoload file exists");
        require_once $template_dir . '/vendor/autoload.php';
    } else {
        error_log("Autoload file does not exist");
    }
    
    // Obtener términos y aplicar stemming
    $terms = explode(' ', $identifier);
    $search_terms = array();
    foreach ($terms as $term) {
        $term = trim($term);
        if (!empty($term)) {
            // Obtener la raíz de la palabra
            $stemmed_term = $stemmer->stem($term);
            $search_terms[] = $term;
            $search_terms[] = $stemmed_term;
            
            // Agregar variaciones comunes
            if (substr($term, -1) === 's') {
                $search_terms[] = substr($term, 0, -1); // singular
            } else {
                $search_terms[] = $term . 's'; // plural
            }
        }
    }
    
    // Eliminar duplicados y términos vacíos
    $search_terms = array_unique(array_filter($search_terms));
    
    $query_args['s'] = $identifier;

    add_filter('posts_search', function ($search, $wp_query) use ($search_terms, $wpdb) {
        if (empty($search_terms)) {
            return $search;
        }

        $search = '';
        
        // Construir condiciones de búsqueda con OR entre términos relacionados
        $term_groups = array();
        foreach ($search_terms as $term) {
            $like_term = '%' . $wpdb->esc_like($term) . '%';
            $term_groups[] = $wpdb->prepare("(
                {$wpdb->posts}.post_title LIKE %s OR
                {$wpdb->posts}.post_content LIKE %s OR
                EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta}
                    WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                    AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                    AND {$wpdb->postmeta}.meta_value LIKE %s
                )
            )", $like_term, $like_term, $like_term);
        }

        if (!empty($term_groups)) {
            // Usar OR entre términos relacionados para mayor flexibilidad
            $search .= ' AND (' . implode(' OR ', $term_groups) . ')';
        }

        return $search;
    }, 10, 2);

    // Agregar relevancia personalizada
    add_filter('posts_orderby', function ($orderby, $wp_query) use ($search_terms, $wpdb) {
        if (empty($search_terms)) {
            return $orderby;
        }

        $relevance_parts = array();
        foreach ($search_terms as $term) {
            $like_term = '%' . $wpdb->esc_like($term) . '%';
            $relevance_parts[] = $wpdb->prepare("
                (CASE 
                    WHEN {$wpdb->posts}.post_title LIKE %s THEN 10
                    WHEN {$wpdb->posts}.post_content LIKE %s THEN 5
                    WHEN EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta}
                        WHERE {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
                        AND {$wpdb->postmeta}.meta_key = 'datosAlgoritmo'
                        AND {$wpdb->postmeta}.meta_value LIKE %s
                    ) THEN 3
                    ELSE 0
                END)", $like_term, $like_term, $like_term);
        }

        $relevance = "(" . implode(" + ", $relevance_parts) . ")";
        return "$relevance DESC, " . $orderby;
    }, 10, 2);

    return $query_args;
}

function ordenamientoQuery($query_args, $filtroTiempo, $current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts)
{
    global $wpdb;
    $likes_table = $wpdb->prefix . 'post_likes';

    switch ($filtroTiempo) {
        case 1: // Posts recientes
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
            postLog("Caso 1: Ordenando por fecha reciente");
            break;

        case 2: // Top semanal
        case 3: // Top mensual
            $interval = ($filtroTiempo === 2) ? '1 WEEK' : '1 MONTH';
            postLog("Caso $filtroTiempo: Usando intervalo de $interval");

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

            postLog("SQL Query: " . $sql);
            $posts_with_likes = $wpdb->get_results($sql, ARRAY_A);
            postLog("Resultados encontrados: " . count($posts_with_likes));

            if (!empty($posts_with_likes)) {
                $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                if (!empty($post_ids)) {
                    $query_args['post__in'] = $post_ids;
                    $query_args['orderby'] = 'post__in';
                }
                postLog("IDs de posts para esta página: " . implode(', ', $post_ids));
            } else {
                postLog("No se encontraron posts con likes en el período especificado");
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
            }
            break;

        default:
            postLog("Caso default: Obteniendo feed personalizado");
            $personalized_feed = obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);

            if (!empty($personalized_feed['post_ids'])) {
                $query_args['post__in'] = $personalized_feed['post_ids'];
                $query_args['orderby'] = 'date';
                $query_args['order'] = 'DESC';
                postLog("Feed personalizado IDs: " . implode(', ', $personalized_feed['post_ids']));
            }
            break;
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