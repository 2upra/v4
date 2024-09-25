<?php
function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    $user_id = obtenerUserId($is_ajax);
    $current_user_id = get_current_user_id();

    // Mezclamos los argumentos recibidos con los valores por defecto
    $defaults = [
        'filtro' => '',
        'tab_id' => '',
        'posts' => 12,
        'exclude' => [],
        'post_type' => 'social_post',  // Valor por defecto
    ];
    $args = array_merge($defaults, $args);

    // Log para depurar los argumentos iniciales
    postLog("Publicaciones args iniciales: " . print_r($args, true));
    postLog("user_id: $user_id, current_user_id: $current_user_id, paged: $paged");
    postLog("Post type recibido: " . $args['post_type']);  

    if ($is_ajax) {
        ajaxPostLog("Publicaciones AJAX: " . print_r($args, true));
    }

    $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
    postLog("Query args generados: " . print_r($query_args, true));

    $output = procesarPublicaciones($query_args, $args, $is_ajax);
    if (empty($output)) {
        postLog("No se encontraron publicaciones para los query args: " . print_r($query_args, true));
    }

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

    // Si el post_type es 'social_post', usar el feed personalizado, de lo contrario usar el orden por fecha.
    if ($args['post_type'] === 'social_post') {
        $posts_personalizados = calcularFeedPersonalizado($current_user_id);
        $post_ids = array_keys($posts_personalizados);

        if ($paged == 1) {
            $post_ids = array_slice($post_ids, 0, $posts);
        }

        // Log para depurar el estado de los posts personalizados y post_ids
        postLog("Posts personalizados: " . print_r($posts_personalizados, true));
        postLog("Post IDs seleccionados: " . print_r($post_ids, true));

        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'post__in' => $post_ids,
            'orderby' => 'post__in',
            'meta_query' => !empty($identifier) ? [['key' => 'datosAlgoritmo', 'value' => $identifier, 'compare' => 'LIKE']] : [],
        ];
    } else {
        // Ordenar por fecha si no es 'social_post'
        $query_args = [
            'post_type' => $args['post_type'],
            'posts_per_page' => $posts,
            'paged' => $paged,
            'orderby' => 'date',
            'order' => 'DESC', // Ordenar por fecha más reciente primero
        ];

        // Log para verificar que la consulta para post_type 'colab' se está generando correctamente
        postLog("Query args para post_type '" . $args['post_type'] . "': " . print_r($query_args, true));
    }

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = $args['exclude'];
    }

    // Log para depurar el estado del identifier y la meta_query
    postLog("Identifier: $identifier");
    postLog("Meta query: " . print_r($query_args['meta_query'] ?? [], true));

    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);

    // Log final para depurar el query_args después de aplicar los filtros
    postLog("Query args finales después de aplicarFiltros: " . print_r($query_args, true));

    return $query_args;
}



function procesarPublicaciones($query_args, $args, $is_ajax)
{
    // Inicia el almacenamiento en búfer de salida
    ob_start();

    // Consulta de publicaciones basada en los argumentos proporcionados
    $query = new WP_Query($query_args);
    if ($query->have_posts()) {

        // Establece el filtro a partir del identificador o el filtro proporcionado
        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];
        $tipoPost = $args['post_type'];

        // Si no es una solicitud AJAX, establece las clases y construye el contenedor HTML
        if (!wp_doing_ajax()) {
            $clase_extra = 'clase-' . esc_attr($filtro);

            // Define una clase especial para ciertos filtros
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $clase_extra = 'clase-rolastatus';
            }

            // Genera la lista de publicaciones con atributos 'data'
            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" 
                  data-filtro="' . esc_attr($filtro) . '" 
                  data-post-type="' . esc_attr($tipoPost) . '" 
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

        // Cierra el contenedor si no es una solicitud AJAX
        if (!wp_doing_ajax()) {
            echo '</ul>';
        }
    } else {
        // Muestra un mensaje si no hay publicaciones, diferenciando por tipo de post
        echo nohayPost($filtro, $is_ajax);
    }

    // Restaura el estado global de publicaciones después de la consulta
    wp_reset_postdata();

    // Devuelve el contenido generado
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
    // Determinar la ruta del archivo de log
    $log_file_path = '/var/www/wordpress/wp-content/themes/wanlogAjax.txt';

    // Obtener los parámetros de la solicitud POST
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';

    // Verificar si 'cargadas' es un array
    $publicacionesCargadas = isset($_POST['cargadas']) && is_array($_POST['cargadas'])
        ? array_map('intval', $_POST['cargadas'])
        : array();
    // Llamar a la función para mostrar las publicaciones sociales
    publicaciones(
        array(
            'filtro' => $filtro,
            'tab_id' => $tab_id,
            'user_id' => $user_id,
            'identifier' => $data_identifier,
            'exclude' => $publicacionesCargadas
        ),
        true,
        $paged
    );
}

add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');
