<?php

define('ENABLE_LOGS', true);
/*[22-Aug-2024 05:35:09 UTC] PHP Notice:  Undefined variable: user_id in /var/www/html/wp-content/themes/records-2upra/wandorius/Posts/manejadorposts.php on line 53
[22-Aug-2024 05:35:09 UTC] PHP Notice:  Undefined variable: user_id in /var/www/html/wp-content/themes/records-2upra/wandorius/Posts/manejadorposts.php on line 72
[22-Aug-2024 05:35:09 UTC] PHP Notice:  Undefined variable: user_id in /var/www/html/wp-content/themes/records-2upra/wandorius/Posts/manejadorposts.php on line 175
[22-Aug-2024 05:35:09 UTC] PHP Notice:  Undefined index: identifier in /var/www/html/wp-content/themes/records-2upra/wandorius/Posts/manejadorposts.php on line 199 */
function mostrar_publicaciones_sociales($atts, $is_ajax = false, $paged = 1)
{
    $log_file_path = $is_ajax
        ? '/var/www/html/wp-content/themes/wanlogAjax.txt'
        : '/var/www/html/wp-content/themes/wanlog.txt';

    if (ENABLE_LOGS) {
        error_log("---------------------------------------\n", 3, $log_file_path);
        error_log("mostrar_publicaciones_sociales\n", 3, $log_file_path);
        error_log("is_ajax: " . ($is_ajax ? 'true' : 'false') . ", paged: $paged\n", 3, $log_file_path);
        error_log("Datos recibidos (atts): " . print_r($atts, true) . "\n", 3, $log_file_path);
    }
    $user_id = null;
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : '';
    if ($is_ajax && isset($_POST['user_id'])) {
        $user_id = sanitize_text_field($_POST['user_id']);
    } else {
        $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $url_segments = explode('/', trim($url_path, '/'));
        $perfil_index = array_search('perfil', $url_segments);
        $music_index = array_search('music', $url_segments);
        $author_index = array_search('author', $url_segments);
        $sello_index = array_search('sello', $url_segments); // Nueva condición para /sello

        if ($sello_index !== false) {
            // Usar el ID del usuario actual
            $user_id = get_current_user_id();
        } elseif ($perfil_index !== false && isset($url_segments[$perfil_index + 1])) {
            $nombre_usuario = $url_segments[$perfil_index + 1];
        } elseif ($music_index !== false && isset($url_segments[$music_index + 1])) {
            $nombre_usuario = $url_segments[$music_index + 1];
        } elseif ($author_index !== false && isset($url_segments[$author_index + 1])) {
            $nombre_usuario = $url_segments[$author_index + 1];
        }

        if (isset($nombre_usuario)) {
            $usuario = get_user_by('slug', $nombre_usuario);
            if ($usuario) {
                $user_id = $usuario->ID;
            }
        }
    }


    if (ENABLE_LOGS) {
        error_log("Filtro: $filtro\n", 3, $log_file_path);
        error_log("Identifier: $identifier\n", 3, $log_file_path);
        error_log("User ID: $user_id\n", 3, $log_file_path);
    }
// [22-Aug-2024 05:35:09 UTC] PHP Notice:  Undefined variable: user_id in /var/www/html/wp-content/themes/records-2upra/wandorius/Posts/manejadorposts.php on line 53
    $meta_query = array();

    if (!empty($identifier)) {
        $meta_query[] = array(
            'key' => 'additional_search_data',
            'value' => $identifier,
            'compare' => 'LIKE'
        );

        if (ENABLE_LOGS) {
            error_log("Se añadió un meta_query con identifier: $identifier\n", 3, $log_file_path);
        }
    }
    $current_user_id = get_current_user_id();

    if (ENABLE_LOGS) {
        error_log("current_user_id: $current_user_id, user_id: $user_id\n", 3, $log_file_path);
    }

    $args = shortcode_atts(
        array(
            'filtro' => '',
            'tab_id' => '',
        ),
        $atts
    );

    if (ENABLE_LOGS) {
        error_log("shortcode_atts: " . print_r($args, true) . "\n", 3, $log_file_path);
    }

    $posts_per_page = isset($atts['posts']) ? intval($atts['posts']) : 12;

    $query_args = array(
        'post_type' => 'social_post',
        'posts_per_page' => $posts_per_page,
        'paged' => $paged,
        'meta_query' => $meta_query,
        'meta_key' => '_post_puntuacion_final',
        'orderby' => array(
            'meta_value_num' => 'DESC',
            'date' => 'DESC'
        ),
    );
    if (ENABLE_LOGS) {
        error_log("query_args: " . print_r($query_args, true) . "\n", 3, $log_file_path);
    }

    $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

    // $query_args['meta_query'] = [];

    $meta_query_conditions = [
        'siguiendo' => function () use ($current_user_id, &$query_args) {
            $siguiendo = array_filter((array) get_user_meta($current_user_id, 'siguiendo', true));
            $query_args['author__in'] = $siguiendo;
            return ['key' => 'rola', 'value' => '1', 'compare' => '!='];
        },
        'con_imagen_sin_audio' => [
            ['key' => 'post_audio', 'compare' => 'NOT EXISTS'],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'solo_colab' => ['key' => 'para_colab', 'value' => '1', 'compare' => '='],
        'rolastatus' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['publish', 'pending'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'nada' => function () use (&$query_args) {

            $query_args['post_status'] = 'publish';
            return [];
        },
        'rolasEliminadas' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['pending_deletion'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'rolasRechazadas' => function () use (&$query_args) {
            $query_args['author'] = get_current_user_id();
            $query_args['post_status'] = ['rejected'];
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'no_bloqueado' => [
            ['key' => 'content-block', 'value' => '0', 'compare' => '='],
            ['key' => 'post_price', 'compare' => 'NOT EXISTS'],
            ['key' => 'rola', 'value' => '1', 'compare' => '!=']
        ],
        'likes' => function () use ($current_user_id, &$query_args) {
            $user_liked_post_ids = get_user_liked_post_ids($current_user_id);
            if (empty($user_liked_post_ids)) {
                $query_args['posts_per_page'] = 0;
                return null;
            }
            $query_args['post__in'] = $user_liked_post_ids;
            return ['key' => 'rola', 'value' => '1', 'compare' => '='];
        },
        'bloqueado' => ['key' => 'content-block', 'value' => '1', 'compare' => '='],
        'sample' => ['key' => 'allow_download', 'value' => '1', 'compare' => '='],
        'venta' => ['key' => 'post_price', 'value' => '0', 'compare' => '>', 'type' => 'NUMERIC'],
        'rola' => function () use (&$query_args) {
            $query_args['post_status'] = 'publish';
            return [
                ['key' => 'rola', 'value' => '1', 'compare' => '='],
                ['key' => 'post_audio', 'compare' => 'EXISTS']
            ];
        },
        'momento' => [
            ['key' => 'momento', 'value' => '1', 'compare' => '='],
            ['key' => '_thumbnail_id', 'compare' => 'EXISTS']
        ],
        'presentacion' => ['key' => 'additional_search_data', 'value' => 'presentacion010101', 'compare' => 'LIKE'],
    ];

    if (isset($meta_query_conditions[$filtro])) {
        $condition = $meta_query_conditions[$filtro];
        $query_args['meta_query'][] = is_callable($condition) ? $condition() : $condition;
    }

    if ($user_id !== null) {
        $query_args['author'] = $user_id;
    }

    if (!empty($args['exclude'])) {
        $query_args['post__not_in'] = $args['exclude'];
    }

    ob_start();

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        $filtro = !empty($args['identifier']) ? $args['identifier'] : $args['filtro'];

        // Si no es una solicitud AJAX, imprime la apertura de la lista
        if (!wp_doing_ajax()) {
            $clase_extra = '';
            if ($filtro === 'rolasEliminadas' || $filtro === 'rolasRechazadas' || $filtro === 'rola' || $filtro === 'likes') {
                $clase_extra = 'clase-rolastatus';
            } else {
                $clase_extra = 'clase-' . $filtro;
            }

            echo '<ul class="social-post-list ' . $clase_extra . '" data-filtro="' . esc_attr($filtro) . '" data-tab-id="' . esc_attr($args['tab_id']) . '">';

        }

        while ($query->have_posts()) {
            $query->the_post();
            echo obtener_html_publicacion($filtro);
        }

        if (!wp_doing_ajax()) {
        }
    } else {
        echo nohayPost($filtro, $is_ajax);
    }
    wp_reset_postdata();

    if ($is_ajax) {
        echo ob_get_clean();
        die();
    } else {
        return ob_get_clean();
    }
}
add_shortcode('mostrar_publicaciones_sociales', 'mostrar_publicaciones_sociales');

function cargar_mas_publicaciones_ajax()
{
    // Determinar la ruta del archivo de log
    $log_file_path = '/var/www/html/wp-content/themes/wanlogAjax.txt';

    // Obtener los parámetros de la solicitud POST
    $paged = isset($_POST['paged']) ? (int) $_POST['paged'] : 1;
    $search_term = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $filtro = isset($_POST['filtro']) ? sanitize_text_field($_POST['filtro']) : '';
    $data_identifier = isset($_POST['identifier']) ? sanitize_text_field($_POST['identifier']) : ''; // Obtener data-identifier
    $tab_id = isset($_POST['tab_id']) ? sanitize_text_field($_POST['tab_id']) : '';
    $user_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';
    $publicacionesCargadas = isset($_POST['cargadas']) ? array_map('intval', $_POST['cargadas']) : array();

    // Registrar logs
    if (defined('ENABLE_LOGS') && ENABLE_LOGS) {
        error_log("---------------------------------------\n", 3, $log_file_path);
        error_log("cargar_mas_publicaciones_ajax\n", 3, $log_file_path);
        error_log("paged: $paged\n", 3, $log_file_path);
        error_log("search_term: $search_term\n", 3, $log_file_path);
        error_log("filtro: $filtro\n", 3, $log_file_path);
        error_log("data_identifier: $data_identifier\n", 3, $log_file_path);
        error_log("tab_id: $tab_id\n", 3, $log_file_path);
        error_log("user_id: $user_id\n", 3, $log_file_path);
        error_log("publicacionesCargadas: " . implode(',', $publicacionesCargadas) . "\n", 3, $log_file_path);
    }

    // Llamar a la función para mostrar las publicaciones sociales
    mostrar_publicaciones_sociales(
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


add_action('wp_ajax_cargar_mas_publicaciones', 'cargar_mas_publicaciones_ajax');
add_action('wp_ajax_nopriv_cargar_mas_publicaciones', 'cargar_mas_publicaciones_ajax');


function enqueue_diferido_post_script()
{
    wp_enqueue_script('diferido-post', get_template_directory_uri() . '/js/diferido-post.js', array('jquery'), '3.0.34', true);

    wp_localize_script(
        'diferido-post',
        'ajax_params',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts', 'enqueue_diferido_post_script'); 


/*
[mostrar_publicaciones_sociales filtro="con_imagen_sin_audio"]
[mostrar_publicaciones_sociales filtro="solo_colab"]
[mostrar_publicaciones_sociales filtro="no_bloqueado"]
[mostrar_publicaciones_sociales filtro="bloqueado"]
[mostrar_publicaciones_sociales filtro="venta"]
[mostrar_publicaciones_sociales filtro="presentacion"]
*/

function mostrar_ultimas_publicaciones_rola_shortcode()
{
    ob_start();

    $user_id = null;
    $url_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $url_segments = explode('/', trim($url_path, '/'));
    $perfil_index = array_search('music', $url_segments);
    if ($perfil_index !== false && isset($url_segments[$perfil_index + 1])) {
        $nombre_usuario = $url_segments[$perfil_index + 1];
        $usuario = get_user_by('slug', $nombre_usuario);
        if ($usuario) {
            $user_id = $usuario->ID;
        }
    }

    // Si no se pudo obtener el user_id, termina aquí
    if (null === $user_id) {
        echo '<p>No se pudo determinar el usuario.</p>';
        return ob_get_clean();  // Limpia el buffer y devuelve el contenido
    }

    $query_args = array(
        'post_type' => 'social_post',
        'posts_per_page' => 5,
        'meta_query' => array(
            array(
                'key' => 'rola',
                'value' => '1',
                'compare' => '='
            )
        ),
        'meta_key' => '_post_puntuacion_final',
        'orderby' => 'meta_value_num',
        'order' => 'DESC',
        'author' => $user_id
    );

    $query = new WP_Query($query_args);
    if ($query->have_posts()) {
        echo '<ul class="rola social-post-list">';
        while ($query->have_posts()) {
            $query->the_post();
            echo obtener_html_publicacion_rola_resumen();
        }
        echo '</ul>';
    } else {
        echo '<p>No se encontraron publicaciones.</p>';
    }

    return ob_get_clean();
}

add_shortcode('mostrar_ultimas_rolas', 'mostrar_ultimas_publicaciones_rola_shortcode');
