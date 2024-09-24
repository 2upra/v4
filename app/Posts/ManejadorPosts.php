<?php

function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    $user_id = obtenerUserId($is_ajax);
    $current_user_id = get_current_user_id();

    // Agregamos 'post_type' a los valores por defecto
    $defaults = [
        'filtro' => '',
        'tab_id' => '',
        'posts' => 12,
        'exclude' => [],
        'post_type' => 'social_post',
    ];

    // Mezclamos los argumentos recibidos con los valores por defecto
    $args = array_merge($defaults, $args);

    if ($is_ajax) {
        guardarLog("Publicaciones AJAX: " . print_r($args, true));
    }

    // Pasamos los argumentos a configuracionQueryArgs
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

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = $args['exclude'];
    }

    $query_args = aplicarFiltros($query_args, $args, $user_id, $current_user_id);

    return $query_args;
}

function aplicarFiltros($query_args, $args, $user_id, $current_user_id)
{
    $filtro = $args['identifier'] ?? $args['filtro'];
    $meta_query_conditions = [

        /*
        FILTROS PARA PAGINA SELLO
        */

        'rolasEliminadas' => fn() => $query_args += ['author' => get_current_user_id(), 'post_status' => ['pending_deletion']] ?: ['key' => 'rola', 'value' => '1', 'compare' => '='],
        'rolasRechazadas' => fn() => $query_args += ['author' => get_current_user_id(), 'post_status' => ['rejected']] ?: ['key' => 'rola', 'value' => '1', 'compare' => '='],
        'rolasPendiente' => fn() => $query_args += ['author' => get_current_user_id(), 'post_status' => ['pending']] ?: ['key' => 'rola', 'value' => '1', 'compare' => '='],

        /*
        FILTROS PAGINA DE MUSICA
        */

        'rola' => fn() => $query_args['post_status'] = 'publish' ?: [['key' => 'rola', 'value' => '1', 'compare' => '='], ['key' => 'post_audio', 'compare' => 'EXISTS']],
        'likesRolas' => fn() => ($user_liked_post_ids = obtenerLikesDelUsuario($current_user_id)) ? $query_args['post__in'] = $user_liked_post_ids ?: ['key' => 'rola', 'value' => '1', 'compare' => '='] : $query_args['posts_per_page'] = 0,

        /*
        FILTROS INICIO
        */

        'nada' => fn() => $query_args['post_status'] = 'publish',
        'colabs' => ['key' => 'paraColab', 'value' => '1', 'compare' => '='],
        'libres' => [['key' => 'esExclusivo', 'value' => '0', 'compare' => '='], ['key' => 'post_price', 'compare' => 'NOT EXISTS'], ['key' => 'rola', 'value' => '1', 'compare' => '!=']],
        'momento' => [['key' => 'momento', 'value' => '1', 'compare' => '='], ['key' => '_thumbnail_id', 'compare' => 'EXISTS']],

        /*
        FILTROS SAMPLES
        */

        'sample' => ['key' => 'paraDescarga', 'value' => '1', 'compare' => '='],

        /*
        FILTROS COLAB
        */

    ];

    if (isset($meta_query_conditions[$filtro])) {
        $query_args['meta_query'][] = is_callable($meta_query_conditions[$filtro]) ? $meta_query_conditions[$filtro]() : $meta_query_conditions[$filtro];
    }

    if ($user_id !== null) $query_args['author'] = $user_id;

    return $query_args;
}


function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

        if (!wp_doing_ajax()) {
            $clase_extra = 'clase-' . $filtro;
            if (in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes'])) {
                $clase_extra = 'clase-rolastatus';
            }

            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" data-filtro="' . esc_attr($filtro) . '" data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        while ($query->have_posts()) {
            $query->the_post();
            echo htmlPost($filtro);
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
