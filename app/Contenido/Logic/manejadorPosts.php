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
    $posts = $args['posts'];
    $similar_to = $args['similar_to'];

    if ($args['post_type'] === 'social_post') {
        $posts_personalizados = calcularFeedPersonalizado($current_user_id);
        $post_ids = array_keys($posts_personalizados);

        if ($paged == 1) {
            $post_ids = array_slice($post_ids, 0, $posts);
        }

        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'post__in' => $post_ids,
            'orderby' => 'post__in',
            'meta_query' => !empty($identifier) ? [['key' => 'datosAlgoritmo', 'value' => $identifier, 'compare' => 'LIKE']] : [],
        ];
    } else {
        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC', 
        ];
    }

    // Manejar publicaciones similares
    if ($similar_to) {
        // Obtener los datosAlgotimo del post similar
        $datosAlgoritmo = get_post_meta($similar_to, 'datosAlgoritmo', true);
        if ($datosAlgoritmo) {
            $data = json_decode($datosAlgoritmo, true);

            $meta_queries = [];
            $relation = 'OR'; // Puedes ajustar esto según cómo quieras combinar las condiciones

            // Ejemplo: Coincidencia de BPM
            if (!empty($data['bpm'])) {
                $meta_queries[] = [
                    'key' => 'datosAlgoritmo',
                    'value' => '"bpm":' . $data['bpm'],
                    'compare' => 'LIKE',
                ];
            }

            // Coincidencia de Key
            if (!empty($data['key'])) {
                $meta_queries[] = [
                    'key' => 'datosAlgoritmo',
                    'value' => '"key":"' . $data['key'] . '"',
                    'compare' => 'LIKE',
                ];
            }

            // Coincidencia de Scale
            if (!empty($data['scale'])) {
                $meta_queries[] = [
                    'key' => 'datosAlgoritmo',
                    'value' => '"scale":"' . $data['scale'] . '"',
                    'compare' => 'LIKE',
                ];
            }

            // Coincidencia de Tags
            if (!empty($data['tags']) && is_array($data['tags'])) {
                foreach ($data['tags'] as $tag) {
                    $meta_queries[] = [
                        'key' => 'datosAlgoritmo',
                        'value' => '"' . $tag . '"',
                        'compare' => 'LIKE',
                    ];
                }
            }

            if (!empty($meta_queries)) {
                $query_args['meta_query'] = array_merge($query_args['meta_query'], [
                    'relation' => $relation,
                    ...$meta_queries
                ]);

                // Excluir el post original
                $query_args['post__not_in'][] = $similar_to;
            }
        }
    }

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = array_merge($query_args['post__not_in'] ?? [], $args['exclude']);
    }
    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);
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

