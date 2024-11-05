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

function configuracionQueryArgs($args, $paged, $user_id, $current_user_id) {
    postLog("---------------------------------------");
    postLog("INICIO");
    postLog("Iniciando configuración de query args: paged=$paged, user_id=$user_id, current_user_id=$current_user_id");
    $filtro = $_POST['filtro'] ?? '';
    $filtroArg = $args['filtro'];
    postLog("FILTRO: $filtro");
    postLog("FILTROARG: $filtroArg");
    postLog("---------------------------------------");
    $identifier = $_POST['identifier'] ?? '';
    $posts = $args['posts'];
    $similar_to = $args['similar_to'] ?? null;
    $post_not_in = [];
    
    if ($similar_to) {
        $post_not_in[] = $similar_to;
    }

    if ($args['post_type'] === 'social_post') {
        $transient_key = 'feed_personalizado_one' . $current_user_id;
        
        // Verificar si el usuario es administrador
        $is_admin = current_user_can('administrator');
        
        // Si es admin, no usar caché
        $post_ids = $is_admin ? false : get_transient($transient_key);
        
        if ($paged === 1 || $post_ids === false) {
            $posts_personalizados = calcularFeedPersonalizado($current_user_id, $identifier, $similar_to);
            $post_ids = array_keys($posts_personalizados);
            if ($similar_to) {
                $post_ids = array_filter($post_ids, function($post_id) use ($similar_to) {
                    return $post_id != $similar_to;
                });
            }
            // Asegurar que todos los IDs sean únicos
            $post_ids = array_unique($post_ids);
            
            // Guardar en caché solo si no es administrador
            if (!$is_admin) {
                set_transient($transient_key, $post_ids, 600);
                postLog("Feed personalizado calculado y guardado en caché (usuario normal)");
            } else {
                postLog("Feed personalizado calculado sin caché (administrador)");
            }
        } else {
            postLog("Usando feed personalizado en caché para página $paged");
        }

        $posts_per_page = $posts;
        $offset = ($paged - 1) * $posts_per_page;
        $current_page_ids = array_slice($post_ids, $offset, $posts_per_page);
        $current_page_ids = array_unique($current_page_ids);
        
        if ($paged > 1) {
            $previous_page_ids = array_slice($post_ids, 0, ($paged - 1) * $posts_per_page);
            $post_not_in = array_merge($post_not_in, $previous_page_ids);
            $post_not_in = array_unique($post_not_in);
            postLog("Excluyendo IDs de páginas anteriores: " . implode(', ', $post_not_in));
        }
        
        $query_args = [
            'post_type'           => $args['post_type'],
            'posts_per_page'      => $posts_per_page,
            'post__in'            => $current_page_ids,
            'orderby'             => 'post__in',
            'meta_query'          => [],
            'ignore_sticky_posts' => true,
        ];

        if (!empty($post_not_in)) {
            $query_args['post__not_in'] = $post_not_in;
        }
    } else {
        $query_args = [
            'post_type'           => $args['post_type'],
            'posts_per_page'      => $posts,
            'paged'               => $paged,
            'orderby'             => 'date',
            'order'               => 'DESC',
            'meta_query'          => [],
            'ignore_sticky_posts' => true,
        ];

        if (!empty($post_not_in)) {
            $query_args['post__not_in'] = array_unique($post_not_in);
        }
    }

    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);
    postLog("FILTRO DESPUES DE query_args : $filtro");
    postLog("FILTROARG query_args: $filtroArg");
    postLog("---------------------------------------");
    postLog("query_args final: " . json_encode($query_args));
    return $query_args;
}
/*
LOS FILTROS FUNCIONAN BIEN

Pero, hay una confución, hay que enviar el filtro a html
2024-11-05 17:45:25 - FILTRO ENVIADO A htmlPost : sampleList
2024-11-05 17:45:25 - ---------------------------------------
2024-11-05 17:47:03 - ---------------------------------------
2024-11-05 17:47:03 - INICIO
2024-11-05 17:47:03 - Iniciando configuración de query args: paged=1, user_id=, current_user_id=1
2024-11-05 17:47:03 - FILTRO: sampleList
2024-11-05 17:47:03 - FILTROARG: sampleList
2024-11-05 17:47:03 - ---------------------------------------
2024-11-05 17:47:05 - ---------------------------------------
2024-11-05 17:47:05 - INICIO FILTRO EN APLICARFILTROS: Soul
2024-11-05 17:47:05 - FINAL FILTRO EN APLICARFILTROS: Soul
2024-11-05 17:47:05 - ---------------------------------------
2024-11-05 17:47:05 - FILTRO DESPUES DE query_args : sampleList
2024-11-05 17:47:05 - FILTROARG query_args: sampleList
2024-11-05 17:47:05 - ---------------------------------------
2024-11-05 17:47:05 - query_args final: {"post_type":"social_post","posts_per_page":12,"post__in":[287401,285436,286401,283808,265942,284542,269920,283814,266392,282366,263175,282630],"orderby":"post__in","meta_query":[],"ignore_sticky_posts":true,"author":""}
2024-11-05 17:47:05 - FILTRO ENVIADO A htmlPost : Soul
2024-11-05 17:47:05 - ---------------------------------------
*/





function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();

    $query = new WP_Query($query_args);
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