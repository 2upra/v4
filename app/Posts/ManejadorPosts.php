<?php
function publicaciones($args = [], $is_ajax = false, $paged = 1)
{
    $user_id = obtenerUserId($is_ajax);
    $current_user_id = get_current_user_id();

    // Mezclamos los argumentos recibidos con los valores por defecto
    $defaults = [
        'filtro'     => '',
        'tab_id'     => '',
        'posts'      => 12,
        'exclude'    => [],
        'post_type'  => 'social_post',
        'identifier' => '',
    ];
    $args = array_merge($defaults, $args);

    $query_args = configuracionQueryArgs($args, $paged, $user_id, $current_user_id);
    return procesarPublicaciones($query_args, $args, $is_ajax);
}

function configuracionQueryArgs($args, $paged, $user_id, $current_user_id)
{
    $identifier = $args['identifier'];
    $posts      = $args['posts'];
    $post_type  = $args['post_type'];

    $meta_query = [];
    if (!empty($identifier)) {
        $meta_query[] = [
            'key'     => 'datosAlgoritmo',
            'value'   => $identifier,
            'compare' => 'LIKE',
        ];
    }

    if ($post_type === 'social_post') {
        $posts_personalizados = calcularFeedPersonalizado($current_user_id);
        $post_ids = array_keys($posts_personalizados);
        if ($paged == 1) {
            $post_ids = array_slice($post_ids, 0, $posts);
        }
        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => $posts,
            'paged'          => $paged,
            'post__in'       => $post_ids,
            'orderby'        => 'post__in',
            'meta_query'     => $meta_query,
        ];
    } else {
        $query_args = [
            'post_type'      => $post_type,
            'posts_per_page' => $posts,
            'paged'          => $paged,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => $meta_query,
        ];
    }

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = $args['exclude'];
    }

    return aplicarFiltros($query_args, $args, $user_id, $current_user_id);
}

function procesarPublicaciones($query_args, $args, $is_ajax)
{
    ob_start();
    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        $filtro   = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];
        $tipoPost = $args['post_type'];

        if (!$is_ajax) {
            $clase_extra = in_array($filtro, ['rolasEliminadas', 'rolasRechazadas', 'rola', 'likes']) ? 'clase-rolastatus' : 'clase-' . esc_attr($filtro);
            echo '<ul class="social-post-list ' . esc_attr($clase_extra) . '" data-filtro="' . esc_attr($filtro) . '" data-post-type="' . esc_attr($tipoPost) . '" data-tab-id="' . esc_attr($args['tab_id']) . '">';
        }

        while ($query->have_posts()) {
            $query->the_post();
            if ($tipoPost === 'social_post') {
                echo htmlPost($filtro);
            } elseif ($tipoPost === 'colab') {
                echo htmlColab($filtro);
            } else {
                echo '<p>Tipo de publicación no reconocido.</p>';
            }
        }

        if (!$is_ajax) {
            echo '</ul>';
        }
    } else {
        echo nohayPost($args['filtro'], $is_ajax);
    }

    wp_reset_postdata();
    return ob_get_clean();
}

function obtenerUserId($is_ajax)
{
    if ($is_ajax && isset($_POST['user_id'])) {
        return intval($_POST['user_id']);
    }

    $url_segments = explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'));
    foreach (['perfil', 'music', 'author', 'sello'] as $index) {
        $pos = array_search($index, $url_segments);
        if ($pos !== false) {
            if ($index === 'sello') {
                return get_current_user_id();
            } elseif (isset($url_segments[$pos + 1])) {
                $usuario = get_user_by('slug', $url_segments[$pos + 1]);
                if ($usuario) {
                    return $usuario->ID;
                }
            }
            break;
        }
    }
    return null;
}

function publicacionAjax()
{

    $paged       = isset($_POST['paged']) ? intval($_POST['paged']) : 1;
    $filtro      = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $identifier  = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
    $tab_id      = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id     = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    $cargadas    = !empty($_POST['cargadas']) ? array_map('intval', explode(',', $_POST['cargadas'])) : [];

    $args = [
        'filtro'     => $filtro,
        'tab_id'     => $tab_id,
        'user_id'    => $user_id,
        'identifier' => $identifier,
        'exclude'    => $cargadas,
    ];

    $output = publicaciones($args, true, $paged);
}

add_action('wp_ajax_cargar_mas_publicaciones', 'publicacionAjax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'publicacionAjax');