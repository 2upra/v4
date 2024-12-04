<?

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
    $colec = isset($_POST['colec']) ? intval($_POST['colec']) : null;
    $idea = isset($_POST['idea']) ? filter_var($_POST['idea'], FILTER_VALIDATE_BOOLEAN) : false;

    if (empty($data_identifier)) {
        error_log("[publicacionAjax] Warning: El valor de 'identifier' está vacío o no se recibió.");
    } else {
        error_log("[publicacionAjax] Valor de 'identifier' recibido: " . $data_identifier);
    }

    publicaciones(
        array(
            'filtro' => $filtro,
            'post_type' => $tipoPost,
            'tab_id' => $tab_id,
            'user_id' => $user_id,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas,
            'similar_to' => $similar_to,
            'colec' => $colec,
            'idea' => $idea
        ),
        true,
        $paged
    );
}
add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');

function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    try {
        $user_id = obtenerUserId($is_ajax);
        $current_user_id = get_current_user_id();

        if (!$current_user_id) {
            //error_log("[publicaciones] Advertencia: No se encontró ID de usuario");
        }

        $defaults = [
            'filtro' => '',
            'tab_id' => '',
            'posts' => 12,
            'exclude' => [],
            'post_type' => 'social_post',
            'similar_to' => null,
            'colec' => null,
            'idea' => null,
            'perfil' => null,
        ];

        $args = array_merge($defaults, $args);

        if (filter_var($args['idea'], FILTER_VALIDATE_BOOLEAN)) {
            guardarLog("cargando mas ideas");
            $query_args = procesarIdeas($args, $paged);
            if (!$query_args) {
                error_log("[publicaciones] Error al procesar ideas.");
                return false;
            }
        } else if (!empty($args['colec']) && is_numeric($args['colec'])) {
            guardarLog("cargando post de coleccion");
            $samples_meta = get_post_meta($args['colec'], 'samples', true);
            if (!is_array($samples_meta)) {
                $samples_meta = maybe_unserialize($samples_meta);
            }
            if (is_array($samples_meta)) {
                $query_args = [
                    'post_type' => $args['post_type'],
                    'post__in' => array_values($samples_meta),
                    'orderby' => 'post__in',
                    'posts_per_page' => 12,
                    'paged'          => $paged,
                ];
            } else {
                error_log("[publicaciones] El meta 'samples' no es un array válido.");
                return false;
            }
        } else {
            $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
        }

        $output = procesarPublicaciones($query_args, $args, $is_ajax);

        if ($is_ajax) {
            echo $output;
            wp_die();
        }
        return $output;
    } catch (Exception $e) {
        error_log("[publicaciones] Error crítico: " . $e->getMessage());
        return false;
    }
}
//procesar idea no gestiona bien la siguiente pagina, al cargar la segunda pagina no cargan el resto de post 
function procesarIdeas($args, $paged)
{
    try {
        //guardarLog("[procesarIdeas] Iniciando procesamiento con args: " . print_r($args, true));

        // Validar que 'colec' es un número válido
        if (empty($args['colec']) || !is_numeric($args['colec'])) {
            error_log("[procesarIdeas] 'colec' no es válido. Valor recibido: " . print_r($args['colec'], true));
            //guardarLog("[procesarIdeas] 'colec' no es válido. Valor recibido: " . print_r($args['colec'], true));
            return false;
        }

        //guardarLog("[procesarIdeas] 'colec' es válido: " . $args['colec']);

        // Obtener meta 'samples' del post
        $samples_meta = get_post_meta($args['colec'], 'samples', true);
        //guardarLog("[procesarIdeas] Obtención de meta 'samples' para colec {$args['colec']}: " . print_r($samples_meta, true));

        if (!is_array($samples_meta)) {
            $samples_meta = maybe_unserialize($samples_meta);
            //guardarLog("[procesarIdeas] Intentando deserializar 'samples': " . print_r($samples_meta, true));
        }

        if (is_array($samples_meta)) {
            //guardarLog("[procesarIdeas] 'samples_meta' es un array con " . count($samples_meta) . " elementos.");
            $all_similar_posts = [];
            foreach ($samples_meta as $post_id) {
                //guardarLog("[procesarIdeas] Procesando post_id: $post_id");

                // Usar cache para obtener posts similares
                $similar_to_cache_key = "similar_to_$post_id";
                $cached_similars = obtenerCache($similar_to_cache_key);

                // Log adicional para inspeccionar el contenido del cache
                //guardarLog("[procesarIdeas] Cache obtenido para post_id $post_id: " . print_r($cached_similars, true));

                if ($cached_similars) {
                    // Ensure the cached similars are sorted if needed
                    arsort($cached_similars);

                    // Extract post IDs from the cached similars
                    $posts_similares = array_keys($cached_similars);

                    //guardarLog("[procesarIdeas] Usando cache para post_id $post_id. Posts similares obtenidos: " . implode(', ', $posts_similares));
                } else {
                    //guardarLog("[procesarIdeas] Cache no encontrado para post_id $post_id. Calculando posts similares.");
                    $posts_similares = calcularFeedPersonalizado(44, '', $post_id);

                    if ($posts_similares) {
                        // If calcularFeedPersonalizado returns an associative array, extract the keys
                        if (is_array($posts_similares)) {
                            $posts_similares_ids = array_keys($posts_similares);
                            guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                            //guardarLog("[procesarIdeas] Cache guardado para post_id $post_id con posts: " . implode(', ', $posts_similares_ids));
                            $posts_similares = $posts_similares_ids; // Update for consistency
                        } else {
                            // Handle case where it returns an indexed array of post IDs
                            guardarCache($similar_to_cache_key, $posts_similares, 15 * DAY_IN_SECONDS);
                            //guardarLog("[procesarIdeas] Cache guardado para post_id $post_id con posts: " . implode(', ', $posts_similares));
                        }
                    } else {
                        error_log("[procesarIdeas] No se pudieron calcular posts similares para post_id $post_id.");
                        //guardarLog("[procesarIdeas] No se pudieron calcular posts similares para post_id $post_id.");
                        continue;
                    }
                }

                // Excluir repetidos y los mismos samples
                $original_count = count($posts_similares);
                $posts_similares = array_diff($posts_similares, [$post_id], $samples_meta, $all_similar_posts);
                $filtered_count = count($posts_similares);
                //guardarLog("[procesarIdeas] Filtrados posts similares para post_id $post_id. Antes: $original_count, Después: $filtered_count");

                // Asegurar que tengamos al menos 5 posts similares
                $posts_similares = array_slice($posts_similares, 0, 5);
                //guardarLog("[procesarIdeas] Limitados a 5 posts similares para post_id $post_id: " . implode(', ', $posts_similares));

                // Añadir a la lista total de posts
                $all_similar_posts = array_merge($all_similar_posts, $posts_similares);
                //guardarLog("[procesarIdeas] Total de posts similares acumulados: " . count($all_similar_posts));
            }

            // Eliminar duplicados y limitar a 620 posts
            $prev_unique_count = count($all_similar_posts);
            $all_similar_posts = array_unique($all_similar_posts);
            $unique_count = count($all_similar_posts);
            //guardarLog("[procesarIdeas] Eliminados duplicados. Antes: $prev_unique_count, Después: $unique_count");

            if ($unique_count > 620) {
                $all_similar_posts = array_slice($all_similar_posts, 0, 620);
                //guardarLog("[procesarIdeas] Limitados a 620 posts: " . implode(', ', $all_similar_posts));
            }

            // Aplicar aleatoriedad del 20%
            if (count($all_similar_posts) > 1) {
                $total_posts = count($all_similar_posts);
                $randomize_count = ceil($total_posts * 0.2);
                //guardarLog("[procesarIdeas] Aplicando aleatoriedad. Total posts: $total_posts, Cantidad a randomizar: $randomize_count");

                if ($randomize_count > 1) {
                    $random_indices = array_rand($all_similar_posts, $randomize_count);
                    if (!is_array($random_indices)) {
                        $random_indices = [$random_indices];
                    }
                    $random_posts = [];
                    foreach ($random_indices as $index) {
                        $random_posts[] = $all_similar_posts[$index];
                    }
                    shuffle($random_posts);
                    //guardarLog("[procesarIdeas] Posts seleccionados para aleatorizar: " . implode(', ', $random_posts));

                    $i = 0;
                    foreach ($random_indices as $index) {
                        $all_similar_posts[$index] = $random_posts[$i];
                        $i++;
                    }
                    //guardarLog("[procesarIdeas] Posts después de aleatorizar: " . implode(', ', $all_similar_posts));
                }
            }

            //guardarLog("[procesarIdeas] Total final de posts similares: " . count($all_similar_posts));

            // Configurar argumentos de la consulta
            $query_args = [
                'post_type'      => $args['post_type'],
                'post__in'       => $all_similar_posts,
                'orderby'        => 'post__in',
                'posts_per_page' => 12,
                'paged'          => $paged,
            ];

            //guardarLog("[procesarIdeas] Query args configurados: " . print_r($query_args, true));

            return $query_args;
        } else {
            error_log("[procesarIdeas] El meta 'samples' no es un array válido. Valor recibido: " . print_r($samples_meta, true));
            //guardarLog("[procesarIdeas] El meta 'samples' no es un array válido. Valor recibido: " . print_r($samples_meta, true));
            return false;
        }
    } catch (Exception $e) {
        error_log("[procesarIdeas] Error crítico: " . $e->getMessage());
        //guardarLog("[procesarIdeas] Error crítico: " . $e->getMessage());
        return false;
    }
}

function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();
    $user_id = get_current_user_id();

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

    // Crear la consulta con manejo de errores
    try {
        $query = new WP_Query($query_args);

        // Verificar si la consulta es válida
        if (!is_a($query, 'WP_Query')) {
            error_log('Error al crear WP_Query');
            return '';
        }
    } catch (Exception $e) {
        error_log('Error en WP_Query: ' . $e->getMessage());
        return '';
    }

    // Verificar que $query sea válido antes de continuar
    if (!is_object($query) || !method_exists($query, 'have_posts')) {
        error_log('Query inválido en procesarPublicaciones');
        return '';
    }

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

            if ($tipoPost === 'social_post') {
                echo htmlPost($filtro);
            } elseif ($tipoPost === 'colab') {
                echo htmlColab($filtro);
            } elseif ($tipoPost === 'colecciones') {
                echo htmlColec($filtro);
            } elseif ($tipoPost === 'post') {
                echo htmlArticulo($filtro);
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

function configuracionQueryArgs($args, $paged, $user_id, $current_user_id)
{
    try {
        $FALLBACK_USER_ID = 44;
        $is_authenticated = $current_user_id && $current_user_id != 0;
        $is_admin = current_user_can('administrator');

        if (!$is_authenticated) {
            $current_user_id = $FALLBACK_USER_ID;
        }

        if ($user_id !== null) {
            $query_args = [
                'post_type' => $args['post_type'],
                'posts_per_page' => $args['posts'],
                'paged' => $paged,
                'ignore_sticky_posts' => true,
                'suppress_filters' => false,
                'orderby' => 'date',
                'order' => 'DESC',
                'author' => $user_id,
            ];

            $query_args = aplicarFiltroGlobal($query_args, $args, $current_user_id, $user_id);
            return $query_args;
        }

        $identifier = '';

        if (isset($_GET['busqueda'])) {
            $identifier = $_GET['busqueda'];
            error_log("[configuracionQueryArgs] Valor de busqueda obtenido de GET: " . $identifier);
        } elseif (isset($_POST['identifier'])) {
            $identifier = $_POST['identifier'];
            error_log("[configuracionQueryArgs] Valor de identifier obtenido de POST: " . $identifier);
        } else {
            error_log("[configuracionQueryArgs] No se encontro valor para identifier ni busqueda.");
        }

        $posts = $args['posts'];
        $similar_to = $args['similar_to'] ?? null;
        $filtroTiempo = (int)get_user_meta($current_user_id, 'filtroTiempo', true);

        if ($filtroTiempo === false) {
            error_log("[configuracionQueryArgs] Error: No se pudo obtener filtroTiempo para el usuario ID: " . $current_user_id);
        }

        $query_args = construirQueryArgs($args, $paged, $current_user_id, $identifier, $is_admin, $posts, $filtroTiempo, $similar_to);

        if ($args['post_type'] === 'social_post' && in_array($args['filtro'], ['sampleList', 'sample'])) {
            $query_args = aplicarFiltrosUsuario($query_args, $current_user_id);
        }

        $query_args = aplicarFiltroGlobal($query_args, $args, $current_user_id, $user_id);

        return $query_args;
    } catch (Exception $e) {
        error_log("[configuracionQueryArgs] Error crítico: " . $e->getMessage());
        return false;
    }
}
function aplicarFiltroGlobal($query_args, $args, $current_user_id, $user_id)
{
    // Si se proporciona un user_id, filtrar solo por los posts de ese usuario
    if (!empty($user_id)) {
        $query_args['author'] = $user_id; // Filtrar posts por el autor especificado
        return $query_args;
    }

    // Obtener filtros personalizados del usuario actual
    $filtrosUsuario = get_user_meta($current_user_id, 'filtroPost', true);
    if (is_array($filtrosUsuario) && in_array('misColecciones', $filtrosUsuario)) {
        $query_args['author'] = $current_user_id;
        return $query_args;
    }

    // Filtro general basado en $args['filtro']
    $filtro = $args['filtro'] ?? 'nada';
    $meta_query_conditions = [
        'rolasEliminadas' => fn() => $query_args['post_status'] = 'pending_deletion',
        'rolasRechazadas' => fn() => $query_args['post_status'] = 'rejected',
        'rolasPendiente' => fn() => $query_args['post_status'] = 'pending',
        'likesRolas' => fn() => ($userLikedPostIds = obtenerLikesDelUsuario($current_user_id))
            ? $query_args['post__in'] = $userLikedPostIds
            : $query_args['posts_per_page'] = 0,
        'nada' => fn() => $query_args['post_status'] = 'publish',
        'colabs' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres' => [
            ['key' => 'esExclusivo', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!=']
        ],
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'sample' => [
            ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
            ['key' => 'post_audio_lite', 'compare' => 'EXISTS'],
        ],
        'sampleList' => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],
        'colab' => fn() => $query_args['post_status'] = 'publish',
        'colabPendiente' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = 'pending';
        },
    ];

    if (isset($meta_query_conditions[$filtro])) {
        $result = $meta_query_conditions[$filtro];
        if (is_callable($result)) {
            $result();
        } else {
            $query_args['meta_query'][] = $result;
        }
    }

    return $query_args;
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
            $query_args = prefiltrarIdentifier($identifier, $query_args);
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
    // Verificar si el usuario tiene configurado un filtro
    $filtrosUsuario = get_user_meta($current_user_id, 'filtroPost', true);
    if (!empty($filtrosUsuario) && is_array($filtrosUsuario)) {
        //guardarLog("[ordenamientoQuery] Usuario con filtros personalizados. Solo se permiten casos 2 y 3.");

        // Si el filtro no es 2 o 3, retornar directamente
        if (!in_array($filtroTiempo, [2, 3])) {
            //guardarLog("[ordenamientoQuery] Filtro $filtroTiempo no permitido para usuarios con filtroPost. Retornando query sin modificaciones.");
            return $query_args;
        }
    }

    try {
        global $wpdb;
        if (!$wpdb) {
            //guardarLog("[ordenamientoQuery] Error crítico: No se pudo acceder a la base de datos wpdb");
            return false;
        }

        $likes_table = $wpdb->prefix . 'post_likes';

        // Validación de query_args
        if (!is_array($query_args)) {
            //guardarLog("[ordenamientoQuery] Advertencia: query_args no es un array, inicializando array vacío");
            $query_args = array();
        }

        switch ($filtroTiempo) {
            case 1:
                //guardarLog("[ordenamientoQuery] Ordenando por fecha descendente (filtroTiempo: 1)");
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

                //guardarLog("[ordenamientoQuery] Ejecutando consulta SQL para top semanal/mensual: $sql");

                $posts_with_likes = $wpdb->get_results($sql, ARRAY_A);

                if ($wpdb->last_error) {
                    //guardarLog("[ordenamientoQuery] Error: Fallo en consulta de likes: " . $wpdb->last_error);
                }

                if (!empty($posts_with_likes)) {
                    $post_ids = wp_list_pluck($posts_with_likes, 'ID');
                    if (!empty($post_ids)) {
                        //guardarLog("[ordenamientoQuery] IDs de posts encontrados: " . implode(',', $post_ids));
                        $query_args['post__in'] = $post_ids;
                        $query_args['orderby'] = 'post__in';
                    } else {
                        //guardarLog("[ordenamientoQuery] Aviso: No se encontraron IDs de posts con likes");
                    }
                } else {
                    //guardarLog("[ordenamientoQuery] Aviso: No se encontraron posts con likes para el período " . $interval);
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;

            default: // Feed personalizado
                //guardarLog("[ordenamientoQuery] Obteniendo feed personalizado");
                $feed_result = obtenerFeedPersonalizado($current_user_id, $identifier, $similar_to, $paged, $is_admin, $posts);

                if (!empty($feed_result['post_ids'])) {
                    $query_args['post__in'] = $feed_result['post_ids'];
                    $query_args['orderby'] = 'post__in';

                    if (count($feed_result['post_ids']) > POSTINLIMIT) {
                        //guardarLog("[ordenamientoQuery] Aviso: Limitando resultados a " . POSTINLIMIT . " posts");
                        $feed_result['post_ids'] = array_slice($feed_result['post_ids'], 0, POSTINLIMIT);
                    }

                    if (!empty($feed_result['post_not_in'])) {
                        $query_args['post__not_in'] = $feed_result['post_not_in'];
                    }
                } else {
                    //guardarLog("[ordenamientoQuery] Aviso: Feed personalizado vacío, usando ordenamiento por fecha");
                    $query_args['orderby'] = 'date';
                    $query_args['order'] = 'DESC';
                }
                break;
        }

        // Validación final de orderby
        if (empty($query_args['orderby'])) {
            //guardarLog("[ordenamientoQuery] Aviso: No se estableció orderby, usando valores por defecto");
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
        }

        return $query_args;
    } catch (Exception $e) {
        //guardarLog("[ordenamientoQuery] Error crítico: " . $e->getMessage());
        return false;
    }
}


function aplicarFiltrosUsuario($query_args, $current_user_id)
{
    //guardarLog("Iniciando aplicarFiltrosUsuario para el usuario $current_user_id");
    $filtrosUsuario = get_user_meta($current_user_id, 'filtroPost', true);

    //guardarLog("Filtros del usuario: " . print_r($filtrosUsuario, true));

    if (empty($filtrosUsuario) || !is_array($filtrosUsuario)) {
        //guardarLog("No hay filtros aplicables o el formato es incorrecto.");
        return $query_args;
    }

    // Inicializar variables para mantener los IDs a incluir y excluir
    $post_not_in = $query_args['post__not_in'] ?? [];
    $post_in = $query_args['post__in'] ?? [];

    // Filtro para ocultar posts descargados
    if (in_array('ocultarDescargados', $filtrosUsuario)) {
        $descargasAnteriores = get_user_meta($current_user_id, 'descargas', true) ?: [];
        //guardarLog("Descargas anteriores: " . print_r($descargasAnteriores, true));
        if (!empty($descargasAnteriores)) {
            $post_not_in = array_merge(
                $post_not_in,
                array_keys($descargasAnteriores)
            );
            //guardarLog("Post__not_in después de ocultar descargados: " . print_r($post_not_in, true));
        }
    }

    // Filtro para ocultar posts en colección
    if (in_array('ocultarEnColeccion', $filtrosUsuario)) {
        $samplesGuardados = get_user_meta($current_user_id, 'samplesGuardados', true) ?: [];
        //guardarLog("Samples guardados: " . print_r($samplesGuardados, true));
        if (!empty($samplesGuardados)) {
            $guardadosIDs = array_keys($samplesGuardados);
            $post_not_in = array_merge(
                $post_not_in,
                $guardadosIDs
            );
            //guardarLog("Post__not_in después de ocultar en colección: " . print_r($post_not_in, true));
        }
    }

    // Filtro para mostrar solo los posts que le han gustado al usuario
    if (in_array('mostrarMeGustan', $filtrosUsuario)) {
        $userLikedPostIds = obtenerLikesDelUsuario($current_user_id);
        //guardarLog("Post IDs que le gustan al usuario: " . print_r($userLikedPostIds, true));
        if (!empty($userLikedPostIds)) {
            if (!empty($post_in)) {
                $post_in = array_intersect($post_in, $userLikedPostIds);
            } else {
                $post_in = $userLikedPostIds;
            }

            //guardarLog("Post__in después de aplicar mostrarMeGustan: " . print_r($post_in, true));

            if (empty($post_in)) {
                $query_args['posts_per_page'] = 0;
                //guardarLog("No hay posts que mostrar después de aplicar mostrarMeGustan.");
            }
        } else {
            $query_args['posts_per_page'] = 0;
            //guardarLog("No hay posts que le gusten al usuario, posts_per_page se establece en 0.");
        }
    }

    // Eliminar los IDs en post_not_in de post_in para evitar conflictos
    if (!empty($post_in) && !empty($post_not_in)) {
        $post_in = array_diff($post_in, $post_not_in);
        //guardarLog("Post__in después de eliminar IDs en post__not_in: " . print_r($post_in, true));

        if (empty($post_in)) {
            $query_args['posts_per_page'] = 0;
            //guardarLog("No hay posts que mostrar después de aplicar los filtros.");
        }
    }


    if (!empty($post_in)) {
        $query_args['post__in'] = $post_in;
    } else {
        unset($query_args['post__in']);
    }

    if (!empty($post_not_in)) {
        $query_args['post__not_in'] = $post_not_in;
    } else {
        unset($query_args['post__not_in']);
    }

    return $query_args;
}


function prefiltrarIdentifier($identifier, $query_args)
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